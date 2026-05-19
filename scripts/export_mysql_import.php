<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$schemaPath = $root . '/euroleague_schema.sql';
$outputPath = $root . '/exports/euroleague_mysql_import.sql';
$databaseName = $argv[1] ?? (getenv('EUROLEAGUE_DB_NAME') ?: 'euroleague');

require_once $root . '/app/bootstrap.php';

$db = appDb();

if (!is_file($schemaPath)) {
    fwrite(STDERR, "Schema file not found at {$schemaPath}\n");
    exit(1);
}

$tableOrder = [
    'countries',
    'people',
    'players',
    'coaches',
    'referees',
    'arenas',
    'seasons',
    'teams',
    'team_seasons',
    'roster_assignments',
    'games',
    'team_game_stats',
    'player_game_stats',
    'awards',
    'grid_puzzles',
    'grid_puzzle_rows',
    'grid_puzzle_columns',
    'grid_puzzle_cells',
    'grid_puzzle_answers',
];

$columnTypes = [];
foreach ($tableOrder as $table) {
    $columnTypes[$table] = columnTypesForTable($db, $table);
}

$schema = file_get_contents($schemaPath);
if ($schema === false) {
    fwrite(STDERR, "Unable to read schema file.\n");
    exit(1);
}

$output = [];
$output[] = '-- Generated from the live archive for phpMyAdmin import.';
$output[] = '-- Import this file into MySQL/phpMyAdmin, then export again from phpMyAdmin if your professor wants a literal phpMyAdmin export artifact.';
$output[] = 'SET NAMES utf8mb4;';
$output[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$output[] = 'DROP DATABASE IF EXISTS `' . escapeIdentifier($databaseName) . '`;';
$output[] = 'CREATE DATABASE `' . escapeIdentifier($databaseName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
$output[] = 'USE `' . escapeIdentifier($databaseName) . '`;';
$output[] = 'START TRANSACTION;';
$output[] = '';

foreach (convertSchemaToMysql($schema) as $statement) {
    $output[] = $statement;
    $output[] = '';
}

foreach ($tableOrder as $table) {
    $rows = $db->query('SELECT * FROM ' . quoteIdentifier($table) . orderByClause($columnTypes[$table]))->fetchAll();
    if ($rows === []) {
        continue;
    }

    $columnNames = array_keys($rows[0]);
    $quotedColumns = array_map(static fn (string $column): string => '`' . escapeIdentifier($column) . '`', $columnNames);
    $chunks = array_chunk($rows, 150);
    foreach ($chunks as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $rowValues = [];
            foreach ($columnNames as $columnName) {
                $rowValues[] = mysqlLiteral($row[$columnName], $columnTypes[$table][$columnName] ?? 'TEXT');
            }
            $values[] = '(' . implode(', ', $rowValues) . ')';
        }

        $output[] = 'INSERT INTO `' . escapeIdentifier($table) . '` (' . implode(', ', $quotedColumns) . ') VALUES';
        $output[] = implode(",\n", $values) . ';';
        $output[] = '';
    }
}

$output[] = 'COMMIT;';
$output[] = 'SET FOREIGN_KEY_CHECKS = 1;';

$directory = dirname($outputPath);
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    fwrite(STDERR, "Unable to create output directory {$directory}\n");
    exit(1);
}

$bytes = file_put_contents($outputPath, implode(PHP_EOL, $output) . PHP_EOL);
if ($bytes === false) {
    fwrite(STDERR, "Unable to write export file.\n");
    exit(1);
}

fwrite(STDOUT, 'Source driver: ' . $db->driver() . PHP_EOL);
fwrite(STDOUT, "Wrote {$outputPath} ({$bytes} bytes)\n");

function columnTypesForTable(AppDbConnection $db, string $table): array
{
    if ($db->driver() === 'mysql') {
        $rows = $db->prepare(
            'SELECT COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ORDINAL_POSITION'
        )->execute([$table])->fetchAll();

        $types = [];
        foreach ($rows as $row) {
            $types[$row['column_name']] = strtoupper((string) $row['column_type']);
        }

        return $types;
    }

    $rows = $db->query('PRAGMA table_info("' . $table . '")')->fetchAll();
    $types = [];
    foreach ($rows as $row) {
        $types[$row['name']] = strtoupper((string) $row['type']);
    }

    return $types;
}

function convertSchemaToMysql(string $schema): array
{
    $autoIncrementTables = [
        'people',
        'countries',
        'arenas',
        'seasons',
        'teams',
        'games',
        'awards',
        'grid_puzzles',
    ];

    preg_match_all('/CREATE TABLE\s+([a-z_]+)\s*\((.*?)\);/si', $schema, $matches, PREG_SET_ORDER);
    $statements = [];

    foreach ($matches as $match) {
        $table = $match[1];
        $body = trim($match[2]);
        $lines = preg_split('/\r?\n/', $body) ?: [];
        $normalizedLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            if (preg_match('/^REFERENCES\b/i', $trimmedLine) === 1 && $normalizedLines !== []) {
                $normalizedLines[array_key_last($normalizedLines)] .= ' ' . $trimmedLine;
                continue;
            }

            $normalizedLines[] = $line;
        }

        $converted = [];

        foreach ($normalizedLines as $line) {
            $trimmed = rtrim(trim($line), ',');
            if ($trimmed === '') {
                continue;
            }

            $trimmed = preg_replace('/\bBOOLEAN\b/i', 'TINYINT(1)', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\bINTEGER\b/i', 'INT', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/DEFAULT\s+TRUE/i', 'DEFAULT 1', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/DEFAULT\s+FALSE/i', 'DEFAULT 0', $trimmed) ?? $trimmed;

            if (in_array($table, $autoIncrementTables, true) && preg_match('/^(\w+_id)\s+INT\s+NOT NULL$/i', $trimmed, $columnMatch)) {
                $primaryKeyColumn = $columnMatch[1];
                if (preg_match('/PRIMARY KEY \(' . preg_quote($primaryKeyColumn, '/') . '\)/i', $body) === 1 && !str_contains($body, PHP_EOL . '    PRIMARY KEY (' . $primaryKeyColumn . ',')) {
                    $trimmed .= ' AUTO_INCREMENT';
                }
            }

            $converted[] = '    ' . $trimmed;
        }

        $statements[] = 'DROP TABLE IF EXISTS `' . escapeIdentifier($table) . '`;';
        $statements[] = 'CREATE TABLE `' . escapeIdentifier($table) . '` (' . PHP_EOL
            . implode(',' . PHP_EOL, $converted) . PHP_EOL
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
    }

    return $statements;
}

function orderByClause(array $columnTypes): string
{
    $preferred = [];
    foreach (['country_id', 'person_id', 'arena_id', 'season_id', 'team_id', 'game_id', 'award_id', 'grid_puzzle_id', 'row_position', 'column_position'] as $column) {
        if (array_key_exists($column, $columnTypes)) {
            $preferred[] = quoteIdentifier($column);
        }
    }

    return $preferred === [] ? '' : ' ORDER BY ' . implode(', ', $preferred);
}

function mysqlLiteral(mixed $value, string $type): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_numeric($value) && isNumericType($type)) {
        return (string) $value;
    }

    $escaped = str_replace(
        ["\\", "'", "\0", "\n", "\r", "\x1a"],
        ["\\\\", "\\'", "\\0", "\\n", "\\r", "\\Z"],
        (string) $value
    );

    return "'{$escaped}'";
}

function isNumericType(string $type): bool
{
    foreach (['INT', 'DECIMAL', 'NUMERIC', 'REAL', 'FLOAT', 'DOUBLE', 'TINYINT', 'SMALLINT'] as $numericType) {
        if (str_contains($type, $numericType)) {
            return true;
        }
    }

    return false;
}

function escapeIdentifier(string $identifier): string
{
    return str_replace('`', '``', $identifier);
}
