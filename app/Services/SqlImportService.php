<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Imports SQL dump files into a temporary database.
 *
 * The main application database is used to:
 * - create a temporary database (schema)
 * - import `.sql` into it (via mysql client)
 * - drop the database after processing
 */
class SqlImportService
{
    /**
     * Build mysql command-line args (without redirection).
     *
     * @return array<int, string>
     */
    private function baseArgs(string $mysqlBin, string $host, string $port, string $user, string $pass, string $dbName): array
    {
        $args = [
            $mysqlBin,
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $user,
            '--default-character-set=utf8mb4',
            '--database=' . $dbName,
            '--ssl=0',
        ];

        if ($pass !== '') {
            $args[] = '--password=' . $pass;
        }

        return $args;
    }

    /**
     * Create a temporary database and return its name.
     */
    public function createTemporaryDatabase(): string
    {
        $prefix = (string) config('parser.import.tmp_db_prefix', 'tmp_content_');
        $name = $prefix . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(3));

        $charset = (string) config('parser.import.charset', 'utf8mb4');
        $collation = (string) config('parser.import.collation', 'utf8mb4_unicode_ci');

        DB::statement(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s',
            str_replace('`', '``', $name),
            $charset,
            $collation
        ));

        return $name;
    }

    /**
     * Drop temporary database by name.
     */
    public function dropDatabase(string $dbName): void
    {
        DB::statement(sprintf('DROP DATABASE IF EXISTS `%s`', str_replace('`', '``', $dbName)));
    }

    /**
     * Import a `.sql` file into the provided database.
     *
     * Uses `mysql` client non-interactively.
     *
     * Notes:
     * - On Windows, using `SOURCE "C:\path\file.sql"` may fail due to quoting rules.
     *   For reliability we use shell redirection: `mysql ... < file.sql`.
     *
     * @throws \RuntimeException
     * @throws ProcessFailedException
     */
    public function importSqlFile(string $dbName, string $absoluteSqlPath): void
    {
        if (!is_file($absoluteSqlPath)) {
            throw new \RuntimeException('SQL file not found.');
        }

        $connection = config('database.connections.mysql');
        $mysqlBin = (string) config('parser.import.mysql_bin', 'mysql');

        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $user = (string) ($connection['username'] ?? '');
        $pass = (string) ($connection['password'] ?? '');

        if (PHP_OS_FAMILY === 'Windows') {
            // cmd.exe quoting for redirection requires: cmd /c ""exe" args < "file""
            $exe = '"' . $mysqlBin . '"';
            $args = array_slice($this->baseArgs($exe, $host, $port, $user, $pass, $dbName), 1);
            $argsStr = implode(' ', array_map('strval', $args));

            $cmd = $exe . ' ' . $argsStr . ' < "' . $absoluteSqlPath . '"';
            $process = new Process(['cmd', '/c', '""' . $cmd . '""'], base_path());
        } else {
            $cmd = trim(implode(' ', array_filter([
                escapeshellcmd($mysqlBin),
                '--host=' . escapeshellarg($host),
                '--port=' . escapeshellarg($port),
                '--user=' . escapeshellarg($user),
                $pass !== '' ? '--password=' . escapeshellarg($pass) : '',
                '--default-character-set=utf8mb4',
                '--database=' . escapeshellarg($dbName),
                '--ssl=0',
                '<',
                escapeshellarg($absoluteSqlPath),
            ])));

            $process = new Process(['bash', '-lc', $cmd], base_path());
        }

        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}

