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
    'sftp_host' => pc_env('RELATORIO_SFTP_HOST', pc_env('BACKUP_SFTP_HOST', '')),
    'sftp_user' => pc_env('RELATORIO_SFTP_USER', pc_env('BACKUP_SFTP_USER', '')),
    'sftp_pass' => pc_env('RELATORIO_SFTP_PASSWORD', pc_env('BACKUP_SFTP_PASSWORD', '')),
    'sftp_port' => (int) pc_env('RELATORIO_SFTP_PORT', pc_env('BACKUP_SFTP_PORT', 22)),
    'sftp_remote_dir' => pc_env('RELATORIO_SFTP_REMOTE_DIR', '/home2/mat06153/prato-cheio.ms-tecnologia.app.br/prato/relatorios'),
    'remote_dir' => pc_env('RELATORIO_REMOTE_DIR', '/prato/relatorios'),
    'public_base_url' => pc_env('RELATORIO_PUBLIC_BASE_URL', 'https://prato-cheio.ms-tecnologia.app.br/prato/relatorios'),
    'local_public_dir' => pc_env('RELATORIO_LOCAL_PUBLIC_DIR', 'prato/relatorios'),
    'temp_dir_name' => pc_env('RELATORIO_TEMP_DIR', 'tmp_relatorios'),
    'log_file' => pc_env('RELATORIO_LOG_FILE', 'relatorio_upload.log')
);
