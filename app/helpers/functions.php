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

if (!function_exists('getRelatorioDbRemotoConfig')) {
    function getRelatorioDbRemotoConfig()
    {
        $configPath = dirname(__DIR__) . '/config/relatorio-db-remoto.php';

        if (!file_exists($configPath)) {
            return array('enabled' => false);
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new \RuntimeException('Configuracao do banco remoto invalida.');
        }

        return $config;
    }
}

if (!function_exists('getPdoRelatorioRemoto')) {
    function getPdoRelatorioRemoto()
    {
        static $pdo = null;

        if ($pdo instanceof \PDO) {
            return $pdo;
        }

        $config = getRelatorioDbRemotoConfig();

        if (empty($config['enabled'])) {
            return null;
        }

        foreach (array('host', 'dbname', 'user', 'pass') as $campo) {
            if (!array_key_exists($campo, $config) || trim((string)$config[$campo]) === '') {
                throw new \RuntimeException(
                    'Banco remoto ativado, mas sem configuracao completa. Preencha RELATORIO_REMOTE_DB_HOST, RELATORIO_REMOTE_DB_NAME, RELATORIO_REMOTE_DB_USER e RELATORIO_REMOTE_DB_PASSWORD no .env, ou defina RELATORIO_REMOTE_DB_ENABLED=false.'
                );
            }
        }

        $charset = !empty($config['charset']) ? $config['charset'] : 'utf8mb4';
        $port = !empty($config['port']) ? ';port=' . (int)$config['port'] : '';

        $pdo = new \PDO(
            'mysql:host=' . $config['host'] . $port . ';dbname=' . $config['dbname'] . ';charset=' . $charset,
            $config['user'],
            $config['pass'],
            array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            )
        );

        return $pdo;
    }
}

if (!function_exists('montarCaminhoRemotoBackup')) {
    function montarCaminhoRemotoBackup(string $arquivo): ?string
    {
        $nomeArquivo = basename($arquivo);

        if (function_exists('pc_env')) {
            $baseUrl = trim((string) pc_env('BACKUP_PUBLIC_BASE_URL', ''));
            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/') . '/' . rawurlencode($nomeArquivo);
            }

            $remoteDir = trim((string) pc_env('BACKUP_REMOTE_DIR', ''));
            if ($remoteDir !== '') {
                return rtrim($remoteDir, '/') . '/' . $nomeArquivo;
            }

            $sftpRemoteDir = trim((string) pc_env('BACKUP_SFTP_REMOTE_DIR', ''));
            if ($sftpRemoteDir !== '') {
                return rtrim($sftpRemoteDir, '/') . '/' . $nomeArquivo;
            }
        }

        return $nomeArquivo;
    }
}

if (!function_exists('registrarBackupRemoto')) {
    function registrarBackupRemoto(array $dados): array
    {
        $resultado = array(
            'enabled' => false,
            'success' => false,
            'id' => 0,
            'message' => null
        );

        $pdo = getPdoRelatorioRemoto();
        if (!$pdo instanceof \PDO) {
            $resultado['message'] = 'Banco remoto desativado.';
            return $resultado;
        }

        $resultado['enabled'] = true;

        $arquivo = isset($dados['arquivo']) ? (string)$dados['arquivo'] : '';
        if ($arquivo === '' || !is_file($arquivo)) {
            throw new \RuntimeException('Arquivo de backup nao encontrado para registro remoto.');
        }

        $nomeArquivo = basename($arquivo);
        $hash = hash_file('sha256', $arquivo);
        $dataBackup = date('Y-m-d H:i:s', filemtime($arquivo) ?: time());
        $statusUpload = !empty($dados['status_upload']) ? (string)$dados['status_upload'] : 'SUCESSO';
        $caminhoRemoto = array_key_exists('caminho_remoto', $dados) ? $dados['caminho_remoto'] : montarCaminhoRemotoBackup($arquivo);
        $nomeBanco = !empty($dados['nome_banco']) ? (string)$dados['nome_banco'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO tb_backups (
                nome_banco,
                nome_arquivo,
                status_upload,
                data_backup,
                caminho_remoto,
                hash_arquivo
            ) VALUES (
                :nome_banco,
                :nome_arquivo,
                :status_upload,
                :data_backup,
                :caminho_remoto,
                :hash_arquivo
            )
            ON DUPLICATE KEY UPDATE
                nome_banco = VALUES(nome_banco),
                nome_arquivo = VALUES(nome_arquivo),
                status_upload = VALUES(status_upload),
                data_backup = VALUES(data_backup),
                data_upload = CURRENT_TIMESTAMP,
                caminho_remoto = VALUES(caminho_remoto)
        ");

        $stmt->execute(array(
            ':nome_banco' => $nomeBanco,
            ':nome_arquivo' => $nomeArquivo,
            ':status_upload' => $statusUpload,
            ':data_backup' => $dataBackup,
            ':caminho_remoto' => $caminhoRemoto,
            ':hash_arquivo' => $hash
        ));

        $id = (int)$pdo->lastInsertId();
        if ($id <= 0) {
            $busca = $pdo->prepare("SELECT id FROM tb_backups WHERE hash_arquivo = :hash_arquivo LIMIT 1");
            $busca->execute(array(':hash_arquivo' => $hash));
            $row = $busca->fetch();
            $id = isset($row['id']) ? (int)$row['id'] : 0;
        }

        $resultado['success'] = true;
        $resultado['id'] = $id;
        $resultado['hash_arquivo'] = $hash;
        $resultado['message'] = 'Backup registrado no banco remoto.';

        return $resultado;
    }
}

function backupAutomatico(bool $force = false, int $cooldown = 86400, string $contexto = 'rotina'): array
{
    $resultado = [
        'success' => false,
        'skipped' => false,
        'message' => '',
        'backup_file' => null,
        'upload' => null,
        'remote_db' => null
    ];

    backupLog("INFO: backupAutomatico() chamado | contexto={$contexto} | force=" . ($force ? '1' : '0') . " | cooldown={$cooldown}");

    if (!$force && !podeRodarBackup($cooldown)) {
        backupLog("INFO: backup não executado por cooldown/lock | contexto={$contexto}");
        $resultado['success'] = true;
        $resultado['skipped'] = true;
        $resultado['message'] = 'Backup nao executado por cooldown/lock.';
        return $resultado;
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
        $resultado['backup_file'] = $file;
        backupLog("INFO: backup gerado | contexto={$contexto} | arquivo=" . basename($file));

        try {
            $uploadService->send($file, $contexto);
            $resultado['upload'] = 'UPLOAD_OK';
        } catch (\Throwable $e) {
            $resultado['success'] = true;
            $resultado['upload'] = array(
                'status' => 'UPLOAD_FAILED',
                'message' => 'Backup gerado localmente, mas nao foi possivel enviar para a nuvem.'
            );
            $resultado['message'] = 'Backup gerado localmente, mas o envio para nuvem falhou. Verifique o log tecnico.';
            backupLog("WARN: falha no upload do backup | contexto={$contexto} | arquivo=" . basename($file) . " | msg=" . $e->getMessage());
            limparBackupsAntigos(7);
            return $resultado;
        }

        try {
            $resultado['remote_db'] = registrarBackupRemoto(array(
                'nome_banco' => $database,
                'arquivo' => $file,
                'status_upload' => 'SUCESSO',
                'caminho_remoto' => montarCaminhoRemotoBackup($file)
            ));

            if (!empty($resultado['remote_db']['success'])) {
                backupLog("INFO: backup registrado no banco remoto | contexto={$contexto} | arquivo=" . basename($file) . " | id=" . (int)$resultado['remote_db']['id']);
            } else {
                backupLog("WARN: backup nao registrado no banco remoto | contexto={$contexto} | arquivo=" . basename($file) . " | msg=" . ($resultado['remote_db']['message'] ?? 'sem detalhe'));
            }
        } catch (\Throwable $e) {
            $resultado['remote_db'] = array(
                'enabled' => true,
                'success' => false,
                'message' => $e->getMessage()
            );
            backupLog("WARN: backup enviado, mas nao registrado no banco remoto | contexto={$contexto} | msg=" . $e->getMessage());
        }

        $resultado['success'] = true;
        $resultado['message'] = 'Backup gerado e enviado com sucesso.';
        backupLog("INFO: upload realizado com sucesso | contexto={$contexto} | arquivo=" . basename($file));

        limparBackupsAntigos(7);
    } catch (\Throwable $e) {
        $resultado['message'] = $e->getMessage();
        backupLog("ERRO: falha no backup | contexto={$contexto} | msg=" . $e->getMessage());
    }

    return $resultado;
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
        if (preg_match('/arquivo=([^|]+)/', $linha, $m)) {
            $file = rtrim(BACKUP_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename(trim($m[1]));
            if (is_file($file)) {
                return $file;
            }
        }

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

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate($token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['_csrf_token'])) {
            return false;
        }

        return is_string($token) && hash_equals((string)$_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('csrf_request_token')) {
    function csrf_request_token(): string
    {
        if (isset($_POST['csrf_token'])) {
            return (string)$_POST['csrf_token'];
        }

        $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : array();
        if (isset($headers['x-csrf-token'])) {
            return (string)$headers['x-csrf-token'];
        }

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return '';
    }
}

if (!function_exists('csrf_verify_request')) {
    function csrf_verify_request($app = null): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, array('GET', 'HEAD', 'OPTIONS'), true)) {
            return;
        }

        if (csrf_validate(csrf_request_token())) {
            return;
        }

        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $wantsJson = strpos($accept, 'application/json') !== false || $requestedWith === 'xmlhttprequest';

        if ($app && method_exists($app, 'response')) {
            $app->response()->status(419);
            $app->response()->header('Content-Type', $wantsJson ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8');
        } else {
            http_response_code(419);
            header('Content-Type: ' . ($wantsJson ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8'));
        }

        echo $wantsJson
            ? json_encode(array('success' => false, 'ok' => false, 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'))
            : 'Sessao expirada. Recarregue a pagina e tente novamente.';
        exit;
    }
}

if (!function_exists('pc_normalizar_data_refeicao')) {
    function pc_normalizar_data_refeicao($dataRef): string
    {
        $dataRef = trim((string)$dataRef);

        if ($dataRef === '') {
            return date('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataRef)) {
            $dt = DateTime::createFromFormat('!Y-m-d', $dataRef);
            if ($dt instanceof DateTime && $dt->format('Y-m-d') === $dataRef) {
                return $dataRef;
            }
        }

        if (strpos($dataRef, '/') !== false) {
            $dt = DateTime::createFromFormat('!d/m/Y', $dataRef);
            if ($dt instanceof DateTime && $dt->format('d/m/Y') === $dataRef) {
                return $dt->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException('Data de refeicao invalida.');
    }
}

if (!function_exists('pc_calcular_idade_atual')) {
    function pc_calcular_idade_atual($dataNascimento, $fallback = null)
    {
        $dataNascimento = trim((string)$dataNascimento);

        if ($dataNascimento === '' || $dataNascimento === '0000-00-00') {
            return $fallback !== null && $fallback !== '' ? (int)$fallback : null;
        }

        try {
            $nascimento = new DateTimeImmutable($dataNascimento);
            $hoje = new DateTimeImmutable('today');

            if ($nascimento > $hoje) {
                return $fallback !== null && $fallback !== '' ? (int)$fallback : null;
            }

            return (int)$nascimento->diff($hoje)->y;
        } catch (\Exception $e) {
            return $fallback !== null && $fallback !== '' ? (int)$fallback : null;
        }
    }
}

if (!function_exists('pc_senha_dedupe_key')) {
    function pc_senha_dedupe_key($tipoSenha, $idTitular = null, $idDependente = null, $cpf = null)
    {
        $tipoSenha = strtoupper(trim((string)$tipoSenha));

        if ($tipoSenha === 'GENERICA') {
            return null;
        }

        $idDependente = $idDependente !== null && $idDependente !== '' ? (int)$idDependente : 0;
        if ($idDependente > 0) {
            return 'DEPENDENTE:' . $idDependente;
        }

        $idTitular = $idTitular !== null && $idTitular !== '' ? (int)$idTitular : 0;
        if ($idTitular > 0) {
            return 'TITULAR:' . $idTitular;
        }

        $cpf = preg_replace('/\D+/', '', (string)$cpf);
        if ($cpf !== '') {
            return 'CPF:' . $cpf;
        }

        return null;
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

if (!function_exists('pc_system_settings_defaults')) {
    function pc_system_settings_defaults(): array
    {
        return array(
            'limite_senhas_dia' => defined('LIMITE_SENHAS_DIA') ? (int)LIMITE_SENHAS_DIA : 1000,
            'programa_nome' => defined('PROGRAMA_NOME') ? (string)PROGRAMA_NOME : 'PRATO CHEIO ALEIXO',
        );
    }
}

if (!function_exists('pc_system_settings_path')) {
    function pc_system_settings_path(): string
    {
        return defined('SYSTEM_SETTINGS_FILE')
            ? SYSTEM_SETTINGS_FILE
            : dirname(__DIR__, 2) . '/storage/config/system-settings.php';
    }
}

if (!function_exists('pc_system_settings_load')) {
    function pc_system_settings_load(): array
    {
        $settings = pc_system_settings_defaults();
        $path = pc_system_settings_path();

        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded)) {
                $settings = array_merge($settings, $loaded);
            }
        }

        $settings['limite_senhas_dia'] = max(1, (int)($settings['limite_senhas_dia'] ?? 1000));
        $settings['programa_nome'] = trim((string)($settings['programa_nome'] ?? 'PRATO CHEIO ALEIXO'));
        if ($settings['programa_nome'] === '') {
            $settings['programa_nome'] = 'PRATO CHEIO ALEIXO';
        }

        return $settings;
    }
}

if (!function_exists('pc_system_settings_save')) {
    function pc_system_settings_save(array $settings): void
    {
        $limite = (int)($settings['limite_senhas_dia'] ?? 0);
        $programa = trim((string)($settings['programa_nome'] ?? ''));

        if ($limite < 1 || $limite > 100000) {
            throw new InvalidArgumentException('O total de senhas deve ficar entre 1 e 100000.');
        }

        $programaLength = function_exists('mb_strlen')
            ? mb_strlen($programa, 'UTF-8')
            : strlen($programa);

        if ($programa === '' || $programaLength > 80) {
            throw new InvalidArgumentException('O nome do programa deve ter entre 1 e 80 caracteres.');
        }

        if (preg_match('/[<>{}]/', $programa)) {
            throw new InvalidArgumentException('O nome do programa possui caracteres invalidos.');
        }

        $path = pc_system_settings_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $content = "<?php\n\nreturn " . var_export(array(
            'limite_senhas_dia' => $limite,
            'programa_nome' => $programa,
            'updated_at' => date('Y-m-d H:i:s'),
        ), true) . ";\n";

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new RuntimeException('Nao foi possivel salvar as configuracoes do sistema.');
        }
    }
}

if (!function_exists('pc_can_manage_system_settings')) {
    function pc_can_manage_system_settings(): bool
    {
        $perfil = strtoupper((string)currentUserPerfil());
        return in_array($perfil, array('ADMIN', 'SUPERVISOR'), true);
    }
}

if (!function_exists('canManageSystemSettings')) {
    function canManageSystemSettings(): bool
    {
        return pc_can_manage_system_settings();
    }
}
