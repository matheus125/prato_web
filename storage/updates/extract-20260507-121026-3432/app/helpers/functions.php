<?php

use \Hcode\Model\Funcionarios;

function formatPrice($vlprice)
{
    // ✅ correção: precedência
    if (!($vlprice > 0)) $vlprice = 0;

    return number_format((float)$vlprice, 2, ",", ".");
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function checkLogin($inadmin = true)
{
    return Funcionarios::checkLogin($inadmin);
}

function getUserName()
{
    $funcionarios = Funcionarios::getFromSession();
    return $funcionarios->getnome_funcionario();
}

/**
 * Garante que a pasta /backup exista e retorna o caminho do arquivo de log
 */
function backupLogFile(): string
{
    $dir = defined('BACKUP_DIR') ? BACKUP_DIR : ($_SERVER["DOCUMENT_ROOT"] . '/backup');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir . '/backup_notifications.log';
}

/**
 * Lock atômico com cooldown (segundos)
 */
function podeRodarBackup(int $cooldownSegundos = 600): bool
{
    $dir = defined('BACKUP_DIR') ? BACKUP_DIR : ($_SERVER["DOCUMENT_ROOT"] . '/backup');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $lockFile = $dir . '/backup.lock';

    $fp = fopen($lockFile, 'c+');
    if (!$fp) return false;

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    // precisa ler desde o começo
    rewind($fp);
    $last = (int) trim(stream_get_contents($fp));
    $agora = time();

    if ($last > 0 && ($agora - $last) < $cooldownSegundos) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) $agora);

    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

function backupLog(string $mensagem): void
{
    $arquivo = backupLogFile();
    $data = date('Y-m-d H:i:s');
    $linha = "{$data}|{$mensagem}\n";
    file_put_contents($arquivo, $linha, FILE_APPEND);
}

function backupAutomatico(bool $force = false, int $cooldown = 86400, string $contexto = 'rotina'): void
{
    backupLog("INFO: backupAutomatico() chamado | contexto={$contexto} | force=" . ($force ? '1' : '0') . " | cooldown={$cooldown}");

    if (!$force && !podeRodarBackup($cooldown)) {
        backupLog("INFO: backup não executado por cooldown/lock | contexto={$contexto}");
        return;
    }

    try {
        $database = 'prato_cheio';

        if (class_exists('Hcode\\DB\\Sql')) {
            try {
                $sql = new \Hcode\DB\Sql();
                $dbInfo = $sql->select("SELECT DATABASE() AS db");
                if ($dbInfo && !empty($dbInfo[0]['db'])) {
                    $database = (string)$dbInfo[0]['db'];
                }
            } catch (\Throwable $e) {
                backupLog("WARN: não foi possível descobrir o banco atual | contexto={$contexto} | msg=" . $e->getMessage());
            }
        }

        if (!class_exists('Hcode\\Backup\\BackupService')) {
            throw new \RuntimeException('Classe Hcode\\Backup\\BackupService não encontrada. Verifique o namespace/autoload do diretório vendor/API/php-classes/src/Backup.');
        }

        if (!class_exists('Hcode\\Backup\\UploadService')) {
            throw new \RuntimeException('Classe Hcode\\Backup\\UploadService não encontrada. Verifique o namespace/autoload do diretório vendor/API/php-classes/src/Backup.');
        }

        $backupService = new \Hcode\Backup\BackupService();
        $uploadService = new \Hcode\Backup\UploadService();

        $file = $backupService->createBackup($database);
        backupLog("INFO: backup gerado | contexto={$contexto} | arquivo=" . basename($file));

        $uploadService->send($file);
        backupLog("INFO: upload realizado com sucesso | contexto={$contexto} | arquivo=" . basename($file));

        limparBackupsAntigos(7);
    } catch (\Throwable $e) {
        backupLog("ERRO: falha no backup | contexto={$contexto} | msg=" . $e->getMessage());
    }
}

function getBackupNotifications(int $limit = 10): array
{
    $arquivo = backupLogFile();
    if (!file_exists($arquivo)) return [];

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas = array_reverse($linhas);

    $items = [];

    foreach (array_slice($linhas, 0, $limit) as $linha) {
        $parts = explode('|', $linha, 2);
        if (count($parts) < 2) continue;

        $data = trim($parts[0]);
        $msg  = trim($parts[1]);

        $items[] = [
            'msg'  => $msg,
            'time' => date('H:i', strtotime($data)),
            'date' => $data
        ];
    }

    return $items;
}

function getUltimoBackup(): ?string
{
    $arquivo = backupLogFile();
    if (!file_exists($arquivo)) return null;

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas = array_reverse($linhas);

    foreach ($linhas as $linha) {
        if (strpos($linha, 'Backup OK:') !== false) {
            $parts = explode('|', $linha, 2);
            if (count($parts) < 2) continue;
            return trim($parts[0]);
        }
    }

    return null;
}

function getStatusBackup(): string
{
    $arquivo = backupLogFile();
    if (!file_exists($arquivo)) return 'Nunca executado';

    $linhas = array_reverse(
        file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
    );

    foreach ($linhas as $linha) {
        if (strpos($linha, 'Erro:') !== false) {
            return 'Erro no último backup';
        }
        if (strpos($linha, 'Upload realizado com sucesso') !== false) {
            return 'Backup enviado com sucesso';
        }
    }

    return 'Status desconhecido';
}

function limparBackupsAntigos(int $dias = 7): void
{
    $dir = defined('BACKUP_DIR') ? BACKUP_DIR : ($_SERVER["DOCUMENT_ROOT"] . '/backup');
    if (!is_dir($dir)) return;

    $limite = time() - ($dias * 86400);

    foreach (glob($dir . "/*.sql") as $file) {
        if (filemtime($file) < $limite) {
            @unlink($file);
            @unlink($file . '.sent');
            @unlink($file . '.uploading');
            backupLog("Backup removido: " . basename($file));
        }
    }
}


function currentUserPerfil(): string
{
    return $_SESSION[\Hcode\Model\Funcionarios::SESSION]['perfil'] ?? '';
}

function canAccess($permissionKey)
{
    return Funcionarios::can($permissionKey);
}

function canAnyAccess($permissions)
{
    foreach ((array)$permissions as $permission) {
        if (Funcionarios::can($permission)) return true;
    }
    return false;
}

if (!function_exists('pc_normalizar_data_refeicao')) {
    function pc_normalizar_data_refeicao($dataRef): string
    {
        $dataRef = trim((string)$dataRef);

        if ($dataRef === '') {
            return date('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataRef)) {
            return $dataRef;
        }

        if (strpos($dataRef, '/') !== false) {
            $dt = DateTime::createFromFormat('d/m/Y', $dataRef);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($dataRef);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}

if (!function_exists('pc_get_limite_senhas_dia')) {
    function pc_get_limite_senhas_dia(): int
    {
        if (defined('LIMITE_SENHAS_DIA')) {
            return max(0, (int)LIMITE_SENHAS_DIA);
        }

        return 400;
    }
}

if (!function_exists('pc_tipo_senha_filtro')) {
    function pc_tipo_senha_filtro($tipoSenha): string
    {
        $tipoSenha = strtoupper(trim((string)$tipoSenha));
        return in_array($tipoSenha, array('NORMAL', 'GENERICA'), true) ? $tipoSenha : '';
    }
}

if (!function_exists('pc_contar_senhas_vendidas_dia')) {
    function pc_contar_senhas_vendidas_dia($sql, $dataRef): int
    {
        $dataRef = pc_normalizar_data_refeicao($dataRef);

        $res = $sql->select("
            SELECT COUNT(*) AS total
            FROM tb_senhas
            WHERE data_refeicao = :data_ref
        ", array(
            ':data_ref' => $dataRef
        ));

        return isset($res[0]['total']) ? (int)$res[0]['total'] : 0;
    }
}

if (!function_exists('pc_obter_resumo_senhas_dia')) {
    function pc_obter_resumo_senhas_dia($sql, $dataRef, $tipoSenha = ''): array
    {
        $dataRef = pc_normalizar_data_refeicao($dataRef);
        $tipoSenha = pc_tipo_senha_filtro($tipoSenha);
        $whereTipo = $tipoSenha !== '' ? " AND tipo_norm = :tipo_senha " : "";
        $params = array(':data_ref' => $dataRef);
        if ($tipoSenha !== '') {
            $params[':tipo_senha'] = $tipoSenha;
        }

        $rows = $sql->select("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN tipo_norm = 'NORMAL' THEN 1 ELSE 0 END), 0) AS total_normal,
                COALESCE(SUM(CASE WHEN tipo_norm = 'GENERICA' THEN 1 ELSE 0 END), 0) AS total_generica,
                COUNT(DISTINCT CASE
                    WHEN tipo_norm = 'NORMAL' AND id_titular IS NOT NULL THEN id_titular
                    WHEN tipo_norm = 'NORMAL' AND cpf_norm <> '' THEN CONCAT('CPF:', cpf_norm)
                    ELSE NULL
                END) AS total_titular,
                COALESCE(SUM(CASE WHEN tipo_norm = 'NORMAL' AND id_dependente IS NOT NULL AND id_dependente > 0 THEN 1 ELSE 0 END), 0) AS total_dependente,
                COALESCE(SUM(CASE WHEN pcd = 1 THEN 1 ELSE 0 END), 0) AS total_deficiente,
                COALESCE(SUM(CASE WHEN rua = 1 AND sexo = 'M' THEN 1 ELSE 0 END), 0) AS total_rua_masculino,
                COALESCE(SUM(CASE WHEN rua = 1 AND sexo = 'F' THEN 1 ELSE 0 END), 0) AS total_rua_feminino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END), 0) AS Idade_3a17Masculino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END), 0) AS Idade_3a17Masculino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 0 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END), 0) AS Idade_3a17Feminino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 1 AND idade BETWEEN 3 AND 17 THEN 1 ELSE 0 END), 0) AS Idade_3a17Feminino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END), 0) AS Idade_18a59Masculino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END), 0) AS Idade_18a59Masculino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 0 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END), 0) AS Idade_17a59Feminino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 1 AND idade BETWEEN 18 AND 59 THEN 1 ELSE 0 END), 0) AS Idade_17a59Feminino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 0 AND idade >= 60 THEN 1 ELSE 0 END), 0) AS Idade_60Masculino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'M' AND pcd = 1 AND idade >= 60 THEN 1 ELSE 0 END), 0) AS Idade_60Masculino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 0 AND idade >= 60 THEN 1 ELSE 0 END), 0) AS Idade_60Feminino,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo = 'F' AND pcd = 1 AND idade >= 60 THEN 1 ELSE 0 END), 0) AS Idade_60Feminino_PCD,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND sexo IN ('M','F') AND idade >= 3 THEN 1 ELSE 0 END), 0) AS total_faixas,
                COALESCE(SUM(CASE WHEN tipo_norm <> 'GENERICA' AND NOT (sexo IN ('M','F') AND idade >= 3) THEN 1 ELSE 0 END), 0) AS total_nao_classificado
            FROM (
                SELECT
                    CASE
                        WHEN UPPER(TRIM(IFNULL(tipoSenha,''))) = 'GENERICA' THEN 'GENERICA'
                        ELSE 'NORMAL'
                    END AS tipo_norm,
                    REGEXP_REPLACE(IFNULL(cpf,''), '[^0-9]', '') AS cpf_norm,
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
                    CASE
                        WHEN UPPER(TRIM(IFNULL(status_cliente,''))) LIKE '%RUA%' THEN 1
                        ELSE 0
                    END AS rua,
                    id_titular,
                    id_dependente
                FROM tb_senhas
                WHERE data_refeicao = :data_ref
            ) X
            WHERE 1 = 1
            {$whereTipo}
        ", $params);

        $row = isset($rows[0]) ? $rows[0] : array();
        $defaults = array(
            'total' => 0,
            'total_normal' => 0,
            'total_generica' => 0,
            'total_titular' => 0,
            'total_dependente' => 0,
            'total_deficiente' => 0,
            'total_rua_masculino' => 0,
            'total_rua_feminino' => 0,
            'Idade_3a17Masculino' => 0,
            'Idade_3a17Masculino_PCD' => 0,
            'Idade_3a17Feminino' => 0,
            'Idade_3a17Feminino_PCD' => 0,
            'Idade_18a59Masculino' => 0,
            'Idade_18a59Masculino_PCD' => 0,
            'Idade_17a59Feminino' => 0,
            'Idade_17a59Feminino_PCD' => 0,
            'Idade_60Masculino' => 0,
            'Idade_60Masculino_PCD' => 0,
            'Idade_60Feminino' => 0,
            'Idade_60Feminino_PCD' => 0,
            'total_faixas' => 0,
            'total_nao_classificado' => 0
        );

        $out = array_merge($defaults, $row);
        foreach ($out as $key => $value) {
            $out[$key] = (int)$value;
        }

        return $out;
    }
}

if (!function_exists('pc_somar_faixas_relatorio')) {
    function pc_somar_faixas_relatorio(array $relatorio): int
    {
        $campos = array(
            'Idade_3a17Masculino',
            'Idade_3a17Masculino_PCD',
            'Idade_3a17Feminino',
            'Idade_3a17Feminino_PCD',
            'Idade_18a59Masculino',
            'Idade_18a59Masculino_PCD',
            'Idade_17a59Feminino',
            'Idade_17a59Feminino_PCD',
            'Idade_60Masculino',
            'Idade_60Masculino_PCD',
            'Idade_60Feminino',
            'Idade_60Feminino_PCD'
        );

        $total = 0;
        foreach ($campos as $campo) {
            $total += isset($relatorio[$campo]) ? (int)$relatorio[$campo] : 0;
        }

        return $total;
    }
}

if (!function_exists('pc_validar_duplicidade_senhas')) {
    function pc_validar_duplicidade_senhas($sql, $dataRef, $tipoSenha, array $itens): array
    {
        $dataRef = pc_normalizar_data_refeicao($dataRef);
        $tipoSenha = strtoupper(trim((string)$tipoSenha));
        $resultado = array(
            'dup_titular' => array(),
            'dup_dependentes' => array(),
            'ha_item_titular_no_payload' => false
        );

        if ($tipoSenha === 'GENERICA') {
            return $resultado;
        }

        foreach ($itens as $item) {
            $cliente = isset($item['cliente']) ? trim((string)$item['cliente']) : '';
            $idDependente = isset($item['id_dependente']) && $item['id_dependente'] !== '' ? (int)$item['id_dependente'] : 0;

            if ($idDependente > 0) {
                $row = $sql->select("
                    SELECT id
                    FROM tb_senhas
                    WHERE data_refeicao = :data_refeicao
                      AND id_dependente = :id_dependente
                    LIMIT 1
                ", array(
                    ':data_refeicao' => $dataRef,
                    ':id_dependente' => $idDependente
                ));

                if (count($row) > 0) {
                    $resultado['dup_dependentes'][] = $cliente . ' (ID ' . $idDependente . ')';
                }

                continue;
            }

            $resultado['ha_item_titular_no_payload'] = true;

            $idTitular = isset($item['id_titular']) && $item['id_titular'] !== '' ? (int)$item['id_titular'] : 0;
            $cpf = isset($item['cpf']) ? preg_replace('/\D+/', '', (string)$item['cpf']) : '';

            if ($idTitular > 0) {
                $row = $sql->select("
                    SELECT id
                    FROM tb_senhas
                    WHERE data_refeicao = :data_refeicao
                      AND id_titular = :id_titular
                      AND (id_dependente IS NULL OR id_dependente = 0)
                    LIMIT 1
                ", array(
                    ':data_refeicao' => $dataRef,
                    ':id_titular' => $idTitular
                ));

                if (count($row) > 0) {
                    $resultado['dup_titular'][] = $cliente . ' (titular #' . $idTitular . ')';
                }

                continue;
            }

            if ($cpf === '') {
                continue;
            }

            $row = $sql->select("
                SELECT id
                FROM tb_senhas
                WHERE data_refeicao = :data_refeicao
                  AND cpf = :cpf
                  AND (id_dependente IS NULL OR id_dependente = 0)
                LIMIT 1
            ", array(
                ':data_refeicao' => $dataRef,
                ':cpf' => $cpf
            ));

            if (count($row) > 0) {
                $resultado['dup_titular'][] = $cliente . ' (CPF ' . $cpf . ')';
            }
        }

        $resultado['dup_titular'] = array_values(array_unique($resultado['dup_titular']));
        $resultado['dup_dependentes'] = array_values(array_unique($resultado['dup_dependentes']));

        return $resultado;
    }
}

if (!function_exists('pc_resposta_erro_duplicidade_senhas')) {
    function pc_resposta_erro_duplicidade_senhas($app, array $duplicidade): bool
    {
        if (count($duplicidade['dup_dependentes']) > 0) {
            $app->response()->status(409);
            $app->response()->header('Content-Type', 'application/json; charset=utf-8');
            echo json_encode(array(
                'ok' => false,
                'error' => 'Um ou mais dependentes ja realizaram uma compra hoje e nao podem comprar novamente.',
                'duplicados' => $duplicidade['dup_dependentes']
            ));
            return true;
        }

        if (count($duplicidade['dup_titular']) > 0 && !empty($duplicidade['ha_item_titular_no_payload'])) {
            $app->response()->status(409);
            $app->response()->header('Content-Type', 'application/json; charset=utf-8');
            echo json_encode(array(
                'ok' => false,
                'error' => 'Titular ja realizou uma compra hoje e nao pode comprar novamente.',
                'duplicados' => $duplicidade['dup_titular']
            ));
            return true;
        }

        return false;
    }
}

if (!function_exists('pc_atualizar_fechamento_dia')) {
    function pc_atualizar_fechamento_dia($sql, $dataRef, $limite, $forcarFechado = false): array
    {
        $dataRef = pc_normalizar_data_refeicao($dataRef);
        $limite = max(0, (int)$limite);
        $total = pc_contar_senhas_vendidas_dia($sql, $dataRef);
        $fechado = ($forcarFechado || ($limite > 0 && $total >= $limite)) ? 1 : 0;
        $fechadoEm = $fechado ? date('Y-m-d H:i:s') : null;

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
                :fechado,
                :fechado_em,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                limite = VALUES(limite),
                total = VALUES(total),
                fechado = VALUES(fechado),
                fechado_em = CASE
                    WHEN VALUES(fechado) = 1 THEN COALESCE(fechado_em, NOW())
                    ELSE NULL
                END,
                atualizado_em = NOW()
        ", array(
            ':data_refeicao' => $dataRef,
            ':limite' => $limite,
            ':total' => $total,
            ':fechado' => $fechado,
            ':fechado_em' => $fechadoEm
        ));

        return array(
            'data_refeicao' => $dataRef,
            'limite' => $limite,
            'total' => $total,
            'fechado' => $fechado
        );
    }
}
