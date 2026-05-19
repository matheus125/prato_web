<?php

return array(
    'driver' => 'ftp',

    // FTP
    'host' => 'prato-cheio.ms-tecnologia.app.br',
    'user' => 'prato@prato-cheio.ms-tecnologia.app.br',
    'pass' => 'MM@t@13192921',
    'port' => 21,

    // Caminho remoto relativo à raiz da conta FTP
    'remote_dir' => '/relatorios',

    // URL pública final
    'public_base_url' => 'https://prato-cheio.ms-tecnologia.app.br/prato/relatorios/',

    // pasta temporária local
    'temp_dir_name' => 'tmp_relatorios',

    // log
    'log_file' => 'relatorio_upload.log'
);
