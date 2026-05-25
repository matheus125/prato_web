<?php

return array(
    // URL do arquivo remoto que informa a versão disponível
    'remote_version_url' => 'https://prato-cheio.ms-tecnologia.app.br/prato/updates/version.json',

    // Caminho temporário para baixar e extrair atualização
    'temp_path' => __DIR__ . '/../storage/update-temp/',

    // Caminho para backup antes da atualização
    'backup_path' => __DIR__ . '/../backups/updates/',

    // Tempo limite para download
    'download_timeout' => 900,
    'download_retries' => 5,
    'download_low_speed_limit' => 1024,
    'download_low_speed_time' => 120,

    // Estes caminhos NÃO serão sobrescritos pelo pacote ZIP.
    // O .env local guarda DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD
    // exclusivos de cada unidade e deve permanecer fora das atualizações.
    // Variáveis globais podem ser distribuídas por .env.update e mescladas no .env.
    'protected_paths' => array(
        '.env',
        '.env.local',
        '.env.production',
        '.env.prod',
        '.env.development',
        '.env.dev',
        'config.php',
        'config/paths.php',
        'app/config/database.php',
        'mysql.cnf',

        'uploads/',
        'storage/',
        'logs/',
        'views-cache/',
        'vendor/',
        'vendor/API/php-classes/src/DB/',
        'vendor/API/php-classes/src/DB/Sql.php',
        'storage/config/system-settings.php',
        'backups/',

        'updater/update-config.php',
        'updater/version-local.json'
    )
);
