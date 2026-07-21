<?php
declare(strict_types=1);

function tableDefinitions(): array
{
    return [
        'countries' => ['label' => 'Countries', 'description' => 'Nation metadata used by teams, arenas, and people.'],
        'arenas' => ['label' => 'Arenas', 'description' => 'Home venues and capacities.'],
        'seasons' => ['label' => 'Seasons', 'description' => 'Competition windows and status.'],
        'people' => ['label' => 'People', 'description' => 'Shared identity records for all roles.'],
        'players' => ['label' => 'Players', 'description' => 'Player-specific athletic profile extending people.'],
        'coaches' => ['label' => 'Coaches', 'description' => 'Coaching profiles extending people.'],
        'referees' => ['label' => 'Referees', 'description' => 'Officials extending people.'],
        'teams' => ['label' => 'Teams', 'description' => 'Club identity, branding, and venue links.'],
        'team_seasons' => ['label' => 'Team Seasons', 'description' => 'Season-level performance per team.'],
        'roster_assignments' => ['label' => 'Roster Assignments', 'description' => 'Player membership for a team in a season.'],
        'games' => ['label' => 'Games', 'description' => 'Scheduled and completed matchups.'],
        'team_game_stats' => ['label' => 'Team Game Stats', 'description' => 'Team box scores for each game.'],
        'player_game_stats' => ['label' => 'Player Game Stats', 'description' => 'Player box scores for each game.'],
        'awards' => ['label' => 'Awards', 'description' => 'Season awards and recognition.'],
        'grid_puzzles' => ['label' => 'Grid Puzzles', 'description' => 'Playable puzzle definitions.'],
        'grid_puzzle_rows' => ['label' => 'Puzzle Rows', 'description' => 'Team clues on puzzle rows.'],
        'grid_puzzle_columns' => ['label' => 'Puzzle Columns', 'description' => 'Stat clues on puzzle columns.'],
        'grid_puzzle_cells' => ['label' => 'Puzzle Cells', 'description' => 'Individual grid cells.'],
        'grid_puzzle_answers' => ['label' => 'Puzzle Answers', 'description' => 'Allowed answers for each puzzle cell.'],
    ];
}

function inspectTable(AppDbConnection $db, string $table): array
{
    if ($db->driver() === 'sqlite') {
        $columns = $db->query('PRAGMA table_info("' . $table . '")')->fetchAll();
        $foreignKeyRows = $db->query('PRAGMA foreign_key_list("' . $table . '")')->fetchAll();
    } else {
        $columns = $db->prepare(
                'SELECT c.ORDINAL_POSITION - 1 AS cid,
                                c.COLUMN_NAME AS name,
                                c.COLUMN_TYPE AS type,
                                CASE WHEN c.IS_NULLABLE = "NO" THEN 1 ELSE 0 END AS notnull,
                                c.COLUMN_DEFAULT AS dflt_value,
                                COALESCE(pk.ORDINAL_POSITION, 0) AS pk
                 FROM information_schema.columns c
                 LEFT JOIN information_schema.key_column_usage pk
                        ON pk.table_schema = c.table_schema
                     AND pk.table_name = c.table_name
                     AND pk.column_name = c.column_name
                     AND pk.constraint_name = "PRIMARY"
                 WHERE c.table_schema = DATABASE() AND c.table_name = ?
                 ORDER BY c.ORDINAL_POSITION'
        )->execute([$table])->fetchAll();

        $foreignKeyRows = $db->prepare(
                'SELECT k.constraint_name AS id,
                                k.ordinal_position - 1 AS seq,
                                k.referenced_table_name AS `table`,
                                k.column_name AS `from`,
                                k.referenced_column_name AS `to`,
                                rc.UPDATE_RULE AS on_update,
                                rc.DELETE_RULE AS on_delete,
                                "NONE" AS `match`
                 FROM information_schema.key_column_usage k
                 JOIN information_schema.referential_constraints rc
                        ON rc.constraint_schema = k.table_schema
                     AND rc.constraint_name = k.constraint_name
                     AND rc.table_name = k.table_name
                 WHERE k.table_schema = DATABASE()
                     AND k.table_name = ?
                     AND k.referenced_table_name IS NOT NULL
                 ORDER BY k.constraint_name, k.ordinal_position'
        )->execute([$table])->fetchAll();
    }

    $primaryKeys = [];
    $columnMap = [];
    foreach ($columns as $column) {
        $columnMap[$column['name']] = $column;
        if ((int) $column['pk'] > 0) {
            $primaryKeys[(int) $column['pk']] = $column['name'];
        }
    }
    ksort($primaryKeys);

    $foreignKeys = [];
    foreach ($foreignKeyRows as $row) {
        $id = (string) $row['id'];
        if (!isset($foreignKeys[$id])) {
            $foreignKeys[$id] = ['table' => $row['table'], 'from' => [], 'to' => []];
        }
        $foreignKeys[$id]['from'][] = $row['from'];
        $foreignKeys[$id]['to'][] = $row['to'];
    }

    return [
        'columns' => $columns,
        'column_map' => $columnMap,
        'primary_keys' => array_values($primaryKeys),
        'foreign_keys' => array_values($foreignKeys),
    ];
}

function tableRows(AppDbConnection $db, string $table, int $limit = 200): array
{
    $schema = inspectTable($db, $table);
    $orderColumns = $schema['primary_keys'] !== [] ? $schema['primary_keys'] : [$schema['columns'][0]['name'] ?? ''];
    $orderColumns = array_filter($orderColumns, static fn (string $column): bool => $column !== '');
    $orderBy = $orderColumns === []
        ? ''
        : ' ORDER BY ' . implode(', ', array_map(static fn (string $column): string => quoteIdentifier($column) . ' DESC', $orderColumns));

    return $db->query('SELECT * FROM ' . quoteIdentifier($table) . $orderBy . ' LIMIT ' . (int) $limit)->fetchAll();
}

function tableCount(AppDbConnection $db, string $table): int
{
    return (int) $db->query('SELECT COUNT(*) FROM ' . quoteIdentifier($table))->fetchColumn();
}

function findRecord(AppDbConnection $db, string $table, array $key): ?array
{
    [$whereSql, $params] = buildWhereClause($key);
    $statement = $db->prepare('SELECT * FROM ' . quoteIdentifier($table) . ' WHERE ' . $whereSql . ' LIMIT 1');
    $statement->execute($params);
    $row = $statement->fetch();

    return $row === false ? null : $row;
}

function insertRecord(AppDbConnection $db, string $table, array $data): void
{
    $columns = array_keys($data);
    $quoted = array_map(static fn (string $column): string => quoteIdentifier($column), $columns);
    $statement = $db->prepare(
        'INSERT INTO ' . quoteIdentifier($table) . ' (' . implode(', ', $quoted) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
    );
    $statement->execute(array_values($data));
}

function updateRecord(AppDbConnection $db, string $table, array $data, array $key): void
{
    $assignments = [];
    $params = [];
    foreach ($data as $column => $value) {
        $assignments[] = quoteIdentifier($column) . ' = ?';
        $params[] = $value;
    }

    [$whereSql, $whereParams] = buildWhereClause($key);
    $statement = $db->prepare('UPDATE ' . quoteIdentifier($table) . ' SET ' . implode(', ', $assignments) . ' WHERE ' . $whereSql);
    $statement->execute(array_merge($params, $whereParams));
}

function deleteRecord(AppDbConnection $db, string $table, array $key): void
{
    [$whereSql, $params] = buildWhereClause($key);
    $statement = $db->prepare('DELETE FROM ' . quoteIdentifier($table) . ' WHERE ' . $whereSql);
    $statement->execute($params);
}

function collectRecordData(array $schema, array $payload): array
{
    $data = [];
    foreach ($schema['columns'] as $column) {
        $name = $column['name'];
        $type = strtoupper((string) $column['type']);
        $rawValue = $payload[$name] ?? null;

        if (isBooleanType($type)) {
            $data[$name] = isset($payload[$name]) ? 1 : 0;
            continue;
        }

        if (is_string($rawValue)) {
            $rawValue = trim($rawValue);
        }

        if ($rawValue === '') {
            $data[$name] = null;
            continue;
        }

        if ($rawValue !== null && isIntegerType($type)) {
            $data[$name] = (int) $rawValue;
            continue;
        }

        if ($rawValue !== null && isDecimalType($type)) {
            $data[$name] = (float) $rawValue;
            continue;
        }

        $data[$name] = $rawValue;
    }

    return $data;
}

function editableRecordData(array $schema, array $data, string $mode): array
{
    if ($mode !== 'edit') {
        return $data;
    }

    foreach ($schema['primary_keys'] as $primaryKey) {
        unset($data[$primaryKey]);
    }

    return $data;
}

function validateRecordData(AppDbConnection $db, string $table, array $schema, array $data, string $mode, array $recordKey = []): array
{
    $errors = [];
    $foreignKeys = singleColumnForeignKeys($schema);

    foreach ($schema['columns'] as $column) {
        $name = $column['name'];
        $type = strtoupper((string) $column['type']);
        $value = $data[$name] ?? null;
        $isPrimaryKey = in_array($name, $schema['primary_keys'], true);
        $isAutoGeneratedPrimaryKey = $mode === 'create' && $isPrimaryKey && count($schema['primary_keys']) === 1 && !isset($foreignKeys[$name]) && isIntegerType($type);

        if ((int) $column['notnull'] === 1 && $value === null && !$isAutoGeneratedPrimaryKey) {
            $errors[] = humanize($name) . ' is required.';
            continue;
        }

        if ($value === null) {
            continue;
        }

        if (isIntegerType($type) && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $errors[] = humanize($name) . ' must be a whole number.';
            continue;
        }

        if (isDecimalType($type) && !is_numeric($value)) {
            $errors[] = humanize($name) . ' must be numeric.';
            continue;
        }

        if (isDateType($type) && !isValidDateValue((string) $value)) {
            $errors[] = humanize($name) . ' must use the YYYY-MM-DD format.';
            continue;
        }

        if (isTimeType($type) && !isValidTimeValue((string) $value)) {
            $errors[] = humanize($name) . ' must use the HH:MM or HH:MM:SS format.';
            continue;
        }

        $maxLength = textLengthLimit($type);
        if ($maxLength !== null && strlen((string) $value) > $maxLength) {
            $errors[] = humanize($name) . ' must be ' . $maxLength . ' characters or fewer.';
            continue;
        }

        if (isset($foreignKeys[$name]) && !foreignKeyExists($db, $foreignKeys[$name], $value)) {
            $errors[] = humanize($name) . ' points to a missing ' . humanize($foreignKeys[$name]['table']) . ' record.';
        }
    }

    if ($mode === 'create') {
        $primaryKey = buildPrimaryKeyFromData($schema, $data);
        if ($primaryKey !== [] && findRecord($db, $table, $primaryKey) !== null) {
            $errors[] = humanize($table) . ' already has a record with that primary key.';
        }
    }

    if ($mode === 'edit' && $recordKey === []) {
        $errors[] = 'Missing record key for update.';
    }

    return array_values(array_unique($errors));
}

function fillAutomaticPrimaryKey(AppDbConnection $db, string $table, array $schema, array $data): array
{
    if (count($schema['primary_keys']) !== 1) {
        return $data;
    }

    $primaryKey = $schema['primary_keys'][0];
    $foreignKeys = foreignKeyMap($schema);
    if (isset($foreignKeys[$primaryKey])) {
        return $data;
    }

    $column = $schema['column_map'][$primaryKey];
    $type = strtoupper((string) $column['type']);
    if (!str_contains($type, 'INT')) {
        return $data;
    }

    if ($data[$primaryKey] !== null) {
        return $data;
    }

    $nextId = (int) $db->query('SELECT COALESCE(MAX(' . quoteIdentifier($primaryKey) . '), 0) + 1 FROM ' . quoteIdentifier($table))->fetchColumn();
    $data[$primaryKey] = $nextId;

    return $data;
}

function relationOptions(AppDbConnection $db, string $table): array
{
    return match ($table) {
        'countries' => fetchOptions($db, 'SELECT country_id AS value, CONCAT(country_name, \' (\', country_code, \')\') AS label FROM countries ORDER BY country_name'),
        'arenas' => fetchOptions($db, 'SELECT arena_id AS value, CONCAT(arena_name, \' - \', city) AS label FROM arenas ORDER BY arena_name'),
        'seasons' => fetchOptions($db, 'SELECT season_id AS value, season_label AS label FROM seasons ORDER BY season_id DESC'),
        'teams' => fetchOptions($db, 'SELECT team_id AS value, team_name AS label FROM teams ORDER BY team_name'),
        'people' => fetchOptions($db, 'SELECT person_id AS value, CONCAT(first_name, \' \', last_name) AS label FROM people ORDER BY last_name, first_name'),
        'players' => fetchOptions($db, 'SELECT players.person_id AS value, CONCAT(people.first_name, \' \', people.last_name) AS label FROM players JOIN people ON people.person_id = players.person_id ORDER BY people.last_name, people.first_name'),
        'coaches' => fetchOptions($db, 'SELECT coaches.person_id AS value, CONCAT(people.first_name, \' \', people.last_name) AS label FROM coaches JOIN people ON people.person_id = coaches.person_id ORDER BY people.last_name, people.first_name'),
        'referees' => fetchOptions($db, 'SELECT referees.person_id AS value, CONCAT(people.first_name, \' \', people.last_name) AS label FROM referees JOIN people ON people.person_id = referees.person_id ORDER BY people.last_name, people.first_name'),
        'grid_puzzles' => fetchOptions($db, 'SELECT grid_puzzle_id AS value, puzzle_name AS label FROM grid_puzzles ORDER BY grid_puzzle_id DESC'),
        default => [],
    };
}

function foreignKeyMap(array $schema): array
{
    $map = [];
    foreach ($schema['foreign_keys'] as $foreignKey) {
        if (count($foreignKey['from']) === 1 && count($foreignKey['to']) === 1) {
            $map[$foreignKey['from'][0]] = $foreignKey['table'];
        }
    }
    return $map;
}

function singleColumnForeignKeys(array $schema): array
{
    $map = [];
    foreach ($schema['foreign_keys'] as $foreignKey) {
        if (count($foreignKey['from']) === 1 && count($foreignKey['to']) === 1) {
            $map[$foreignKey['from'][0]] = [
                'table' => $foreignKey['table'],
                'column' => $foreignKey['to'][0],
            ];
        }
    }

    return $map;
}

function quoteIdentifier(string $identifier): string
{
    $definitions = tableDefinitions();
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('Unsafe identifier requested.');
    }

    if (!isset($definitions[$identifier]) && !columnNameExists($identifier)) {
        throw new InvalidArgumentException('Unknown identifier requested.');
    }

    return '`' . $identifier . '`';
}

function columnNameExists(string $identifier): bool
{
    static $knownColumns = [
        'person_id', 'first_name', 'last_name', 'date_of_birth', 'nationality', 'photo_url',
        'country_id', 'country_name', 'country_code', 'continent',
        'position', 'height', 'weight', 'experience_years', 'coaching_role', 'license_level', 'active_since', 'federation', 'current_flag',
        'arena_id', 'arena_name', 'city', 'capacity', 'website_url',
        'season_id', 'season_label', 'start_date', 'end_date', 'competition_name', 'is_completed',
        'team_id', 'home_arena_id', 'team_name', 'short_name', 'nickname', 'founded_year', 'primary_color', 'secondary_color', 'logo_url', 'is_active',
        'coach_person_id', 'wins', 'losses', 'points_for', 'points_against', 'point_diff', 'final_rank', 'playoff_seed', 'qualified_playin', 'qualified_playoffs', 'final_four_flag', 'avg_attendance', 'notes',
        'jersey_number', 'role', 'game_id', 'home_team_id', 'away_team_id', 'referee_id', 'game_date', 'tipoff_time', 'status', 'home_score', 'away_score', 'attendance', 'overtime_count',
        'points', 'rebounds', 'assists', 'turnovers', 'fouls', 'field_goals_made', 'field_goals_attempted', 'three_points_made', 'three_points_attempted', 'free_throws_made', 'free_throws_attempted',
        'award_id', 'award_name', 'award_type', 'grid_puzzle_id', 'puzzle_name', 'puzzle_date', 'row_position', 'column_position', 'stat_name', 'comparison_operator', 'target_value', 'units', 'clue_text', 'cell_value', 'is_primary',
    ];

    return in_array($identifier, $knownColumns, true);
}

function buildWhereClause(array $key): array
{
    $clauses = [];
    $params = [];
    foreach ($key as $column => $value) {
        $clauses[] = quoteIdentifier($column) . ' = ?';
        $params[] = $value;
    }
    return [implode(' AND ', $clauses), $params];
}

function fetchOptions(AppDbConnection $db, string $sql): array
{
    $options = [];
    foreach ($db->query($sql)->fetchAll() as $row) {
        $options[(string) $row['value']] = $row['label'];
    }
    return $options;
}

function isBooleanType(string $type): bool
{
    $normalized = strtoupper($type);
    return str_contains($normalized, 'BOOL') || $normalized === 'TINYINT(1)' || str_starts_with($normalized, 'TINYINT(1');
}

function isIntegerType(string $type): bool
{
    $normalized = strtoupper($type);
    return str_contains($normalized, 'INT') && !str_contains($normalized, 'POINT');
}

function isDecimalType(string $type): bool
{
    $normalized = strtoupper($type);
    foreach (['DECIMAL', 'NUMERIC', 'REAL', 'FLOAT', 'DOUBLE'] as $token) {
        if (str_contains($normalized, $token)) {
            return true;
        }
    }

    return false;
}

function isDateType(string $type): bool
{
    return strtoupper($type) === 'DATE';
}

function isTimeType(string $type): bool
{
    return strtoupper($type) === 'TIME';
}

function textLengthLimit(string $type): ?int
{
    if (preg_match('/(?:CHAR|VARCHAR)\((\d+)\)/i', $type, $matches) === 1) {
        return (int) $matches[1];
    }

    return null;
}

function isValidDateValue(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

function isValidTimeValue(string $value): bool
{
    foreach (['H:i', 'H:i:s'] as $format) {
        $time = DateTimeImmutable::createFromFormat($format, $value);
        if ($time instanceof DateTimeImmutable && $time->format($format) === $value) {
            return true;
        }
    }

    return false;
}

function foreignKeyExists(AppDbConnection $db, array $foreignKey, mixed $value): bool
{
    $statement = $db->prepare(
        'SELECT 1 FROM ' . quoteIdentifier($foreignKey['table']) . ' WHERE ' . quoteIdentifier($foreignKey['column']) . ' = ? LIMIT 1'
    );
    $statement->execute([$value]);

    return $statement->fetchColumn() !== false;
}

function buildPrimaryKeyFromData(array $schema, array $data): array
{
    $key = [];
    foreach ($schema['primary_keys'] as $primaryKey) {
        if (!array_key_exists($primaryKey, $data) || $data[$primaryKey] === null) {
            return [];
        }

        $key[$primaryKey] = $data[$primaryKey];
    }

    return $key;
}

function humanize(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function recordKeyToken(array $key): string
{
    return rtrim(strtr(base64_encode(json_encode($key, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
}

function decodeRecordKey(string $token): array
{
    $normalized = strtr($token, '-_', '+/');
    $padding = strlen($normalized) % 4;
    if ($padding > 0) {
        $normalized .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode($normalized, true);
    if ($decoded === false) {
        throw new InvalidArgumentException('Invalid record key.');
    }

    $value = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($value)) {
        throw new InvalidArgumentException('Invalid record key.');
    }

    return $value;
}

function recordKeyFromRow(array $schema, array $row): array
{
    $key = [];
    foreach ($schema['primary_keys'] as $column) {
        $key[$column] = $row[$column];
    }
    return $key;
}
