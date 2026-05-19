<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Clientes;
use \Hcode\Model\Funcionarios;
use \Hcode\DB\Sql;
use Hcode\Model\Notification;
use Hcode\Model\Permissions;
use Dompdf\Dompdf;

require_once __DIR__ . '/../functions/relatorio_hostgator.php';
require_once ROOT_DIR . '/updater/update.php';
/*
|--------------------------------------------------------------------------
| ROOT -> manda pro login
|--------------------------------------------------------------------------
*/

$app->get('/', function () {
    header("Location: /admin/login");
    exit;
});

/*
|--------------------------------------------------------------------------
| DASHBOARD ADMIN (/admin)
|--------------------------------------------------------------------------
*/
$app->get('/admin', function () {

    Funcionarios::checkPermission('DASHBOARD_VIEW');

    $notificacoesSessao = Notification::getAll();
    $notificacoesBackup = function_exists('getBackupNotifications') ? getBackupNotifications(10) : [];

    $notificacoes = array_merge($notificacoesSessao, $notificacoesBackup);
    $total = count($notificacoes);

    $ultimoBackup = function_exists('getUltimoBackup') ? getUltimoBackup() : null;

    $page = new PageAdmin([
        "data" => [
            "notificacoes" => $notificacoes,
            "total" => $total,
            "ultimoBackup" => $ultimoBackup
        ]
    ]);

    $page->setTpl("index");
});

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/
$app->get("/api/total-titulares", function () {
    $total = Clientes::total_usuarios();
    echo json_encode(["total" => $total]);
});

$app->get('/api/usuarios-titulares', function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new Sql();

        $dados = $sql->select("
            SELECT 
                id,
                nome_completo,
                telefone
            FROM tb_titular
            ORDER BY nome_completo
        ");

        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'erro' => true,
            'mensagem' => $e->getMessage()
        ]);
    }
});

$app->get("/api/total-dependentes", function () {
    $total = Clientes::total_dependentes();
    echo json_encode(["total" => $total]);
});

$app->get("/api/total-familias", function () {
    $total = Clientes::total_familias();
    echo json_encode(["total" => $total]);
});

$app->get("/api/grafico-titulares", function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new Sql();

        $rows = $sql->select("
            SELECT data_nascimento, genero FROM tb_titular
            UNION ALL
            SELECT data_nascimento, genero FROM tb_dependentes
        ");

        $faixas = [
            '3-17' => 0,
            '18-59' => 0,
            '60+' => 0
        ];

        foreach ($rows as $row) {

            if (empty($row['data_nascimento'])) continue;

            $idade = (new DateTime($row['data_nascimento']))->diff(new DateTime())->y;

            if ($idade >= 3 && $idade <= 17) {
                $faixas['3-17']++;
            } elseif ($idade >= 18 && $idade <= 59) {
                $faixas['18-59']++;
            } else {
                $faixas['60+']++;
            }
        }

        echo json_encode($faixas, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'erro' => true,
            'mensagem' => $e->getMessage()
        ]);
    }
});

/*
|--------------------------------------------------------------------------
| LOGIN (GET)
|--------------------------------------------------------------------------
*/
$app->get('/admin/login', function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("login", [
        'error' => Funcionarios::getError(),
        'attempts' => Funcionarios::getLoginAttempts()
    ]);
});

/*
|--------------------------------------------------------------------------
| TESTE ESTRUTURA TABELA (APENAS ADMIN)
|--------------------------------------------------------------------------
*/
$app->get('/admin/test-tb-usuario', function () {

    Funcionarios::checkPermission('SISTEMA_DEBUG');

    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = new \Hcode\DB\Sql();
        $results = $sql->select("DESCRIBE tb_usuario");

        echo json_encode([
            "success"   => true,
            "structure" => $results
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "error"   => $e->getMessage()
        ]);
    }

    exit;
});

/*
|--------------------------------------------------------------------------
| LOGIN (POST)
|--------------------------------------------------------------------------
*/
$app->post('/admin/login', function () {

    try {

        if (!isset($_POST["cpf"]) || trim($_POST["cpf"]) === '') {
            throw new Exception("Informe o CPF.");
        }

        if (!isset($_POST["senha"]) || trim($_POST["senha"]) === '') {
            throw new Exception("Informe a senha.");
        }

        if (Funcionarios::getLoginAttempts() >= 5) {
            throw new Exception("Muitas tentativas inválidas. Aguarde um momento antes de tentar novamente.");
        }

        $cpf = preg_replace('/\D+/', '', $_POST["cpf"]);
        $funcionarios = Funcionarios::login($cpf, $_POST["senha"]);
        $_SESSION[Funcionarios::SESSION] = $funcionarios->getValues();

        Funcionarios::clearError();
        Funcionarios::clearLoginAttempts();
        Funcionarios::registerAccess($funcionarios, 'LOGIN');

        if (function_exists('backupAutomatico')) {
            backupAutomatico(false, 86400, 'login');
        }

        header("Location: /admin");
        exit;
    } catch (Exception $e) {

        Funcionarios::addLoginAttempt();
        $tentativas = Funcionarios::getLoginAttempts();
        $msg = $e->getMessage();

        if (
            $tentativas < 5 &&
            $msg !== "Muitas tentativas inválidas. Aguarde um momento antes de tentar novamente."
        ) {
            $msg .= " Tentativa " . $tentativas . " de 5.";
        }

        Funcionarios::setError($msg);
        header("Location: /admin/login");
        exit;
    }
});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/
$app->get('/admin/logout', function () {

    $funcionarios = Funcionarios::getFromSession();

    if ($funcionarios && $funcionarios->getid_usuario() > 0) {
        Funcionarios::registerAccess($funcionarios, 'LOGOUT');
    }

    Funcionarios::logout();

    header("Location: /admin/login");
    exit;
});

/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

$app->get('/admin/forgot', function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot", [
        'error' => Funcionarios::getError(),
        'success' => Funcionarios::getSuccess()
    ]);
});

$app->post('/admin/forgot', function () {

    try {

        if (!isset($_POST["cpf"]) || trim($_POST["cpf"]) === '') {
            throw new Exception("Informe o CPF.");
        }

        $cpf = preg_replace('/\D/', '', $_POST["cpf"]);

        if (!Funcionarios::validaCPF($cpf)) {
            throw new Exception("Informe um CPF válido.");
        }

        Funcionarios::getForgot($cpf);

        Funcionarios::setSuccess("Se o CPF estiver cadastrado e possuir e-mail válido, você receberá um link de recuperação.");
        header("Location: /admin/forgot");
        exit;
    } catch (Exception $e) {
        Funcionarios::setError($e->getMessage());
        header("Location: /admin/forgot");
        exit;
    }
});

$app->get('/admin/forgot/reset', function () {

    try {

        if (!isset($_GET["code"]) || trim($_GET["code"]) === '') {
            throw new Exception("Código inválido.");
        }

        Funcionarios::validForgotDecrypt($_GET["code"]);

        $page = new PageAdmin([
            "header" => false,
            "footer" => false
        ]);

        $page->setTpl("forgot-reset", [
            "code" => $_GET["code"],
            "error" => Funcionarios::getError(),
            "success" => Funcionarios::getSuccess()
        ]);
    } catch (Exception $e) {
        Funcionarios::setError($e->getMessage());
        header("Location: /admin/forgot");
        exit;
    }
});

$app->post('/admin/forgot/reset', function () {

    try {

        if (!isset($_POST["code"]) || trim($_POST["code"]) === '') {
            throw new Exception("Código inválido.");
        }

        if (!isset($_POST["password"]) || trim($_POST["password"]) === '') {
            throw new Exception("Informe a nova senha.");
        }

        if (!isset($_POST["password_confirm"]) || trim($_POST["password_confirm"]) === '') {
            throw new Exception("Confirme a nova senha.");
        }

        if (strlen(trim($_POST["password"])) < 6) {
            throw new Exception("A senha deve ter pelo menos 6 caracteres.");
        }

        if ($_POST["password"] !== $_POST["password_confirm"]) {
            throw new Exception("As senhas não coincidem.");
        }

        $recovery = Funcionarios::validForgotDecrypt($_POST["code"]);

        $funcionario = new Funcionarios();
        $funcionario->get((int)$recovery["id_usuario"]);
        $funcionario->setPassword($_POST["password"]);

        Funcionarios::setForgotUsed((int)$recovery["id_recovery"]);

        Funcionarios::audit(
            'PASSWORD_RECOVERY_SUCCESS',
            'AUTH',
            (int)$recovery["id_usuario"],
            'Senha redefinida via token de recuperação'
        );

        Funcionarios::setSuccess("Senha alterada com sucesso.");
        header("Location: /admin/login");
        exit;
    } catch (Exception $e) {
        Funcionarios::setError($e->getMessage());
        header("Location: /admin/forgot/reset?code=" . urlencode($_POST["code"] ?? ""));
        exit;
    }
});

$app->get('/admin/test-email', function () {

    try {

        \Hcode\Mailer::quickSend(
            "mmota350@gmail.com",
            "Teste",
            "Teste SMTP Prato Cheio",
            "<h1>🔥 Funcionando!</h1><p>Seu sistema de e-mail está OK.</p>"
        );

        echo "✅ E-mail enviado com sucesso!";
        exit;
    } catch (Exception $e) {

        echo "❌ Erro: " . $e->getMessage();
        exit;
    }
});

/*
|--------------------------------------------------------------------------
| Compatibilidade
|--------------------------------------------------------------------------
*/
$app->get('/admin/index', function () {
    header("Location: /admin");
    exit;
});

/*
|--------------------------------------------------------------------------
| Rotas de teste (mantidas)
|--------------------------------------------------------------------------
*/
$app->get('/admin/teste-backup', function () {

    Funcionarios::checkPermission('BACKUP_RUN');

    if (function_exists('backupAutomatico')) {
        backupAutomatico(true, 0, 'teste_manual');
    }

    Notification::add("Backup executado (teste).");

    Funcionarios::audit(
        'BACKUP_RUN',
        'BACKUP',
        null,
        'Backup executado pela rota de teste'
    );

    $notificacoesSessao = Notification::getAll();
    $notificacoesBackup = function_exists('getBackupNotifications') ? getBackupNotifications(10) : [];

    $notificacoes = array_merge($notificacoesSessao, $notificacoesBackup);
    $total = count($notificacoes);

    $ultimoBackup = function_exists('getUltimoBackup') ? getUltimoBackup() : null;

    $page = new PageAdmin([
        "data" => [
            "notificacoes" => $notificacoes,
            "total" => $total,
            "ultimoBackup" => $ultimoBackup
        ]
    ]);

    $page->setTpl("index", [
        "msg" => "Backup executado (teste)."
    ]);
});

$app->get('/admin/teste-notifs', function () {

    Funcionarios::checkPermission('SISTEMA_DEBUG');

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        function_exists('getBackupNotifications') ? getBackupNotifications(5) : [],
        JSON_UNESCAPED_UNICODE
    );

    exit;
});

$app->get('/admin/debug-logpath', function () {

    Funcionarios::checkPermission('SISTEMA_DEBUG');

    header('Content-Type: application/json; charset=utf-8');

    $arquivo = defined('BACKUP_DIR') ? (BACKUP_DIR . '/backup_notifications.log') : (__DIR__ . '/backup/backup_notifications.log');

    echo json_encode([
        "admin_php_dir" => __DIR__,
        "log_path" => $arquivo,
        "exists" => file_exists($arquivo),
        "size" => file_exists($arquivo) ? filesize($arquivo) : 0,
        "realpath" => file_exists($arquivo) ? realpath($arquivo) : null,
    ], JSON_UNESCAPED_UNICODE);

    exit;
});

$app->get('/admin/backup/run', function () {

    Funcionarios::checkPermission('BACKUP_RUN');

    if (function_exists('backupAutomatico')) {
        backupAutomatico(true, 0, 'backup_manual');
    }

    Funcionarios::audit(
        'BACKUP_RUN',
        'BACKUP',
        null,
        'Backup manual executado'
    );

    echo "CHAMOU backupAutomatico(contexto manual)";
    exit;
});

$app->get('/admin/ping', function () {
    echo "OK PING";
    exit;
});

$app->get('/admin/notificacoes', function () {

    Funcionarios::checkPermission('NOTIFICACOES_VIEW');

    $notificacoesSessao = Notification::getAll();
    $notificacoesBackup = function_exists('getBackupNotifications') ? getBackupNotifications(50) : [];

    $notificacoes = array_merge($notificacoesSessao, $notificacoesBackup);
    $total = count($notificacoes);

    $page = new PageAdmin();

    $page->setTpl("notificacoes", [
        "notificacoes" => $notificacoes,
        "total" => $total
    ]);
});

$app->get('/admin/notificacoes/limpar', function () {

    Funcionarios::checkPermission('NOTIFICACOES_CLEAR');

    Notification::clear();

    if (function_exists('backupLogFile')) {
        $arquivo = backupLogFile();
        if (file_exists($arquivo)) {
            file_put_contents($arquivo, '');
        }
    }

    Funcionarios::audit(
        'NOTIFICACOES_CLEAR',
        'NOTIFICACOES',
        null,
        'Todas as notificações foram removidas'
    );

    header("Location: /admin/notificacoes");
    exit;
});

$app->post('/admin/notificacoes/limpar', function () {

    Funcionarios::checkPermission('NOTIFICACOES_CLEAR');

    Notification::clear();

    if (function_exists('backupLogFile')) {
        $arquivo = backupLogFile();
        if (file_exists($arquivo)) {
            file_put_contents($arquivo, '');
        }
    }

    Funcionarios::audit(
        'NOTIFICACOES_CLEAR',
        'NOTIFICACOES',
        null,
        'Todas as notificações foram removidas via AJAX'
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
});

$app->get('/admin/notificacoes/add-teste', function () {

    Funcionarios::checkPermission('NOTIFICACOES_CLEAR');

    Notification::add("Notificação de teste criada com sucesso.");

    Funcionarios::audit(
        'NOTIFICACAO_TESTE_CREATE',
        'NOTIFICACOES',
        null,
        'Notificação de teste criada manualmente'
    );

    header("Location: /admin");
    exit;
});

$app->get('/admin/api/notificacoes', function () {

    header('Content-Type: application/json; charset=utf-8');

    $user = Funcionarios::getFromSession();

    if (!$user || !$user->getid_usuario()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'mensagem' => 'Usuário não autenticado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $permissions = $_SESSION['User']['permissions'] ?? [];

    if (!in_array('NOTIFICACOES_VIEW', $permissions)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'mensagem' => 'Acesso negado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $notificacoesSessao = Notification::getAll();
        $notificacoesBackup = function_exists('getBackupNotifications') ? getBackupNotifications(50) : [];

        $notificacoes = array_merge($notificacoesSessao, $notificacoesBackup);

        echo json_encode([
            'success' => true,
            'total' => count($notificacoes),
            'notificacoes' => $notificacoes
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
});

$app->get('/admin/seguranca/permissoes', function () {
    Funcionarios::checkPermission('ACL_PROFILES_MANAGE');

    $page = new PageAdmin();
    $all = Permissions::listAll();

    $page->setTpl('seguranca-permissoes', [
        'permissions' => $all,
        'admin_permissions' => Permissions::listByProfile('ADMIN'),
        'supervisor_permissions' => Permissions::listByProfile('SUPERVISOR'),
        'assessor_permissions' => Permissions::listByProfile('ASSESSOR')
    ]);
});

$app->post('/admin/seguranca/permissoes', function () {
    Funcionarios::checkPermission('ACL_PROFILES_MANAGE');

    foreach (['ADMIN', 'SUPERVISOR', 'ASSESSOR'] as $perfil) {
        Permissions::saveProfilePermissions($perfil, $_POST['permissions'][$perfil] ?? []);
    }

    Permissions::clearSessionCache();

    Funcionarios::audit(
        'ACL_PERMISSION_UPDATE',
        'SEGURANCA',
        null,
        'Permissões por perfil foram atualizadas'
    );

    header('Location: /admin/seguranca/permissoes');
    exit;
});

$app->get('/admin/seguranca/acessos-negados', function () {
    Funcionarios::checkPermission('ACL_DENIED_VIEW');

    $sql = new Sql();
    $rows = $sql->select("SELECT * FROM tb_access_denied ORDER BY created_at DESC LIMIT 300");

    $page = new PageAdmin();
    $page->setTpl('seguranca-acessos-negados', ['rows' => $rows]);
});

$app->get('/admin/usuarios/seguranca', function () {
    Funcionarios::checkPermission('USUARIOS_SECURITY_MANAGE');

    $page = new PageAdmin();
    $page->setTpl('usuarios-seguranca', ['usuarios' => Funcionarios::listAllSecurity()]);
});

$app->post('/admin/:id_usuario/status', function ($id_usuario) {
    Funcionarios::checkPermission('USUARIOS_SECURITY_MANAGE');

    Funcionarios::setUserActive((int)$id_usuario, (int)($_POST['ativo'] ?? 0));

    header('Location: /admin/usuarios/seguranca');
    exit;
});

$app->post('/admin/funcionarios/:id_pessoa/status-funcionario', function ($id_pessoa) {
    Funcionarios::checkPermission('USUARIOS_SECURITY_MANAGE');

    Funcionarios::setFuncionarioActive((int)$id_pessoa, (int)($_POST['ativo'] ?? 0));

    header('Location: /admin/usuarios/seguranca');
    exit;
});

$app->get('/admin/debug-permissoes', function () {
    Funcionarios::checkPermission('SISTEMA_DEBUG');

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($_SESSION['User']['permissions'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});

$app->get('/admin/seguranca/auditoria', function () {

    Funcionarios::checkPermission('ACL_DENIED_VIEW');

    $sql = new Sql();

    $logs = $sql->select("
        SELECT *
        FROM tb_userlogs
        ORDER BY created_at DESC
        LIMIT 300
    ");

    $page = new PageAdmin();

    $page->setTpl('seguranca-auditoria', [
        'logs' => $logs
    ]);
});

/*
|--------------------------------------------------------------------------
| RELATÓRIO PDF + UPLOAD HOSTGATOR
|--------------------------------------------------------------------------
*/

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

if (!function_exists('buscarHistoricoRelatorioExistente')) {
    function buscarHistoricoRelatorioExistente(Sql $sql, array $dados)
    {
        $res = $sql->select("
            SELECT id
            FROM tb_relatorios_pdf
            WHERE DATE(data_relatorio) = :data_relatorio
              AND nome_arquivo = :nome_arquivo
              AND caminho_remoto = :caminho_remoto
            ORDER BY id DESC
            LIMIT 1
        ", array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':caminho_remoto' => $dados['caminho_remoto']
        ));

        return isset($res[0]['id']) ? (int)$res[0]['id'] : 0;
    }
}

if (!function_exists('registrarHistoricoRelatorio')) {
    function registrarHistoricoRelatorio(Sql $sql, array $dados)
    {
        $idExistente = buscarHistoricoRelatorioExistente($sql, $dados);

        if ($idExistente > 0) {
            $sql->query("
                UPDATE tb_relatorios_pdf
                SET
                    data_relatorio = :data_relatorio,
                    nome_arquivo = :nome_arquivo,
                    url_publica = :url_publica,
                    caminho_remoto = :caminho_remoto,
                    status_upload = :status_upload,
                    mensagem_erro = :mensagem_erro,
                    responsavel = :responsavel,
                    cpf_responsavel = :cpf_responsavel,
                    data_geracao = NOW(),
                    data_upload = " . ($dados['status_upload'] === 'SUCESSO' ? 'NOW()' : 'NULL') . "
                WHERE id = :id
            ", array(
                ':data_relatorio' => $dados['data_relatorio'],
                ':nome_arquivo' => $dados['nome_arquivo'],
                ':url_publica' => $dados['url_publica'],
                ':caminho_remoto' => $dados['caminho_remoto'],
                ':status_upload' => $dados['status_upload'],
                ':mensagem_erro' => ($dados['status_upload'] === 'SUCESSO' ? null : $dados['mensagem_erro']),
                ':responsavel' => $dados['responsavel'],
                ':cpf_responsavel' => $dados['cpf_responsavel'],
                ':id' => $idExistente
            ));

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
        ", array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':url_publica' => $dados['url_publica'],
            ':caminho_remoto' => $dados['caminho_remoto'],
            ':status_upload' => $dados['status_upload'],
            ':mensagem_erro' => ($dados['status_upload'] === 'SUCESSO' ? null : $dados['mensagem_erro']),
            ':responsavel' => $dados['responsavel'],
            ':cpf_responsavel' => $dados['cpf_responsavel']
        ));

        $ret = $sql->select('SELECT LAST_INSERT_ID() AS id');
        return isset($ret[0]['id']) ? (int)$ret[0]['id'] : 0;
    }
}

if (!function_exists('buscarHistoricoRelatorioExistenteRemoto')) {
    function buscarHistoricoRelatorioExistenteRemoto(PDO $pdo, array $dados)
    {
        $stmt = $pdo->prepare("
            SELECT id
            FROM tb_relatorios_pdf
            WHERE DATE(data_relatorio) = :data_relatorio
              AND nome_arquivo = :nome_arquivo
              AND caminho_remoto = :caminho_remoto
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':caminho_remoto' => $dados['caminho_remoto']
        ));

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['id']) ? (int)$row['id'] : 0;
    }
}

if (!function_exists('registrarHistoricoRelatorioRemoto')) {
    function registrarHistoricoRelatorioRemoto(PDO $pdo, array $dados)
    {
        $idExistente = buscarHistoricoRelatorioExistenteRemoto($pdo, $dados);

        if ($idExistente > 0) {
            $sql = "
                UPDATE tb_relatorios_pdf
                SET
                    data_relatorio = :data_relatorio,
                    nome_arquivo = :nome_arquivo,
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
            $stmt->execute(array(
                ':data_relatorio' => $dados['data_relatorio'],
                ':nome_arquivo' => $dados['nome_arquivo'],
                ':url_publica' => $dados['url_publica'],
                ':caminho_remoto' => $dados['caminho_remoto'],
                ':status_upload' => $dados['status_upload'],
                ':mensagem_erro' => ($dados['status_upload'] === 'SUCESSO' ? null : $dados['mensagem_erro']),
                ':responsavel' => $dados['responsavel'],
                ':cpf_responsavel' => $dados['cpf_responsavel'],
                ':id' => $idExistente
            ));

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
        $stmt->execute(array(
            ':data_relatorio' => $dados['data_relatorio'],
            ':nome_arquivo' => $dados['nome_arquivo'],
            ':url_publica' => $dados['url_publica'],
            ':caminho_remoto' => $dados['caminho_remoto'],
            ':status_upload' => $dados['status_upload'],
            ':mensagem_erro' => ($dados['status_upload'] === 'SUCESSO' ? null : $dados['mensagem_erro']),
            ':responsavel' => $dados['responsavel'],
            ':cpf_responsavel' => $dados['cpf_responsavel']
        ));

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

if (!function_exists('uploadRelatorioRemoto')) {
    function uploadRelatorioRemoto(array $config, $caminhoLocal, $nomeArquivo)
    {
        $ftpHost = $config['host'];
        $ftpUser = $config['user'];
        $ftpPass = $config['pass'];
        $remoteDir = $config['remote_dir'];
        $publicBaseUrl = rtrim($config['public_base_url'], '/') . '/';

        $remoteFile = rtrim($remoteDir, '/') . '/' . $nomeArquivo;

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
            'remote_file' => $remoteFile,
            'url_publica' => $publicBaseUrl . rawurlencode($nomeArquivo)
        );
    }
}



// ==========================================
// 🔁 SINCRONIZAÇÃO REMOTA DE FECHAMENTO
// tb_relatorios + tb_fechamento_dia
// ==========================================

if (!function_exists('normalizarDataSyncFechamentoRemoto')) {
    function normalizarDataSyncFechamentoRemoto($dataRef)
    {
        $dataRef = trim((string)$dataRef);
        if ($dataRef === '') {
            return date('Y-m-d');
        }

        if (function_exists('pc_normalizar_data_refeicao')) {
            return pc_normalizar_data_refeicao($dataRef);
        }

        $ts = strtotime($dataRef);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}

if (!function_exists('garantirFilaSyncFechamentoRemoto')) {
    function garantirFilaSyncFechamentoRemoto(Sql $sql)
    {
        $sql->query("
            CREATE TABLE IF NOT EXISTS tb_fechamento_sync_remoto (
                id INT NOT NULL AUTO_INCREMENT,
                data_refeicao VARCHAR(10) NOT NULL,
                status ENUM('PENDENTE','SINCRONIZADO','ERRO') NOT NULL DEFAULT 'PENDENTE',
                tentativas INT NOT NULL DEFAULT 0,
                ultima_tentativa DATETIME DEFAULT NULL,
                sincronizado_em DATETIME DEFAULT NULL,
                mensagem_erro TEXT DEFAULT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_data_refeicao (data_refeicao),
                KEY idx_status (status),
                KEY idx_atualizado_em (atualizado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

if (!function_exists('registrarTentativaSyncFechamentoRemoto')) {
    function registrarTentativaSyncFechamentoRemoto(Sql $sql, $dataRef)
    {
        garantirFilaSyncFechamentoRemoto($sql);
        $dataRef = normalizarDataSyncFechamentoRemoto($dataRef);

        $sql->query("
            INSERT INTO tb_fechamento_sync_remoto (
                data_refeicao,
                status,
                tentativas,
                ultima_tentativa,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                'PENDENTE',
                1,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = CASE WHEN status = 'SINCRONIZADO' THEN 'PENDENTE' ELSE status END,
                tentativas = tentativas + 1,
                ultima_tentativa = NOW(),
                atualizado_em = NOW()
        ", array(
            ':data_refeicao' => $dataRef
        ));
    }
}

if (!function_exists('registrarPendenteSyncFechamentoRemoto')) {
    function registrarPendenteSyncFechamentoRemoto(Sql $sql, $dataRef, $mensagemErro)
    {
        garantirFilaSyncFechamentoRemoto($sql);
        $dataRef = normalizarDataSyncFechamentoRemoto($dataRef);
        $mensagemErro = trim((string)$mensagemErro);

        $sql->query("
            INSERT INTO tb_fechamento_sync_remoto (
                data_refeicao,
                status,
                mensagem_erro,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                'PENDENTE',
                :mensagem_erro_insert,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = 'PENDENTE',
                mensagem_erro = :mensagem_erro_update,
                sincronizado_em = NULL,
                atualizado_em = NOW()
        ", array(
            ':data_refeicao' => $dataRef,
            ':mensagem_erro_insert' => $mensagemErro,
            ':mensagem_erro_update' => $mensagemErro
        ));
    }
}

if (!function_exists('registrarSucessoSyncFechamentoRemoto')) {
    function registrarSucessoSyncFechamentoRemoto(Sql $sql, $dataRef)
    {
        garantirFilaSyncFechamentoRemoto($sql);
        $dataRef = normalizarDataSyncFechamentoRemoto($dataRef);

        $sql->query("
            INSERT INTO tb_fechamento_sync_remoto (
                data_refeicao,
                status,
                sincronizado_em,
                atualizado_em
            ) VALUES (
                :data_refeicao,
                'SINCRONIZADO',
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = 'SINCRONIZADO',
                mensagem_erro = NULL,
                sincronizado_em = NOW(),
                atualizado_em = NOW()
        ", array(
            ':data_refeicao' => $dataRef
        ));
    }
}

if (!function_exists('mensagemErroSyncFechamentoRemoto')) {
    function mensagemErroSyncFechamentoRemoto(array $sync)
    {
        $erros = array();

        if (!empty($sync['erro_tb_relatorios'])) {
            $erros[] = 'tb_relatorios: ' . $sync['erro_tb_relatorios'];
        }

        if (!empty($sync['erro_tb_fechamento_dia'])) {
            $erros[] = 'tb_fechamento_dia: ' . $sync['erro_tb_fechamento_dia'];
        }

        return $erros ? implode(' | ', $erros) : 'Falha desconhecida ao sincronizar com a nuvem.';
    }
}

if (!function_exists('listarPendenciasSyncFechamentoRemoto')) {
    function listarPendenciasSyncFechamentoRemoto(Sql $sql, $limite = 10)
    {
        garantirFilaSyncFechamentoRemoto($sql);
        $limite = max(1, (int)$limite);

        return $sql->select("
            SELECT data_refeicao, status, tentativas, mensagem_erro
            FROM tb_fechamento_sync_remoto
            WHERE status <> 'SINCRONIZADO'
            ORDER BY ultima_tentativa ASC, atualizado_em ASC, id ASC
            LIMIT {$limite}
        ");
    }
}

if (!function_exists('resumoFilaSyncFechamentoRemoto')) {
    function resumoFilaSyncFechamentoRemoto(Sql $sql)
    {
        garantirFilaSyncFechamentoRemoto($sql);

        $rows = $sql->select("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status <> 'SINCRONIZADO' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'SINCRONIZADO' THEN 1 ELSE 0 END) AS sincronizados
            FROM tb_fechamento_sync_remoto
        ");

        return array(
            'total' => isset($rows[0]['total']) ? (int)$rows[0]['total'] : 0,
            'pendentes' => isset($rows[0]['pendentes']) ? (int)$rows[0]['pendentes'] : 0,
            'sincronizados' => isset($rows[0]['sincronizados']) ? (int)$rows[0]['sincronizados'] : 0
        );
    }
}

if (!function_exists('sincronizarTbRelatoriosRemoto')) {
    function sincronizarTbRelatoriosRemoto(PDO $pdo, array $dados)
    {
        $sql = "
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
                qtd_refeicoes_servidas,
                ocorrencias,
                cardapio,
                nome_banco,
                refeicoes_ofertadas,
                sobra_refeicoes,
                sobra_senhas,
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
                :qtd_refeicoes_servidas,
                :ocorrencias,
                :cardapio,
                :nome_banco,
                :refeicoes_ofertadas,
                :sobra_refeicoes,
                :sobra_senhas,
                :data,
                1
            )
            ON DUPLICATE KEY UPDATE
                Idade_3a17Masculino = VALUES(Idade_3a17Masculino),
                Idade_3a17Masculino_PCD = VALUES(Idade_3a17Masculino_PCD),
                Idade_3a17Feminino = VALUES(Idade_3a17Feminino),
                Idade_3a17Feminino_PCD = VALUES(Idade_3a17Feminino_PCD),
                Idade_18a59Masculino = VALUES(Idade_18a59Masculino),
                Idade_18a59Masculino_PCD = VALUES(Idade_18a59Masculino_PCD),
                Idade_17a59Feminino = VALUES(Idade_17a59Feminino),
                Idade_17a59Feminino_PCD = VALUES(Idade_17a59Feminino_PCD),
                Idade_60Masculino = VALUES(Idade_60Masculino),
                Idade_60Masculino_PCD = VALUES(Idade_60Masculino_PCD),
                Idade_60Feminino = VALUES(Idade_60Feminino),
                Idade_60Feminino_PCD = VALUES(Idade_60Feminino_PCD),
                Situacao_risco_masculino = VALUES(Situacao_risco_masculino),
                Situacao_risco_Feminino = VALUES(Situacao_risco_Feminino),
                Deficientes = VALUES(Deficientes),
                senhas_genericas = VALUES(senhas_genericas),
                Total_pessoas_atendidas = VALUES(Total_pessoas_atendidas),
                qtd_refeicoes_servidas = VALUES(qtd_refeicoes_servidas),
                ocorrencias = VALUES(ocorrencias),
                cardapio = VALUES(cardapio),
                nome_banco = VALUES(nome_banco),
                refeicoes_ofertadas = VALUES(refeicoes_ofertadas),
                sobra_refeicoes = VALUES(sobra_refeicoes),
                sobra_senhas = VALUES(sobra_senhas),
                fechado = 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            ':Idade_3a17Masculino' => isset($dados['Idade_3a17Masculino']) ? (int)$dados['Idade_3a17Masculino'] : 0,
            ':Idade_3a17Masculino_PCD' => isset($dados['Idade_3a17Masculino_PCD']) ? (int)$dados['Idade_3a17Masculino_PCD'] : 0,
            ':Idade_3a17Feminino' => isset($dados['Idade_3a17Feminino']) ? (int)$dados['Idade_3a17Feminino'] : 0,
            ':Idade_3a17Feminino_PCD' => isset($dados['Idade_3a17Feminino_PCD']) ? (int)$dados['Idade_3a17Feminino_PCD'] : 0,
            ':Idade_18a59Masculino' => isset($dados['Idade_18a59Masculino']) ? (int)$dados['Idade_18a59Masculino'] : 0,
            ':Idade_18a59Masculino_PCD' => isset($dados['Idade_18a59Masculino_PCD']) ? (int)$dados['Idade_18a59Masculino_PCD'] : 0,
            ':Idade_17a59Feminino' => isset($dados['Idade_17a59Feminino']) ? (int)$dados['Idade_17a59Feminino'] : 0,
            ':Idade_17a59Feminino_PCD' => isset($dados['Idade_17a59Feminino_PCD']) ? (int)$dados['Idade_17a59Feminino_PCD'] : 0,
            ':Idade_60Masculino' => isset($dados['Idade_60Masculino']) ? (int)$dados['Idade_60Masculino'] : 0,
            ':Idade_60Masculino_PCD' => isset($dados['Idade_60Masculino_PCD']) ? (int)$dados['Idade_60Masculino_PCD'] : 0,
            ':Idade_60Feminino' => isset($dados['Idade_60Feminino']) ? (int)$dados['Idade_60Feminino'] : 0,
            ':Idade_60Feminino_PCD' => isset($dados['Idade_60Feminino_PCD']) ? (int)$dados['Idade_60Feminino_PCD'] : 0,
            ':Situacao_risco_masculino' => isset($dados['Situacao_risco_masculino']) ? (int)$dados['Situacao_risco_masculino'] : 0,
            ':Situacao_risco_Feminino' => isset($dados['Situacao_risco_Feminino']) ? (int)$dados['Situacao_risco_Feminino'] : 0,
            ':Deficientes' => isset($dados['Deficientes']) ? (int)$dados['Deficientes'] : 0,
            ':senhas_genericas' => isset($dados['senhas_genericas']) ? (int)$dados['senhas_genericas'] : 0,
            ':Total_pessoas_atendidas' => isset($dados['Total_pessoas_atendidas']) ? (int)$dados['Total_pessoas_atendidas'] : 0,
            ':qtd_refeicoes_servidas' => isset($dados['qtd_refeicoes_servidas']) ? (int)$dados['qtd_refeicoes_servidas'] : 0,
            ':ocorrencias' => isset($dados['ocorrencias']) ? $dados['ocorrencias'] : '',
            ':cardapio' => isset($dados['cardapio']) ? $dados['cardapio'] : '',
            ':nome_banco' => isset($dados['nome_banco']) ? $dados['nome_banco'] : '',
            ':refeicoes_ofertadas' => isset($dados['refeicoes_ofertadas']) ? (int)$dados['refeicoes_ofertadas'] : 0,
            ':sobra_refeicoes' => isset($dados['sobra_refeicoes']) ? (int)$dados['sobra_refeicoes'] : 0,
            ':sobra_senhas' => isset($dados['sobra_senhas']) ? (int)$dados['sobra_senhas'] : 0,
            ':data' => $dados['data']
        ));

        return true;
    }
}

if (!function_exists('sincronizarTbFechamentoDiaRemoto')) {
    function sincronizarTbFechamentoDiaRemoto(PDO $pdo, array $dados)
    {
        $sql = "
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
                :atualizado_em
            )
            ON DUPLICATE KEY UPDATE
                limite = VALUES(limite),
                total = VALUES(total),
                fechado = VALUES(fechado),
                fechado_em = VALUES(fechado_em),
                atualizado_em = VALUES(atualizado_em)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            ':data_refeicao' => $dados['data_refeicao'],
            ':limite' => isset($dados['limite']) ? (int)$dados['limite'] : 0,
            ':total' => isset($dados['total']) ? (int)$dados['total'] : 0,
            ':fechado' => isset($dados['fechado']) ? (int)$dados['fechado'] : 0,
            ':fechado_em' => isset($dados['fechado_em']) ? $dados['fechado_em'] : null,
            ':atualizado_em' => isset($dados['atualizado_em']) ? $dados['atualizado_em'] : null
        ));

        return true;
    }
}

if (!function_exists('sincronizarFechamentoRemotoCompleto')) {
    function sincronizarFechamentoRemotoCompleto(Sql $sql, $dataRef, array $configUpload = array())
    {
        $dataRef = normalizarDataSyncFechamentoRemoto($dataRef);
        $resultado = array(
            'data' => $dataRef,
            'tb_relatorios_ok' => false,
            'tb_fechamento_dia_ok' => false,
            'remoto_ok' => false,
            'pendente_reenvio' => false,
            'erro_tb_relatorios' => null,
            'erro_tb_fechamento_dia' => null
        );

        try {
            registrarTentativaSyncFechamentoRemoto($sql, $dataRef);
            $pdoRemoto = getPdoRelatorioRemoto();

            if (!($pdoRemoto instanceof PDO)) {
                throw new Exception('Banco remoto desativado ou sem configuracao.');
            }

            $dadosRel = $sql->select("
                SELECT *
                FROM tb_relatorios
                WHERE data = :data
                LIMIT 1
            ", array(
                ':data' => $dataRef
            ));

            $dadosFech = $sql->select("
                SELECT *
                FROM tb_fechamento_dia
                WHERE data_refeicao = :data
                LIMIT 1
            ", array(
                ':data' => $dataRef
            ));

            if (!isset($dadosRel[0])) {
                throw new Exception('tb_relatorios não encontrado.');
            }

            if (!isset($dadosFech[0])) {
                throw new Exception('tb_fechamento_dia não encontrado.');
            }

            $rel = $dadosRel[0];
            $fech = $dadosFech[0];

            try {
                sincronizarTbRelatoriosRemoto($pdoRemoto, $rel);
                $resultado['tb_relatorios_ok'] = true;
            } catch (Exception $e) {
                $resultado['erro_tb_relatorios'] = $e->getMessage();
                if (!empty($configUpload)) {
                    escreverLogRelatorio($configUpload, 'ERRO tb_relatorios remoto: ' . $e->getMessage());
                }
            }

            try {
                sincronizarTbFechamentoDiaRemoto($pdoRemoto, $fech);
                $resultado['tb_fechamento_dia_ok'] = true;
            } catch (Exception $e) {
                $resultado['erro_tb_fechamento_dia'] = $e->getMessage();
                if (!empty($configUpload)) {
                    escreverLogRelatorio($configUpload, 'ERRO tb_fechamento_dia remoto: ' . $e->getMessage());
                }
            }

            $resultado['remoto_ok'] = $resultado['tb_relatorios_ok'] && $resultado['tb_fechamento_dia_ok'];

            if ($resultado['remoto_ok']) {
                registrarSucessoSyncFechamentoRemoto($sql, $dataRef);
            } else {
                $resultado['pendente_reenvio'] = true;
                registrarPendenteSyncFechamentoRemoto($sql, $dataRef, mensagemErroSyncFechamentoRemoto($resultado));
            }

            return $resultado;
        } catch (Exception $e) {
            $resultado['erro_tb_relatorios'] = $e->getMessage();
            $resultado['erro_tb_fechamento_dia'] = $e->getMessage();
            $resultado['pendente_reenvio'] = true;

            try {
                registrarPendenteSyncFechamentoRemoto($sql, $dataRef, mensagemErroSyncFechamentoRemoto($resultado));
            } catch (Exception $filaErro) {
                if (!empty($configUpload)) {
                    escreverLogRelatorio($configUpload, 'ERRO ao registrar fila sync remoto: ' . $filaErro->getMessage());
                }
            }

            if (!empty($configUpload)) {
                escreverLogRelatorio($configUpload, 'ERRO geral sync remoto: ' . $e->getMessage());
            }

            return $resultado;
        }
    }
}

if (!function_exists('sincronizarPendenciasFechamentoRemoto')) {
    function sincronizarPendenciasFechamentoRemoto(Sql $sql, array $configUpload = array(), $limite = 5, $ignorarDataRef = '')
    {
        $ignorarDataRef = $ignorarDataRef !== '' ? normalizarDataSyncFechamentoRemoto($ignorarDataRef) : '';
        $pendencias = listarPendenciasSyncFechamentoRemoto($sql, $limite);
        $resultado = array(
            'total_analisado' => 0,
            'sincronizados' => 0,
            'falhas' => 0,
            'itens' => array()
        );

        foreach ($pendencias as $pendencia) {
            $dataPendente = isset($pendencia['data_refeicao']) ? (string)$pendencia['data_refeicao'] : '';
            if ($dataPendente === '' || $dataPendente === $ignorarDataRef) {
                continue;
            }

            $resultado['total_analisado']++;
            $sync = sincronizarFechamentoRemotoCompleto($sql, $dataPendente, $configUpload);

            if (!empty($sync['remoto_ok'])) {
                $resultado['sincronizados']++;
            } else {
                $resultado['falhas']++;
            }

            $resultado['itens'][] = array(
                'data' => $dataPendente,
                'ok' => !empty($sync['remoto_ok']),
                'erro' => !empty($sync['remoto_ok']) ? null : mensagemErroSyncFechamentoRemoto($sync)
            );
        }

        return $resultado;
    }
}

$app->map('/admin/api/fechamento/sync-remoto', function () {

    Funcionarios::checkPermission('RELATORIOS_VIEW');

    $sql = new Sql();

    $data = normalizarDataSyncFechamentoRemoto(isset($_REQUEST['data']) ? $_REQUEST['data'] : date('Y-m-d'));

    $configUpload = getRelatorioUploadConfig();

    $pendenciasAntes = sincronizarPendenciasFechamentoRemoto($sql, $configUpload, 5, $data);
    $sync = sincronizarFechamentoRemotoCompleto($sql, $data, $configUpload);
    $pendenciasDepois = sincronizarPendenciasFechamentoRemoto($sql, $configUpload, 5, $data);
    $fila = resumoFilaSyncFechamentoRemoto($sql);
    $ok = !empty($sync['remoto_ok']);
    $mensagem = $ok
        ? 'Fechamento sincronizado com a nuvem.'
        : 'Fechamento salvo localmente, mas ainda nao foi enviado para a nuvem. Conecte a internet e clique em reenviar sincronizacao.';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $ok,
        'ok' => $ok,
        'message' => $mensagem,
        'data' => $data,
        'needs_network' => !$ok,
        'deve_reenviar' => !$ok,
        'sync' => $sync,
        'pendencias_antes' => $pendenciasAntes,
        'pendencias_depois' => $pendenciasDepois,
        'fila' => $fila
    ]);
    exit;
})->via('GET', 'POST');


$app->get('/admin/api/relatorio/pdf', function () {

    Funcionarios::checkPermission('RELATORIOS_VIEW');

    $sql = new Sql();
    $config = getRelatorioUploadConfig();

    if (isset($_GET['debug']) || isset($_GET['teste_rota'])) {
        Funcionarios::checkPermission('SISTEMA_DEBUG');
    }

    if (isset($_GET['debug'])) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }

    if (isset($_GET['teste_rota'])) {
        limparBufferSaida();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'success' => true,
            'arquivo' => __FILE__,
            'upload' => isset($_GET['upload']) ? $_GET['upload'] : null
        ));
        exit;
    }

    $data = isset($_GET['data']) && $_GET['data'] != ''
        ? trim($_GET['data'])
        : date('Y-m-d');

    $upload = isset($_GET['upload']) ? (int)$_GET['upload'] : 0;

    if (strpos($data, '/') !== false) {
        $dt = DateTime::createFromFormat('d/m/Y', $data);
        if (!$dt) {
            responderJson(array(
                'success' => false,
                'message' => 'Data inválida.'
            ));
        }
        $dataSql = $dt->format('Y-m-d');
    } else {
        $dataSql = $data;
    }

    if (function_exists('gerarRelatorioDia')) {
        try {
            gerarRelatorioDia($sql, $dataSql);
        } catch (\Exception $e) {
            if (function_exists('relatorioLog')) {
                relatorioLog('Falha ao atualizar resumo antes do PDF | data=' . $dataSql . ' | msg=' . $e->getMessage());
            }
        }
    }

    $qtdRefeicoesServidas = isset($_GET['qtd_refeicoes_servidas']) && $_GET['qtd_refeicoes_servidas'] !== ''
        ? (int)$_GET['qtd_refeicoes_servidas']
        : null;

    $ocorrencias = isset($_GET['ocorrencias']) && $_GET['ocorrencias'] != ''
        ? trim($_GET['ocorrencias'])
        : null;

    $cardapio = isset($_GET['cardapio']) && $_GET['cardapio'] != ''
        ? trim($_GET['cardapio'])
        : null;

    $nomeBanco = isset($_GET['nome_banco']) && $_GET['nome_banco'] != ''
        ? trim($_GET['nome_banco'])
        : null;

    $ruaMasculinoExtra = isset($_GET['rua_masculino']) && $_GET['rua_masculino'] !== ''
        ? (int)$_GET['rua_masculino']
        : 0;

    $ruaFemininoExtra = isset($_GET['rua_feminino']) && $_GET['rua_feminino'] !== ''
        ? (int)$_GET['rua_feminino']
        : 0;

    $pcdExtra = isset($_GET['pcd_extra']) && $_GET['pcd_extra'] !== ''
        ? (int)$_GET['pcd_extra']
        : 0;
    $ruaMasculinoExtra = 0;
    $ruaFemininoExtra = 0;
    $pcdExtra = 0;

    $result = $sql->select("
        SELECT
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
            qtd_refeicoes_servidas,
            ocorrencias,
            cardapio,
            nome_banco,
            refeicoes_ofertadas,
            sobra_refeicoes,
            sobra_senhas,
            data
        FROM tb_relatorios
        WHERE data = :data
        LIMIT 1
    ", array(
        ':data' => $dataSql
    ));

    if (!isset($result[0])) {
        responderJson(array(
            'success' => false,
            'message' => 'Nenhum fechamento encontrado para a data ' . $data
        ));
    }

    $rel = $result[0];
    $resumoFonteSenhas = function_exists('pc_obter_resumo_senhas_dia')
        ? pc_obter_resumo_senhas_dia($sql, $dataSql)
        : array();
    $totalFaixasEtarias = function_exists('pc_somar_faixas_relatorio')
        ? pc_somar_faixas_relatorio($rel)
        : 0;

    if ($qtdRefeicoesServidas === null) {
        $qtdRefeicoesServidas = isset($rel['qtd_refeicoes_servidas']) && $rel['qtd_refeicoes_servidas'] !== null
            ? (int)$rel['qtd_refeicoes_servidas']
            : 0;
    }

    if ($ocorrencias === null || trim($ocorrencias) === '') {
        $ocorrencias = isset($rel['ocorrencias']) ? trim((string)$rel['ocorrencias']) : '';
    }

    if ($cardapio === null) {
        $cardapio = isset($rel['cardapio']) ? trim((string)$rel['cardapio']) : '';
    }

    if ($nomeBanco === null || trim($nomeBanco) === '') {
        $nomeBanco = isset($rel['nome_banco']) ? trim((string)$rel['nome_banco']) : '';
    }

    if ($nomeBanco === '') {
        try {
            $dbInfo = $sql->select("SELECT DATABASE() AS nome_banco");
            if ($dbInfo && isset($dbInfo[0]['nome_banco'])) {
                $nomeBanco = (string)$dbInfo[0]['nome_banco'];
            }
        } catch (\Exception $e) {
            $nomeBanco = '';
        }
    }

    if ($ocorrencias === null || trim($ocorrencias) === '') {
        $ocorrencias = 'NÃO HOUVE NENHUMA OCORRÊNCIA.';
    }

    $LIMITE_SENHAS_DIA = getLimiteSenhasDiaRelatorio();

    $situacaoRiscoMasculino = isset($resumoFonteSenhas['total_rua_masculino'])
        ? (int)$resumoFonteSenhas['total_rua_masculino']
        : (int)$rel['Situacao_risco_masculino'];
    $situacaoRiscoFeminino = isset($resumoFonteSenhas['total_rua_feminino'])
        ? (int)$resumoFonteSenhas['total_rua_feminino']
        : (int)$rel['Situacao_risco_Feminino'];
    $deficientesTotal = isset($resumoFonteSenhas['total_deficiente'])
        ? (int)$resumoFonteSenhas['total_deficiente']
        : (int)$rel['Deficientes'];
    $totalPessoasAtendidas = isset($resumoFonteSenhas['total'])
        ? (int)$resumoFonteSenhas['total']
        : (isset($rel['Total_pessoas_atendidas']) && $rel['Total_pessoas_atendidas'] !== null
            ? (int)$rel['Total_pessoas_atendidas']
            : contarSenhasVendidasRelatorio($sql, $dataSql));
    $senhasGenericasTotal = isset($resumoFonteSenhas['total_generica'])
        ? (int)$resumoFonteSenhas['total_generica']
        : (int)$rel['senhas_genericas'];
    $totalNaoClassificado = isset($resumoFonteSenhas['total_nao_classificado'])
        ? (int)$resumoFonteSenhas['total_nao_classificado']
        : max(0, $totalPessoasAtendidas - $totalFaixasEtarias - $senhasGenericasTotal);
    $totalConferencia = $totalFaixasEtarias + $senhasGenericasTotal + $totalNaoClassificado;
    if ($totalConferencia !== $totalPessoasAtendidas) {
        $totalNaoClassificado = max(0, $totalPessoasAtendidas - $totalFaixasEtarias - $senhasGenericasTotal);
        $totalConferencia = $totalFaixasEtarias + $senhasGenericasTotal + $totalNaoClassificado;
    }
    $refeicoesOfertadas = isset($rel['refeicoes_ofertadas']) && $rel['refeicoes_ofertadas'] !== null
        ? (int)$rel['refeicoes_ofertadas']
        : $LIMITE_SENHAS_DIA;
    $sobraRefeicoes = isset($rel['sobra_refeicoes']) && $rel['sobra_refeicoes'] !== null
        ? max(0, (int)$rel['sobra_refeicoes'])
        : max(0, $refeicoesOfertadas - $qtdRefeicoesServidas);
    $sobraSenhas = isset($rel['sobra_senhas']) && $rel['sobra_senhas'] !== null
        ? max(0, (int)$rel['sobra_senhas'])
        : max(0, $refeicoesOfertadas - $totalPessoasAtendidas);

    $percentualUtilizacao = 0;
    if ($totalPessoasAtendidas > 0) {
        $percentualUtilizacao = ($qtdRefeicoesServidas / $totalPessoasAtendidas) * 100;
    }
    $percentualUtilizacaoFormatado = number_format($percentualUtilizacao, 1, ',', '.');

    $observacaoIndicadores = array();

    $totalSituacaoRua = $situacaoRiscoMasculino + $situacaoRiscoFeminino;
    $totalPcd = $deficientesTotal;

    if ($totalSituacaoRua > 0) {
        $observacaoIndicadores[] = 'Pessoas em situação de rua (' . $totalSituacaoRua . ') já estão incluídas nas faixas etárias acima.';
    }

    if ($totalPcd > 0) {
        $observacaoIndicadores[] = 'Pessoas com deficiência (PCD) (' . $totalPcd . ') já estão contabilizadas nas faixas etárias.';
    }

    $responsavelFechamento = 'Não identificado';
    if ($totalNaoClassificado > 0) {
        $observacaoIndicadores[] = 'Registros sem idade/genero valido (' . $totalNaoClassificado . ') entram no total registrado, mas nao entram nas faixas etarias.';
    }

    $observacaoIndicadores[] = 'Conferencia: faixas etarias (' . $totalFaixasEtarias . ') + senhas genericas (' . $senhasGenericasTotal . ') + sem classificacao (' . $totalNaoClassificado . ') = total registrado (' . $totalConferencia . ').';

    $cpfResponsavel = '';

    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $dadosSessao = isset($_SESSION[Funcionarios::SESSION]) && is_array($_SESSION[Funcionarios::SESSION])
            ? $_SESSION[Funcionarios::SESSION]
            : array();

        if (!empty($dadosSessao)) {
            if (!empty($dadosSessao['nome_funcionario'])) {
                $responsavelFechamento = trim($dadosSessao['nome_funcionario']);
            } elseif (!empty($dadosSessao['nome'])) {
                $responsavelFechamento = trim($dadosSessao['nome']);
            }

            if (!empty($dadosSessao['cpf'])) {
                $cpfResponsavel = trim($dadosSessao['cpf']);
            }
        }

        if ($responsavelFechamento === '' || $responsavelFechamento === null) {
            $responsavelFechamento = 'Não identificado';
        }
    } catch (\Exception $e) {
        $responsavelFechamento = 'Não identificado';
    }

    $dataHoraEmissao = new DateTime('now', new DateTimeZone('America/Manaus'));

    $logoPath = __DIR__ . '/../views/assets/img/logo-prato-cheio.png';

    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $imgData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode($imgData);
    }

    ob_start();
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>Relatório de Fechamento</title>
        <style>
            @page {
                margin: 32px 28px 32px 28px;
            }

            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 11px;
                color: #1f2937;
                margin: 0;
                padding: 0;
            }

            .watermark {
                position: fixed;
                top: 180px;
                left: 110px;
                width: 360px;
                opacity: 0.06;
                z-index: -1;
            }

            .header-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 18px;
                border-bottom: 2px solid #d9e2ec;
                padding-bottom: 10px;
            }

            .header-table td {
                border: none;
                vertical-align: middle;
            }

            .logo-topo {
                width: 85px;
            }

            .header-title {
                text-align: center;
            }

            .header-title h1 {
                margin: 0 0 6px 0;
                font-size: 20px;
                color: #1f3b57;
            }

            .header-title p {
                margin: 2px 0;
                font-size: 11px;
                color: #4b5563;
            }

            .bloco-info {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
            }

            .bloco-info td {
                border: 1px solid #d1d5db;
                background: #f8fafc;
                padding: 8px 10px;
                font-size: 11px;
            }

            .duas-colunas {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
            }

            .duas-colunas td {
                vertical-align: top;
                border: none;
            }

            .col-esq {
                width: 49%;
                padding-right: 1%;
            }

            .col-dir {
                width: 49%;
                padding-left: 1%;
            }

            .card {
                border: 1px solid #d1d5db;
                margin-bottom: 14px;
            }

            .card-title {
                background: #1f3b57;
                color: #ffffff;
                font-size: 12px;
                font-weight: bold;
                padding: 9px 10px;
            }

            table.report {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }

            table.report th,
            table.report td {
                border: 1px solid #d1d5db;
                padding: 7px;
                font-size: 10.5px;
            }

            table.report th {
                background: #eef2f7;
                color: #374151;
                text-align: left;
            }

            table.report td.qtd,
            table.report th.qtd {
                width: 85px;
                text-align: center;
            }

            .card table.report tr:nth-child(even) td {
                background: #fafafa;
            }

            .totais-grid {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
                margin-bottom: 12px;
            }

            .totais-grid td {
                width: 25%;
                border: 2px solid #2e7d32;
                background: #f0f9f1;
                text-align: center;
                padding: 14px 10px;
                vertical-align: top;
            }

            .totais-grid .label {
                font-size: 12px;
                color: #2e7d32;
                margin-bottom: 4px;
            }

            .totais-grid .valor {
                font-size: 24px;
                font-weight: bold;
                color: #1b5e20;
            }

            .totais-grid .valor.sobra {
                color: #c62828;
            }

            .observacao-alerta {
                border: 1px solid #f59e0b;
                background: #fff8e1;
                margin-top: 12px;
                margin-bottom: 14px;
            }

            .observacao-alerta .titulo {
                background: #fef3c7;
                color: #92400e;
                font-size: 12px;
                font-weight: bold;
                padding: 9px 10px;
                border-bottom: 1px solid #f59e0b;
            }

            .observacao-alerta .conteudo {
                padding: 10px 12px;
                font-size: 10.8px;
                color: #78350f;
                line-height: 1.5;
            }

            .observacao-alerta ul {
                margin: 6px 0 0 18px;
                padding: 0;
            }

            .observacao-alerta li {
                margin-bottom: 4px;
            }

            .ocorrencias-box {
                border: 1px solid #d1d5db;
                margin-top: 10px;
                margin-bottom: 12px;
            }

            .ocorrencias-box .titulo {
                background: #1f3b57;
                color: #fff;
                font-size: 12px;
                font-weight: bold;
                padding: 9px 10px;
            }

            .ocorrencias-box .conteudo {
                min-height: 60px;
                padding: 10px;
                font-size: 11px;
                background: #fafafa;
                white-space: pre-wrap;
            }

            .assinatura-box {
                border: 1px solid #d1d5db;
                margin-top: 14px;
                padding: 16px 12px 10px 12px;
                text-align: center;
                background: #fcfcfc;
            }

            .assinatura-nome {
                font-size: 12px;
                font-weight: bold;
                color: #1f2937;
            }

            .assinatura-cargo {
                font-size: 10px;
                color: #6b7280;
            }

            .footer {
                text-align: center;
                font-size: 10px;
                color: #6b7280;
                margin-top: 10px;
            }
        </style>
    </head>

    <body>

        <?php if ($logoBase64 != '') { ?>
            <img src="<?php echo $logoBase64; ?>" class="watermark" alt="Logo">
        <?php } ?>

        <table class="header-table">
            <tr>
                <td style="width: 90px;">
                    <?php if ($logoBase64 != '') { ?>
                        <img src="<?php echo $logoBase64; ?>" class="logo-topo" alt="Logo">
                    <?php } ?>
                </td>
                <td class="header-title">
                    <h1>RELATÓRIO DE FECHAMENTO DIÁRIO</h1>
                    <p>Resumo consolidado dos atendimentos realizados</p>
                    <p><strong>Emitido em:</strong> <?php echo $dataHoraEmissao->format('d/m/Y H:i'); ?></p>
                </td>
                <td style="width: 90px;"></td>
            </tr>
        </table>

        <table class="bloco-info">
            <tr>
                <td><strong>Data do fechamento:</strong> <?php echo htmlspecialchars($rel['data']); ?></td>
                <td><strong>Total registrado:</strong> <?php echo $totalPessoasAtendidas; ?> atendimentos</td>
            </tr>
            <tr>
                <td><strong>Prato Cheio:</strong> <?php echo htmlspecialchars($nomeBanco !== '' ? $nomeBanco : '-'); ?></td>
                <td><strong>Cardápio:</strong> <?php echo nl2br(htmlspecialchars($cardapio !== '' ? $cardapio : '-')); ?></td>
            </tr>
        </table>

        <table class="duas-colunas">
            <tr>
                <td class="col-esq">
                    <div class="card">
                        <div class="card-title">Faixa etária - Masculino</div>
                        <table class="report">
                            <tr>
                                <th>Categoria</th>
                                <th class="qtd">Qtd.</th>
                            </tr>
                            <tr>
                                <td>3 a 17 anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_3a17Masculino']; ?></td>
                            </tr>
                            <tr>
                                <td>3 a 17 anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_3a17Masculino_PCD']; ?></td>
                            </tr>
                            <tr>
                                <td>18 a 59 anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_18a59Masculino']; ?></td>
                            </tr>
                            <tr>
                                <td>18 a 59 anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_18a59Masculino_PCD']; ?></td>
                            </tr>
                            <tr>
                                <td>60+ anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_60Masculino']; ?></td>
                            </tr>
                            <tr>
                                <td>60+ anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_60Masculino_PCD']; ?></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="col-dir">
                    <div class="card">
                        <div class="card-title">Faixa etária - Feminino</div>
                        <table class="report">
                            <tr>
                                <th>Categoria</th>
                                <th class="qtd">Qtd.</th>
                            </tr>
                            <tr>
                                <td>3 a 17 anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_3a17Feminino']; ?></td>
                            </tr>
                            <tr>
                                <td>3 a 17 anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_3a17Feminino_PCD']; ?></td>
                            </tr>
                            <tr>
                                <td>17 a 59 anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_17a59Feminino']; ?></td>
                            </tr>
                            <tr>
                                <td>17 a 59 anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_17a59Feminino_PCD']; ?></td>
                            </tr>
                            <tr>
                                <td>60+ anos</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_60Feminino']; ?></td>
                            </tr>
                            <tr>
                                <td>60+ anos PCD</td>
                                <td class="qtd"><?php echo (int)$rel['Idade_60Feminino_PCD']; ?></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="card">
            <div class="card-title">Outros indicadores</div>
            <table class="report">
                <tr>
                    <th>Indicador</th>
                    <th class="qtd">Qtd.</th>
                </tr>
                <tr>
                    <td>Situação de risco masculino</td>
                    <td class="qtd"><?php echo $situacaoRiscoMasculino; ?></td>
                </tr>
                <tr>
                    <td>Situação de risco feminino</td>
                    <td class="qtd"><?php echo $situacaoRiscoFeminino; ?></td>
                </tr>
                <tr>
                    <td>Deficientes</td>
                    <td class="qtd"><?php echo $deficientesTotal; ?></td>
                </tr>
                <tr>
                    <td>Senhas genéricas</td>
                    <td class="qtd"><?php echo $senhasGenericasTotal; ?></td>
                </tr>
                <tr>
                    <td>Sem faixa/gÃªnero vÃ¡lido</td>
                    <td class="qtd"><?php echo $totalNaoClassificado; ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($observacaoIndicadores)) { ?>
            <div class="observacao-alerta">
                <div class="titulo">Observações importantes</div>
                <div class="conteudo">
                    <ul>
                        <?php foreach ($observacaoIndicadores as $obs) { ?>
                            <li><?php echo htmlspecialchars($obs); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>

        <table class="totais-grid">
            <tr>
                <td>
                    <div class="label">Total de pessoas atendidas</div>
                    <div class="valor"><?php echo $totalPessoasAtendidas; ?></div>
                </td>
                <td>
                    <div class="label">Total de refeições servidas</div>
                    <div class="valor"><?php echo $qtdRefeicoesServidas; ?></div>
                </td>
                <td>
                    <div class="label">Sobra de refeições</div>
                    <div class="valor <?php echo ($sobraRefeicoes > 0) ? 'sobra' : ''; ?>"><?php echo $sobraRefeicoes; ?></div>
                </td>
                <td>
                    <div class="label">% de utilização</div>
                    <div class="valor"><?php echo $percentualUtilizacaoFormatado; ?>%</div>
                </td>
            </tr>
        </table>

        <div class="ocorrencias-box">
            <div class="titulo">Ocorrências</div>
            <div class="conteudo"><?php echo nl2br(htmlspecialchars($ocorrencias)); ?></div>
        </div>

        <div class="assinatura-box">
            <div class="assinatura-nome">
                <?php echo htmlspecialchars($responsavelFechamento); ?>
                <?php if (!empty($cpfResponsavel)) { ?>
                    <br><span style="font-size:10px;">CPF: <?php echo htmlspecialchars($cpfResponsavel); ?></span>
                <?php } ?>
            </div>
            <div class="assinatura-cargo">Responsável pelo fechamento</div>
        </div>

        <div class="footer">Relatório gerado automaticamente pelo sistema</div>

    </body>

    </html>
<?php

    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfOutput = $dompdf->output();
    // ===============================
    // 🔥 NOME DO ARQUIVO PERSONALIZADO
    // ===============================
    $nomeBaseArquivo = trim((string)$nomeBanco);

    if ($nomeBaseArquivo === '') {
        $nomeBaseArquivo = 'relatorio';
    }

    // deixa minúsculo
    $nomeBaseArquivo = strtolower($nomeBaseArquivo);

    // remove acentos
    $nomeBaseArquivo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeBaseArquivo);

    // mantém só letras, números, _ e -
    $nomeBaseArquivo = preg_replace('/[^a-z0-9_-]+/i', '_', $nomeBaseArquivo);

    // remove duplicações
    $nomeBaseArquivo = preg_replace('/_+/', '_', $nomeBaseArquivo);

    // limpa bordas
    $nomeBaseArquivo = trim($nomeBaseArquivo, '_-');

    if ($nomeBaseArquivo === '') {
        $nomeBaseArquivo = 'relatorio';
    }

    // nome final
    $nomeArquivo = $nomeBaseArquivo . '_' . $dataSql . '.pdf';

    if ($upload === 1) {
        $dirTemp = getRelatorioTempDir($config);

        if (!is_dir($dirTemp)) {
            if (!mkdir($dirTemp, 0775, true) && !is_dir($dirTemp)) {
                $dadosHistorico = array(
                    'data_relatorio' => $dataSql,
                    'nome_arquivo' => $nomeArquivo,
                    'url_publica' => null,
                    'caminho_remoto' => null,
                    'status_upload' => 'ERRO',
                    'mensagem_erro' => 'Não foi possível criar a pasta temporária.',
                    'responsavel' => $responsavelFechamento,
                    'cpf_responsavel' => $cpfResponsavel
                );

                $resultadoHistorico = registrarHistoricoRelatorioEmAmbosBancos($sql, $dadosHistorico, $config);
                $resultadoHistorico = garantirHistoricoRelatorioRemoto($resultadoHistorico, $dadosHistorico, $config);

                responderJson(array(
                    'success' => false,
                    'message' => 'Não foi possível criar a pasta temporária.'
                ));
            }
        }

        $caminhoLocal = $dirTemp . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (file_put_contents($caminhoLocal, $pdfOutput) === false) {
            $dadosHistorico = array(
                'data_relatorio' => $dataSql,
                'nome_arquivo' => $nomeArquivo,
                'url_publica' => null,
                'caminho_remoto' => null,
                'status_upload' => 'ERRO',
                'mensagem_erro' => 'Não foi possível salvar o PDF temporariamente.',
                'responsavel' => $responsavelFechamento,
                'cpf_responsavel' => $cpfResponsavel
            );

            $resultadoHistorico = registrarHistoricoRelatorioEmAmbosBancos($sql, $dadosHistorico, $config);
            $resultadoHistorico = garantirHistoricoRelatorioRemoto($resultadoHistorico, $dadosHistorico, $config);

            responderJson(array(
                'success' => false,
                'message' => 'Não foi possível salvar o PDF temporariamente.'
            ));
        }

        try {
            $uploadInfo = uploadRelatorioRemoto($config, $caminhoLocal, $nomeArquivo);

            $dadosHistorico = array(
                'data_relatorio' => $dataSql,
                'nome_arquivo' => $nomeArquivo,
                'url_publica' => $uploadInfo['url_publica'],
                'caminho_remoto' => $uploadInfo['remote_file'],
                'status_upload' => 'SUCESSO',
                'mensagem_erro' => null,
                'responsavel' => $responsavelFechamento,
                'cpf_responsavel' => $cpfResponsavel
            );

            $resultadoHistorico = registrarHistoricoRelatorioEmAmbosBancos($sql, $dadosHistorico, $config);
            $resultadoHistorico = garantirHistoricoRelatorioRemoto($resultadoHistorico, $dadosHistorico, $config);

            $idHistorico = isset($resultadoHistorico['local_id']) ? (int)$resultadoHistorico['local_id'] : 0;

            escreverLogRelatorio($config, 'SUCESSO upload PDF: ' . $nomeArquivo . ' | URL: ' . $uploadInfo['url_publica']);

            responderJson(array(
                'success' => true,
                'message' => 'PDF gerado e enviado com sucesso.',
                'arquivo' => $nomeArquivo,
                'url_publica' => $uploadInfo['url_publica'],
                'id_historico' => $idHistorico,
                'id_historico_remoto' => isset($resultadoHistorico['remoto_id']) ? (int)$resultadoHistorico['remoto_id'] : 0,
                'historico_remoto_ok' => !empty($resultadoHistorico['remoto_ok']),
                'erro_historico_remoto' => isset($resultadoHistorico['erro_remoto']) ? $resultadoHistorico['erro_remoto'] : null
            ));
        } catch (\Exception $e) {
            escreverLogRelatorio($config, 'ERRO upload PDF: ' . $nomeArquivo . ' | ' . $e->getMessage());

            $dadosHistorico = array(
                'data_relatorio' => $dataSql,
                'nome_arquivo' => $nomeArquivo,
                'url_publica' => null,
                'caminho_remoto' => null,
                'status_upload' => 'ERRO',
                'mensagem_erro' => $e->getMessage(),
                'responsavel' => $responsavelFechamento,
                'cpf_responsavel' => $cpfResponsavel
            );

            $resultadoHistorico = registrarHistoricoRelatorioEmAmbosBancos($sql, $dadosHistorico, $config);
            $resultadoHistorico = garantirHistoricoRelatorioRemoto($resultadoHistorico, $dadosHistorico, $config);

            $idHistorico = isset($resultadoHistorico['local_id']) ? (int)$resultadoHistorico['local_id'] : 0;

            responderJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'id_historico' => $idHistorico,
                'id_historico_remoto' => isset($resultadoHistorico['remoto_id']) ? (int)$resultadoHistorico['remoto_id'] : 0,
                'historico_remoto_ok' => !empty($resultadoHistorico['remoto_ok']),
                'erro_historico_remoto' => isset($resultadoHistorico['erro_remoto']) ? $resultadoHistorico['erro_remoto'] : null
            ));
        }
    }

    limparBufferSaida();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
    header('Content-Length: ' . strlen($pdfOutput));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    echo $pdfOutput;
    exit;
});


/*
|--------------------------------------------------------------------------
| DASHBOARD EXECUTIVO / COMPARATIVOS / RANKING / ALERTAS / INSIGHTS
|--------------------------------------------------------------------------
*/

if (!function_exists('backupExecutarComEnvio')) {
    function backupExecutarComEnvio($force = false)
    {
        $resultado = array(
            'success' => false,
            'message' => '',
            'backup_file' => null,
            'upload' => null
        );

        try {
            if (!function_exists('backupAutomatico')) {
                throw new Exception('backupAutomatico() não está disponível.');
            }

            backupAutomatico($force ? true : false, 0, 'backup_manual_envio');

            $arquivoZip = null;

            if (defined('BACKUP_DIR') && is_dir(BACKUP_DIR)) {
                $arquivos = glob(rtrim(BACKUP_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.zip');
                if ($arquivos) {
                    usort($arquivos, function ($a, $b) {
                        return filemtime($b) <=> filemtime($a);
                    });
                    $arquivoZip = $arquivos[0];
                }
            }

            if (!$arquivoZip || !file_exists($arquivoZip)) {
                throw new Exception('Backup executado, mas o arquivo ZIP não foi localizado.');
            }

            $resultado['backup_file'] = $arquivoZip;

            if (!function_exists('getRelatorioUploadConfig') || !function_exists('uploadRelatorioRemoto')) {
                throw new Exception('Funções de upload remoto não estão disponíveis.');
            }

            $config = getRelatorioUploadConfig();
            $nomeArquivo = basename($arquivoZip);

            $upload = uploadRelatorioRemoto($config, $arquivoZip, $nomeArquivo);
            $resultado['upload'] = $upload;
            $resultado['success'] = true;
            $resultado['message'] = 'Backup executado e enviado com sucesso.';

            if (function_exists('escreverLogRelatorio')) {
                escreverLogRelatorio($config, 'SUCESSO envio de backup: ' . $nomeArquivo . ' | URL: ' . $upload['url_publica']);
            }

            return $resultado;
        } catch (Exception $e) {
            $resultado['message'] = $e->getMessage();

            if (function_exists('getRelatorioUploadConfig') && function_exists('escreverLogRelatorio')) {
                try {
                    $config = getRelatorioUploadConfig();
                    escreverLogRelatorio($config, 'ERRO backup+envio: ' . $e->getMessage());
                } catch (Exception $ignored) {
                }
            }

            return $resultado;
        }
    }
}

if (!function_exists('dashboardInsightsGerais')) {
    function dashboardInsightsGerais(array $comparativoMes, array $comparativoAno, array $metricas = array())
    {
        $insights = array();

        if (count($comparativoMes) >= 2) {
            $ultimo = $comparativoMes[count($comparativoMes) - 1];
            $anterior = $comparativoMes[count($comparativoMes) - 2];

            $atUltimo = isset($ultimo['atendimentos']) ? (int)$ultimo['atendimentos'] : 0;
            $atAnterior = isset($anterior['atendimentos']) ? (int)$anterior['atendimentos'] : 0;

            if ($atAnterior > 0) {
                $variacao = (($atUltimo - $atAnterior) / $atAnterior) * 100;
                if ($variacao <= -20) {
                    $insights[] = array(
                        'titulo' => 'Queda de atendimento',
                        'mensagem' => 'Os atendimentos do último dia ficaram ' . number_format(abs($variacao), 1, ',', '.') . '% abaixo do dia anterior.'
                    );
                } elseif ($variacao >= 20) {
                    $insights[] = array(
                        'titulo' => 'Alta de atendimento',
                        'mensagem' => 'Os atendimentos do último dia ficaram ' . number_format($variacao, 1, ',', '.') . '% acima do dia anterior.'
                    );
                }
            }
        }

        $uploadsErro = isset($metricas['uploads_erro']) ? (int)$metricas['uploads_erro'] : 0;
        if ($uploadsErro > 0) {
            $insights[] = array(
                'titulo' => 'Uploads com falha',
                'mensagem' => 'Existem ' . $uploadsErro . ' registro(s) de PDF com erro no histórico. Vale revisar a fila de reenvio.'
            );
        }

        $backupsErro = isset($metricas['backups_erro']) ? (int)$metricas['backups_erro'] : 0;
        if ($backupsErro > 0) {
            $insights[] = array(
                'titulo' => 'Falhas de backup',
                'mensagem' => 'Foram detectadas ' . $backupsErro . ' ocorrência(s) de backup com falha nos logs recentes.'
            );
        }

        if (count($comparativoAno) >= 2) {
            $ultimoMes = $comparativoAno[count($comparativoAno) - 1];
            $mesAnterior = $comparativoAno[count($comparativoAno) - 2];
            $v1 = isset($ultimoMes['atendimentos']) ? (int)$ultimoMes['atendimentos'] : 0;
            $v0 = isset($mesAnterior['atendimentos']) ? (int)$mesAnterior['atendimentos'] : 0;

            if ($v0 > 0) {
                $varMensal = (($v1 - $v0) / $v0) * 100;
                $insights[] = array(
                    'titulo' => 'Comparação mensal',
                    'mensagem' => 'O mês atual está com variação de ' . number_format($varMensal, 1, ',', '.') . '% em atendimentos em relação ao mês anterior.'
                );
            }
        }

        if (empty($insights)) {
            $insights[] = array(
                'titulo' => 'Operação estável',
                'mensagem' => 'Não foram detectadas anomalias relevantes com os dados recentes.'
            );
        }

        return $insights;
    }
}

$app->get('/admin/dashboard/geral', function () {
    Funcionarios::checkPermission('DASHBOARD_VIEW');
    $page = new PageAdmin();
    $page->setTpl('dashboard-geral');
});

$app->get('/admin/api/dashboard/geral', function () {

    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('DASHBOARD_VIEW');
        $sql = new Sql();

        $mes = isset($_GET['mes']) && trim($_GET['mes']) !== '' ? trim($_GET['mes']) : date('Y-m');
        $ano = isset($_GET['ano']) && (int)$_GET['ano'] > 0 ? (int)$_GET['ano'] : (int)date('Y');
        $rankingPeriodo = isset($_GET['ranking_periodo']) ? strtoupper(trim($_GET['ranking_periodo'])) : 'MES';
        $rankingOrder = isset($_GET['ranking_order']) ? strtoupper(trim($_GET['ranking_order'])) : 'REFEICOES';

        $titulares = $sql->select("SELECT COUNT(*) AS total FROM tb_titular");
        $dependentes = $sql->select("SELECT COUNT(*) AS total FROM tb_dependentes");
        $pdfs = $sql->select("SELECT COUNT(*) AS total FROM tb_relatorios_pdf");
        $pdfsStatus = $sql->select("
            SELECT status_upload, COUNT(*) AS total
            FROM tb_relatorios_pdf
            GROUP BY status_upload
        ");

        $hoje = date('Y-m-d');
        $relHoje = $sql->select("
            SELECT 
                COALESCE(Total_pessoas_atendidas,0) AS atendimentos_hoje,
                COALESCE(qtd_refeicoes_servidas,0) AS refeicoes_hoje
            FROM tb_relatorios
            WHERE data = :data
            LIMIT 1
        ", array(':data' => $hoje));

        $comparativoMes = $sql->select("
            SELECT 
                data,
                COALESCE(Total_pessoas_atendidas,0) AS atendimentos,
                COALESCE(qtd_refeicoes_servidas,0) AS refeicoes
            FROM tb_relatorios
            WHERE DATE_FORMAT(data, '%Y-%m') = :mes
            ORDER BY data ASC
        ", array(':mes' => $mes));

        $comparativoAnoRows = $sql->select("
            SELECT 
                MONTH(data) AS mes_num,
                SUM(COALESCE(Total_pessoas_atendidas,0)) AS atendimentos,
                SUM(COALESCE(qtd_refeicoes_servidas,0)) AS refeicoes
            FROM tb_relatorios
            WHERE YEAR(data) = :ano
            GROUP BY MONTH(data)
            ORDER BY MONTH(data)
        ", array(':ano' => $ano));

        $nomesMes = array(1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez');
        $comparativoAno = array();
        foreach ($comparativoAnoRows as $row) {
            $mesNum = (int)$row['mes_num'];
            $comparativoAno[] = array(
                'mes_num' => $mesNum,
                'mes_label' => isset($nomesMes[$mesNum]) ? $nomesMes[$mesNum] : (string)$mesNum,
                'atendimentos' => (int)$row['atendimentos'],
                'refeicoes' => (int)$row['refeicoes']
            );
        }

        $ranking = array();
        if (method_exists('Sql', 'select')) {
            $wherePeriodo = '';
            $paramsRanking = array();

            if ($rankingPeriodo === 'ANO') {
                $wherePeriodo = " AND YEAR(s.data_refeicao) = :ano ";
                $paramsRanking[':ano'] = $ano;
            } else {
                $wherePeriodo = " AND DATE_FORMAT(s.data_refeicao, '%Y-%m') = :mes ";
                $paramsRanking[':mes'] = $mes;
            }

            $orderBy = $rankingOrder === 'FREQ' ? 'frequencia DESC, total_refeicoes DESC' : 'total_refeicoes DESC, frequencia DESC';

            try {
                $ranking = $sql->select("
                    SELECT 
                        t.nome_completo AS nome,
                        t.cpf,
                        COUNT(*) AS total_refeicoes,
                        COUNT(DISTINCT s.data_refeicao) AS frequencia
                    FROM tb_senhas s
                    INNER JOIN tb_titular t ON t.id = s.id_titular
                    WHERE s.id_titular IS NOT NULL
                    {$wherePeriodo}
                    GROUP BY t.id, t.nome_completo, t.cpf
                    ORDER BY {$orderBy}
                    LIMIT 10
                ", $paramsRanking);
            } catch (Exception $e) {
                $ranking = array();
            }
        }

        $ultimoBackup = function_exists('getUltimoBackup') ? getUltimoBackup() : null;
        $logsBackup = function_exists('getBackupNotifications') ? getBackupNotifications(10) : array();

        $remoteDbOk = false;
        $remoteDbMessage = 'Banco remoto não testado.';
        if (function_exists('getPdoRelatorioRemoto')) {
            try {
                $pdo = getPdoRelatorioRemoto();
                if ($pdo instanceof PDO) {
                    $pdo->query('SELECT 1');
                    $remoteDbOk = true;
                    $remoteDbMessage = 'Conexão remota validada com sucesso.';
                } else {
                    $remoteDbMessage = 'Banco remoto desativado na configuração.';
                }
            } catch (Exception $e) {
                $remoteDbMessage = $e->getMessage();
            }
        }

        $uploadsOk = 0;
        $uploadsErro = 0;
        foreach ($pdfsStatus as $row) {
            if (strtoupper((string)$row['status_upload']) === 'SUCESSO') $uploadsOk = (int)$row['total'];
            if (strtoupper((string)$row['status_upload']) === 'ERRO') $uploadsErro = (int)$row['total'];
        }

        $recentesPdfs = $sql->select("
            SELECT id, nome_arquivo, status_upload, responsavel, data_geracao
            FROM tb_relatorios_pdf
            ORDER BY id DESC
            LIMIT 5
        ");

        $recentesBackups = array();
        $backupsEnvio = 0;
        $backupsErro = 0;
        foreach ($logsBackup as $item) {
            $mensagem = isset($item['mensagem']) ? (string)$item['mensagem'] : '';
            $status = stripos($mensagem, 'erro') !== false ? 'ERRO' : 'SUCESSO';
            if (stripos($mensagem, 'envio') !== false || stripos($mensagem, 'upload') !== false) $backupsEnvio++;
            if ($status === 'ERRO') $backupsErro++;
            $recentesBackups[] = array(
                'contexto' => isset($item['contexto']) ? $item['contexto'] : 'backup',
                'status' => $status,
                'data_execucao' => isset($item['data']) ? $item['data'] : null,
                'mensagem' => $mensagem
            );
        }

        $rankingOut = array();
        foreach ($ranking as $item) {
            $rankingOut[] = array(
                'nome' => isset($item['nome']) ? $item['nome'] : 'Titular',
                'cpf' => isset($item['cpf']) ? $item['cpf'] : '',
                'valor' => $rankingOrder === 'FREQ' ? (int)$item['frequencia'] : (int)$item['total_refeicoes'],
                'criterio' => $rankingOrder === 'FREQ' ? 'Frequência' : 'Refeições'
            );
        }

        $insights = dashboardInsightsGerais($comparativoMes, $comparativoAno, array(
            'uploads_erro' => $uploadsErro,
            'backups_erro' => $backupsErro
        ));

        echo json_encode(array(
            'success' => true,
            'total_titulares' => isset($titulares[0]['total']) ? (int)$titulares[0]['total'] : 0,
            'total_dependentes' => isset($dependentes[0]['total']) ? (int)$dependentes[0]['total'] : 0,
            'total_pdfs' => isset($pdfs[0]['total']) ? (int)$pdfs[0]['total'] : 0,
            'atendimentos_hoje' => isset($relHoje[0]['atendimentos_hoje']) ? (int)$relHoje[0]['atendimentos_hoje'] : 0,
            'refeicoes_hoje' => isset($relHoje[0]['refeicoes_hoje']) ? (int)$relHoje[0]['refeicoes_hoje'] : 0,
            'uploads_ok' => $uploadsOk,
            'uploads_erro' => $uploadsErro,
            'ultimo_backup' => $ultimoBackup,
            'recentes_pdfs' => $recentesPdfs,
            'recentes_backups' => $recentesBackups,
            'remote_db_ok' => $remoteDbOk,
            'remote_db_message' => $remoteDbMessage,
            'backup_send_enabled' => true,
            'backups_envio' => $backupsEnvio,
            'backups_erro' => $backupsErro,
            'comparativo_mes' => $comparativoMes,
            'comparativo_ano' => $comparativoAno,
            'ranking' => $rankingOut,
            'insights' => $insights
        ));
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        exit;
    }
});

$app->post('/admin/api/backup/run-and-send', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('BACKUP_RUN');
        $resultado = backupExecutarComEnvio(true);

        echo json_encode(array(
            'success' => !empty($resultado['success']),
            'message' => isset($resultado['message']) ? $resultado['message'] : 'Backup executado.',
            'data' => $resultado
        ));
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage(),
            'error' => $e->getMessage()
        ));
        exit;
    }
});
