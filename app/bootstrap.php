<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/MySqlConnection.php';
require_once __DIR__ . '/Seeder.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AdvancedQueryCatalog.php';
require_once __DIR__ . '/TableRegistry.php';
require_once __DIR__ . '/PuzzleService.php';
require_once __DIR__ . '/StatsRepository.php';

function appEnv(string ...$keys): ?string
{
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }
    }

    return null;
}

function parseDatabaseUrl(?string $url): array
{
    if ($url === null || trim($url) === '') {
        return [];
    }

    $parts = parse_url($url);
    if ($parts === false) {
        throw new RuntimeException('The database URL is invalid.');
    }

    if (isset($parts['scheme']) && stripos((string) $parts['scheme'], 'mysql') !== 0) {
        throw new RuntimeException('The database URL must use the mysql scheme.');
    }

    $databaseName = isset($parts['path']) ? ltrim(rawurldecode((string) $parts['path']), '/') : null;

    return [
        'host' => $parts['host'] ?? null,
        'port' => $parts['port'] ?? null,
        'database' => $databaseName !== '' ? $databaseName : null,
        'username' => isset($parts['user']) ? rawurldecode((string) $parts['user']) : null,
        'password' => isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : null,
    ];
}

function appDatabaseConfig(): array
{
    $urlConfig = parseDatabaseUrl(appEnv('EUROLEAGUE_DB_URL', 'MYSQL_URL', 'DATABASE_URL'));

    return [
        'host' => appEnv('EUROLEAGUE_DB_HOST', 'MYSQLHOST', 'DB_HOST') ?? ($urlConfig['host'] ?? '127.0.0.1'),
        'port' => (int) (appEnv('EUROLEAGUE_DB_PORT', 'MYSQLPORT', 'DB_PORT') ?? (string) ($urlConfig['port'] ?? 3306)),
        'database' => appEnv('EUROLEAGUE_DB_NAME', 'MYSQLDATABASE', 'DB_DATABASE') ?? ($urlConfig['database'] ?? 'euroleague'),
        'username' => appEnv('EUROLEAGUE_DB_USER', 'MYSQLUSER', 'DB_USERNAME') ?? ($urlConfig['username'] ?? 'root'),
        'password' => appEnv('EUROLEAGUE_DB_PASS', 'MYSQLPASSWORD', 'DB_PASSWORD') ?? ($urlConfig['password'] ?? ''),
    ];
}

$database = new Database(
    appDatabaseConfig(),
    __DIR__ . '/../exports/euroleague_mysql_import.sql'
);
$database->initializeIfNeeded();
$db = $database->connection();
$puzzleService = new PuzzleService($db);
$statsRepository = new StatsRepository($db);
$advancedQueryCatalog = new AdvancedQueryCatalog(
    __DIR__ . '/../docs/advanced_queries.sql',
    __DIR__ . '/../docs/advanced_queries.md'
);

function appDatabase(): Database
{
    global $database;
    return $database;
}

function appDb(): AppDbConnection
{
    global $db;
    return $db;
}

function puzzleService(): PuzzleService
{
    global $puzzleService;
    return $puzzleService;
}

function statsRepository(): StatsRepository
{
    global $statsRepository;
    return $statsRepository;
}

function advancedQueryCatalog(): AdvancedQueryCatalog
{
    global $advancedQueryCatalog;
    return $advancedQueryCatalog;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function appUrl(array $params = []): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $anchor = null;
    if (isset($params['_anchor'])) {
        $anchor = trim((string) $params['_anchor']);
        unset($params['_anchor']);
    }

    $query = $params !== [] ? '?' . http_build_query($params) : '';
    $fragment = $anchor !== '' && $anchor !== null ? '#' . rawurlencode($anchor) : '';

    return $script . $query . $fragment;
}

function assetUrl(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), (string) $token)) {
        throw new RuntimeException('The form token expired. Refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirectTo(array $params = []): never
{
    header('Location: ' . appUrl($params));
    exit;
}
