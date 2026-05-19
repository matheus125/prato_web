<?php

if (!function_exists('atualizadorNormalizarRelativo')) {
    function atualizadorNormalizarRelativo($path)
    {
        $path = str_replace('\\', '/', (string)$path);
        $path = preg_replace('#/+#', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || preg_match('/^[a-zA-Z]:/', $path)) {
            return false;
        }

        $partes = explode('/', $path);
        $limpas = array();
        foreach ($partes as $parte) {
            if ($parte === '' || $parte === '.') {
                continue;
            }
            if ($parte === '..') {
                return false;
            }
            $limpas[] = $parte;
        }

        return empty($limpas) ? false : implode('/', $limpas);
    }
}

if (!function_exists('atualizadorConfig')) {
    function atualizadorConfig()
    {
        $config = array(
            'remote_version_url' => '',
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
                'updater/update-config.php',
                'updater/version-local.json',
                'backups/'
            ),
            'allow_insecure_ssl' => false
        );
        $protectedDefault = $config['protected_paths'];

        $arquivo = ROOT_DIR . DIRECTORY_SEPARATOR . 'updater' . DIRECTORY_SEPARATOR . 'update-config.php';
        if (is_file($arquivo)) {
            $local = include $arquivo;
            if (is_array($local)) {
                $protectedLocal = isset($local['protected_paths']) && is_array($local['protected_paths'])
                    ? $local['protected_paths']
                    : array();

                $config = array_merge($config, $local);
                $config['protected_paths'] = array_values(array_unique(array_merge(
                    $protectedDefault,
                    $protectedLocal
                )));
            }
        }

        $envUrl = getenv('UPDATE_VERSION_URL');
        if ($envUrl) {
            $config['remote_version_url'] = $envUrl;
        }

        return $config;
    }
}

if (!function_exists('atualizadorLog')) {
    function atualizadorLog($mensagem)
    {
        $dir = defined('LOG_DIR') ? LOG_DIR : (ROOT_DIR . DIRECTORY_SEPARATOR . 'logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $dir . DIRECTORY_SEPARATOR . 'update.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL,
            FILE_APPEND
        );
    }
}

if (!function_exists('atualizadorVersaoLocalPath')) {
    function atualizadorVersaoLocalPath()
    {
        return ROOT_DIR . DIRECTORY_SEPARATOR . 'updater' . DIRECTORY_SEPARATOR . 'version-local.json';
    }
}

if (!function_exists('atualizadorLerVersaoLocal')) {
    function atualizadorLerVersaoLocal()
    {
        $arquivo = atualizadorVersaoLocalPath();
        if (!is_file($arquivo)) {
            return array(
                'version' => '1.0.0',
                'updated_at' => null,
                'notes' => ''
            );
        }

        $dados = json_decode(file_get_contents($arquivo), true);
        if (!is_array($dados)) {
            throw new Exception('Arquivo updater/version-local.json invalido.');
        }

        if (empty($dados['version'])) {
            $dados['version'] = '1.0.0';
        }

        return $dados;
    }
}

if (!function_exists('atualizadorSalvarVersaoLocal')) {
    function atualizadorSalvarVersaoLocal($remote)
    {
        $arquivo = atualizadorVersaoLocalPath();
        $dir = dirname($arquivo);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $dados = array(
            'version' => isset($remote['version']) ? (string)$remote['version'] : '1.0.0',
            'updated_at' => date('Y-m-d H:i:s'),
            'notes' => isset($remote['notes']) ? (string)$remote['notes'] : ''
        );

        $ok = file_put_contents(
            $arquivo,
            json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($ok === false) {
            throw new Exception('Nao foi possivel atualizar updater/version-local.json.');
        }

        return $dados;
    }
}

if (!function_exists('atualizadorUrlConfigurada')) {
    function atualizadorUrlConfigurada($url)
    {
        $url = trim((string)$url);
        if ($url === '' || stripos($url, 'seudominio.com.br') !== false) {
            throw new Exception('Configure a URL real em updater/update-config.php antes de verificar atualizacoes.');
        }

        if (!preg_match('#^https?://#i', $url)) {
            throw new Exception('A URL de atualizacao precisa iniciar com http:// ou https://.');
        }

        return $url;
    }
}

if (!function_exists('atualizadorHttpGet')) {
    function atualizadorHttpGet($url, $config)
    {
        $url = atualizadorUrlConfigurada($url);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, empty($config['allow_insecure_ssl']));
            curl_setopt($ch, CURLOPT_USERAGENT, 'PratoCheio-Updater/1.0');

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                throw new Exception('Nao foi possivel acessar o servidor de atualizacao. Verifique a internet. Detalhe: ' . $error);
            }
            if ($status >= 400) {
                throw new Exception('Servidor de atualizacao retornou HTTP ' . $status . '.');
            }
            if ($body === false || trim($body) === '') {
                throw new Exception('O arquivo version.json remoto esta vazio. Envie um JSON valido em: ' . $url);
            }

            return $body;
        }

        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 30,
                'header' => "User-Agent: PratoCheio-Updater/1.0\r\n"
            ),
            'ssl' => array(
                'verify_peer' => empty($config['allow_insecure_ssl']),
                'verify_peer_name' => empty($config['allow_insecure_ssl'])
            )
        ));

        $body = @file_get_contents($url, false, $context);
        if ($body === false || trim($body) === '') {
            throw new Exception('Nao foi possivel acessar o version.json remoto ou o arquivo esta vazio. Verifique a internet e o arquivo no servidor.');
        }

        return $body;
    }
}

if (!function_exists('atualizadorHttpDownload')) {
    function atualizadorHttpDownload($url, $destino, $config)
    {
        $url = atualizadorUrlConfigurada($url);
        $downloadTimeout = isset($config['download_timeout']) ? (int)$config['download_timeout'] : 120;
        if ($downloadTimeout <= 0) {
            $downloadTimeout = 120;
        }
        $downloadTimeout = max(15, min($downloadTimeout, 180));

        $dir = dirname($destino);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (function_exists('curl_init')) {
            $fp = fopen($destino, 'wb');
            if (!$fp) {
                throw new Exception('Nao foi possivel criar o arquivo temporario do pacote.');
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, $downloadTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, empty($config['allow_insecure_ssl']));
            curl_setopt($ch, CURLOPT_USERAGENT, 'PratoCheio-Updater/1.0');

            $ok = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if (!$ok || $errno) {
                @unlink($destino);
                throw new Exception('Nao foi possivel baixar o ZIP de atualizacao. Verifique a internet. Detalhe: ' . $error);
            }
            if ($status >= 400) {
                @unlink($destino);
                throw new Exception('Download do ZIP retornou HTTP ' . $status . '.');
            }
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'timeout' => $downloadTimeout,
                    'header' => "User-Agent: PratoCheio-Updater/1.0\r\n"
                ),
                'ssl' => array(
                    'verify_peer' => empty($config['allow_insecure_ssl']),
                    'verify_peer_name' => empty($config['allow_insecure_ssl'])
                )
            ));
            $in = @fopen($url, 'rb', false, $context);
            $out = @fopen($destino, 'wb');
            if (!$in || !$out) {
                if ($in) fclose($in);
                if ($out) fclose($out);
                @unlink($destino);
                throw new Exception('Nao foi possivel baixar o ZIP de atualizacao. Verifique a internet.');
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        }

        if (!is_file($destino) || filesize($destino) <= 0) {
            @unlink($destino);
            throw new Exception('O pacote ZIP baixado esta vazio.');
        }

        return $destino;
    }
}

if (!function_exists('atualizadorChecarAtualizacao')) {
    function atualizadorChecarAtualizacao()
    {
        $config = atualizadorConfig();
        $local = atualizadorLerVersaoLocal();
        $remoteJson = atualizadorHttpGet(isset($config['remote_version_url']) ? $config['remote_version_url'] : '', $config);
        $remote = json_decode($remoteJson, true);

        if (!is_array($remote)) {
            throw new Exception('version.json remoto invalido.');
        }
        if (empty($remote['version'])) {
            throw new Exception('version.json remoto nao possui o campo version.');
        }
        if (empty($remote['url'])) {
            throw new Exception('version.json remoto nao possui o campo url do ZIP.');
        }

        $hasUpdate = version_compare((string)$remote['version'], (string)$local['version'], '>');

        return array(
            'success' => true,
            'local_version' => (string)$local['version'],
            'remote_version' => (string)$remote['version'],
            'has_update' => $hasUpdate,
            'notes' => isset($remote['notes']) ? (string)$remote['notes'] : '',
            'package_url' => (string)$remote['url'],
            'remote' => $remote
        );
    }
}

if (!function_exists('atualizadorCaminhoProtegido')) {
    function atualizadorCaminhoProtegido($relative, $protectedPaths)
    {
        $relative = atualizadorNormalizarRelativo($relative);
        if ($relative === false) {
            return true;
        }

        $relativeLower = strtolower($relative);
        foreach ($protectedPaths as $item) {
            $itemRaw = str_replace('\\', '/', (string)$item);
            $isDir = substr($itemRaw, -1) === '/';
            $itemNorm = atualizadorNormalizarRelativo($itemRaw);
            if ($itemNorm === false) {
                continue;
            }

            $itemLower = strtolower($itemNorm);
            if ($isDir) {
                if ($relativeLower === $itemLower || strpos($relativeLower, $itemLower . '/') === 0) {
                    return true;
                }
            } elseif ($relativeLower === $itemLower) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('atualizadorCaminhoSeguro')) {
    function atualizadorCaminhoSeguro($base, $relative)
    {
        $baseReal = realpath($base);
        if ($baseReal === false) {
            throw new Exception('Caminho base invalido para atualizacao.');
        }

        $relative = atualizadorNormalizarRelativo($relative);
        if ($relative === false) {
            throw new Exception('Caminho invalido dentro do pacote de atualizacao.');
        }

        $target = $baseReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $baseNorm = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
        $targetNorm = str_replace('\\', '/', $target);

        if (strpos($targetNorm, $baseNorm) !== 0) {
            throw new Exception('Pacote de atualizacao tentou acessar caminho fora do sistema.');
        }

        return $target;
    }
}

if (!function_exists('atualizadorTempBase')) {
    function atualizadorTempBase()
    {
        $dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'updates';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }
}

if (!function_exists('atualizadorDentroDe')) {
    function atualizadorDentroDe($path, $base)
    {
        $realPath = realpath($path);
        $realBase = realpath($base);
        if ($realPath === false || $realBase === false) {
            return false;
        }

        $realPath = str_replace('\\', '/', $realPath);
        $realBase = rtrim(str_replace('\\', '/', $realBase), '/') . '/';

        return strpos($realPath, $realBase) === 0;
    }
}

if (!function_exists('atualizadorRemoverDiretorioSeguro')) {
    function atualizadorRemoverDiretorioSeguro($dir)
    {
        $base = atualizadorTempBase();
        if (!is_dir($dir) || !atualizadorDentroDe($dir, $base)) {
            return;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $item) {
            $path = $item->getPathname();
            if (!atualizadorDentroDe($path, $base)) {
                continue;
            }
            if ($item->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}

if (!function_exists('atualizadorExtrairZipSeguro')) {
    function atualizadorExtrairZipSeguro($zipFile, $destDir)
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Extensao PHP ZipArchive nao esta habilitada no XAMPP.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception('Nao foi possivel abrir o pacote ZIP de atualizacao.');
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $arquivos = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $relative = atualizadorNormalizarRelativo($name);
            if ($relative === false || strpos($relative, '__MACOSX/') === 0 || basename($relative) === '.DS_Store') {
                continue;
            }

            $target = atualizadorCaminhoSeguro($destDir, $relative);
            if (substr($name, -1) === '/') {
                if (!is_dir($target)) {
                    mkdir($target, 0775, true);
                }
                continue;
            }

            $parent = dirname($target);
            if (!is_dir($parent)) {
                mkdir($parent, 0775, true);
            }

            $in = $zip->getStream($name);
            $out = fopen($target, 'wb');
            if (!$in || !$out) {
                if ($in) fclose($in);
                if ($out) fclose($out);
                $zip->close();
                throw new Exception('Nao foi possivel extrair o arquivo ' . $relative . '.');
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            $arquivos++;
        }

        $zip->close();

        return $arquivos;
    }
}

if (!function_exists('atualizadorDetectarRaizPacote')) {
    function atualizadorDetectarRaizPacote($extractDir)
    {
        $marcadores = array('app', 'public', 'config', 'database', 'index.php', 'admin.php', 'composer.json');
        foreach ($marcadores as $marcador) {
            if (file_exists($extractDir . DIRECTORY_SEPARATOR . $marcador)) {
                return $extractDir;
            }
        }

        $entradas = array_values(array_filter(scandir($extractDir), function ($item) {
            return $item !== '.' && $item !== '..' && $item !== '__MACOSX';
        }));

        if (count($entradas) === 1) {
            $sub = $extractDir . DIRECTORY_SEPARATOR . $entradas[0];
            if (is_dir($sub)) {
                foreach ($marcadores as $marcador) {
                    if (file_exists($sub . DIRECTORY_SEPARATOR . $marcador)) {
                        return $sub;
                    }
                }
            }
        }

        return $extractDir;
    }
}

if (!function_exists('atualizadorCriarBackupSistema')) {
    function atualizadorCriarBackupSistema($localVersion, $remoteVersion)
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Extensao PHP ZipArchive nao esta habilitada no XAMPP.');
        }

        $backupDir = BACKUP_DIR . DIRECTORY_SEPARATOR . 'updates';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $limpar = function ($valor) {
            $valor = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', (string)$valor);
            $valor = trim($valor, '-_.');
            return $valor !== '' ? $valor : 'versao';
        };

        $arquivo = $backupDir . DIRECTORY_SEPARATOR
            . 'backup-sistema-' . $limpar($localVersion) . '-para-' . $limpar($remoteVersion)
            . '-' . date('Ymd-His') . '.zip';

        $root = realpath(ROOT_DIR);
        $zip = new ZipArchive();
        if ($zip->open($arquivo, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Nao foi possivel criar o backup da versao atual.');
        }

        $skipDirs = array(
            'storage/updates/',
            'storage/backup/',
            'storage/cache/',
            'storage/logs/'
        );

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            if ($item->isLink()) {
                continue;
            }

            $path = $item->getPathname();
            $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
            $relativeNorm = atualizadorNormalizarRelativo($relative);
            if ($relativeNorm === false) {
                continue;
            }

            $skip = false;
            foreach ($skipDirs as $skipDir) {
                if (strpos(strtolower($relativeNorm), strtolower($skipDir)) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($relativeNorm);
            } elseif ($item->isFile()) {
                $zip->addFile($path, $relativeNorm);
            }
        }

        $zip->close();

        return $arquivo;
    }
}

if (!function_exists('atualizadorAplicarArquivos')) {
    function atualizadorAplicarArquivos($sourceDir, $protectedPaths)
    {
        $sourceReal = realpath($sourceDir);
        $rootReal = realpath(ROOT_DIR);
        if ($sourceReal === false || $rootReal === false) {
            throw new Exception('Diretorio do pacote de atualizacao invalido.');
        }

        $resultado = array(
            'copied' => 0,
            'skipped' => 0
        );

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceReal, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            if ($item->isLink()) {
                $resultado['skipped']++;
                continue;
            }

            $path = $item->getPathname();
            $relative = str_replace('\\', '/', substr($path, strlen($sourceReal) + 1));
            $relativeNorm = atualizadorNormalizarRelativo($relative);
            if ($relativeNorm === false || atualizadorCaminhoProtegido($relativeNorm, $protectedPaths)) {
                $resultado['skipped']++;
                continue;
            }

            $target = atualizadorCaminhoSeguro($rootReal, $relativeNorm);
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0775, true);
                }
                continue;
            }

            $parent = dirname($target);
            if (!is_dir($parent)) {
                mkdir($parent, 0775, true);
            }

            if (file_exists($target) && !is_writable($target)) {
                throw new Exception('Arquivo sem permissao de escrita: ' . $relativeNorm);
            }

            if (!copy($path, $target)) {
                throw new Exception('Falha ao substituir o arquivo: ' . $relativeNorm);
            }

            @chmod($target, fileperms($path));
            $resultado['copied']++;
        }

        return $resultado;
    }
}

if (!function_exists('atualizadorLimparCacheViews')) {
    function atualizadorLimparCacheViews()
    {
        if (!defined('VIEW_CACHE_DIR') || !is_dir(VIEW_CACHE_DIR)) {
            return 0;
        }

        $cacheReal = realpath(VIEW_CACHE_DIR);
        $storageReal = realpath(STORAGE_DIR);
        if ($cacheReal === false || $storageReal === false) {
            return 0;
        }

        $cacheNorm = str_replace('\\', '/', $cacheReal);
        $storageNorm = rtrim(str_replace('\\', '/', $storageReal), '/') . '/';
        if (strpos($cacheNorm, $storageNorm) !== 0) {
            throw new Exception('Cache de views fora da pasta storage.');
        }

        $removidos = 0;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheReal, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($path);
            } elseif (@unlink($path)) {
                $removidos++;
            }
        }

        return $removidos;
    }
}

if (!function_exists('atualizadorValidarChecksumSha256')) {
    function atualizadorValidarChecksumSha256($checksum, $arquivo)
    {
        $checksum = strtolower(trim((string)$checksum));

        if ($checksum === '') {
            return true;
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $checksum)) {
            throw new Exception('Checksum SHA256 remoto invalido. O campo sha256/checksum_sha256 deve ter 64 caracteres hexadecimais.');
        }

        $hash = strtolower(hash_file('sha256', $arquivo));
        if (function_exists('hash_equals') ? hash_equals($checksum, $hash) : $checksum === $hash) {
            return true;
        }

        $emptyHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
        if ($checksum === $emptyHash) {
            throw new Exception(
                'Checksum SHA256 do pacote nao confere. O version.json remoto esta usando o hash de arquivo vazio. ' .
                'Atualize o campo sha256/checksum_sha256 para: ' . $hash
            );
        }

        throw new Exception(
            'Checksum SHA256 do pacote nao confere. Esperado pelo version.json: ' . $checksum .
            '. Baixado/calculado: ' . $hash . '. Corrija o checksum remoto e tente novamente.'
        );
    }
}

if (!function_exists('atualizadorAplicarAtualizacao')) {
    function atualizadorAplicarAtualizacao($force = false)
    {
        $check = atualizadorChecarAtualizacao();
        if (empty($check['has_update']) && !$force) {
            return array(
                'success' => true,
                'message' => 'O sistema ja esta na versao mais recente.',
                'updated' => false,
                'check' => $check
            );
        }

        $config = atualizadorConfig();
        $remote = isset($check['remote']) && is_array($check['remote']) ? $check['remote'] : array();
        $tempBase = atualizadorTempBase();
        $zipFile = $tempBase . DIRECTORY_SEPARATOR . 'sistema-' . preg_replace('/[^a-zA-Z0-9_.-]+/', '-', $check['remote_version']) . '-' . time() . '.zip';
        $extractDir = $tempBase . DIRECTORY_SEPARATOR . 'extract-' . date('Ymd-His') . '-' . mt_rand(1000, 9999);

        try {
            atualizadorHttpDownload($check['package_url'], $zipFile, $config);

            $checksum = '';
            if (!empty($remote['checksum_sha256'])) {
                $checksum = (string)$remote['checksum_sha256'];
            } elseif (!empty($remote['sha256'])) {
                $checksum = (string)$remote['sha256'];
            }

            atualizadorValidarChecksumSha256($checksum, $zipFile);

            $extraidos = atualizadorExtrairZipSeguro($zipFile, $extractDir);
            if ($extraidos <= 0) {
                throw new Exception('O pacote ZIP nao possui arquivos validos.');
            }

            $sourceDir = atualizadorDetectarRaizPacote($extractDir);
            $backup = atualizadorCriarBackupSistema($check['local_version'], $check['remote_version']);
            $copiados = atualizadorAplicarArquivos($sourceDir, isset($config['protected_paths']) ? $config['protected_paths'] : array());
            $cache = atualizadorLimparCacheViews();
            $versaoLocal = atualizadorSalvarVersaoLocal($remote);

            atualizadorLog('SUCESSO update ' . $check['local_version'] . ' -> ' . $check['remote_version'] . ' | backup: ' . basename($backup));

            if (is_file($zipFile) && atualizadorDentroDe($zipFile, $tempBase)) {
                @unlink($zipFile);
            }
            atualizadorRemoverDiretorioSeguro($extractDir);

            return array(
                'success' => true,
                'message' => 'Atualizacao aplicada com sucesso. Recarregue o sistema para usar a nova versao.',
                'updated' => true,
                'local_version' => $versaoLocal['version'],
                'remote_version' => $check['remote_version'],
                'backup_file' => $backup,
                'files' => $copiados,
                'cache_removed' => $cache
            );
        } catch (Exception $e) {
            atualizadorLog('ERRO update para ' . $check['remote_version'] . ': ' . $e->getMessage());
            if (is_file($zipFile) && atualizadorDentroDe($zipFile, $tempBase)) {
                @unlink($zipFile);
            }
            atualizadorRemoverDiretorioSeguro($extractDir);
            throw $e;
        }
    }
}

if (isset($app)) {
    $app->get('/admin/api/update/check', function () {
        header('Content-Type: application/json; charset=utf-8');

        try {
            \Hcode\Model\Funcionarios::checkPermission('DASHBOARD_VIEW');

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $resultado = atualizadorChecarAtualizacao();

            echo json_encode(array(
                'success' => true,
                'local_version' => $resultado['local_version'],
                'remote_version' => $resultado['remote_version'],
                'has_update' => $resultado['has_update'],
                'notes' => $resultado['notes'],
                'package_url' => $resultado['package_url']
            ));
            exit;
        } catch (Throwable $e) {
            atualizadorLog('ERRO check update: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage(),
                'needs_network' => true
            ));
            exit;
        }
    });

    $app->post('/admin/api/update/apply', function () {
        header('Content-Type: application/json; charset=utf-8');

        try {
            \Hcode\Model\Funcionarios::checkPermission('BACKUP_RUN');
            $force = isset($_POST['force']) && (int)$_POST['force'] === 1;

            if (function_exists('set_time_limit')) {
                @set_time_limit(300);
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $resultado = atualizadorAplicarAtualizacao($force);

            echo json_encode($resultado);
            exit;
        } catch (Throwable $e) {
            atualizadorLog('ERRO apply update: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage(),
                'needs_network' => true
            ));
            exit;
        }
    });
}
