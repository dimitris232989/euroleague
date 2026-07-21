<?php
declare(strict_types=1);

final class Database
{
    private ?AppDbConnection $connection = null;

    public function __construct(
        private readonly array $config,
        private readonly string $importPath,
    ) {
    }

    public function connection(): AppDbConnection
    {
        if ($this->connection instanceof AppDbConnection) {
            return $this->connection;
        }

        $this->connection = AppDbConnection::mysql(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database'],
            $this->config['port'],
            $this->config['ssl'] ?? false,
        );

        return $this->connection;
    }

    public function initializeIfNeeded(): void
    {
        try {
            $this->ensureDatabaseExists();
            $connection = $this->connection();
            if (!$this->databaseContainsSeedData($connection)) {
                $this->rebuild();
            }

            return;
        } catch (Throwable $exception) {
            if ($this->connection instanceof AppDbConnection) {
                $this->connection->close();
            }

            $this->connection = null;

            $message = 'MySQL mode is required and the connection failed: ' . $exception->getMessage();
            if (stripos($exception->getMessage(), 'Unknown database') !== false) {
                $message .= ' Ensure the configured database already exists or use credentials that can create it.';
            }

            throw new RuntimeException($message, 0, $exception);
        }
    }

    public function rebuild(): void
    {
        $sql = file_get_contents($this->importPath);
        if ($sql === false) {
            throw new RuntimeException('Unable to read the MySQL import file.');
        }

        $connection = $this->connection();
        $importSql = $this->prepareImportSql($sql);

        $connection->execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->dropExistingTables($connection);
            $connection->executeScript($importSql);
        } finally {
            $connection->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        if ($this->connection instanceof AppDbConnection) {
            $this->connection->close();
        }
        $this->connection = null;
        $this->connection();
    }

    private function ensureDatabaseExists(): void
    {
        $server = null;

        try {
            $server = $this->serverConnection();
            $server->execute(
                'CREATE DATABASE IF NOT EXISTS ' . $this->quoteIdentifier($this->config['database'])
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        } catch (Throwable) {
            // Managed MySQL services often expose a pre-created schema and do not grant CREATE DATABASE.
        } finally {
            if ($server instanceof AppDbConnection) {
                $server->close();
            }
        }
    }

    private function databaseContainsSeedData(AppDbConnection $connection): bool
    {
        $exists = $connection->prepare(
            'SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1'
        )->execute(['countries'])->fetchColumn();

        if ($exists === false) {
            return false;
        }

        return (int) $connection->query('SELECT COUNT(*) FROM `countries`')->fetchColumn() > 0;
    }

    private function prepareImportSql(string $sql): string
    {
        $sql = preg_replace('/^\s*DROP DATABASE IF EXISTS\s+`[^`]+`;\s*$/mi', '', $sql) ?? $sql;
        $sql = preg_replace('/^\s*CREATE DATABASE\s+`[^`]+`[^;]*;\s*$/mi', '', $sql) ?? $sql;
        $sql = preg_replace('/^\s*USE\s+`[^`]+`;\s*$/mi', '', $sql) ?? $sql;

        return trim($sql);
    }

    private function dropExistingTables(AppDbConnection $connection): void
    {
        foreach ($this->userTables($connection) as $table) {
            $connection->execute('DROP TABLE IF EXISTS ' . $this->quoteIdentifier($table));
        }
    }

    private function userTables(AppDbConnection $connection): array
    {
        $rows = $connection->query(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
             ORDER BY table_name DESC"
        )->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['table_name'], $rows);
    }

    private function serverConnection(): AppDbConnection
    {
        return AppDbConnection::mysql(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            null,
            $this->config['port'],
            $this->config['ssl'] ?? false,
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}