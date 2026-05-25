<?php

namespace Hcode\Backup;

use CURLFile;
use Exception;
use phpseclib3\Net\SFTP;
use RuntimeException;

class UploadService
{
    private int $timeoutSeconds = 60;
    private int $connectTimeoutSeconds = 15;

    public function send(string $filePath, string $contexto = 'backup'): void
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("Arquivo de backup nao encontrado: {$filePath}");
        }

        $sentFlag = $filePath . '.sent';
        $lockFlag = $filePath . '.uploading';

        if (is_file($sentFlag)) {
            return;
        }

        if (is_file($lockFlag)) {
            $lockTime = (int)@file_get_contents($lockFlag);
            if ($lockTime > 0 && (time() - $lockTime) < 600) {
                return;
            }
        }

        @file_put_contents($lockFlag, (string)time());

        try {
            if ($this->httpUploadConfigured()) {
                $this->sendHttp($filePath);
            } elseif ($this->sftpConfigured()) {
                $this->sendSftp($filePath);
            } else {
                throw new RuntimeException('Nenhuma configuracao de envio de backup encontrada. Configure BACKUP_UPLOAD_URL/TOKEN ou BACKUP_SFTP_* no .env.');
            }

            file_put_contents($sentFlag, 'OK|' . date('Y-m-d H:i:s') . '|' . $contexto);
        } finally {
            @unlink($lockFlag);
        }
    }

    private function sendHttp(string $filePath): void
    {
        $uploadUrl = trim((string)\pc_env('BACKUP_UPLOAD_URL', ''));
        $token = trim((string)\pc_env('BACKUP_UPLOAD_TOKEN', ''));

        $ch = curl_init($uploadUrl);
        if ($ch === false) {
            throw new RuntimeException('Falha ao inicializar cURL.');
        }

        $post = array(
            'backup' => new CURLFile($filePath, 'application/sql', basename($filePath)),
            'token' => $token
        );

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => array('Expect:'),
        ));

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $errNo = curl_errno($ch);
            $errMsg = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erro cURL ({$errNo}): {$errMsg}");
        }

        curl_close($ch);
        $responseTrim = trim((string)$response);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Upload falhou. HTTP={$httpCode}. Resposta='{$responseTrim}'");
        }

        if ($responseTrim !== 'UPLOAD_OK') {
            throw new Exception("Upload falhou. Resposta inesperada: '{$responseTrim}'");
        }
    }

    private function sendSftp(string $filePath): void
    {
        $host = trim((string)\pc_env('BACKUP_SFTP_HOST', ''));
        $port = (int)\pc_env('BACKUP_SFTP_PORT', 22);
        $user = trim((string)\pc_env('BACKUP_SFTP_USER', ''));
        $password = (string)\pc_env('BACKUP_SFTP_PASSWORD', '');
        $remoteDir = rtrim(trim((string)\pc_env('BACKUP_SFTP_REMOTE_DIR', '')), '/');

        $sftp = new SFTP($host, $port, $this->connectTimeoutSeconds);
        if (!$sftp->login($user, $password)) {
            throw new RuntimeException('Falha ao autenticar no SFTP de backup.');
        }

        $this->ensureRemoteDir($sftp, $remoteDir);

        $remoteFile = $remoteDir . '/' . basename($filePath);
        if (!$sftp->put($remoteFile, $filePath, SFTP::SOURCE_LOCAL_FILE)) {
            throw new RuntimeException('Falha ao enviar backup por SFTP.');
        }
    }

    private function ensureRemoteDir(SFTP $sftp, string $remoteDir): void
    {
        if ($remoteDir === '') {
            throw new RuntimeException('BACKUP_SFTP_REMOTE_DIR nao configurado.');
        }

        $current = '';
        foreach (explode('/', trim($remoteDir, '/')) as $part) {
            if ($part === '') {
                continue;
            }

            $current .= '/' . $part;
            if (!$sftp->is_dir($current) && !$sftp->mkdir($current)) {
                throw new RuntimeException("Nao foi possivel criar diretorio remoto de backup: {$current}");
            }
        }
    }

    private function httpUploadConfigured(): bool
    {
        return function_exists('pc_env')
            && trim((string)\pc_env('BACKUP_UPLOAD_URL', '')) !== ''
            && trim((string)\pc_env('BACKUP_UPLOAD_TOKEN', '')) !== '';
    }

    private function sftpConfigured(): bool
    {
        return function_exists('pc_env')
            && trim((string)\pc_env('BACKUP_SFTP_HOST', '')) !== ''
            && trim((string)\pc_env('BACKUP_SFTP_USER', '')) !== ''
            && trim((string)\pc_env('BACKUP_SFTP_PASSWORD', '')) !== ''
            && trim((string)\pc_env('BACKUP_SFTP_REMOTE_DIR', '')) !== '';
    }
}
