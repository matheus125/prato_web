<?php
// Paths/constants first
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/php-compat.php';

if (session_status() === PHP_SESSION_NONE) {
    $sessionName = pc_env('SESSION_NAME', 'PRATO_CHEIO_SESS');
    if (is_string($sessionName) && $sessionName !== '') {
        session_name($sessionName);
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
    }

    session_start();
}

// Composer autoload
require_once ROOT_DIR . '/vendor/autoload.php';

use Slim\Slim;
use Hcode\Middleware\PerfilMiddleware;

$app = new Slim();
$app->config('debug', pc_env_bool('APP_DEBUG', false));

// 🔐 Middleware GLOBAL (ANTES DAS ROTAS)
$app->add(new PerfilMiddleware());

// Helpers + routes + JWT
require_once APP_DIR . '/helpers/functions.php';
require_once APP_DIR . '/helpers/view-cache.php';
limparCacheViewsAutomatico();

if (function_exists('csrf_verify_request')) {
    $app->hook('slim.before.dispatch', function () use ($app) {
        csrf_verify_request($app);
    });
}


require_once APP_DIR . '/routes/admin.php';
require_once APP_DIR . '/routes/admin-funcionarios.php';
require_once APP_DIR . '/routes/admin-dependentes.php';
require_once APP_DIR . '/routes/admin-clientes.php';
require_once APP_DIR . '/routes/admin-socio-economico.php';
require_once APP_DIR . '/routes/admin-vendas.php';
require_once APP_DIR . '/routes/admin-relatorio.php';

require_once APP_DIR . '/core/jwt.php';

$app->run();
