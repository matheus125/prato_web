<?php

if (!function_exists('backupExecutarComEnvio')) {
    function backupExecutarComEnvio($force = false)
    {
        $resultado = array(
            'success' => false,
            'message' => '',
            'backup_file' => null,
            'upload' => null
        );

        try {
            if (!function_exists('backupAutomatico')) {
                throw new Exception('backupAutomatico() não está disponível.');
            }

            backupAutomatico($force ? true : false, 0, 'backup_manual_envio');

            $arquivoBackup = null;

            if (function_exists('getUltimoBackup')) {
                $ultimo = getUltimoBackup();
                if ($ultimo && is_string($ultimo) && file_exists($ultimo)) {
                    $arquivoBackup = $ultimo;
                }
            }

            if (!$arquivoBackup && defined('BACKUP_DIR') && is_dir(BACKUP_DIR)) {
                $arquivos = array_merge(
                    glob(rtrim(BACKUP_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') ?: array(),
                    glob(rtrim(BACKUP_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.zip') ?: array()
                );
                if ($arquivos) {
                    usort($arquivos, function ($a, $b) {
                        return filemtime($b) <=> filemtime($a);
                    });
                    $arquivoBackup = $arquivos[0];
                }
            }

            if (!$arquivoBackup || !file_exists($arquivoBackup)) {
                throw new Exception('Backup executado, mas o arquivo SQL/ZIP não foi localizado.');
            }

            $resultado['backup_file'] = $arquivoBackup;

            if (!function_exists('getRelatorioUploadConfig') || !function_exists('uploadRelatorioRemoto')) {
                throw new Exception('Funções de upload remoto não estão disponíveis.');
            }

            $config = getRelatorioUploadConfig();
            $nomeArquivo = basename($arquivoBackup);

            $upload = uploadRelatorioRemoto($config, $arquivoBackup, $nomeArquivo);
            $resultado['upload'] = $upload;

            if (function_exists('registrarBackupRemoto')) {
                $nomeBanco = preg_replace('/_\d{4}-\d{2}-\d{2}.*$/', '', pathinfo($nomeArquivo, PATHINFO_FILENAME));
                $resultado['remote_db'] = registrarBackupRemoto(array(
                    'nome_banco' => $nomeBanco,
                    'arquivo' => $arquivoBackup,
                    'status_upload' => 'SUCESSO',
                    'caminho_remoto' => isset($upload['remote_file']) ? $upload['remote_file'] : (isset($upload['url_publica']) ? $upload['url_publica'] : null)
                ));
            }

            $resultado['success'] = true;
            $resultado['message'] = 'Backup executado e enviado com sucesso.';

            if (function_exists('escreverLogRelatorio')) {
                escreverLogRelatorio($config, 'SUCESSO envio de backup: ' . $nomeArquivo . ' | URL: ' . $upload['url_publica']);
            }

            return $resultado;
        } catch (\Exception $e) {
            $resultado['message'] = $e->getMessage();

            if (function_exists('getRelatorioUploadConfig') && function_exists('escreverLogRelatorio')) {
                try {
                    $config = getRelatorioUploadConfig();
                    escreverLogRelatorio($config, 'ERRO backup+envio: ' . $e->getMessage());
                } catch (\Exception $ignored) {
                }
            }

            return $resultado;
        }
    }
}
