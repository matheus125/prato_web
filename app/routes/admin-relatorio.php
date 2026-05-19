<?php

use \Hcode\DB\Sql;
use \Hcode\Model\Funcionarios;
use \Hcode\PageAdmin;
use Dompdf\Dompdf;

if (!function_exists('getLimiteSenhasDiaRelatorio')) {
    function getLimiteSenhasDiaRelatorio()
    {
        return function_exists('pc_get_limite_senhas_dia') ? pc_get_limite_senhas_dia() : 400;
/*
        if (defined('LIMITE_SENHAS_DIA')) {
            return max(0, (int)LIMITE_SENHAS_DIA);
        }

        return 600;
*/
    }
}

if (!function_exists('contarSenhasVendidasRelatorio')) {
    function contarSenhasVendidasRelatorio($sql, $dataRef)
    {
        return function_exists('pc_contar_senhas_vendidas_dia')
            ? pc_contar_senhas_vendidas_dia($sql, $dataRef)
            : 0;
/*
        $res = $sql->select("
            SELECT COUNT(*) AS total
            FROM tb_senhas
            WHERE data_refeicao = :data_ref
        ", array(
            ':data_ref' => $dataRef
        ));

        return isset($res[0]['total']) ? (int)$res[0]['total'] : 0;
*/
    }
}

$app->get('/admin/api/relatorio/fechamento-info', function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new Sql();

        $data = isset($_GET['data']) && trim($_GET['data']) !== ''
            ? trim($_GET['data'])
            : date('Y-m-d');

        $result = $sql->select("
            SELECT
                data,
                qtd_refeicoes_servidas,
                ocorrencias,
                cardapio,
                nome_banco,
                refeicoes_ofertadas,
                sobra_refeicoes,
                sobra_senhas,
                fechado
            FROM tb_relatorios
            WHERE data = :data
            LIMIT 1
        ", array(
            ':data' => $data
        ));

        if (!isset($result[0])) {
            echo json_encode(array(
                'ok' => true,
                'dados' => array(
                    'data' => $data,
                    'qtd_refeicoes_servidas' => null,
                    'ocorrencias' => 'NÃO HOUVE NENHUMA OCORRÊNCIA.',
                    'cardapio' => '',
                    'nome_banco' => '',
                    'refeicoes_ofertadas' => getLimiteSenhasDiaRelatorio(),
                    'sobra_refeicoes' => null,
                    'sobra_senhas' => null,
                    'fechado' => 1
                )
            ));
            exit;
        }

        echo json_encode(array(
            'ok' => true,
            'dados' => $result[0]
        ));
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'ok' => false,
            'error' => $e->getMessage()
        ));
        exit;
    }
});

$app->post('/admin/api/relatorio/fechamento-info', function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new Sql();

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!is_array($input)) {
            throw new Exception('JSON inválido.');
        }

        $dataHoje = isset($input['data']) && trim($input['data']) !== ''
            ? trim($input['data'])
            : date('Y-m-d');

        $qtdRefeicoes = isset($input['qtd_refeicoes_servidas']) ? (int)$input['qtd_refeicoes_servidas'] : 0;
        $ocorrencias = isset($input['ocorrencias']) ? trim($input['ocorrencias']) : '';
        $cardapio = isset($input['cardapio']) ? trim($input['cardapio']) : '';

        if ($ocorrencias === '') {
            $ocorrencias = 'NÃO HOUVE NENHUMA OCORRÊNCIA.';
        }

        $dbInfo = $sql->select("SELECT DATABASE() AS nome_banco");
        $nomeBanco = isset($dbInfo[0]['nome_banco']) ? (string)$dbInfo[0]['nome_banco'] : '';

        $relatorioAtual = $sql->select("
            SELECT
                id,
                Total_pessoas_atendidas,
                fechado
            FROM tb_relatorios
            WHERE data = :data
            LIMIT 1
        ", array(
            ':data' => $dataHoje
        ));

        $totalPessoasAtendidas = 0;
        $fechado = 0;

        if (isset($relatorioAtual[0])) {
            $totalPessoasAtendidas = isset($relatorioAtual[0]['Total_pessoas_atendidas'])
                ? (int)$relatorioAtual[0]['Total_pessoas_atendidas']
                : 0;
            $fechado = isset($relatorioAtual[0]['fechado']) ? (int)$relatorioAtual[0]['fechado'] : 0;
        }

        if ($fechado === 1) {
            throw new Exception('Relatório já fechado. Não é possível alterar.');
        }

        $LIMITE_SENHAS_DIA = getLimiteSenhasDiaRelatorio();

        if ($qtdRefeicoes < 0) {
            $qtdRefeicoes = 0;
        }
        if (false && $qtdRefeicoes > $LIMITE_SENHAS_DIA) {
            $qtdRefeicoes = $LIMITE_SENHAS_DIA;
        }

        $totalPessoasAtendidas = contarSenhasVendidasRelatorio($sql, $dataHoje);
        if (false && $totalPessoasAtendidas > $LIMITE_SENHAS_DIA) {
            $totalPessoasAtendidas = $LIMITE_SENHAS_DIA;
        }

        $refeicoesOfertadas = $LIMITE_SENHAS_DIA;
        $sobraRefeicoes = max(0, $refeicoesOfertadas - $qtdRefeicoes);
        $sobraSenhas = max(0, $refeicoesOfertadas - $totalPessoasAtendidas);

        $sql->query("
            INSERT INTO tb_relatorios (
                data,
                Total_pessoas_atendidas,
                qtd_refeicoes_servidas,
                ocorrencias,
                cardapio,
                nome_banco,
                refeicoes_ofertadas,
                sobra_refeicoes,
                sobra_senhas,
                fechado
            ) VALUES (
                :data,
                :total_pessoas_atendidas,
                :qtd,
                :ocorrencias,
                :cardapio,
                :nome_banco,
                :refeicoes_ofertadas,
                :sobra_refeicoes,
                :sobra_senhas,
                1
            )
            ON DUPLICATE KEY UPDATE
                Total_pessoas_atendidas = VALUES(Total_pessoas_atendidas),
                qtd_refeicoes_servidas = VALUES(qtd_refeicoes_servidas),
                ocorrencias = VALUES(ocorrencias),
                cardapio = VALUES(cardapio),
                nome_banco = VALUES(nome_banco),
                refeicoes_ofertadas = VALUES(refeicoes_ofertadas),
                sobra_refeicoes = VALUES(sobra_refeicoes),
                sobra_senhas = VALUES(sobra_senhas),
                fechado = 1
        ", array(
            ':data' => $dataHoje,
            ':total_pessoas_atendidas' => $totalPessoasAtendidas,
            ':qtd' => $qtdRefeicoes,
            ':ocorrencias' => $ocorrencias,
            ':cardapio' => $cardapio,
            ':nome_banco' => $nomeBanco,
            ':refeicoes_ofertadas' => $refeicoesOfertadas,
            ':sobra_refeicoes' => $sobraRefeicoes,
            ':sobra_senhas' => $sobraSenhas
        ));

        echo json_encode(array(
            'ok' => true,
            'dados' => array(
                'data' => $dataHoje,
                'senhas_vendidas' => $totalPessoasAtendidas,
                'qtd_refeicoes_servidas' => $qtdRefeicoes,
                'ocorrencias' => $ocorrencias,
                'cardapio' => $cardapio,
                'nome_banco' => $nomeBanco,
                'refeicoes_ofertadas' => $refeicoesOfertadas,
                'sobra_refeicoes' => $sobraRefeicoes,
                'sobra_senhas' => $sobraSenhas,
                'fechado' => 1
            )
        ));
        exit;
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(array(
            'ok' => false,
            'error' => $e->getMessage()
        ));
        exit;
    }
});

$app->post('/admin/api/relatorio/fechar', function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new Sql();

        $data = date('Y-m-d');

        $sql->query("
            UPDATE tb_relatorios
            SET fechado = 1
            WHERE data = :data
        ", array(
            ':data' => $data
        ));

        echo json_encode(array(
            'ok' => true
        ));
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'ok' => false,
            'error' => $e->getMessage()
        ));
        exit;
    }
});

if (!function_exists('getRelatorioUploadConfig')) {
    function getRelatorioUploadConfig()
    {
        $configPath = __DIR__ . '/../config/relatorio-upload.php';

        if (!file_exists($configPath)) {
            throw new Exception('Arquivo de configuração do upload não encontrado: ' . $configPath);
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new Exception('Configuração de upload inválida.');
        }

        return $config;
    }
}

if (!function_exists('getRelatorioDbRemotoConfig')) {
    function getRelatorioDbRemotoConfig()
    {
        $configPath = __DIR__ . '/../config/relatorio-db-remoto.php';

        if (!file_exists($configPath)) {
            return array(
                'enabled' => false
            );
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new Exception('Configuração do banco remoto inválida.');
        }

        return $config;
    }
}

if (!function_exists('getPdoRelatorioRemoto')) {
    function getPdoRelatorioRemoto()
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = getRelatorioDbRemotoConfig();

        if (empty($config['enabled'])) {
            return null;
        }

        $required = array('host', 'dbname', 'user', 'pass');
        foreach ($required as $campo) {
            if (!array_key_exists($campo, $config)) {
                throw new Exception('Campo ausente na configuração do banco remoto: ' . $campo);
            }
        }

        $charset = !empty($config['charset']) ? $config['charset'] : 'utf8mb4';
        $port = !empty($config['port']) ? ';port=' . (int)$config['port'] : '';

        $pdo = new PDO(
            'mysql:host=' . $config['host'] . $port . ';dbname=' . $config['dbname'] . ';charset=' . $charset,
            $config['user'],
            $config['pass'],
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        );

        return $pdo;
    }
}

if (!function_exists('getRelatorioTempDir')) {
    function getRelatorioTempDir(array $config)
    {
        $tempDirName = !empty($config['temp_dir_name']) ? $config['temp_dir_name'] : 'tmp_relatorios';
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tempDirName;
    }
}

if (!function_exists('limparBufferSaida')) {
    function limparBufferSaida()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}

if (!function_exists('responderJson')) {
    function responderJson(array $payload)
    {
        limparBufferSaida();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('escreverLogRelatorio')) {
    function escreverLogRelatorio(array $config, $mensagem)
    {
        $logFile = !empty($config['log_file']) ? $config['log_file'] : 'relatorio_upload.log';
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logPath = $logDir . DIRECTORY_SEPARATOR . $logFile;
        $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL;

        @file_put_contents($logPath, $linha, FILE_APPEND);
    }
}

if (!function_exists('gerarNomeArquivoRelatorioPdf')) {
    function gerarNomeArquivoRelatorioPdf($dataRelatorio, $nomeBanco = '')
    {
        $dataRelatorio = trim((string)$dataRelatorio);
        if ($dataRelatorio === '') {
            $dataRelatorio = date('Y-m-d');
        }

        $prefixo = trim((string)$nomeBanco);
        if ($prefixo === '') {
            $prefixo = 'relatorio_fechamento';
        }

        $prefixo = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $prefixo);
        $prefixo = trim($prefixo, '_');

        if ($prefixo === '') {
            $prefixo = 'relatorio_fechamento';
        }

        return $prefixo . '_' . $dataRelatorio . '.pdf';
    }
}

if (!function_exists('buscarHistoricoRelatorioExistente')) {
    function buscarHistoricoRelatorioExistente(Sql $sql, array $dados)
    {
        $where = array();
        $params = array();

        if (!empty($dados['data_relatorio'])) {
            $where[] = 'DATE(data_relatorio) = :data_relatorio';
            $params[':data_relatorio'] = $dados['data_relatorio'];
        }

        if (!empty($dados['nome_arquivo'])) {
            $where[] = 'nome_arquivo = :nome_arquivo';
            $params[':nome_arquivo'] = $dados['nome_arquivo'];
        }

        if (!$where) {
            return null;
        }

        $res = $sql->select("
            SELECT id
            FROM tb_relatorios_pdf
            WHERE " . implode(' AND ', $where) . "
            ORDER BY id DESC
            LIMIT 1
        ", $params);

        return isset($res[0]['id']) ? (int)$res[0]['id'] : null;
    }
}

if (!function_exists('buscarHistoricoRelatorioExistenteRemoto')) {
    function buscarHistoricoRelatorioExistenteRemoto(PDO $pdo, array $dados)
    {
        $where = array();
        $params = array();

        if (!empty($dados['data_relatorio'])) {
            $where[] = 'DATE(data_relatorio) = :data_relatorio';
            $params[':data_relatorio'] = $dados['data_relatorio'];
        }

        if (!empty($dados['nome_arquivo'])) {
            $where[] = 'nome_arquivo = :nome_arquivo';
            $params[':nome_arquivo'] = $dados['nome_arquivo'];
        }

        if (!$where) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT id
            FROM tb_relatorios_pdf
            WHERE " . implode(' AND ', $where) . "
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();

        return isset($row['id']) ? (int)$row['id'] : null;
    }
}

if (!function_exists('registrarHistoricoRelatorio')) {
    function registrarHistoricoRelatorio(Sql $sql, array $dados)
    {
        $idExistente = buscarHistoricoRelatorioExistente($sql, $dados);

        $params = array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':url_publica' => $dados['url_publica'],
            ':caminho_remoto' => $dados['caminho_remoto'],
            ':status_upload' => $dados['status_upload'],
            ':mensagem_erro' => $dados['mensagem_erro'],
            ':responsavel' => $dados['responsavel'],
            ':cpf_responsavel' => $dados['cpf_responsavel']
        );

        if ($idExistente) {
            $sql->query("
                UPDATE tb_relatorios_pdf
                SET
                    url_publica = :url_publica,
                    caminho_remoto = :caminho_remoto,
                    status_upload = :status_upload,
                    mensagem_erro = :mensagem_erro,
                    responsavel = :responsavel,
                    cpf_responsavel = :cpf_responsavel,
                    data_geracao = NOW(),
                    data_upload = " . ($dados['status_upload'] === 'SUCESSO' ? 'NOW()' : 'NULL') . "
                WHERE id = :id
            ", $params + array(':id' => $idExistente));

            return (int)$idExistente;
        }

        $sql->query("
            INSERT INTO tb_relatorios_pdf (
                data_relatorio,
                nome_arquivo,
                url_publica,
                caminho_remoto,
                status_upload,
                mensagem_erro,
                responsavel,
                cpf_responsavel,
                data_geracao,
                data_upload
            ) VALUES (
                :data_relatorio,
                :nome_arquivo,
                :url_publica,
                :caminho_remoto,
                :status_upload,
                :mensagem_erro,
                :responsavel,
                :cpf_responsavel,
                NOW(),
                " . ($dados['status_upload'] === 'SUCESSO' ? 'NOW()' : 'NULL') . "
            )
        ", $params);

        $ret = $sql->select('SELECT LAST_INSERT_ID() AS id');
        return isset($ret[0]['id']) ? (int)$ret[0]['id'] : 0;
    }
}

if (!function_exists('registrarHistoricoRelatorioRemoto')) {
    function registrarHistoricoRelatorioRemoto(PDO $pdo, array $dados)
    {
        $idExistente = buscarHistoricoRelatorioExistenteRemoto($pdo, $dados);

        $params = array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':url_publica' => $dados['url_publica'],
            ':caminho_remoto' => $dados['caminho_remoto'],
            ':status_upload' => $dados['status_upload'],
            ':mensagem_erro' => $dados['mensagem_erro'],
            ':responsavel' => $dados['responsavel'],
            ':cpf_responsavel' => $dados['cpf_responsavel']
        );

        if ($idExistente) {
            $sql = "
                UPDATE tb_relatorios_pdf
                SET
                    url_publica = :url_publica,
                    caminho_remoto = :caminho_remoto,
                    status_upload = :status_upload,
                    mensagem_erro = :mensagem_erro,
                    responsavel = :responsavel,
                    cpf_responsavel = :cpf_responsavel,
                    data_geracao = NOW(),
                    data_upload = " . ($dados['status_upload'] === 'SUCESSO' ? 'NOW()' : 'NULL') . "
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params + array(':id' => $idExistente));

            return (int)$idExistente;
        }

        $sql = "
            INSERT INTO tb_relatorios_pdf (
                data_relatorio,
                nome_arquivo,
                url_publica,
                caminho_remoto,
                status_upload,
                mensagem_erro,
                responsavel,
                cpf_responsavel,
                data_geracao,
                data_upload
            ) VALUES (
                :data_relatorio,
                :nome_arquivo,
                :url_publica,
                :caminho_remoto,
                :status_upload,
                :mensagem_erro,
                :responsavel,
                :cpf_responsavel,
                NOW(),
                " . ($dados['status_upload'] === 'SUCESSO' ? 'NOW()' : 'NULL') . "
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('registrarHistoricoRelatorioEmAmbosBancos')) {
    function registrarHistoricoRelatorioEmAmbosBancos(Sql $sqlLocal, array $dados, array $configUpload = array())
    {
        $resultado = array(
            'local_id' => 0,
            'remoto_id' => 0,
            'remoto_ok' => false,
            'erro_remoto' => null
        );

        $resultado['local_id'] = registrarHistoricoRelatorio($sqlLocal, $dados);

        try {
            $pdoRemoto = getPdoRelatorioRemoto();

            if ($pdoRemoto instanceof PDO) {
                $resultado['remoto_id'] = registrarHistoricoRelatorioRemoto($pdoRemoto, $dados);
                $resultado['remoto_ok'] = true;
            }
        } catch (\Exception $e) {
            $resultado['erro_remoto'] = $e->getMessage();

            if (!empty($configUpload)) {
                escreverLogRelatorio($configUpload, 'ERRO ao salvar histórico no banco remoto: ' . $e->getMessage());
            } else {
                error_log('ERRO ao salvar histórico no banco remoto: ' . $e->getMessage());
            }
        }

        return $resultado;
    }
}

if (!function_exists('garantirHistoricoRelatorioRemoto')) {
    function garantirHistoricoRelatorioRemoto(array $resultado, array $dados, array $configUpload = array())
    {
        if (!isset($resultado['remoto_ok'])) {
            $resultado['remoto_ok'] = false;
        }

        if (!isset($resultado['remoto_id'])) {
            $resultado['remoto_id'] = 0;
        }

        if (!isset($resultado['erro_remoto'])) {
            $resultado['erro_remoto'] = null;
        }

        if (!empty($resultado['remoto_ok'])) {
            return $resultado;
        }

        try {
            $pdoRemoto = getPdoRelatorioRemoto();

            if ($pdoRemoto instanceof PDO) {
                $resultado['remoto_id'] = registrarHistoricoRelatorioRemoto($pdoRemoto, $dados);
                $resultado['remoto_ok'] = true;
                $resultado['erro_remoto'] = null;

                if (!empty($configUpload)) {
                    escreverLogRelatorio($configUpload, 'Histórico garantido no banco remoto para o arquivo: ' . $dados['nome_arquivo']);
                }
            }
        } catch (\Exception $e) {
            $resultado['erro_remoto'] = $e->getMessage();

            if (!empty($configUpload)) {
                escreverLogRelatorio($configUpload, 'Falha ao garantir histórico no banco remoto: ' . $e->getMessage());
            } else {
                error_log('Falha ao garantir histórico no banco remoto: ' . $e->getMessage());
            }
        }

        return $resultado;
    }
}

if (!function_exists('normalizarHostFtpRelatorio')) {
    function normalizarHostFtpRelatorio(array $config)
    {
        $host = trim((string) ($config['host'] ?? ''));
        $publicBaseUrl = trim((string) ($config['public_base_url'] ?? ''));

        if ($host !== '' && preg_match('#^[a-z][a-z0-9+.-]*://#i', $host)) {
            $parsedHost = parse_url($host);
            if (!empty($parsedHost['host'])) {
                $host = $parsedHost['host'];
                if (!empty($parsedHost['port'])) {
                    $host .= ':' . $parsedHost['port'];
                }
            }
        }

        $host = trim($host, " \t\n\r\0\x0B/");

        if ($host === '' || strpos($host, '/') !== false || strcasecmp($host, 'relatorios') === 0) {
            if ($publicBaseUrl !== '') {
                $parsedPublic = parse_url($publicBaseUrl);
                if (!empty($parsedPublic['host'])) {
                    $host = $parsedPublic['host'];
                    if (!empty($parsedPublic['port'])) {
                        $host .= ':' . $parsedPublic['port'];
                    }
                }
            }
        }

        if ($host === '' || strpos($host, '/') !== false || strcasecmp($host, 'relatorios') === 0) {
            throw new Exception('Configuração FTP inválida: RELATORIO_FTP_HOST não está configurado corretamente. Valor atual: ' . ($config['host'] ?? '<vazio>'));
        }

        return $host;
    }
}

if (!function_exists('obterDiretorioPublicoRelatorioLocal')) {
    function obterDiretorioPublicoRelatorioLocal(array $config)
    {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? trim((string)$_SERVER['DOCUMENT_ROOT']) : '';

        if ($documentRoot !== '') {
            $baseDir = realpath($documentRoot);
            if ($baseDir === false) {
                $baseDir = $documentRoot;
            }
        } else {
            $baseDir = dirname(__DIR__, 2);
        }

        $relativeDir = isset($config['local_public_dir'])
            ? trim((string)$config['local_public_dir'])
            : 'relatorios';

        $relativeDir = trim(str_replace('\\', '/', $relativeDir), '/');
        if ($relativeDir === '') {
            $relativeDir = 'relatorios';
        }

        return rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    }
}

if (!function_exists('montarUrlPublicaRelatorioLocal')) {
    function montarUrlPublicaRelatorioLocal(array $config, $nomeArquivo)
    {
        $relativeDir = isset($config['local_public_dir'])
            ? trim((string)$config['local_public_dir'])
            : 'relatorios';

        $relativeDir = trim(str_replace('\\', '/', $relativeDir), '/');
        if ($relativeDir === '') {
            $relativeDir = 'relatorios';
        }

        $path = '/' . $relativeDir . '/' . rawurlencode($nomeArquivo);
        $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';

        if ($host === '') {
            return $path;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        return ($https ? 'https://' : 'http://') . $host . $path;
    }
}

if (!function_exists('salvarRelatorioLocalmente')) {
    function salvarRelatorioLocalmente(array $config, $caminhoLocal, $nomeArquivo)
    {
        $nomeSeguro = basename(str_replace('\\', '/', (string)$nomeArquivo));
        if ($nomeSeguro === '' || $nomeSeguro === '.' || $nomeSeguro === '..') {
            throw new Exception('Nome de arquivo do relatorio invalido.');
        }

        $dirDestino = obterDiretorioPublicoRelatorioLocal($config);
        if (!is_dir($dirDestino) && !@mkdir($dirDestino, 0775, true) && !is_dir($dirDestino)) {
            throw new Exception('Nao foi possivel criar o diretorio publico de relatorios.');
        }

        $arquivoDestino = rtrim($dirDestino, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeSeguro;
        if (!@copy($caminhoLocal, $arquivoDestino)) {
            throw new Exception('Nao foi possivel salvar o PDF no diretorio publico de relatorios.');
        }

        $relativeDir = isset($config['local_public_dir'])
            ? trim((string)$config['local_public_dir'])
            : 'relatorios';

        $relativeDir = trim(str_replace('\\', '/', $relativeDir), '/');
        if ($relativeDir === '') {
            $relativeDir = 'relatorios';
        }

        return array(
            'success' => true,
            'driver' => 'local',
            'remote_file' => '/' . $relativeDir . '/' . $nomeSeguro,
            'url_publica' => montarUrlPublicaRelatorioLocal($config, $nomeSeguro)
        );
    }
}

if (!function_exists('uploadRelatorioRemoto')) {
    function uploadRelatorioRemoto(array $config, $caminhoLocal, $nomeArquivo)
    {
        $driver = strtolower(trim((string) ($config['driver'] ?? 'ftp')));
        $ftpUser = trim((string) ($config['user'] ?? ''));
        $ftpPass = (string) ($config['pass'] ?? '');

        if ($driver === 'local' || $driver === 'file' || $driver === 'filesystem' || $ftpUser === '' || $ftpPass === '') {
            return salvarRelatorioLocalmente($config, $caminhoLocal, $nomeArquivo);
        }

        try {
            $ftpHost = normalizarHostFtpRelatorio($config);
        } catch (\Exception $e) {
            return salvarRelatorioLocalmente($config, $caminhoLocal, $nomeArquivo);
        }

        $remoteDir = '/' . ltrim((string) ($config['remote_dir'] ?? '/relatorios'), '/');
        $publicBaseUrl = rtrim((string) ($config['public_base_url'] ?? ''), '/') . '/';

        $remoteFile = $remoteDir . '/' . ltrim($nomeArquivo, '/');

        $fp = fopen($caminhoLocal, 'r');
        if (!$fp) {
            throw new Exception('Erro ao abrir arquivo local: ' . $caminhoLocal);
        }

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => 'ftp://' . $ftpHost . $remoteFile,
            CURLOPT_USERPWD => $ftpUser . ':' . $ftpPass,
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fp,
            CURLOPT_INFILESIZE => filesize($caminhoLocal),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_FTP_USE_EPSV => true,
            CURLOPT_FTP_CREATE_MISSING_DIRS => true,
            CURLOPT_VERBOSE => false
        ));

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);
        fclose($fp);

        if ($errno) {
            throw new Exception('Erro FTP (' . $errno . '): ' . $error);
        }

        if ($result === false) {
            throw new Exception('Falha no upload FTP sem retorno válido.');
        }

        return array(
            'success' => true,
            'driver' => 'ftp',
            'remote_file' => $remoteFile,
            'url_publica' => $publicBaseUrl . rawurlencode($nomeArquivo)
        );
    }
}

if (!function_exists('obterResponsavelRelatorioPdf')) {
    function obterResponsavelRelatorioPdf()
    {
        $nome = 'Não informado';
        $cpf = '';

        try {
            if (class_exists('Hcode\\Model\\Funcionarios')) {
                $func = Funcionarios::getFromSession();
                if (is_object($func) && method_exists($func, 'getValues')) {
                    $dados = $func->getValues();
                    if (is_array($dados)) {
                        if (!empty($dados['nome_funcionario'])) {
                            $nome = (string)$dados['nome_funcionario'];
                        } elseif (!empty($dados['desfuncionario'])) {
                            $nome = (string)$dados['desfuncionario'];
                        } elseif (!empty($dados['nome'])) {
                            $nome = (string)$dados['nome'];
                        }

                        if (!empty($dados['cpf'])) {
                            $cpf = preg_replace('/\D+/', '', (string)$dados['cpf']);
                        } elseif (!empty($dados['nrcpf'])) {
                            $cpf = preg_replace('/\D+/', '', (string)$dados['nrcpf']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // segue com fallback
        }

        return array(
            'responsavel' => $nome,
            'cpf_responsavel' => $cpf
        );
    }
}

if (!function_exists('obterResumoRelatorioPdf')) {
    function obterResumoRelatorioPdf(Sql $sql, $data)
    {
        if (function_exists('pc_obter_resumo_senhas_dia')) {
            return pc_obter_resumo_senhas_dia($sql, $data);
        }

        $res = $sql->select("\n            SELECT\n                COUNT(*) AS total,\n                SUM(CASE WHEN tipoSenha = 'NORMAL' THEN 1 ELSE 0 END) AS total_normal,\n                SUM(CASE WHEN tipoSenha = 'GENERICA' THEN 1 ELSE 0 END) AS total_generica,\n                COUNT(DISTINCT CASE WHEN tipoSenha = 'NORMAL' AND id_titular IS NOT NULL THEN id_titular ELSE NULL END) AS total_titular,\n                SUM(CASE WHEN id_dependente IS NOT NULL THEN 1 ELSE 0 END) AS total_dependente,\n                SUM(CASE WHEN UPPER(TRIM(Deficiente)) IN ('SIM','S','1','TRUE','YES','PCD','DEFICIENTE') THEN 1 ELSE 0 END) AS total_deficiente,\n                SUM(CASE WHEN UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(status_cliente,''),'Á','A'),'À','A'),'Â','A'),'Ã','A'),'É','E'),'Ç','C'))) = 'PESSOA EM SITUACAO DE RUA' AND UPPER(TRIM(Genero)) = 'M' THEN 1 ELSE 0 END) AS total_rua_masculino,\n                SUM(CASE WHEN UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(status_cliente,''),'Á','A'),'À','A'),'Â','A'),'Ã','A'),'É','E'),'Ç','C'))) = 'PESSOA EM SITUACAO DE RUA' AND UPPER(TRIM(Genero)) = 'F' THEN 1 ELSE 0 END) AS total_rua_feminino\n            FROM tb_senhas\n            WHERE data_refeicao = :data\n        ", array(':data' => $data));

        return isset($res[0]) ? $res[0] : array();
    }
}

if (!function_exists('obterDadosFechamentoRelatorioPdf')) {
    function obterDadosFechamentoRelatorioPdf(Sql $sql, $data)
    {
        if (function_exists('gerarRelatorioDia')) {
            try {
                gerarRelatorioDia($sql, $data);
            } catch (\Exception $e) {
                if (function_exists('relatorioLog')) {
                    relatorioLog('Falha ao atualizar resumo antes do PDF simples | data=' . $data . ' | msg=' . $e->getMessage());
                }
            }
        }

        $res = $sql->select("\n            SELECT *\n            FROM tb_relatorios\n            WHERE data = :data\n            LIMIT 1\n        ", array(':data' => $data));

        return isset($res[0]) ? $res[0] : array();
    }
}

if (!function_exists('renderizarHtmlRelatorioPdf')) {
    function renderizarHtmlRelatorioPdf($data, array $fechamento, array $resumo)
    {
        $e = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $n = function ($v) {
            return number_format((int)$v, 0, ',', '.');
        };

        $nomeBanco = !empty($fechamento['nome_banco']) ? $fechamento['nome_banco'] : 'prato_cheio';
        $ocorrencias = trim((string)($fechamento['ocorrencias'] ?? ''));
        if ($ocorrencias === '') {
            $ocorrencias = 'NÃO HOUVE NENHUMA OCORRÊNCIA.';
        }
        $cardapio = trim((string)($fechamento['cardapio'] ?? ''));
        if ($cardapio === '') {
            $cardapio = 'Não informado.';
        }

        $ruaMasc = (int)($resumo['total_rua_masculino'] ?? 0);
        $ruaFem = (int)($resumo['total_rua_feminino'] ?? 0);
        $ruaTotal = $ruaMasc + $ruaFem;

        return '<html><head><meta charset="utf-8"><style>
            body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:12px;}
            .topo{border-bottom:2px solid #0f766e;padding-bottom:10px;margin-bottom:18px;}
            .titulo{font-size:20px;font-weight:700;color:#0f172a;}
            .sub{font-size:11px;color:#475569;margin-top:4px;}
            .grid{width:100%;border-collapse:collapse;margin-bottom:14px;}
            .grid td,.grid th{border:1px solid #cbd5e1;padding:8px;vertical-align:top;}
            .grid th{background:#f1f5f9;text-align:left;font-size:11px;}
            .kpi{width:100%;border-collapse:separate;border-spacing:8px 8px;margin-bottom:10px;}
            .kpi td{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;}
            .kpi .t{font-size:10px;color:#64748b;text-transform:uppercase;}
            .kpi .v{font-size:18px;font-weight:700;color:#0f172a;margin-top:4px;}
            .sec{font-size:14px;font-weight:700;color:#0f172a;margin:14px 0 8px;}
            .alerta{background:#fef9c3;border:1px solid #facc15;padding:10px;border-radius:8px;font-size:11px;margin:10px 0;}
            .rodape{margin-top:18px;font-size:10px;color:#64748b;border-top:1px solid #cbd5e1;padding-top:8px;}
        </style></head><body>
            <div class="topo">
                <div class="titulo">RELATÓRIO DIÁRIO - SENHAS</div>
                <div class="sub">Data de referência: ' . $e(date('d/m/Y', strtotime($data))) . ' | Banco: ' . $e($nomeBanco) . '</div>
            </div>

            <table class="kpi">
                <tr>
                    <td><div class="t">Total do dia</div><div class="v">' . $n($resumo['total'] ?? 0) . '</div></td>
                    <td><div class="t">Normais</div><div class="v">' . $n($resumo['total_normal'] ?? 0) . '</div></td>
                    <td><div class="t">Genéricas</div><div class="v">' . $n($resumo['total_generica'] ?? 0) . '</div></td>
                </tr>
                <tr>
                    <td><div class="t">Titulares</div><div class="v">' . $n($resumo['total_titular'] ?? 0) . '</div></td>
                    <td><div class="t">Dependentes</div><div class="v">' . $n($resumo['total_dependente'] ?? 0) . '</div></td>
                    <td><div class="t">PCD</div><div class="v">' . $n($resumo['total_deficiente'] ?? 0) . '</div></td>
                </tr>
            </table>

            <div class="sec">Fechamento do dia</div>
            <table class="grid">
                <tr>
                    <th>Refeições ofertadas</th>
                    <th>Refeições servidas</th>
                    <th>Sobra de refeições</th>
                    <th>Sobra de senhas</th>
                </tr>
                <tr>
                    <td>' . $n($fechamento['refeicoes_ofertadas'] ?? 0) . '</td>
                    <td>' . $n($fechamento['qtd_refeicoes_servidas'] ?? 0) . '</td>
                    <td>' . $n($fechamento['sobra_refeicoes'] ?? 0) . '</td>
                    <td>' . $n($fechamento['sobra_senhas'] ?? 0) . '</td>
                </tr>
            </table>

            <div class="sec">Faixas e grupos</div>
            <table class="grid">
                <tr><th>Categoria</th><th>Quantidade</th></tr>
                <tr><td>3 a 17 Masculino</td><td>' . $n($fechamento['Idade_3a17Masculino'] ?? 0) . '</td></tr>
                <tr><td>3 a 17 Feminino</td><td>' . $n($fechamento['Idade_3a17Feminino'] ?? 0) . '</td></tr>
                <tr><td>18 a 59 Masculino</td><td>' . $n($fechamento['Idade_18a59Masculino'] ?? 0) . '</td></tr>
                <tr><td>18 a 59 Feminino</td><td>' . $n($fechamento['Idade_17a59Feminino'] ?? 0) . '</td></tr>
                <tr><td>60+ Masculino</td><td>' . $n($fechamento['Idade_60Masculino'] ?? 0) . '</td></tr>
                <tr><td>60+ Feminino</td><td>' . $n($fechamento['Idade_60Feminino'] ?? 0) . '</td></tr>
                <tr><td>Situação de rua - Masculino</td><td>' . $n($ruaMasc) . '</td></tr>
                <tr><td>Situação de rua - Feminino</td><td>' . $n($ruaFem) . '</td></tr>
                <tr><td>PCD (total)</td><td>' . $n($resumo['total_deficiente'] ?? 0) . '</td></tr>
            </table>

            <div class="alerta">
                Pessoas em situação de rua (' . $n($ruaTotal) . ') e PCD (' . $n($resumo['total_deficiente'] ?? 0) . ') já estão contabilizadas nas faixas etárias do relatório.
            </div>

            <div class="sec">Cardápio</div>
            <table class="grid"><tr><td>' . nl2br($e($cardapio)) . '</td></tr></table>

            <div class="sec">Ocorrências</div>
            <table class="grid"><tr><td>' . nl2br($e($ocorrencias)) . '</td></tr></table>

            <div class="rodape">Documento gerado automaticamente em ' . $e(date('d/m/Y H:i:s')) . '.</div>
        </body></html>';
    }
}

$app->get('/admin/api/relatorio/pdf', function () {
    try {
        $sql = new Sql();

        $data = isset($_GET['data']) && trim($_GET['data']) !== '' ? trim($_GET['data']) : date('Y-m-d');
        $upload = isset($_GET['upload']) && (string)$_GET['upload'] === '1';

        $fechamento = obterDadosFechamentoRelatorioPdf($sql, $data);
        $resumo = obterResumoRelatorioPdf($sql, $data);

        $nomeBanco = isset($fechamento['nome_banco']) ? (string)$fechamento['nome_banco'] : '';
        if ($nomeBanco === '') {
            $dbInfo = $sql->select("SELECT DATABASE() AS nome_banco");
            $nomeBanco = isset($dbInfo[0]['nome_banco']) ? (string)$dbInfo[0]['nome_banco'] : 'relatorio_fechamento';
        }

        $nomeArquivo = gerarNomeArquivoRelatorioPdf($data, $nomeBanco);
        $html = renderizarHtmlRelatorioPdf($data, $fechamento, $resumo);

        $dompdf = new Dompdf(array(
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans'
        ));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if (!$upload) {
            limparBufferSaida();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
            echo $dompdf->output();
            exit;
        }

        $configUpload = getRelatorioUploadConfig();
        $tempDir = getRelatorioTempDir($configUpload);
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new Exception('Não foi possível criar o diretório temporário do relatório.');
        }

        $arquivoLocal = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeArquivo;
        file_put_contents($arquivoLocal, $dompdf->output());

        escreverLogRelatorio($configUpload, 'Gerando upload do relatório PDF: ' . $nomeArquivo);
        $uploadInfo = uploadRelatorioRemoto($configUpload, $arquivoLocal, $nomeArquivo);

        $usuario = obterResponsavelRelatorioPdf();
        $dadosHistorico = array(
            'data_relatorio' => $data,
            'nome_arquivo' => $nomeArquivo,
            'url_publica' => isset($uploadInfo['url_publica']) ? $uploadInfo['url_publica'] : '',
            'caminho_remoto' => isset($uploadInfo['remote_file']) ? $uploadInfo['remote_file'] : '',
            'status_upload' => 'SUCESSO',
            'mensagem_erro' => null,
            'responsavel' => $usuario['responsavel'],
            'cpf_responsavel' => $usuario['cpf_responsavel']
        );

        $historico = registrarHistoricoRelatorioEmAmbosBancos($sql, $dadosHistorico, $configUpload);

        responderJson(array(
            'success' => true,
            'message' => 'PDF gerado e enviado com sucesso.',
            'nome_arquivo' => $nomeArquivo,
            'url_publica' => $dadosHistorico['url_publica'],
            'caminho_remoto' => $dadosHistorico['caminho_remoto'],
            'historico' => $historico
        ));
    } catch (\Exception $e) {
        $mensagem = $e->getMessage();

        try {
            $sqlErro = new Sql();
            $dataErro = isset($_GET['data']) && trim($_GET['data']) !== '' ? trim($_GET['data']) : date('Y-m-d');
            $usuario = obterResponsavelRelatorioPdf();
            $nomeArquivoErro = gerarNomeArquivoRelatorioPdf($dataErro, 'relatorio_fechamento');
            $configUploadErro = getRelatorioUploadConfig();
            escreverLogRelatorio($configUploadErro, 'Falha ao gerar/upload PDF: ' . $mensagem);
            registrarHistoricoRelatorioEmAmbosBancos($sqlErro, array(
                'data_relatorio' => $dataErro,
                'nome_arquivo' => $nomeArquivoErro,
                'url_publica' => '',
                'caminho_remoto' => '',
                'status_upload' => 'ERRO',
                'mensagem_erro' => $mensagem,
                'responsavel' => $usuario['responsavel'],
                'cpf_responsavel' => $usuario['cpf_responsavel']
            ), isset($configUploadErro) ? $configUploadErro : array());
        } catch (\Exception $e2) {
            // evita mascarar o erro principal
        }

        responderJson(array(
            'success' => false,
            'message' => $mensagem
        ));
    }
});

$app->get('/admin/relatorio/pdf/historico', function () {
    $page = new PageAdmin();
    $page->setTpl("relatorio-pdf-historico");
});

$app->get('/admin/api/relatorio/pdf/historico', function () {

    $sql = new Sql();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 20;
    $data = isset($_GET['data']) ? trim($_GET['data']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    if ($page < 1) $page = 1;
    if ($pageSize < 1) $pageSize = 20;
    if ($pageSize > 100) $pageSize = 100;

    $offset = ($page - 1) * $pageSize;

    $where = array();
    $params = array();

    if ($data !== '') {
        if (strpos($data, '/') !== false) {
            $dt = DateTime::createFromFormat('d/m/Y', $data);
            if ($dt) {
                $data = $dt->format('Y-m-d');
            }
        }
        $where[] = "DATE(data_relatorio) = :data";
        $params[':data'] = $data;
    }

    if ($status !== '') {
        $where[] = "status_upload = :status";
        $params[':status'] = strtoupper($status);
    }

    $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $totalResult = $sql->select("
        SELECT COUNT(*) AS total
        FROM tb_relatorios_pdf
        {$whereSql}
    ", $params);

    $total = isset($totalResult[0]['total']) ? (int)$totalResult[0]['total'] : 0;

    $items = $sql->select("
        SELECT
            id,
            data_relatorio,
            nome_arquivo,
            url_publica,
            caminho_remoto,
            status_upload,
            mensagem_erro,
            responsavel,
            cpf_responsavel,
            data_geracao,
            data_upload
        FROM tb_relatorios_pdf
        {$whereSql}
        ORDER BY id DESC
        LIMIT {$offset}, {$pageSize}
    ", $params);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => true,
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'pages' => $pageSize > 0 ? ceil($total / $pageSize) : 1,
        'items' => $items
    ));
    exit;
});
