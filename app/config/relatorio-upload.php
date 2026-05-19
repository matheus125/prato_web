<?php

if (!function_exists('pc_env')) {
    require_once dirname(__DIR__, 2) . '/config/env.php';
    pc_load_env(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
}

return array(
    'driver' => pc_env('RELATORIO_UPLOAD_DRIVER', 'ftp'),
    'host' => pc_env('RELATORIO_FTP_HOST', ''),
    'user' => pc_env('RELATORIO_FTP_USER', ''),
    'pass' => pc_env('RELATORIO_FTP_PASSWORD', ''),
    'port' => (int) pc_env('RELATORIO_FTP_PORT', 21),
    'remote_dir' => pc_env('RELATORIO_REMOTE_DIR', '/relatorios'),
    'public_base_url' => pc_env('RELATORIO_PUBLIC_BASE_URL', ''),
    'local_public_dir' => pc_env('RELATORIO_LOCAL_PUBLIC_DIR', 'relatorios'),
    'temp_dir_name' => pc_env('RELATORIO_TEMP_DIR', 'tmp_relatorios'),
    'log_file' => pc_env('RELATORIO_LOG_FILE', 'relatorio_upload.log')
);
