<?php
session_start();

// Paths/constants first
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/php-compat.php';

// Composer autoload
require_once ROOT_DIR . '/vendor/autoload.php';

use Slim\Slim;
use Hcode\Middleware\PerfilMiddleware;

$app = new Slim();
$app->config('debug', true);

// 🔐 Middleware GLOBAL (ANTES DAS ROTAS)
$app->add(new PerfilMiddleware());

// Helpers + routes + JWT
require_once APP_DIR . '/helpers/functions.php';

require_once APP_DIR . '/routes/admin.php';
require_once APP_DIR . '/routes/admin-funcionarios.php';
require_once APP_DIR . '/routes/admin-dependentes.php';
require_once APP_DIR . '/routes/admin-clientes.php';
require_once APP_DIR . '/routes/admin-socio-economico.php';
require_once APP_DIR . '/routes/admin-vendas.php';
require_once APP_DIR . '/routes/admin-relatorio.php';

require_once APP_DIR . '/core/jwt.php';

$app->run();
