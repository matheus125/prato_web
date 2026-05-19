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

            $arquivoZip = null;

            if (function_exists('getUltimoBackup')) {
                $ultimo = getUltimoBackup();
                if ($ultimo && is_string($ultimo) && file_exists($ultimo)) {
                    $arquivoZip = $ultimo;
                }
            }

            if (!$arquivoZip && defined('BACKUP_DIR') && is_dir(BACKUP_DIR)) {
                $arquivos = glob(rtrim(BACKUP_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.zip');
                if ($arquivos) {
                    usort($arquivos, function ($a, $b) {
                        return filemtime($b) <=> filemtime($a);
                    });
                    $arquivoZip = $arquivos[0];
                }
            }

            if (!$arquivoZip || !file_exists($arquivoZip)) {
                throw new Exception('Backup executado, mas o arquivo ZIP não foi localizado.');
            }

            $resultado['backup_file'] = $arquivoZip;

            if (!function_exists('getRelatorioUploadConfig') || !function_exists('uploadRelatorioRemoto')) {
                throw new Exception('Funções de upload remoto não estão disponíveis.');
            }

            $config = getRelatorioUploadConfig();
            $nomeArquivo = basename($arquivoZip);

            $upload = uploadRelatorioRemoto($config, $arquivoZip, $nomeArquivo);
            $resultado['upload'] = $upload;
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
