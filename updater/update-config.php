<?php

return array(
    // URL do arquivo remoto que informa a versão disponível
    'remote_version_url' => 'https://prato-cheio.ms-tecnologia.app.br/prato/updates/version.json',

    // Caminho temporário para baixar e extrair atualização
    'temp_path' => __DIR__ . '/../storage/update-temp/',

    // Caminho para backup antes da atualização
    'backup_path' => __DIR__ . '/../backups/updates/',

    // Tempo limite para download
    'download_timeout' => 120,

    // Estes caminhos NÃO serão sobrescritos pelo pacote ZIP
    'protected_paths' => array(
        '.env',
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
