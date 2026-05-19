<?php

if (!function_exists('pc_env')) {
    require_once dirname(__DIR__, 2) . '/config/env.php';
    pc_load_env(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
}

return array(
    'enabled' => pc_env_bool('RELATORIO_REMOTE_DB_ENABLED', false),
    'host' => pc_env('RELATORIO_REMOTE_DB_HOST', ''),
    'port' => (int) pc_env('RELATORIO_REMOTE_DB_PORT', 3306),
    'dbname' => pc_env('RELATORIO_REMOTE_DB_NAME', ''),
    'user' => pc_env('RELATORIO_REMOTE_DB_USER', ''),
    'pass' => pc_env('RELATORIO_REMOTE_DB_PASSWORD', ''),
    'charset' => pc_env('RELATORIO_REMOTE_DB_CHARSET', 'utf8mb4')
);
