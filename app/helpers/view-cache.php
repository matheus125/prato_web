<?php

if (!function_exists('pc_view_cache_manifest')) {
    function pc_view_cache_manifest(string $viewsDir): array
    {
        $files = [];
        $ultimaAlteracao = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, ['html', 'tpl', 'php'], true)) {
                continue;
            }

            $path = $file->getPathname();
            $relativePath = ltrim(str_replace($viewsDir, '', $path), DIRECTORY_SEPARATOR);
            $mtime = $file->getMTime();
            $size = $file->getSize();

            $ultimaAlteracao = max($ultimaAlteracao, $mtime);
            $files[str_replace(DIRECTORY_SEPARATOR, '/', $relativePath)] = $mtime . ':' . $size . ':' . sha1_file($path);
        }

        ksort($files);

        return [
            'signature' => sha1(json_encode($files, JSON_UNESCAPED_SLASHES)),
            'last_modified' => $ultimaAlteracao,
            'files_count' => count($files),
        ];
    }
}

if (!function_exists('pc_clear_view_cache_files')) {
    function pc_clear_view_cache_files(string $cacheDir): int
    {
        $removed = 0;

        foreach (glob(rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . '*.rtpl.php') ?: [] as $cacheFile) {
            if (is_file($cacheFile) && @unlink($cacheFile)) {
                $removed++;
            }
        }

        return $removed;
    }
}

if (!function_exists('limparCacheViewsAutomatico')) {
    function limparCacheViewsAutomatico(): void
    {
        $viewsDir = realpath(__DIR__ . '/../views');
        $cacheDir = __DIR__ . '/../../storage/cache/views';

        if (!$viewsDir || !is_dir($viewsDir)) {
            return;
        }

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $cacheDirReal = realpath($cacheDir);
        if (!$cacheDirReal || !is_dir($cacheDirReal)) {
            return;
        }

        $controleFile = $cacheDirReal . DIRECTORY_SEPARATOR . '.views-cache-control.json';
        $manifest = pc_view_cache_manifest($viewsDir);

        $controle = [];
        if (is_file($controleFile)) {
            $decoded = json_decode((string)file_get_contents($controleFile), true);
            $controle = is_array($decoded) ? $decoded : [];
        }

        if (($controle['views_signature'] ?? null) !== $manifest['signature']) {
            $removed = pc_clear_view_cache_files($cacheDirReal);

            file_put_contents($controleFile, json_encode([
                'views_signature' => $manifest['signature'],
                'ultima_limpeza' => time(),
                'ultima_alteracao_views' => $manifest['last_modified'],
                'arquivos_views' => $manifest['files_count'],
                'arquivos_cache_removidos' => $removed,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
