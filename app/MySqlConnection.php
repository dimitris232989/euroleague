<?php
declare(strict_types=1);

final class AppDbConnection
{
    private function __construct(
        private readonly string $driver,
        private mysqli|PDO $connection,
    ) {
    }

    public static function mysql(
        string $host,
        string $username,
        string $password,
        ?string $database = null,
        int $port = 3306,
        bool $ssl = false,
    ): self {
        if (!extension_loaded('mysqli')) {
            throw new RuntimeException('The mysqli extension is not enabled in PHP.');
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $connection = mysqli_init();
        $connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

        $flags = 0;
        if ($ssl) {
            $connection->ssl_set(null, null, null, null, null);
            $flags = MYSQLI_CLIENT_SSL;
        }

        $connection->real_connect($host, $username, $password, $database, $port, null, $flags);
        $connection->set_charset('utf8mb4');

        return new self('mysql', $connection);
    }

    public static function sqlite(string $databasePath): self
    {
        $directory = dirname($databasePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $connection = new PDO('sqlite:' . $databasePath);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->exec('PRAGMA foreign_keys = ON;');

        return new self('sqlite', $connection);
    }

    public function raw(): mysqli|PDO
    {
        return $this->connection;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function query(string $sql): AppDbStatementResult
    {
        if ($this->driver === 'mysql') {
            $result = $this->connection->query($sql);
            if ($result instanceof mysqli_result) {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                return new AppDbStatementResult($rows);
            }

            return new AppDbStatementResult([]);
        }

        $statement = $this->connection->query($sql);
        return new AppDbStatementResult($statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function prepare(string $sql): AppDbPreparedStatement
    {
        if ($this->driver === 'mysql') {
            return new AppDbPreparedStatement('mysql', $this->connection->prepare($sql));
        }

        return new AppDbPreparedStatement('sqlite', $this->connection->prepare($sql));
    }

    public function execute(string $sql): void
    {
        if ($this->driver === 'mysql') {
            $this->connection->query($sql);
            return;
        }

        $this->connection->exec($sql);
    }

    public function executeScript(string $sql): void
    {
        if ($this->driver === 'sqlite') {
            $this->connection->exec($sql);
            return;
        }

        if (!$this->connection->multi_query($sql)) {
            return;
        }

        do {
            $result = $this->connection->store_result();
            if ($result instanceof mysqli_result) {
                $result->free();
            }
        } while ($this->connection->more_results() && $this->connection->next_result());
    }

    public function beginTransaction(): void
    {
        if ($this->driver === 'mysql') {
            $this->connection->begin_transaction();
            return;
        }

        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        if ($this->driver === 'mysql') {
            $this->connection->commit();
            return;
        }

        $this->connection->commit();
    }

    public function rollBack(): void
    {
        if ($this->driver === 'mysql') {
            $this->connection->rollback();
            return;
        }

        $this->connection->rollBack();
    }

    public function close(): void
    {
        if ($this->driver === 'mysql') {
            $this->connection->close();
        }
    }
}

final class AppDbPreparedStatement
{
    private array $rows = [];

    public function __construct(
        private readonly string $driver,
        private readonly mysqli_stmt|PDOStatement $statement,
    )
    {
    }

    public function execute(array $params = []): self
    {
        if ($this->driver === 'sqlite') {
            $this->statement->execute($params);
            $this->rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            return $this;
        }

        if ($params !== []) {
            $types = '';
            $values = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                    $values[] = $param;
                    continue;
                }

                if (is_float($param)) {
                    $types .= 'd';
                    $values[] = $param;
                    continue;
                }

                if (is_bool($param)) {
                    $types .= 'i';
                    $values[] = $param ? 1 : 0;
                    continue;
                }

                $types .= 's';
                $values[] = $param;
            }

            $references = [];
            foreach ($values as $index => $value) {
                $references[$index] = &$values[$index];
            }

            $bindArgs = array_merge([$types], $references);
            $this->statement->bind_param(...$bindArgs);
        }

        $this->statement->execute();
        $result = $this->statement->get_result();
        $this->rows = $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        if ($result instanceof mysqli_result) {
            $result->free();
        }

        return $this;
    }

    public function fetch(): array|false
    {
        return array_shift($this->rows) ?? false;
    }

    public function fetchAll(): array
    {
        return $this->rows;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->rows[0] ?? null;
        if (!is_array($row)) {
            return false;
        }

        $values = array_values($row);
        return $values[$column] ?? false;
    }
}

final class AppDbStatementResult
{
    public function __construct(private readonly array $rows)
    {
    }

    public function fetch(): array|false
    {
        return $this->rows[0] ?? false;
    }

    public function fetchAll(): array
    {
        return $this->rows;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->rows[0] ?? null;
        if (!is_array($row)) {
            return false;
        }

        $values = array_values($row);
        return $values[$column] ?? false;
    }
}