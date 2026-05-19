<?php

function gerarNomePdfRelatorio($data)
{
    // Nome fixo por data (NÃO MUDA MAIS)
    return "centro_" . $data . ".pdf";
}

function uploadPdfHostgator($localFile, $remoteFile)
{
    $config = require __DIR__ . '/../config/relatorio_hostgator.php';

    $conn = ftp_connect($config['host'], $config['port']);
    if (!$conn) {
        throw new Exception("Erro ao conectar no FTP");
    }

    if (!ftp_login($conn, $config['user'], $config['pass'])) {
        throw new Exception("Erro ao autenticar no FTP");
    }

    ftp_pasv($conn, true);

    // 🔥 AQUI É O SEGREDO → SOBRESCREVE
    if (!ftp_put($conn, $remoteFile, $localFile, FTP_BINARY)) {
        throw new Exception("Erro ao enviar arquivo para FTP");
    }

    ftp_close($conn);

    return true;
}
