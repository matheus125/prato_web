<?php

require 'functions.php';

echo "<pre>";

try {
    $internet = new InternetService();

    if (!$internet->isConnected()) {
        throw new Exception('Sem conexão com a internet');
    }

    echo "Internet OK\n";

    $backup = new BackupService();
    $file = $backup->createBackup('prato_cheio');

    echo "Backup criado: {$file}\n";

    $upload = new UploadService();
    $upload->send($file);

    echo "UPLOAD REALIZADO COM SUCESSO\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}

echo "</pre>";
