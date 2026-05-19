<?php

require_once __DIR__ . '/../config/paths.php';

$version = isset($argv[1]) && trim($argv[1]) !== '' ? trim($argv[1]) : '1.0.1';
$root = realpath(ROOT_DIR);
$outDir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'updates';

if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$safeVersion = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', $version);
$out = $outDir . DIRECTORY_SEPARATOR . 'sistema-v' . $safeVersion . '.zip';

if (is_file($out)) {
    unlink($out);
}

$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Nao foi possivel criar o ZIP.\n");
    exit(1);
}

$protectedFiles = array(
    '.env',
    'config.php',
    'config/paths.php',
    'app/config/database.php',
    'mysql.cnf',
    'updater/update-config.php',
    'updater/version-local.json',
    'teste_backup.php'
);

$protectedDirs = array(
    'uploads/',
    'storage/',
    'logs/',
    'views-cache/',
    'vendor/',
    'backups/'
);

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$total = 0;
foreach ($it as $item) {
    if ($item->isLink() || !$item->isFile()) {
        continue;
    }

    $path = $item->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

    if (in_array($relative, $protectedFiles, true)) {
        continue;
    }

    $skip = false;
    foreach ($protectedDirs as $dir) {
        if (stripos($relative, $dir) === 0) {
            $skip = true;
            break;
        }
    }

    if ($skip) {
        continue;
    }

    $zip->addFile($path, $relative);
    $total++;
}

$zip->close();

echo json_encode(array(
    'success' => true,
    'version' => $version,
    'zip' => $out,
    'files' => $total,
    'size' => filesize($out),
    'sha256' => hash_file('sha256', $out)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
