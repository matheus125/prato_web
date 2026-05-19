<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Funcionarios;
use \Hcode\DB\Sql;

date_default_timezone_set('America/Manaus');


/**
 * Helper: responde JSON (Slim 2) e finaliza.
 */
function jsonResponse($app, $statusCode, $payload)
{
    $app->response()->status($statusCode);
    $app->response()->header("Content-Type", "application/json; charset=utf-8");
    echo json_encode($payload);
    exit;
}

/**
 * Log do relatório (grava dentro do projeto, evitando /tmp e open_basedir).
 * Arquivo: <esta_pasta>/logs/relatorio_errors.log
 */
function relatorioLog($msg)
{
    $dir = defined('LOG_DIR') ? LOG_DIR : (__DIR__ . DIRECTORY_SEPARATOR . "logs");
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $file = $dir . DIRECTORY_SEPARATOR . "relatorio_errors.log";
    $line = "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL;

    // tenta gravar no arquivo
    @file_put_contents($file, $line, FILE_APPEND);

    // fallback: também manda para o error_log do PHP
    error_log($line);
}


function getLimiteSenhasDia()
{
    if (function_exists('pc_get_limite_senhas_dia')) {
        return pc_get_limite_senhas_dia();
    }

    if (defined('LIMITE_SENHAS_DIA')) {
        return max(0, (int)LIMITE_SENHAS_DIA);
    }

    return 400;
/*
    if (defined('LIMITE_SENHAS_DIA')) {
        return max(0, (int)LIMITE_SENHAS_DIA);
    }

    return 400; // valor padrão se a constante não estiver definida.
*/
}

function contarSenhasVendidasNoDia($sql, $dataRef)
{
    return function_exists('pc_contar_senhas_vendidas_dia')
        ? pc_contar_senhas_vendidas_dia($sql, $dataRef)
        : 0;
/*
    $res = $sql->select("
        SELECT COUNT(*) AS total
        FROM tb_senhas
        WHERE data_refeicao = :data_ref
    ", [
        ':data_ref' => $dataRef
    ]);

    return isset($res[0]['total']) ? (int)$res[0]['total'] : 0;
*/
}

/**
 * Descobre automaticamente qual coluna de data existe em tb_relatorios.
 * (Evita erro "Unknown column data_refeicao".)
 */
function getColunaDataRelatorio($sql)
{
    // 1) tenta por nomes comuns (prioridade)
    $prefer = [
        'data_refeicao',
        'data_relatorio',
        'data',
        'data_ref',
        'dt_refeicao',
        'dt_relatorio',
        'dt_data',
        'dt',
        'created_at',
        'registration_date'
    ];

    $cols = $sql->select("
        SELECT COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tb_relatorios'
    ");

    if (!$cols || count($cols) === 0) {
        throw new \Exception("Tabela tb_relatorios não encontrada (ou sem colunas visíveis) no schema atual.");
    }

    $byName = [];
    foreach ($cols as $c) {
        $byName[strtolower($c['COLUMN_NAME'])] = $c;
    }

    foreach ($prefer as $p) {
        $k = strtolower($p);
        if (isset($byName[$k])) return $byName[$k]['COLUMN_NAME'];
    }

    // 2) fallback: primeira coluna do tipo date/datetime/timestamp
    foreach ($cols as $c) {
        $dt = strtolower($c['DATA_TYPE']);
        if (in_array($dt, ['date', 'datetime', 'timestamp'])) {
            return $c['COLUMN_NAME'];
        }
    }

    // 3) se nada, falha explícita
    $lista = array_map(function ($c) {
        return $c['COLUMN_NAME'];
    }, $cols);
    throw new \Exception("Não consegui identificar a coluna de data em tb_relatorios. Colunas: " . implode(", ", $lista));
}

/**
 * Gera/atualiza o relatório do dia em tb_relatorios chamando a procedure.
 * (A procedure deve fazer UPSERT por data.)
 *
 * Agora:
 * - loga tudo em /logs/relatorio_errors.log
 * - valida que a linha apareceu em tb_relatorios após o CALL
 * - lança Exception se falhar (não fica silencioso)
 */
function gerarRelatorioDia($sql, $dataRef)
{
    $dataRef = function_exists('pc_normalizar_data_refeicao') ? pc_normalizar_data_refeicao($dataRef) : $dataRef;
    relatorioLog("Iniciando gerarRelatorioDia | data={$dataRef}");

    try {
        $resumo = function_exists('pc_obter_resumo_senhas_dia')
            ? pc_obter_resumo_senhas_dia($sql, $dataRef)
            : array();

        $sql->query("
            INSERT INTO tb_relatorios (
                Idade_3a17Masculino,
                Idade_3a17Masculino_PCD,
                Idade_3a17Feminino,
                Idade_3a17Feminino_PCD,
                Idade_18a59Masculino,
                Idade_18a59Masculino_PCD,
                Idade_17a59Feminino,
                Idade_17a59Feminino_PCD,
                Idade_60Masculino,
                Idade_60Masculino_PCD,
                Idade_60Feminino,
                Idade_60Feminino_PCD,
                Situacao_risco_masculino,
                Situacao_risco_Feminino,
                Deficientes,
                senhas_genericas,
                Total_pessoas_atendidas,
                data,
                fechado
            ) VALUES (
                :Idade_3a17Masculino,
                :Idade_3a17Masculino_PCD,
                :Idade_3a17Feminino,
                :Idade_3a17Feminino_PCD,
                :Idade_18a59Masculino,
                :Idade_18a59Masculino_PCD,
                :Idade_17a59Feminino,
                :Idade_17a59Feminino_PCD,
                :Idade_60Masculino,
                :Idade_60Masculino_PCD,
                :Idade_60Feminino,
                :Idade_60Feminino_PCD,
                :Situacao_risco_masculino,
                :Situacao_risco_Feminino,
                :Deficientes,
                :senhas_genericas,
                :Total_pessoas_atendidas,
                :data_ref,
                1
            )
            ON DUPLICATE KEY UPDATE
                Idade_3a17Masculino      = VALUES(Idade_3a17Masculino),
                Idade_3a17Masculino_PCD  = VALUES(Idade_3a17Masculino_PCD),
                Idade_3a17Feminino       = VALUES(Idade_3a17Feminino),
                Idade_3a17Feminino_PCD   = VALUES(Idade_3a17Feminino_PCD),
                Idade_18a59Masculino     = VALUES(Idade_18a59Masculino),
                Idade_18a59Masculino_PCD = VALUES(Idade_18a59Masculino_PCD),
                Idade_17a59Feminino      = VALUES(Idade_17a59Feminino),
                Idade_17a59Feminino_PCD  = VALUES(Idade_17a59Feminino_PCD),
                Idade_60Masculino        = VALUES(Idade_60Masculino),
                Idade_60Masculino_PCD    = VALUES(Idade_60Masculino_PCD),
                Idade_60Feminino         = VALUES(Idade_60Feminino),
                Idade_60Feminino_PCD     = VALUES(Idade_60Feminino_PCD),
                Situacao_risco_masculino = VALUES(Situacao_risco_masculino),
                Situacao_risco_Feminino  = VALUES(Situacao_risco_Feminino),
                Deficientes              = VALUES(Deficientes),
                senhas_genericas         = VALUES(senhas_genericas),
                Total_pessoas_atendidas  = VALUES(Total_pessoas_atendidas),
                fechado                  = 1
        ", array(
            ':Idade_3a17Masculino' => (int)($resumo['Idade_3a17Masculino'] ?? 0),
            ':Idade_3a17Masculino_PCD' => (int)($resumo['Idade_3a17Masculino_PCD'] ?? 0),
            ':Idade_3a17Feminino' => (int)($resumo['Idade_3a17Feminino'] ?? 0),
            ':Idade_3a17Feminino_PCD' => (int)($resumo['Idade_3a17Feminino_PCD'] ?? 0),
            ':Idade_18a59Masculino' => (int)($resumo['Idade_18a59Masculino'] ?? 0),
            ':Idade_18a59Masculino_PCD' => (int)($resumo['Idade_18a59Masculino_PCD'] ?? 0),
            ':Idade_17a59Feminino' => (int)($resumo['Idade_17a59Feminino'] ?? 0),
            ':Idade_17a59Feminino_PCD' => (int)($resumo['Idade_17a59Feminino_PCD'] ?? 0),
            ':Idade_60Masculino' => (int)($resumo['Idade_60Masculino'] ?? 0),
            ':Idade_60Masculino_PCD' => (int)($resumo['Idade_60Masculino_PCD'] ?? 0),
            ':Idade_60Feminino' => (int)($resumo['Idade_60Feminino'] ?? 0),
            ':Idade_60Feminino_PCD' => (int)($resumo['Idade_60Feminino_PCD'] ?? 0),
            ':Situacao_risco_masculino' => (int)($resumo['total_rua_masculino'] ?? 0),
            ':Situacao_risco_Feminino' => (int)($resumo['total_rua_feminino'] ?? 0),
            ':Deficientes' => (int)($resumo['total_deficiente'] ?? 0),
            ':senhas_genericas' => (int)($resumo['total_generica'] ?? 0),
            ':Total_pessoas_atendidas' => (int)($resumo['total'] ?? 0),
            ':data_ref' => $dataRef
        ));

        relatorioLog(
            "UPSERT tb_relatorios executado | data={$dataRef}"
            . " | total=" . (int)($resumo['total'] ?? 0)
            . " | faixas=" . (int)($resumo['total_faixas'] ?? 0)
            . " | genericas=" . (int)($resumo['total_generica'] ?? 0)
            . " | nao_classificados=" . (int)($resumo['total_nao_classificado'] ?? 0)
        );
    } catch (\Exception $e) {
        relatorioLog("ERRO ao atualizar tb_relatorios | data={$dataRef} | msg=" . $e->getMessage());
        throw new \Exception("Falha ao atualizar tb_relatorios: " . $e->getMessage());
    }

    $colData = getColunaDataRelatorio($sql);
    $chk = $sql->select("
        SELECT COUNT(*) AS total
        FROM tb_relatorios
        WHERE DATE(`{$colData}`) = :data_ref
    ", array(
        ':data_ref' => $dataRef
    ));

    $total = ($chk && isset($chk[0]['total'])) ? (int)$chk[0]['total'] : 0;
    if ($total <= 0) {
        $msg = "UPSERT executado, mas nenhum registro encontrado em tb_relatorios para a data {$dataRef} (coluna {$colData}).";
        relatorioLog("ERRO: " . $msg);
        throw new \Exception($msg);
    }

    relatorioLog("OK gerarRelatorioDia | data={$dataRef} | coluna={$colData} | total_encontrado={$total}");
    return true;

    try {
        $sql->query("
            INSERT INTO tb_relatorios (
                Idade_3a17Masculino,
                Idade_3a17Masculino_PCD,
                Idade_3a17Feminino,
                Idade_3a17Feminino_PCD,
                Idade_18a59Masculino,
                Idade_18a59Masculino_PCD,
                Idade_17a59Feminino,
                Idade_17a59Feminino_PCD,
                Idade_60Masculino,
                Idade_60Masculino_PCD,
                Idade_60Feminino,
                Idade_60Feminino_PCD,
                Situacao_risco_masculino,
                Situacao_risco_Feminino,
                Deficientes,
                senhas_genericas,
                Total_pessoas_atendidas,
                data,
                fechado
            )
            SELECT
                SUM(CASE WHEN sexo='M' AND pcd=0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='M' AND pcd=1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='M' AND pcd=0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='M' AND pcd=1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='M' AND pcd=0 AND idade >= 60 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='M' AND pcd=1 AND idade >= 60 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=0 AND idade >= 60 THEN 1 ELSE 0 END),
                SUM(CASE WHEN sexo='F' AND pcd=1 AND idade >= 60 THEN 1 ELSE 0 END),
                SUM(CASE WHEN status_norm = 'PESSOA EM SITUACAO DE RUA' AND sexo='M' THEN 1 ELSE 0 END),
                SUM(CASE WHEN status_norm = 'PESSOA EM SITUACAO DE RUA' AND sexo='F' THEN 1 ELSE 0 END),
                SUM(CASE WHEN pcd=1 THEN 1 ELSE 0 END),
                SUM(CASE WHEN UPPER(tipoSenha)='GENERICA' THEN 1 ELSE 0 END),
                COUNT(*),
                :data_ref,
                1
            FROM (
                SELECT
                    CAST(NULLIF(REGEXP_SUBSTR(IFNULL(Idade,''), '[0-9]+'), '') AS UNSIGNED) AS idade,
                    CASE
                        WHEN UPPER(TRIM(IFNULL(Genero,''))) IN ('M','MASCULINO') THEN 'M'
                        WHEN UPPER(TRIM(IFNULL(Genero,''))) IN ('F','FEMININO') THEN 'F'
                        ELSE 'N'
                    END AS sexo,
                    CASE
                        WHEN UPPER(TRIM(IFNULL(Deficiente,''))) IN ('SIM','S','1','TRUE','YES','PCD','DEFICIENTE') THEN 1
                        ELSE 0
                    END AS pcd,
                    UPPER(TRIM(
                        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                            IFNULL(status_cliente,''),
                            'Á','A'),'À','A'),'Â','A'),'Ã','A'),'É','E'),'Ç','C')
                    )) AS status_norm,
                    IFNULL(tipoSenha,'') AS tipoSenha
                FROM tb_senhas
                WHERE data_refeicao = :data_ref
            ) X
            ON DUPLICATE KEY UPDATE
                Idade_3a17Masculino      = VALUES(Idade_3a17Masculino),
                Idade_3a17Masculino_PCD  = VALUES(Idade_3a17Masculino_PCD),
                Idade_3a17Feminino       = VALUES(Idade_3a17Feminino),
                Idade_3a17Feminino_PCD   = VALUES(Idade_3a17Feminino_PCD),
                Idade_18a59Masculino     = VALUES(Idade_18a59Masculino),
                Idade_18a59Masculino_PCD = VALUES(Idade_18a59Masculino_PCD),
                Idade_17a59Feminino      = VALUES(Idade_17a59Feminino),
                Idade_17a59Feminino_PCD  = VALUES(Idade_17a59Feminino_PCD),
                Idade_60Masculino        = VALUES(Idade_60Masculino),
                Idade_60Masculino_PCD    = VALUES(Idade_60Masculino_PCD),
                Idade_60Feminino         = VALUES(Idade_60Feminino),
                Idade_60Feminino_PCD     = VALUES(Idade_60Feminino_PCD),
                Situacao_risco_masculino = VALUES(Situacao_risco_masculino),
                Situacao_risco_Feminino  = VALUES(Situacao_risco_Feminino),
                Deficientes              = VALUES(Deficientes),
                senhas_genericas         = VALUES(senhas_genericas),
                Total_pessoas_atendidas  = VALUES(Total_pessoas_atendidas),
                fechado                  = 1
        ", [
            ":data_ref" => $dataRef
        ]);
        relatorioLog("UPSERT tb_relatorios executado | data={$dataRef}");
    } catch (\Exception $e) {
        relatorioLog("ERRO ao atualizar tb_relatorios | data={$dataRef} | msg=" . $e->getMessage());
        throw new \Exception("Falha ao atualizar tb_relatorios: " . $e->getMessage());
    }

    $colData = getColunaDataRelatorio($sql);

    try {
        $chk = $sql->select("
            SELECT COUNT(*) AS total
            FROM tb_relatorios
            WHERE DATE(`{$colData}`) = :data_ref
        ", [
            ":data_ref" => $dataRef
        ]);
        $total = ($chk && isset($chk[0]['total'])) ? (int)$chk[0]['total'] : 0;
    } catch (\Exception $e) {
        relatorioLog("ERRO ao verificar tb_relatorios | coluna={$colData} | data={$dataRef} | msg=" . $e->getMessage());
        throw new \Exception("Erro ao verificar tb_relatorios: " . $e->getMessage());
    }

    if ($total <= 0) {
        $msg = "UPSERT executado, mas nenhum registro encontrado em tb_relatorios para a data {$dataRef} (coluna {$colData}).";
        relatorioLog("ERRO: " . $msg);
        throw new \Exception($msg);
    }

    relatorioLog("OK gerarRelatorioDia | data={$dataRef} | coluna={$colData} | total_encontrado={$total}");
    return true;
}

function obterNomeBancoAtual($sql)
{
    try {
        $res = $sql->select("SELECT DATABASE() AS nome_banco");
        if ($res && isset($res[0]['nome_banco'])) {
            return (string)$res[0]['nome_banco'];
        }
    } catch (\Exception $e) {
        relatorioLog("Falha ao obter nome do banco atual | msg=" . $e->getMessage());
    }

    return '';
}

function salvarInformacoesFechamentoRelatorio($sql, $dataRef, $qtdRefeicoesServidas, $ocorrencias, $cardapio)
{
    $dataRef = trim((string)$dataRef);
    if ($dataRef === '') {
        $dataRef = date('Y-m-d');
    }
    $dataRef = function_exists('pc_normalizar_data_refeicao') ? pc_normalizar_data_refeicao($dataRef) : $dataRef;

    $LIMITE_SENHAS_DIA = getLimiteSenhasDia();

    $qtdRefeicoesServidas = (int)$qtdRefeicoesServidas;
    if ($qtdRefeicoesServidas < 0) {
        $qtdRefeicoesServidas = 0;
    }
    if (false && $qtdRefeicoesServidas > $LIMITE_SENHAS_DIA) {
        $qtdRefeicoesServidas = $LIMITE_SENHAS_DIA;
    }

    $ocorrencias = trim((string)$ocorrencias);
    if ($ocorrencias === '') {
        $ocorrencias = 'NÃO HOUVE NENHUMA OCORRÊNCIA.';
    }

    $cardapio = trim((string)$cardapio);
    $nomeBanco = obterNomeBancoAtual($sql);

    gerarRelatorioDia($sql, $dataRef);

    $colData = getColunaDataRelatorio($sql);
    $totalSenhasVendidas = contarSenhasVendidasNoDia($sql, $dataRef);

    if ($totalSenhasVendidas < 0) {
        $totalSenhasVendidas = 0;
    }
    if (false && $totalSenhasVendidas > $LIMITE_SENHAS_DIA) {
        $totalSenhasVendidas = $LIMITE_SENHAS_DIA;
    }

    $refeicoesOfertadas = $LIMITE_SENHAS_DIA;
    $sobraSenhas = max(0, $refeicoesOfertadas - $totalSenhasVendidas);
    $sobraRefeicoes = max(0, $refeicoesOfertadas - $qtdRefeicoesServidas);

    $sql->query("
        UPDATE tb_relatorios
        SET
            Total_pessoas_atendidas = :total_senhas_vendidas,
            qtd_refeicoes_servidas = :qtd,
            ocorrencias = :ocorrencias,
            cardapio = :cardapio,
            nome_banco = :nome_banco,
            refeicoes_ofertadas = :refeicoes_ofertadas,
            sobra_refeicoes = :sobra_refeicoes,
            sobra_senhas = :sobra_senhas,
            fechado = 1
        WHERE DATE(`{$colData}`) = :data_ref
    ", [
        ':total_senhas_vendidas' => $totalSenhasVendidas,
        ':qtd' => $qtdRefeicoesServidas,
        ':ocorrencias' => $ocorrencias,
        ':cardapio' => $cardapio,
        ':nome_banco' => $nomeBanco,
        ':refeicoes_ofertadas' => $refeicoesOfertadas,
        ':sobra_refeicoes' => $sobraRefeicoes,
        ':sobra_senhas' => $sobraSenhas,
        ':data_ref' => $dataRef
    ]);

    $res = $sql->select("
        SELECT
            Total_pessoas_atendidas,
            qtd_refeicoes_servidas,
            ocorrencias,
            cardapio,
            nome_banco,
            refeicoes_ofertadas,
            sobra_refeicoes,
            sobra_senhas,
            fechado
        FROM tb_relatorios
        WHERE DATE(`{$colData}`) = :data_ref
        LIMIT 1
    ", [
        ':data_ref' => $dataRef
    ]);

    return [
        'data' => $dataRef,
        'senhas_vendidas' => isset($res[0]['Total_pessoas_atendidas']) && $res[0]['Total_pessoas_atendidas'] !== null ? (int)$res[0]['Total_pessoas_atendidas'] : $totalSenhasVendidas,
        'qtd_refeicoes_servidas' => isset($res[0]['qtd_refeicoes_servidas']) && $res[0]['qtd_refeicoes_servidas'] !== null ? (int)$res[0]['qtd_refeicoes_servidas'] : $qtdRefeicoesServidas,
        'ocorrencias' => isset($res[0]['ocorrencias']) ? (string)$res[0]['ocorrencias'] : $ocorrencias,
        'cardapio' => isset($res[0]['cardapio']) ? (string)$res[0]['cardapio'] : $cardapio,
        'nome_banco' => isset($res[0]['nome_banco']) ? (string)$res[0]['nome_banco'] : $nomeBanco,
        'refeicoes_ofertadas' => isset($res[0]['refeicoes_ofertadas']) && $res[0]['refeicoes_ofertadas'] !== null ? (int)$res[0]['refeicoes_ofertadas'] : $refeicoesOfertadas,
        'sobra_refeicoes' => isset($res[0]['sobra_refeicoes']) && $res[0]['sobra_refeicoes'] !== null ? (int)$res[0]['sobra_refeicoes'] : $sobraRefeicoes,
        'sobra_senhas' => isset($res[0]['sobra_senhas']) && $res[0]['sobra_senhas'] !== null ? (int)$res[0]['sobra_senhas'] : $sobraSenhas,
        'fechado' => 1
    ];
}

/**
/**
 * Busca dados do titular para gravar corretamente em tb_senhas.
 * Prioriza id_titular e usa CPF como fallback.
 */
function buscarDadosTitularParaSenha($sql, $idTitular = null, $cpf = '')
{
    $idTitular = $idTitular !== null ? (int)$idTitular : 0;
    $cpf = preg_replace("/\D+/", "", (string)$cpf);

    if ($idTitular > 0) {
        $res = $sql->select("
            SELECT
                id,
                nome_completo,
                cpf,
                genero,
                idade,
                status_cliente
            FROM tb_titular
            WHERE id = :id
            LIMIT 1
        ", [
            ":id" => $idTitular
        ]);

        if ($res && isset($res[0])) {
            return $res[0];
        }
    }

    if ($cpf !== '') {
        $res = $sql->select("
            SELECT
                id,
                nome_completo,
                cpf,
                genero,
                idade,
                status_cliente
            FROM tb_titular
            WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') = :cpf
            LIMIT 1
        ", [
            ":cpf" => $cpf
        ]);

        if ($res && isset($res[0])) {
            return $res[0];
        }
    }

    return null;
}

/**
 * Tela de Vendas
 */
$app->get("/admin/vendas", function () {
    Funcionarios::checkPermission('VENDAS_VIEW');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    $page = new PageAdmin();
    $page->setTpl("admin/vendas", array(
        "vendas" => [],
        "limiteSenhasDia" => getLimiteSenhasDia()
    ));
});

/**
 * ✅ TESTE DE LOG (JSON)
 * GET /admin/api/relatorios/logtest
 */
$app->get("/admin/api/relatorios/logtest", function () use ($app) {
    relatorioLog("TESTE DE LOG: rota /admin/api/relatorios/logtest acionada.");
    jsonResponse($app, 200, ["ok" => true, "msg" => "Log de teste escrito (verifique /logs/relatorio_errors.log)"]);
});

/**
 * ✅ GERAR/ATUALIZAR RELATÓRIO DO DIA (tb_relatorios)
 * GET /admin/api/relatorios/gerar?data=YYYY-MM-DD
 */
$app->get("/admin/api/relatorios/gerar", function () use ($app) {

    $req  = $app->request(); // Slim 2
    $data = $req->get("data");
    if (!$data) $data = date("Y-m-d");

    $sql = new Sql();

    try {
        gerarRelatorioDia($sql, $data);
        jsonResponse($app, 200, ["ok" => true, "data" => $data]);
    } catch (\Exception $e) {
        relatorioLog("ERRO rota /admin/api/relatorios/gerar | data={$data} | msg=" . $e->getMessage());
        jsonResponse($app, 500, ["ok" => false, "error" => "Erro ao gerar relatório", "details" => $e->getMessage()]);
    }
});

$app->get("/admin/api/relatorio/fechamento-info", function () use ($app) {

    $req  = $app->request();
    $data = trim((string)$req->get('data'));
    if ($data === '') $data = date('Y-m-d');

    $sql = new Sql();

    try {
        gerarRelatorioDia($sql, $data);

        $colData = getColunaDataRelatorio($sql);
        $res = $sql->select("
            SELECT
                qtd_refeicoes_servidas,
                ocorrencias,
                cardapio,
                nome_banco,
                refeicoes_ofertadas,
                sobra_refeicoes,
                sobra_senhas
            FROM tb_relatorios
            WHERE DATE(`{$colData}`) = :data_ref
            LIMIT 1
        ", [
            ':data_ref' => $data
        ]);

        $dados = isset($res[0]) ? $res[0] : [];
        $ocorrencias = isset($dados['ocorrencias']) ? trim((string)$dados['ocorrencias']) : '';

        jsonResponse($app, 200, [
            'ok' => true,
            'message' => 'Dados de fechamento carregados com sucesso.',
            'data' => [
                'data' => $data,
                'qtd_refeicoes_servidas' => isset($dados['qtd_refeicoes_servidas']) && $dados['qtd_refeicoes_servidas'] !== null ? (int)$dados['qtd_refeicoes_servidas'] : 0,
                'ocorrencias' => $ocorrencias !== '' ? $ocorrencias : 'NÃO HOUVE NENHUMA OCORRÊNCIA.',
                'cardapio' => isset($dados['cardapio']) ? (string)$dados['cardapio'] : '',
                'nome_banco' => isset($dados['nome_banco']) ? (string)$dados['nome_banco'] : obterNomeBancoAtual($sql),
                'refeicoes_ofertadas' => isset($dados['refeicoes_ofertadas']) && $dados['refeicoes_ofertadas'] !== null ? (int)$dados['refeicoes_ofertadas'] : getLimiteSenhasDia(),
                'sobra_refeicoes' => isset($dados['sobra_refeicoes']) && $dados['sobra_refeicoes'] !== null ? (int)$dados['sobra_refeicoes'] : 0,
                'sobra_senhas' => isset($dados['sobra_senhas']) && $dados['sobra_senhas'] !== null ? (int)$dados['sobra_senhas'] : 0
            ]
        ]);
    } catch (\Exception $e) {
        relatorioLog("ERRO rota /admin/api/relatorio/fechamento-info [GET] | data={$data} | msg=" . $e->getMessage());
        jsonResponse($app, 500, [
            'ok' => false,
            'error' => 'Erro ao carregar informações do fechamento.',
            'message' => $e->getMessage()
        ]);
    }
});

$app->post("/admin/api/relatorio/fechamento-info", function () use ($app) {

    $sql = new Sql();

    try {
        $raw = $app->request()->getBody();
        $input = json_decode($raw, true);

        if (!is_array($input)) {
            throw new \Exception('JSON inválido.');
        }

        $data = isset($input['data']) ? trim((string)$input['data']) : date('Y-m-d');
        if ($data === '') $data = date('Y-m-d');

        $qtdRefeicoesServidas = isset($input['qtd_refeicoes_servidas']) ? (int)$input['qtd_refeicoes_servidas'] : 0;
        $ocorrencias = isset($input['ocorrencias']) ? (string)$input['ocorrencias'] : '';
        $cardapio = isset($input['cardapio']) ? (string)$input['cardapio'] : '';

        $dados = salvarInformacoesFechamentoRelatorio($sql, $data, $qtdRefeicoesServidas, $ocorrencias, $cardapio);

        jsonResponse($app, 200, [
            'ok' => true,
            'message' => 'Informações do fechamento salvas com sucesso.',
            'data' => $dados
        ]);
    } catch (\Exception $e) {
        relatorioLog("ERRO rota /admin/api/relatorio/fechamento-info [POST] | msg=" . $e->getMessage());
        jsonResponse($app, 500, [
            'ok' => false,
            'error' => 'Erro ao salvar informações do fechamento.',
            'message' => $e->getMessage()
        ]);
    }
});


/**
 * 📊 Tela de Relatório (Senhas)
 */
$app->get("/admin/relatorio/senhas", function () {
    Funcionarios::checkPermission('RELATORIOS_VIEW');
    $page = new PageAdmin();
    $page->setTpl("admin/relatorio-senhas", array(
        "relatorio" => []
    ));
});

/**
 * Helper: normaliza tipoSenha
 */
function _tipoSenhaFiltro()
{
    $t = isset($_GET['tipoSenha']) ? trim($_GET['tipoSenha']) : '';
    $t = strtoupper($t);
    if ($t === 'NORMAL' || $t === 'GENERICA') return $t;
    return '';
}

/**
 * ✅ RESUMO DO DIA (JSON)
 * /admin/api/relatorio/senhas/resumo?data=YYYY-MM-DD&tipoSenha=NORMAL|GENERICA
 */
$app->get("/admin/api/relatorio/senhas/resumo", function () use ($app) {

    $data = (isset($_GET["data"]) && $_GET["data"]) ? $_GET["data"] : date("Y-m-d");
    $tipoSenha = _tipoSenhaFiltro();

    $sql = new Sql();

    if (function_exists('pc_obter_resumo_senhas_dia')) {
        $row = pc_obter_resumo_senhas_dia($sql, $data, $tipoSenha);

        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode([
            "ok" => true,
            "data" => function_exists('pc_normalizar_data_refeicao') ? pc_normalizar_data_refeicao($data) : $data,
            "tipoSenha" => $tipoSenha ?: null,
            "resumo" => [
                "total" => (int)$row["total"],
                "normal" => (int)$row["total_normal"],
                "generica" => (int)$row["total_generica"],
                "titulares" => (int)$row["total_titular"],
                "dependentes" => (int)$row["total_dependente"],
                "deficientes" => (int)$row["total_deficiente"],
                "situacao_risco_masculino" => (int)$row["total_rua_masculino"],
                "situacao_risco_feminino" => (int)$row["total_rua_feminino"],
                "faixas_etarias" => (int)$row["total_faixas"],
                "nao_classificados" => (int)$row["total_nao_classificado"]
            ]
        ]);
        exit;
    }

    $whereTipo = $tipoSenha ? " AND tipoSenha = :tipoSenha" : "";

    // Observação: considera "linhas em tb_senhas" como refeições liberadas
    $res = $sql->select("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN tipoSenha = 'NORMAL' THEN 1 ELSE 0 END) AS total_normal,
            SUM(CASE WHEN tipoSenha = 'GENERICA' THEN 1 ELSE 0 END) AS total_generica,
            /*
              Titulares do dia: conta o atendimento por titular mesmo quando só foram liberados dependentes.
              (no fluxo atual, ao selecionar dependentes, pode não existir linha do titular em tb_senhas)
            */
            COUNT(DISTINCT CASE
                WHEN tipoSenha = 'NORMAL' AND id_titular IS NOT NULL THEN id_titular
                ELSE NULL
            END) AS total_titular,
            SUM(CASE WHEN id_dependente IS NOT NULL THEN 1 ELSE 0 END) AS total_dependente,
            SUM(CASE WHEN UPPER(TRIM(Deficiente)) IN ('SIM','S','1','TRUE','YES','PCD','DEFICIENTE') THEN 1 ELSE 0 END) AS total_deficiente,
            SUM(CASE WHEN UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(status_cliente,''),'Á','A'),'À','A'),'Â','A'),'Ã','A'),'É','E'),'Ç','C'))) = 'PESSOA EM SITUACAO DE RUA' AND UPPER(TRIM(Genero)) = 'M' THEN 1 ELSE 0 END) AS total_rua_masculino,
            SUM(CASE WHEN UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(status_cliente,''),'Á','A'),'À','A'),'Â','A'),'Ã','A'),'É','E'),'Ç','C'))) = 'PESSOA EM SITUACAO DE RUA' AND UPPER(TRIM(Genero)) = 'F' THEN 1 ELSE 0 END) AS total_rua_feminino
        FROM tb_senhas
        WHERE data_refeicao = :data
        {$whereTipo}
    ", $tipoSenha ? [":data" => $data, ":tipoSenha" => $tipoSenha] : [":data" => $data]);

    $row = isset($res[0]) ? $res[0] : [
        "total" => 0,
        "total_normal" => 0,
        "total_generica" => 0,
        "total_titular" => 0,
        "total_dependente" => 0,
        "total_deficiente" => 0,
        "total_rua_masculino" => 0,
        "total_rua_feminino" => 0
    ];

    $app->response()->header("Content-Type", "application/json; charset=utf-8");
    echo json_encode([
        "ok" => true,
        "data" => $data,
        "tipoSenha" => $tipoSenha ?: null,
        "resumo" => [
            "total" => (int)$row["total"],
            "normal" => (int)$row["total_normal"],
            "generica" => (int)$row["total_generica"],
            "titulares" => (int)$row["total_titular"],
            "dependentes" => (int)$row["total_dependente"],
            "deficientes" => (int)$row["total_deficiente"],
            "situacao_risco_masculino" => (int)$row["total_rua_masculino"],
            "situacao_risco_feminino" => (int)$row["total_rua_feminino"],
        ]
    ]);
    exit;
});

/**
 * ✅ LISTA DO DIA (JSON + paginação)
 * /admin/api/relatorio/senhas/lista?data=YYYY-MM-DD&page=1&pageSize=50&tipoSenha=NORMAL|GENERICA
 */
$app->get("/admin/api/relatorio/senhas/lista", function () use ($app) {

    $data = (isset($_GET["data"]) && $_GET["data"]) ? $_GET["data"] : date("Y-m-d");
    $tipoSenha = _tipoSenhaFiltro();

    $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    $pageSize = isset($_GET["pageSize"]) ? (int)$_GET["pageSize"] : 50;

    if ($page < 1) $page = 1;
    if ($pageSize < 10) $pageSize = 10;
    if ($pageSize > 200) $pageSize = 200;

    $offset = ($page - 1) * $pageSize;

    $sql = new Sql();

    $whereTipo = $tipoSenha ? " AND tipoSenha = :tipoSenha" : "";
    $params = $tipoSenha ? [":data" => $data, ":tipoSenha" => $tipoSenha] : [":data" => $data];

    $totalRes = $sql->select("
        SELECT COUNT(*) AS total
        FROM tb_senhas
        WHERE data_refeicao = :data
        {$whereTipo}
    ", $params);

    $total = isset($totalRes[0]["total"]) ? (int)$totalRes[0]["total"] : 0;

    // LIMIT com inteiros "injetados" (page/pageSize já sanitizados como int)
    $rows = $sql->select("
        SELECT
            id,
            cliente,
            cpf,
            Idade AS idade,
            Genero AS genero,
            Deficiente AS deficiente,
            tipoSenha,
            status_cliente,
            data_refeicao,
            id_titular,
            id_dependente,
            registration_date
        FROM tb_senhas
        WHERE data_refeicao = :data
        {$whereTipo}
        ORDER BY id DESC
        LIMIT {$offset}, {$pageSize}
    ", $params);

    $app->response()->header("Content-Type", "application/json; charset=utf-8");
    echo json_encode([
        "ok" => true,
        "data" => $data,
        "tipoSenha" => $tipoSenha ?: null,
        "page" => $page,
        "pageSize" => $pageSize,
        "total" => $total,
        "items" => $rows
    ]);
    exit;
});

/**
 * ✅ TOP 10 TITULARES (por frequência no período)
 * /admin/api/relatorio/senhas/top10?data=YYYY-MM-DD&period=DIA|SEMANA|MES|ANO
 *
 * Retorna:
 * - total_dias: em quantos dias diferentes o titular apareceu no período (frequência)
 * - total_refeicoes: total de linhas (refeições) no período
 */
$app->get("/admin/api/relatorio/senhas/top10", function () use ($app) {

    $refData = (isset($_GET["data"]) && $_GET["data"]) ? $_GET["data"] : date("Y-m-d");
    $periodo = isset($_GET["period"]) ? strtoupper(trim($_GET["period"])) : "DIA";
    $ordenar = isset($_GET["order"]) ? strtoupper(trim($_GET["order"])) : "FREQ"; // FREQ | REFEICOES

    // calcula range (YYYY-MM-DD)
    try {
        $ref = new DateTimeImmutable($refData);
    } catch (Exception $e) {
        $ref = new DateTimeImmutable(date("Y-m-d"));
        $refData = $ref->format("Y-m-d");
    }

    switch ($periodo) {
        case "SEMANA":
            // segunda a domingo
            $inicio = $ref->modify("monday this week")->format("Y-m-d");
            $fim    = $ref->modify("sunday this week")->format("Y-m-d");
            break;

        case "MES":
            $inicio = $ref->modify("first day of this month")->format("Y-m-d");
            $fim    = $ref->modify("last day of this month")->format("Y-m-d");
            break;

        case "ANO":
            $inicio = $ref->setDate((int)$ref->format("Y"), 1, 1)->format("Y-m-d");
            $fim    = $ref->setDate((int)$ref->format("Y"), 12, 31)->format("Y-m-d");
            break;

        case "DIA":
        default:
            $periodo = "DIA";
            $inicio  = $ref->format("Y-m-d");
            $fim     = $ref->format("Y-m-d");
            break;
    }

    $sql = new Sql();

    // Ordenação (whitelist)
    $orderBy = "total_dias DESC, total_refeicoes DESC";
    if ($ordenar === "REFEICOES" || $ordenar === "REFEICOE" || $ordenar === "REFEICAO") {
        $ordenar = "REFEICOES";
        $orderBy = "total_refeicoes DESC, total_dias DESC";
    } else {
        $ordenar = "FREQ";
        $orderBy = "total_dias DESC, total_refeicoes DESC";
    }

    $rows = $sql->select("
        SELECT
            s.id_titular,
            t.nome_completo AS titular_nome,
            t.cpf AS titular_cpf,
            COUNT(DISTINCT s.data_refeicao) AS total_dias,
            COUNT(*) AS total_refeicoes
        FROM tb_senhas s
        INNER JOIN tb_titular t ON t.id = s.id_titular
        WHERE s.id_titular IS NOT NULL
          AND s.tipoSenha = 'NORMAL'
          AND s.data_refeicao BETWEEN :inicio AND :fim
        GROUP BY s.id_titular, t.nome_completo, t.cpf
        ORDER BY {$orderBy}
        LIMIT 10
    ", [
        ":inicio" => $inicio,
        ":fim" => $fim
    ]);

    $app->response()->header("Content-Type", "application/json; charset=utf-8");
    echo json_encode([
        "ok" => true,
        "data_ref" => $refData,
        "periodo" => $periodo,
        "ordenar" => $ordenar,
        "inicio" => $inicio,
        "fim" => $fim,
        "items" => $rows
    ]);
    exit;
});

/**
 * ✅ RELATÓRIO MENSAL (por dia) p/ gráfico
 * /admin/api/relatorio/senhas/mensal?mes=YYYY-MM
 */
$app->get("/admin/api/relatorio/senhas/mensal", function () use ($app) {

    $mes = (isset($_GET["mes"]) && $_GET["mes"]) ? $_GET["mes"] : date("Y-m");

    // valida formato YYYY-MM
    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode(["ok" => false, "error" => "Parâmetro mes inválido. Use YYYY-MM."]);
        exit;
    }

    $sql = new Sql();

    $rows = $sql->select("
        SELECT
            data_refeicao,
            COUNT(*) AS total,
            SUM(CASE WHEN tipoSenha = 'NORMAL' THEN 1 ELSE 0 END) AS normal,
            SUM(CASE WHEN tipoSenha = 'GENERICA' THEN 1 ELSE 0 END) AS generica,
            COUNT(DISTINCT CASE
                WHEN tipoSenha = 'NORMAL' AND id_titular IS NOT NULL THEN id_titular
                ELSE NULL
            END) AS titulares,
            SUM(CASE WHEN id_dependente IS NOT NULL THEN 1 ELSE 0 END) AS dependentes,
            SUM(CASE WHEN UPPER(TRIM(Deficiente)) IN ('SIM','S','1','TRUE','YES','PCD','DEFICIENTE') THEN 1 ELSE 0 END) AS deficientes
        FROM tb_senhas
        WHERE data_refeicao LIKE :mes
        GROUP BY data_refeicao
        ORDER BY data_refeicao ASC
    ", [":mes" => $mes . "-%"]);

    $app->response()->header("Content-Type", "application/json; charset=utf-8");
    echo json_encode([
        "ok" => true,
        "mes" => $mes,
        "items" => $rows
    ]);
    exit;
});

/**
 * ✅ EXPORT CSV DO DIA
 * /admin/api/relatorio/senhas/export?data=YYYY-MM-DD&tipoSenha=NORMAL|GENERICA
 */
$app->get("/admin/api/relatorio/senhas/export", function () use ($app) {

    $data = (isset($_GET["data"]) && $_GET["data"]) ? $_GET["data"] : date("Y-m-d");
    $tipoSenha = _tipoSenhaFiltro();

    $sql = new Sql();

    $whereTipo = $tipoSenha ? " AND tipoSenha = :tipoSenha" : "";
    $params = $tipoSenha ? [":data" => $data, ":tipoSenha" => $tipoSenha] : [":data" => $data];

    $rows = $sql->select("
        SELECT
            id,
            cliente,
            cpf,
            Idade AS idade,
            Genero AS genero,
            Deficiente AS deficiente,
            tipoSenha,
            status_cliente,
            data_refeicao,
            id_titular,
            id_dependente,
            registration_date
        FROM tb_senhas
        WHERE data_refeicao = :data
        {$whereTipo}
        ORDER BY id ASC
    ", $params);

    $suffix = $tipoSenha ? "_" . strtolower($tipoSenha) : "";
    $filename = "relatorio_senhas_{$data}{$suffix}.csv";

    $app->response()->header("Content-Type", "text/csv; charset=utf-8");
    $app->response()->header("Content-Disposition", "attachment; filename={$filename}");

    // BOM UTF-8 para Excel
    echo "\xEF\xBB\xBF";

    $out = fopen("php://output", "w");

    fputcsv($out, [
        "ID",
        "CLIENTE",
        "CPF",
        "IDADE",
        "GENERO",
        "DEFICIENTE",
        "TIPO_SENHA",
        "STATUS",
        "DATA_REFEICAO",
        "ID_TITULAR",
        "ID_DEPENDENTE",
        "REGISTRATION_DATE"
    ], ";");

    foreach ($rows as $r) {
        fputcsv($out, [
            $r["id"] ?? "",
            $r["cliente"] ?? "",
            $r["cpf"] ?? "",
            $r["idade"] ?? "",
            $r["genero"] ?? "",
            $r["deficiente"] ?? "",
            $r["tipoSenha"] ?? "",
            $r["status_cliente"] ?? "",
            $r["data_refeicao"] ?? "",
            $r["id_titular"] ?? "",
            $r["id_dependente"] ?? "",
            $r["registration_date"] ?? ""
        ], ";");
    }

    fclose($out);
    exit;
});




/**
 * ✅ LISTA TITULARES (JSON)
 */
$app->get("/admin/api/titulares", function () {

    $sql = new Sql();

    $result = $sql->select("
        SELECT 
            id,
            nome_completo AS nome,
            cpf,
            idade,
            genero
        FROM tb_titular
        ORDER BY nome_completo
    ");

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($result);
    exit;
});

/**
 * ✅ LISTA DEPENDENTES DO TITULAR (JSON)
 */
$app->get("/admin/titulares/:id/dependentes", function ($id) {

    $sql = new Sql();

    $result = $sql->select("
        SELECT 
            id,
            nome,
            idade,
            genero,
            dependencia_cliente
        FROM tb_dependentes
        WHERE id_titular = :id
        ORDER BY nome
    ", [
        ":id" => (int)$id
    ]);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($result);
    exit;
});

/**
 * ✅ CONTAGEM DE SENHAS VENDIDAS NO DIA (baseada no banco)
 * GET /admin/api/senhas/contagem?data=YYYY-MM-DD
 */
$app->get("/admin/api/senhas/contagem", function () use ($app) {

    $req = $app->request(); // Slim 2
    $data = $req->get("data");

    if (!$data) $data = date("Y-m-d");

    $sql = new Sql();
    $LIMITE_SENHAS_DIA = getLimiteSenhasDia();

    $total = contarSenhasVendidasNoDia($sql, $data);

    $fech = $sql->select("SELECT fechado, total, limite, fechado_em FROM tb_fechamento_dia WHERE data_refeicao = :data", [
        ":data" => $data
    ]);

    $fechado = 0;
    $fechadoEm = null;
    $limite = $LIMITE_SENHAS_DIA;

    if ($fech && isset($fech[0])) {
        if (isset($fech[0]['fechado'])) {
            $fechado = (int)$fech[0]['fechado'];
        }
        if (isset($fech[0]['fechado_em'])) {
            $fechadoEm = $fech[0]['fechado_em'];
        }
        if ($fechado === 0) {
            $sql->query("
                UPDATE tb_fechamento_dia
                SET limite = :limite, atualizado_em = NOW()
                WHERE data_refeicao = :data
                  AND fechado = 0
            ", [
                ":limite" => $LIMITE_SENHAS_DIA,
                ":data" => $data
            ]);
        } elseif (isset($fech[0]['limite']) && (int)$fech[0]['limite'] > 0) {
            $limite = (int)$fech[0]['limite'];
        }
    }

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "ok" => true,
        "message" => "Contagem carregada com sucesso.",
        "data" => [
            "total" => $total,
            "limite" => $limite,
            "fechado" => $fechado,
            "fechado_em" => $fechadoEm
        ]
    ]);
    exit;
});


/**
 * ✅ VERIFICA SE TITULAR JÁ COMPROU NO DIA (JSON)
 * GET /admin/api/senhas/ja-comprou?data=YYYY-MM-DD&cpf=...
 */
/**
 * GET /admin/api/senhas/ja-comprou?data=YYYY-MM-DD&cpf=...
 */
$app->get("/admin/api/senhas/ja-comprou", function () use ($app) {

    try {

        $req  = $app->request();
        $data = trim((string)$req->get("data"));
        $cpf  = preg_replace("/\D+/", "", (string)$req->get("cpf"));

        if ($data === "") {
            $data = date("Y-m-d");
        }

        if ($cpf === "") {
            jsonResponse($app, 400, [
                "ok" => false,
                "error" => "CPF inválido."
            ]);
        }

        $sql = new Sql();

        $row = $sql->select("
            SELECT 1
            FROM tb_senhas
            WHERE data_refeicao = :data
            AND cpf = :cpf
            AND (id_dependente IS NULL OR id_dependente = 0)
            LIMIT 1
        ", [
            ":data" => $data,
            ":cpf"  => $cpf
        ]);

        $jaComprou = !empty($row);

        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo json_encode([
            "ok" => true,
            "ja_comprou" => $jaComprou
        ]);
        exit;
    } catch (Exception $e) {

        jsonResponse($app, 500, [
            "ok" => false,
            "error" => "Erro ao verificar compra do titular.",
            "details" => $e->getMessage()
        ]);
    }
});
/**
 * ✅ SALVAR SENHAS (titular + dependentes OU genérica)
 *
 * Body JSON:
 * {
 *   "tipoSenha": "NORMAL"|"GENERICA",
 *   "status_cliente": "...",
 *   "data_refeicao": "YYYY-MM-DD",
 *   "itens": [
 *     {"cliente":"...", "cpf":"...", "idade":"...", "genero":"...", "deficiente":"..."},
 *     ...
 *   ]
 * }
 *
 * Regras de validação (recompra no mesmo dia):
 * - Para tipoSenha = "GENERICA" (ou cpf vazio): NÃO valida duplicidade (permite várias, mas limite diário controla).
 * - Para tipoSenha = "NORMAL": bloqueia se já existir registro no mesmo dia para a mesma combinação (cpf + cliente).
 *   => Isso resolve o problema de N pessoas com o mesmo nome: quem manda é o CPF.
 *   => Para dependentes, como normalmente não há CPF próprio, o frontend envia o CPF do titular + nome do dependente,
 *      e a checagem vira (cpf do titular + nome do dependente) - bloqueia cada dependente individualmente.
 */
$app->post("/admin/api/senhas", function () use ($app) {

    $req  = $app->request(); // Slim 2
    $body = json_decode($req->getBody(), true);

    if (!$body) {
        $app->response()->status(400);
        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode(["ok" => false, "error" => "JSON inválido"]);
        exit;
    }

    $tipoSenha      = isset($body["tipoSenha"]) ? $body["tipoSenha"] : "NORMAL";
    $tipoSenha      = function_exists('pc_tipo_senha_filtro') ? (pc_tipo_senha_filtro($tipoSenha) ?: 'NORMAL') : strtoupper(trim((string)$tipoSenha));
    $status_cliente = isset($body["status_cliente"]) ? $body["status_cliente"] : "";
    $data_refeicao  = isset($body["data_refeicao"]) ? $body["data_refeicao"] : date("Y-m-d");
    $data_refeicao  = function_exists('pc_normalizar_data_refeicao') ? pc_normalizar_data_refeicao($data_refeicao) : $data_refeicao;
    $itens          = isset($body["itens"]) ? $body["itens"] : [];

    if (!is_array($itens) || count($itens) === 0) {
        $app->response()->status(400);
        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode(["ok" => false, "error" => "Nenhum item para salvar"]);
        exit;
    }

    $sql = new Sql();

    $titularJaComprou = false; // usado para permitir venda de dependentes mesmo se titular já comprou

    // 1) Validação de duplicidade (ANTES da transação)
    //    Regras:
    //      - TITULAR: bloqueia recompra no mesmo dia por CPF (cpf != "")
    //      - DEPENDENTE: bloqueia recompra no mesmo dia por ID do dependente (id_dependente)
    //      - GENERICA: não valida duplicidade (não é pessoa)
    //
    // Flexibilidade desejada:
    // - Se o titular JÁ comprou hoje, ainda pode comprar (imprimir) os dependentes.
    //   => Nesse caso, removemos o item do titular e seguimos com os dependentes.
    // - Para dependentes, o bloqueio continua: se algum dependente já comprou hoje, bloqueia.
    if (strtoupper($tipoSenha) !== "GENERICA") {

        $dupTitular = [];
        $dupDependentes = [];

        foreach ($itens as $item) {

            $cliente = isset($item["cliente"]) ? trim($item["cliente"]) : "";

            $idDependente = isset($item["id_dependente"]) && $item["id_dependente"] !== "" ? (int)$item["id_dependente"] : 0;

            // DEPENDENTE: valida por id_dependente
            if ($idDependente > 0) {

                $row = $sql->select("
                    SELECT id
                    FROM tb_senhas
                    WHERE data_refeicao = :data_refeicao
                      AND id_dependente = :id_dependente
                    LIMIT 1
                ", [
                    ":data_refeicao"  => $data_refeicao,
                    ":id_dependente" => $idDependente
                ]);

                if (count($row) > 0) {
                    $dupDependentes[] = $cliente . " (ID " . $idDependente . ")";
                }

                continue;
            }

            // TITULAR: valida por id_titular primeiro; se não vier, usa CPF como fallback
            $idTitular = isset($item["id_titular"]) && $item["id_titular"] !== "" ? (int)$item["id_titular"] : 0;
            $cpfRaw = isset($item["cpf"]) ? (string)$item["cpf"] : "";
            $cpf    = preg_replace("/\D+/", "", $cpfRaw);

            if ($idTitular > 0) {
                $row = $sql->select("
                    SELECT id
                    FROM tb_senhas
                    WHERE data_refeicao = :data_refeicao
                      AND id_titular = :id_titular
                      AND (id_dependente IS NULL OR id_dependente = 0)
                    LIMIT 1
                ", [
                    ":data_refeicao" => $data_refeicao,
                    ":id_titular"    => $idTitular
                ]);

                if (count($row) > 0) {
                    $dupTitular[] = $cliente . " (titular #" . $idTitular . ")";
                }
                continue;
            }

            if ($cpf === "") continue;

            $row = $sql->select("
                SELECT id
                FROM tb_senhas
                WHERE data_refeicao = :data_refeicao
                  AND cpf = :cpf
                  AND (id_dependente IS NULL OR id_dependente = 0)
                LIMIT 1
            ", [
                ":data_refeicao" => $data_refeicao,
                ":cpf"           => $cpf
            ]);

            if (count($row) > 0) {
                $dupTitular[] = $cliente . " (CPF " . $cpf . ")";
            }
        }

        // Se algum DEPENDENTE já comprou, bloqueia (regra mantém-se)
        if (count($dupDependentes) > 0) {
            $app->response()->status(409);
            $app->response()->header("Content-Type", "application/json; charset=utf-8");
            echo json_encode([
                "ok" => false,
                "error" => "Um ou mais dependentes já realizaram uma compra hoje e não podem comprar novamente.",
                "duplicados" => array_values(array_unique($dupDependentes))
            ]);
            exit;
        }

        // Se o TITULAR já comprou hoje, bloqueia apenas quando houver tentativa de vender
        // novamente para o titular. Dependentes continuam podendo comprar normalmente,
        // mas SEM remover ou alterar o payload no backend.
        if (count($dupTitular) > 0) {
            $haItemTitularNoPayload = false;
            foreach ($itens as $item) {
                $idDependente = isset($item["id_dependente"]) && $item["id_dependente"] !== "" ? (int)$item["id_dependente"] : 0;
                if ($idDependente <= 0) {
                    $haItemTitularNoPayload = true;
                    break;
                }
            }

            if ($haItemTitularNoPayload) {
                $app->response()->status(409);
                $app->response()->header("Content-Type", "application/json; charset=utf-8");
                echo json_encode([
                    "ok" => false,
                    "error" => "Titular já realizou uma compra hoje e não pode comprar novamente.",
                    "duplicados" => array_values(array_unique($dupTitular))
                ]);
                exit;
            }

            $titularJaComprou = true;
        }
    }
    $LIMITE_SENHAS_DIA = getLimiteSenhasDia();
    $data_refeicao = function_exists('pc_normalizar_data_refeicao') ? pc_normalizar_data_refeicao($data_refeicao) : $data_refeicao;

    if (false) {
        $sql->select("CALL sp_fechamento_atualizar(:data, :limite)", [
            ":data" => $data_refeicao,
            ":limite" => $LIMITE_SENHAS_DIA
        ]);
    }

    $fech = $sql->select("SELECT fechado, total, limite FROM tb_fechamento_dia WHERE data_refeicao = :data", [
        ":data" => $data_refeicao
    ]);

    if ($fech && (int)$fech[0]["fechado"] === 1) {
        $app->response()->status(409);
        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode([
            "ok" => false,
            "error" => "Dia fechado: limite diário atingido.",
            "total" => (int)$fech[0]["total"],
            "limite" => (int)$fech[0]["limite"]
        ]);
        exit;
    }



    // 2) Insert (transação)
    $sql->query("START TRANSACTION");

    try {

        $sql->query("
            INSERT INTO tb_fechamento_dia (
                data_refeicao,
                limite,
                total,
                fechado,
                fechado_em,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                :limite,
                0,
                0,
                NULL,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                limite = CASE
                    WHEN fechado = 1 THEN limite
                    ELSE VALUES(limite)
                END,
                atualizado_em = NOW()
        ", [
            ':data_refeicao' => $data_refeicao,
            ':limite' => $LIMITE_SENHAS_DIA
        ]);

        $fechLock = $sql->select("
            SELECT fechado, total, limite
            FROM tb_fechamento_dia
            WHERE data_refeicao = :data_refeicao
            LIMIT 1
            FOR UPDATE
        ", [
            ':data_refeicao' => $data_refeicao
        ]);

        $limiteTransacao = isset($fechLock[0]['limite']) && (int)$fechLock[0]['limite'] > 0
            ? (int)$fechLock[0]['limite']
            : $LIMITE_SENHAS_DIA;
        $totalAtualBanco = contarSenhasVendidasNoDia($sql, $data_refeicao);
        $qtdSolicitada = count($itens);
        $fechadoTransacao = isset($fechLock[0]['fechado']) ? (int)$fechLock[0]['fechado'] : 0;

        if ($fechadoTransacao === 1 || $totalAtualBanco >= $limiteTransacao) {
            $sql->query("ROLLBACK");
            $app->response()->status(409);
            $app->response()->header("Content-Type", "application/json; charset=utf-8");
            echo json_encode([
                "ok" => false,
                "error" => "Dia fechado: limite diÃ¡rio atingido.",
                "total" => $totalAtualBanco,
                "limite" => $limiteTransacao
            ]);
            exit;
        }

        if (($totalAtualBanco + $qtdSolicitada) > $limiteTransacao) {
            $sql->query("ROLLBACK");
            $restante = max(0, $limiteTransacao - $totalAtualBanco);
            $app->response()->status(409);
            $app->response()->header("Content-Type", "application/json; charset=utf-8");
            echo json_encode([
                "ok" => false,
                "error" => "A venda ultrapassa o limite diÃ¡rio de senhas.",
                "total" => $totalAtualBanco,
                "limite" => $limiteTransacao,
                "solicitadas" => $qtdSolicitada,
                "restante" => $restante
            ]);
            exit;
        }

        if (function_exists('pc_validar_duplicidade_senhas')) {
            $duplicidadeTransacao = pc_validar_duplicidade_senhas($sql, $data_refeicao, $tipoSenha, $itens);
            $temDuplicidadeTransacao = count($duplicidadeTransacao['dup_dependentes']) > 0
                || (count($duplicidadeTransacao['dup_titular']) > 0 && !empty($duplicidadeTransacao['ha_item_titular_no_payload']));

            if ($temDuplicidadeTransacao && function_exists('pc_resposta_erro_duplicidade_senhas')) {
                $sql->query("ROLLBACK");
                pc_resposta_erro_duplicidade_senhas($app, $duplicidadeTransacao);
                exit;
            }
        }

        $ids = [];

        foreach ($itens as $item) {

            $cliente    = isset($item["cliente"]) ? trim($item["cliente"]) : "";
            $cpfRaw     = isset($item["cpf"]) ? (string)$item["cpf"] : "";
            $idade      = isset($item["idade"]) ? $item["idade"] : "";
            $genero     = isset($item["genero"]) ? $item["genero"] : "";
            $deficiente = isset($item["deficiente"]) ? $item["deficiente"] : "";

            // CPF só com dígitos para padronizar
            $cpf = preg_replace("/\D+/", "", $cpfRaw);

            $idTitular    = isset($item["id_titular"]) && $item["id_titular"] !== "" ? (int)$item["id_titular"] : null;
            $idDependente = isset($item["id_dependente"]) && $item["id_dependente"] !== "" ? (int)$item["id_dependente"] : null;

            $statusClienteBanco = $status_cliente;

            if (strtoupper($tipoSenha) !== "GENERICA") {
                $dadosTitularBanco = buscarDadosTitularParaSenha($sql, $idTitular, $cpf);

                if ($dadosTitularBanco) {
                    if ($cpf === '' && !empty($dadosTitularBanco['cpf'])) {
                        $cpf = preg_replace("/\D+/", "", (string)$dadosTitularBanco['cpf']);
                    }

                    if (($idade === '' || $idade === null) && isset($dadosTitularBanco['idade'])) {
                        $idade = $dadosTitularBanco['idade'];
                    }

                    if (($genero === '' || $genero === null) && isset($dadosTitularBanco['genero'])) {
                        $genero = $dadosTitularBanco['genero'];
                    }

                    if (isset($dadosTitularBanco['status_cliente']) && trim((string)$dadosTitularBanco['status_cliente']) !== '') {
                        $statusClienteBanco = trim((string)$dadosTitularBanco['status_cliente']);
                    }
                }
            }

            if ($statusClienteBanco === '' || $statusClienteBanco === null) {
                $statusClienteBanco = 'ATIVO';
            }

            $sql->query("
                INSERT INTO tb_senhas
                (cliente, cpf, Idade, Genero, Deficiente, tipoSenha, status_cliente, data_refeicao, id_titular, id_dependente, registration_date, registration_date_update)
                VALUES
                (:cliente, :cpf, :idade, :genero, :deficiente, :tipoSenha, :status_cliente, :data_refeicao, :id_titular, :id_dependente, NOW(), NOW())
            ", [
                ":cliente"        => $cliente,
                ":cpf"            => $cpf,
                ":idade"          => $idade,
                ":genero"         => $genero,
                ":deficiente"     => $deficiente,
                ":tipoSenha"      => $tipoSenha,
                ":status_cliente" => $statusClienteBanco,
                ":data_refeicao" => $data_refeicao,
                ":id_titular" => $idTitular,
                ":id_dependente" => $idDependente
            ]);


            $row = $sql->select("SELECT LAST_INSERT_ID() AS id");
            $ids[] = (int)$row[0]["id"];
        }

        if (function_exists('pc_atualizar_fechamento_dia')) {
            pc_atualizar_fechamento_dia($sql, $data_refeicao, $limiteTransacao, false);
        }

        $sql->query("COMMIT");

        $sql->select("CALL sp_fechamento_atualizar(:data, :limite)", [
            ":data" => $data_refeicao,
            ":limite" => isset($limiteTransacao) ? $limiteTransacao : $LIMITE_SENHAS_DIA
        ]);


        // Se fechou agora, tenta gerar/atualizar o relatório do dia em tb_relatorios
        try {
            $fech2 = $sql->select("SELECT fechado, total, limite FROM tb_fechamento_dia WHERE data_refeicao = :data", [
                ":data" => $data_refeicao
            ]);

            if ($fech2 && (int)$fech2[0]["fechado"] === 1) {
                relatorioLog("Dia fechado após venda | data={$data_refeicao} | total=" . (int)$fech2[0]["total"] . " | limite=" . (int)$fech2[0]["limite"]);
                gerarRelatorioDia($sql, $data_refeicao);
            } else {
                relatorioLog("Dia ainda aberto após venda | data={$data_refeicao}");
            }
        } catch (\Exception $e) {
            // Não bloqueia a venda por falha no relatório, mas registra para depuração
            relatorioLog("Falha ao gerar/verificar relatório após venda | data={$data_refeicao} | msg=" . $e->getMessage());
        }

        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode(["ok" => true, "ids" => $ids, "titular_ja_comprou" => (bool)$titularJaComprou]);
        exit;
    } catch (\Exception $e) {

        $sql->query("ROLLBACK");

        $app->response()->status(500);
        $app->response()->header("Content-Type", "application/json; charset=utf-8");
        echo json_encode(["ok" => false, "error" => "Erro ao salvar senhas", "details" => $e->getMessage()]);
        exit;
    }
});



$app->post("/admin/api/relatorio/fechar", function () use ($app) {

    $sql = new Sql();
    $dataRef = date('Y-m-d');
    $limite = getLimiteSenhasDia();

    try {
        $total = contarSenhasVendidasNoDia($sql, $dataRef);

        $sql->query("
            INSERT INTO tb_fechamento_dia (
                data_refeicao,
                limite,
                total,
                fechado,
                fechado_em,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                :limite,
                :total,
                1,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                limite = VALUES(limite),
                total = VALUES(total),
                fechado = 1,
                fechado_em = NOW(),
                atualizado_em = NOW()
        ", [
            ':data_refeicao' => $dataRef,
            ':limite' => $limite,
            ':total' => $total
        ]);

        $colData = getColunaDataRelatorio($sql);
        $sql->query("
            UPDATE tb_relatorios
            SET fechado = 1
            WHERE DATE(`{$colData}`) = :data_ref
        ", [
            ':data_ref' => $dataRef
        ]);

        if (function_exists('backupAutomatico')) {
            @backupAutomatico(true, 86400, 'fechamento_dia');
        }

        jsonResponse($app, 200, [
            'ok' => true,
            'message' => 'Dia fechado com sucesso.',
            'data' => [
                'data' => $dataRef,
                'limite' => $limite,
                'total' => $total,
                'fechado' => 1
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse($app, 500, [
            'ok' => false,
            'error' => 'Falha ao fechar o dia.',
            'message' => $e->getMessage()
        ]);
    }
});

/**
 * ROTA: Fechamento manual do dia
 *
 * Ajustada para a estrutura real da tabela tb_fechamento_dia:
 * - data_refeicao (varchar(10) PK)
 * - limite (int)
 * - total (int)
 * - fechado (tinyint)
 * - fechado_em (timestamp)
 * - atualizado_em (timestamp)
 *
 * IMPORTANTE:
 * Cole este bloco dentro do seu app/routes/admin-vendas.php,
 * junto das demais rotas já existentes do arquivo.
 */

$app->post('/admin/api/fechamento/manual', function () use ($app) {

    $sql = new Sql();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $dataRef = !empty($data['data']) ? trim($data['data']) : (!empty($data['data_refeicao']) ? trim($data['data_refeicao']) : date('Y-m-d'));
    $qtdRefeicoesServidas = isset($data['qtd_refeicoes_servidas']) ? (int)$data['qtd_refeicoes_servidas'] : 0;
    $ocorrencias = isset($data['ocorrencias']) ? trim($data['ocorrencias']) : '';
    $cardapio = isset($data['cardapio']) ? trim($data['cardapio']) : '';

    try {

        if (!function_exists('salvarInformacoesFechamentoRelatorio')) {
            throw new Exception('Função salvarInformacoesFechamentoRelatorio() não encontrada.');
        }

        $resultado = salvarInformacoesFechamentoRelatorio(
            $sql,
            $dataRef,
            $qtdRefeicoesServidas,
            $ocorrencias,
            $cardapio
        );

        $sql->query("
            INSERT INTO tb_fechamento_dia (
                data_refeicao,
                limite,
                total,
                fechado,
                fechado_em,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                :limite,
                :total,
                1,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                limite = VALUES(limite),
                total = VALUES(total),
                fechado = 1,
                fechado_em = NOW(),
                atualizado_em = NOW()
        ", [
            ':data_refeicao' => $dataRef,
            ':limite' => isset($resultado['refeicoes_ofertadas']) ? (int)$resultado['refeicoes_ofertadas'] : getLimiteSenhasDia(),
            ':total' => isset($resultado['senhas_vendidas']) ? (int)$resultado['senhas_vendidas'] : 0
        ]);

        if (function_exists('backupAutomatico')) {
            @backupAutomatico(true, 86400, 'fechamento_dia');
        }

        jsonResponse($app, 200, [
            'ok' => true,
            'message' => 'Dia encerrado com sucesso.',
            'data' => $resultado
        ]);

    } catch (Exception $e) {
        jsonResponse($app, 500, [
            'ok' => false,
            'error' => 'Erro ao encerrar o dia.',
            'message' => $e->getMessage()
        ]);
    }
});
