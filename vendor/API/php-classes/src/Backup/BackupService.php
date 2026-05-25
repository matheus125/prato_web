<?php

namespace Hcode\Backup;

use Hcode\DB\Sql;
use RuntimeException;

class BackupService
{
    private string $backupDir;

    public function __construct(?string $backupDir = null)
    {
        if ($backupDir === null && defined('BACKUP_DIR')) {
            $backupDir = BACKUP_DIR;
        }

        $this->backupDir = rtrim($backupDir ?: (__DIR__ . '/../../../../backup'), DIRECTORY_SEPARATOR);

        if (!is_dir($this->backupDir) && !mkdir($this->backupDir, 0775, true) && !is_dir($this->backupDir)) {
            throw new RuntimeException('Nao foi possivel criar o diretorio de backup.');
        }
    }

    public function createBackup(string $database): string
    {
        $database = trim($database);
        if ($database === '') {
            throw new RuntimeException('Nome do banco de dados nao informado para backup.');
        }

        $config = class_exists(Sql::class) ? Sql::config() : array();
        $host = (string)($config['host'] ?? '127.0.0.1');
        $port = (int)($config['port'] ?? 3306);
        $user = (string)($config['username'] ?? 'root');
        $password = (string)($config['password'] ?? '');
        $charset = (string)($config['charset'] ?? 'utf8mb4');

        $file = $this->backupDir . DIRECTORY_SEPARATOR . $this->safeFileName($database) . '_' . date('Y-m-d_His') . '.sql';
        $mysqldump = $this->findMysqlDump();

        $command = array(
            escapeshellarg($mysqldump),
            '--host=' . escapeshellarg($host),
            '--port=' . escapeshellarg((string)$port),
            '--user=' . escapeshellarg($user),
            '--default-character-set=' . escapeshellarg($charset),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--result-file=' . escapeshellarg($file),
        );

        if ($password !== '') {
            $command[] = '--password=' . escapeshellarg($password);
        }

        $command[] = escapeshellarg($database);

        exec(implode(' ', $command) . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0 || !is_file($file) || filesize($file) === 0) {
            @unlink($file);
            $detail = trim(implode(' ', $output));
            throw new RuntimeException('Falha ao gerar backup' . ($detail !== '' ? ': ' . $detail : '.'));
        }

        return $file;
    }

    private function findMysqlDump(): string
    {
        if (function_exists('pc_env')) {
            $configured = trim((string)\pc_env('MYSQLDUMP_PATH', ''));
            if ($configured !== '' && is_file($configured)) {
                return $configured;
            }
        }

        foreach (array('mysqldump', 'mariadb-dump') as $binary) {
            $path = trim((string)shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null'));
            if ($path !== '') {
                return $path;
            }
        }

        foreach (array('C:\\xampp\\mysql\\bin\\mysqldump.exe', 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe') as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('mysqldump nao encontrado. Configure MYSQLDUMP_PATH no .env ou instale o cliente MySQL/MariaDB.');
    }

    private function safeFileName(string $name): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $name);
        return trim((string)$safe, '_') ?: 'backup';
    }
}
