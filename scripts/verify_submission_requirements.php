<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$queryDocPath = $root . '/docs/advanced_queries.md';
$querySqlPath = $root . '/docs/advanced_queries.sql';
$exportPath = $root . '/exports/euroleague_mysql_import.sql';

require_once $root . '/app/bootstrap.php';

$db = appDb();
$tables = listUserTables($db);
$tableCount = count($tables);
$underfilled = [];

foreach ($tables as $table) {
    $count = (int) $db->query('SELECT COUNT(*) FROM ' . quoteIdentifier($table))->fetchColumn();
    if ($count < 10) {
        $underfilled[$table] = $count;
    }
}

$queryDoc = is_file($queryDocPath) ? (string) file_get_contents($queryDocPath) : '';
$querySql = is_file($querySqlPath) ? (string) file_get_contents($querySqlPath) : '';
$documentedQueries = preg_match_all('/^## Query \d+/m', $queryDoc);
$sqlQueries = preg_match_all('/^-- Query \d+/m', $querySql);
$requiredFeatures = [
    'DISTINCT' => (bool) preg_match('/\bDISTINCT\b/i', $querySql),
    'HAVING' => (bool) preg_match('/\bHAVING\b/i', $querySql),
    'RIGHT JOIN' => (bool) preg_match('/\bRIGHT\s+JOIN\b/i', $querySql),
    'LEFT JOIN' => (bool) preg_match('/\bLEFT\s+JOIN\b/i', $querySql),
    'Subquery' => (bool) preg_match('/\bFROM\s*\(|\bIN\s*\(\s*SELECT\b/i', $querySql),
];

$missingFeatures = array_keys(array_filter($requiredFeatures, static fn (bool $present): bool => !$present));

echo "Assignment verification\n";
echo "=====================\n";
echo 'Active driver: ' . $db->driver() . "\n";
echo 'Tables found: ' . $tableCount . "\n";
echo 'Tables with at least 10 rows: ' . ($underfilled === [] ? 'yes' : 'no') . "\n";
if ($underfilled !== []) {
    foreach ($underfilled as $table => $count) {
        echo '  - ' . $table . ': ' . $count . "\n";
    }
}
echo 'Documented queries in docs/advanced_queries.md: ' . $documentedQueries . "\n";
echo 'Queries in docs/advanced_queries.sql: ' . $sqlQueries . "\n";
echo 'Required SQL features present: ' . ($missingFeatures === [] ? 'yes' : 'no') . "\n";
if ($missingFeatures !== []) {
    echo '  Missing: ' . implode(', ', $missingFeatures) . "\n";
}
echo 'MySQL import file present: ' . (is_file($exportPath) ? 'yes' : 'no') . "\n";
echo "\nManual submission steps still required:\n";
echo "1. Import exports/euroleague_mysql_import.sql into phpMyAdmin.\n";
echo "2. Run the 15 queries and capture screenshots.\n";
echo "3. Export the imported MySQL database from phpMyAdmin with structure and data.\n";

function listUserTables(AppDbConnection $db): array
{
    if ($db->driver() === 'mysql') {
        $rows = $db->query(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name"
        )->fetchAll();
        return array_map(static fn (array $row): string => (string) $row['table_name'], $rows);
    }

    $rows = $db->query("SELECT name AS table_name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll();
    return array_map(static fn (array $row): string => (string) $row['table_name'], $rows);
}
