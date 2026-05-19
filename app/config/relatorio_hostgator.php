<?php

if (!function_exists('pc_env')) {
    require_once dirname(__DIR__, 2) . '/config/env.php';
    pc_load_env(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
}

$host = pc_env('RELATORIO_FTP_HOST', 'localhost');
$user = pc_env('RELATORIO_FTP_USER', 'user');
$pass = pc_env('RELATORIO_FTP_PASSWORD', '');

return array(
    'host' => $host,
    'user' => $user,
    'pass' => $pass,
    'port' => (int) pc_env('RELATORIO_FTP_PORT', 21),
    'base_path' => pc_env('RELATORIO_REMOTE_DIR', '/public_html/relatorios/'),
    'ftp_host' => $host,
    'ftp_user' => $user,
    'ftp_pass' => $pass
);
