<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = appDb();
$puzzleService = puzzleService();
$stats = statsRepository();
$queryCatalog = advancedQueryCatalog();
$definitions = tableDefinitions();

$page = normalizePage((string) ($_GET['page'] ?? 'home'));
$table = (string) ($_GET['table'] ?? 'countries');
$action = (string) ($_GET['action'] ?? 'list');
$flash = pullFlash();

if (!isset($definitions[$table])) {
    $table = 'countries';
}

if (isPostRequest()) {
    try {
        verifyCsrf();
        $postAction = (string) ($_POST['action'] ?? '');

        if ($postAction === 'reset_database') {
            appDatabase()->rebuild();
            flash('success', 'The archive was refreshed successfully.');
            redirectTo(['page' => normalizePage((string) ($_POST['redirect_page'] ?? 'admin'))]);
        }

        if ($postAction === 'save_record') {
            $table = (string) ($_POST['table'] ?? 'countries');
            $mode = (string) ($_POST['mode'] ?? 'create');
            $schema = inspectTable($db, $table);
            $data = collectRecordData($schema, $_POST);
            $recordKey = $mode === 'edit' ? decodeRecordKey((string) ($_POST['record_key'] ?? '')) : [];

            if ($mode === 'create') {
                $data = fillAutomaticPrimaryKey($db, $table, $schema, $data);
                $errors = validateRecordData($db, $table, $schema, $data, $mode);
                if ($errors !== []) {
                    throw new RuntimeException(implode(' ', $errors));
                }
                insertRecord($db, $table, $data);
                flash('success', humanize($table) . ' record created.');
            } else {
                $data = editableRecordData($schema, $data, $mode);
                $errors = validateRecordData($db, $table, $schema, $data, $mode, $recordKey);
                if ($errors !== []) {
                    throw new RuntimeException(implode(' ', $errors));
                }
                updateRecord($db, $table, $data, $recordKey);
                flash('success', humanize($table) . ' record updated.');
            }

            redirectTo(['page' => 'crud', 'table' => $table]);
        }

        if ($postAction === 'delete_record') {
            $table = (string) ($_POST['table'] ?? 'countries');
            $key = decodeRecordKey((string) ($_POST['record_key'] ?? ''));
            deleteRecord($db, $table, $key);
            flash('success', humanize($table) . ' record deleted.');
            redirectTo(['page' => 'crud', 'table' => $table]);
        }

        if ($postAction === 'play_puzzle') {
            $puzzleId = (int) ($_POST['puzzle_id'] ?? 0);
            $result = $puzzleService->grade($puzzleId, $_POST['cell'] ?? []);
            $_SESSION['puzzle_results'][$puzzleId] = $result;
            flash('success', 'Grid checked: ' . $result['score'] . ' / ' . $result['total'] . ' correct.');
            redirectTo(['page' => 'play', 'puzzle' => $puzzleId, '_anchor' => 'grid-board']);
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
        $redirectPage = normalizePage((string) ($_POST['redirect_page'] ?? $page));
        $redirectParams = ['page' => $redirectPage];
        if ($redirectPage === 'crud') {
            $redirectParams['table'] = (string) ($_POST['table'] ?? $table);
        }
        if (isset($_POST['puzzle_id'])) {
            $redirectParams['puzzle'] = (int) $_POST['puzzle_id'];
            $redirectParams['_anchor'] = 'grid-board';
        }
        redirectTo($redirectParams);
    }
}

$availablePages = ['home', 'seasons', 'teams', 'team', 'players', 'player', 'awards', 'games', 'boxscore', 'playoffs', 'matchup', 'play', 'admin', 'queries', 'crud'];
if (!in_array($page, $availablePages, true)) {
    $page = 'home';
}

$tableList = array_keys($definitions);
$requiresAdminData = in_array($page, ['admin', 'queries', 'crud'], true);
$dashboardCounts = [];
if ($requiresAdminData) {
    foreach ($tableList as $definitionTable) {
        $dashboardCounts[$definitionTable] = tableCount($db, $definitionTable);
    }
}

$seasons = $stats->listSeasons();
$currentSeasonId = $stats->currentSeasonId();
$seasonId = pickSeasonId($seasons, queryInt('season', $currentSeasonId, 1), $currentSeasonId);
$selectedSeason = findSeasonRow($seasons, $seasonId);

$seasonOverview = $stats->getSeasonOverview($seasonId);
$seasonLeaders = $stats->getSeasonLeaders($seasonId);
$homeStandings = $stats->getSeasonStandings($seasonId, 'rank', 'asc', 5, 0);
$storyStandings = $stats->getSeasonStandings($seasonId, 'rank', 'asc', 10, 0);
$recentGames = $stats->getRecentGames($seasonId, 6);
$featuredGames = $stats->getFeaturedGames($seasonId, 4);
$topPerformances = $stats->getTopPerformances($seasonId, 4);
$teamDirectory = $stats->listTeams($seasonId);
$playoffBracket = $stats->getPlayoffBracket($seasonId);
$seasonNarrative = buildSeasonNarrative($selectedSeason, $seasonOverview, $homeStandings, $seasonLeaders, $playoffBracket);
$homepagePackage = buildHomepagePackage($seasonId, $selectedSeason, $seasonOverview, $homeStandings, $seasonLeaders, $playoffBracket, $featuredGames, $topPerformances);
$homepageStoryRail = buildHomepageStoryRail($stats, $seasonId, $storyStandings, $featuredGames, $topPerformances, $playoffBracket);
$playoffGuide = buildPlayoffGuide($playoffBracket);

$teamSearch = trim((string) ($_GET['team_search'] ?? ''));
$playerSearch = trim((string) ($_GET['player_search'] ?? ''));
$gameSearch = trim((string) ($_GET['game_search'] ?? ''));

$filteredTeams = $teamDirectory;
if ($teamSearch !== '') {
    $filteredTeams = filterRowsByText($filteredTeams, $teamSearch, ['team_name', 'short_name', 'country_name', 'arena_name', 'nickname']);
}

$standingsSort = (string) ($_GET['standings_sort'] ?? 'rank');
$standingsDir = normalizeDirection((string) ($_GET['standings_dir'] ?? 'asc'));
$standingsPager = paginate($stats->countSeasonStandings($seasonId), queryInt('standings_page', 1, 1), 5);
$seasonStandings = $page === 'seasons'
    ? $stats->getSeasonStandings($seasonId, $standingsSort, $standingsDir, $standingsPager['per_page'], $standingsPager['offset'])
    : [];

$playerSort = (string) ($_GET['player_sort'] ?? 'ppg');
$playerDir = normalizeDirection((string) ($_GET['player_dir'] ?? 'desc'));
$playerPager = paginate($stats->countPlayers($seasonId, $playerSearch), queryInt('player_page', 1, 1), 18);
$players = $page === 'players'
    ? $stats->listPlayers($seasonId, $playerSearch, $playerSort, $playerDir, $playerPager['per_page'], $playerPager['offset'])
    : [];

$gameSort = (string) ($_GET['game_sort'] ?? 'date');
$gameDir = normalizeDirection((string) ($_GET['game_dir'] ?? 'desc'));
$gamePager = paginate($stats->countGamesForSeason($seasonId, $gameSearch), queryInt('game_page', 1, 1), 12);
$games = $page === 'games'
    ? $stats->getGamesForSeason($seasonId, $gameSearch, $gameSort, $gameDir, $gamePager['per_page'], $gamePager['offset'])
    : [];
$gameStoryCards = $page === 'games' ? buildGameStoryCards($games) : [];

$allTeamsBySeason = $teamDirectory;

$teamProfile = null;
$teamHistory = [];
$teamSnapshot = null;
$teamRoster = [];
$teamGames = [];
$teamCompareLinks = [];
$teamContinuity = null;
$teamStoryPackage = null;

if ($page === 'team') {
    $teamId = queryInt('team', 0, 0);
    $teamProfile = $stats->getTeamProfile($teamId);
    if ($teamProfile === null) {
        flash('error', 'Club not found.');
        redirectTo(['page' => 'teams', 'season' => $seasonId]);
    }

    $teamHistory = $stats->getTeamSeasonHistory($teamId);
    $teamSeasonId = pickSeasonId($teamHistory, queryInt('team_season', $seasonId, 1), (int) ($teamHistory[0]['season_id'] ?? $seasonId), 'season_id');
    $teamSnapshot = $stats->getTeamSeasonSnapshot($teamId, $teamSeasonId);
    $teamRoster = $stats->getTeamRoster($teamId, $teamSeasonId);
    $teamContinuity = $stats->getTeamContinuity($teamId, $teamSeasonId);
    $teamGames = $stats->getTeamGames($teamId, $teamSeasonId, 'date', 'desc', 10, 0);
    $comparisonPool = $stats->listTeams($teamSeasonId);
    foreach ($comparisonPool as $club) {
        if ((int) $club['team_id'] !== $teamId) {
            $teamCompareLinks[] = $club;
        }
    }

    $teamStoryPackage = buildTeamStoryPackage($teamProfile, $teamSnapshot, $teamRoster, $teamGames, $teamHistory, $teamContinuity);
}

$playerProfile = null;
$playerSeasons = [];
$playerAwards = [];
$playerGameLog = [];
$playerStoryPackage = null;
$playerSelectedSeason = null;
$logSort = (string) ($_GET['log_sort'] ?? 'date');
$logDir = normalizeDirection((string) ($_GET['log_dir'] ?? 'desc'));
$logPager = paginate(0, 1, 10);

if ($page === 'player') {
    $personId = queryInt('player', 0, 0);
    $playerProfile = $stats->getPlayerProfile($personId);
    if ($playerProfile === null) {
        flash('error', 'Player not found.');
        redirectTo(['page' => 'players', 'season' => $seasonId]);
    }

    $playerSeasons = $stats->getPlayerSeasonSummaries($personId);
    $playerSelectedSeason = pickSeasonId($playerSeasons, queryInt('player_season', $seasonId, 1), (int) ($playerSeasons[0]['season_id'] ?? $seasonId), 'season_id');
    $playerAwards = $stats->getPlayerAwards($personId);
    $logPager = paginate($stats->countPlayerGameLog($personId, $playerSelectedSeason), queryInt('log_page', 1, 1), 10);
    $playerGameLog = $stats->getPlayerGameLog($personId, $playerSelectedSeason, $logSort, $logDir, $logPager['per_page'], $logPager['offset']);
    $playerStoryPackage = buildPlayerStoryPackage($playerProfile, $playerSeasons, $playerAwards, $playerGameLog, findSeasonRow($playerSeasons, $playerSelectedSeason, 'season_id'));
}

$seasonAwards = $page === 'awards' ? $stats->getSeasonAwards($seasonId) : [];
$awardsArchive = $page === 'awards' ? $stats->getAwardsArchive() : [];
$awardsPagePackage = $page === 'awards' ? buildAwardsPagePackage($seasonAwards, $awardsArchive) : null;

$matchupData = null;
$matchupTeamId = 0;
$matchupOpponentId = 0;
if ($page === 'matchup') {
    $matchupTeamId = queryInt('team', (int) ($allTeamsBySeason[0]['team_id'] ?? 0), 0);
    $defaultOpponent = 0;
    foreach ($allTeamsBySeason as $club) {
        if ((int) $club['team_id'] !== $matchupTeamId) {
            $defaultOpponent = (int) $club['team_id'];
            break;
        }
    }
    $matchupOpponentId = queryInt('opponent', $defaultOpponent, 0);
    $matchupData = $stats->getHeadToHead($matchupTeamId, $matchupOpponentId, $seasonId, 20);
    if ($matchupData === null) {
        flash('error', 'That matchup is not available for the selected season.');
        redirectTo(['page' => 'teams', 'season' => $seasonId]);
    }
}

$boxscore = null;
$boxscoreStory = null;
if ($page === 'boxscore') {
    $gameId = queryInt('game', 0, 0);
    $boxscore = $stats->getGameDetail($gameId);
    if ($boxscore === null) {
        flash('error', 'Game not found.');
        redirectTo(['page' => 'games', 'season' => $seasonId]);
    }

    $boxscoreStory = buildBoxscoreStory($boxscore);
}

$crudSchema = null;
$crudRows = [];
$editingRecord = null;
$editingKey = null;
if ($page === 'crud') {
    $crudSchema = inspectTable($db, $table);
    $crudRows = tableRows($db, $table, 250);

    if ($action === 'edit' && isset($_GET['key'])) {
        $editingKey = decodeRecordKey((string) $_GET['key']);
        $editingRecord = findRecord($db, $table, $editingKey);
        if ($editingRecord === null) {
            flash('error', 'Record not found.');
            redirectTo(['page' => 'crud', 'table' => $table]);
        }
    }
}

$advancedQueries = [];
$selectedAdvancedQuery = null;
$advancedQueryRows = [];
$advancedQueryPreview = [];
$advancedQueryError = null;
$advancedQueryExecutionEnabled = false;
if ($page === 'queries') {
    $advancedQueries = $queryCatalog->all();
    $queryIds = array_keys($advancedQueries);
    $defaultQueryId = (int) ($queryIds[0] ?? 1);
    $selectedQueryId = queryInt('query_id', $defaultQueryId, 1);
    $selectedAdvancedQuery = $advancedQueries[$selectedQueryId] ?? ($advancedQueries[$defaultQueryId] ?? null);

    if ($selectedAdvancedQuery === null) {
        flash('error', 'Advanced query not found.');
        redirectTo(['page' => 'admin']);
    }

    $advancedQueryExecutionEnabled = true;
    try {
        $advancedQueryRows = $db->query($selectedAdvancedQuery['sql'])->fetchAll();
        $advancedQueryPreview = array_slice($advancedQueryRows, 0, 60);
    } catch (Throwable $exception) {
        $advancedQueryError = $exception->getMessage();
    }
}

$puzzles = [];
$selectedPuzzleId = 0;
$board = null;
$puzzleResult = null;
$puzzlePagePackage = null;
if ($page === 'play') {
    $puzzles = $puzzleService->listPuzzles();
    $selectedPuzzleId = queryInt('puzzle', (int) ($puzzles[0]['grid_puzzle_id'] ?? 1), 1);

    if (queryInt('reset', 0, 0) === 1) {
        unset($_SESSION['puzzle_results'][$selectedPuzzleId]);
        redirectTo(['page' => 'play', 'puzzle' => $selectedPuzzleId, '_anchor' => 'grid-board']);
    }

    $board = $puzzleService->loadBoard($selectedPuzzleId);
    $puzzleResult = $_SESSION['puzzle_results'][$selectedPuzzleId] ?? null;
    $puzzlePagePackage = $board !== null ? buildPuzzlePagePackage($board, $puzzleResult) : null;
}

$navPage = navSection($page);
$seasonRailPage = in_array($navPage, ['admin', 'queries', 'crud'], true) ? 'seasons' : ($navPage === 'home' ? 'seasons' : $navPage);
$pageTitle = match ($page) {
    'seasons' => 'Season Archive',
    'teams', 'team', 'matchup' => 'Clubs',
    'players', 'player' => 'Players',
    'awards' => 'Awards',
    'games', 'boxscore' => 'Scores',
    'playoffs' => 'Playoffs',
    'play' => 'Grid',
    'admin' => 'Data Desk',
    'queries' => 'Advanced Queries',
    'crud' => 'Data Desk',
    default => 'Euroleague Atlas',
};
$activePlayerSummary = $page === 'player' ? findSeasonRow($playerSeasons, (int) $playerSelectedSeason, 'season_id') : null;
$breadcrumbItems = buildBreadcrumbs($page, $seasonId, $selectedSeason, $teamProfile, $teamSnapshot, $playerProfile, $activePlayerSummary, $boxscore);
$topSeed = $homeStandings[0] ?? null;
$scoringSnapshot = $seasonLeaders['points'][0] ?? null;
$latestFinal = $recentGames[0] ?? null;
$mastheadClubs = $page === 'home' ? array_slice($homeStandings, 0, 3) : [];

function renderField(AppDbConnection $db, array $schema, ?array $record, array $column, string $mode): string
{
    $name = $column['name'];
    $type = strtoupper((string) $column['type']);
    $label = humanize($name);
    $value = $record[$name] ?? null;
    $isPrimaryKey = in_array($name, $schema['primary_keys'], true);
    $isReadonly = $mode === 'edit' && $isPrimaryKey;
    $required = ((int) $column['notnull'] === 1 && !$isReadonly) ? 'required' : '';
    $readonly = $isReadonly ? 'readonly' : '';
    $foreignKeys = foreignKeyMap($schema);
    $attributes = [];

    if (($maxLength = textLengthLimit($type)) !== null) {
        $attributes[] = 'maxlength="' . $maxLength . '"';
    }

    if (isset($foreignKeys[$name])) {
        $options = relationOptions($db, $foreignKeys[$name]);
        $html = '<label class="field"><span>' . h($label) . '</span><select name="' . h($name) . '" ' . $required . ' ' . $readonly . ' ' . implode(' ', $attributes) . '>';
        $html .= '<option value="">Select...</option>';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = ((string) $value === (string) $optionValue) ? 'selected' : '';
            $html .= '<option value="' . h($optionValue) . '" ' . $selected . '>' . h($optionLabel) . '</option>';
        }
        $html .= '</select></label>';
        return $html;
    }

    if (isBooleanType($type)) {
        $checked = (string) $value === '1' ? 'checked' : '';
        return '<label class="field checkbox-field"><span>' . h($label) . '</span><input type="checkbox" name="' . h($name) . '" value="1" ' . $checked . '></label>';
    }

    if (str_contains($type, 'TEXT')) {
        return '<label class="field field-wide"><span>' . h($label) . '</span><textarea name="' . h($name) . '" rows="4" ' . $readonly . ' ' . implode(' ', $attributes) . '>' . h((string) $value) . '</textarea></label>';
    }

    $inputType = 'text';
    $step = '';
    $min = '';
    if (str_contains($type, 'DATE')) {
        $inputType = 'date';
    } elseif (str_contains($type, 'TIME')) {
        $inputType = 'time';
    } elseif (str_contains($type, 'INT')) {
        $inputType = 'number';
        $step = 'step="1"';
        if (!str_contains($name, 'score') && !str_contains($name, 'diff')) {
            $min = 'min="0"';
        }
    } elseif (str_contains($type, 'DECIMAL')) {
        $inputType = 'number';
        $step = 'step="0.01"';
    }

    return '<label class="field"><span>' . h($label) . '</span><input type="' . $inputType . '" name="' . h($name) . '" value="' . h((string) $value) . '" ' . $required . ' ' . $readonly . ' ' . $step . ' ' . $min . ' ' . implode(' ', $attributes) . '></label>';
}

function normalizePage(string $page): string
{
    return match ($page) {
        'dashboard' => 'admin',
        'game' => 'play',
        'playoff' => 'playoffs',
        default => $page,
    };
}

function navSection(string $page): string
{
    return match ($page) {
        'team', 'matchup' => 'teams',
        'player' => 'players',
        'awards' => 'awards',
        'boxscore' => 'games',
        'queries', 'crud' => 'admin',
        default => $page,
    };
}

function queryInt(string $key, int $default, int $min = 1): int
{
    $value = isset($_GET[$key]) ? (int) $_GET[$key] : $default;
    return $value < $min ? $default : $value;
}

function normalizeDirection(string $direction): string
{
    $value = strtolower($direction);
    return $value === 'asc' ? 'asc' : 'desc';
}

function paginate(int $total, int $currentPage, int $perPage): array
{
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    $currentPage = max(1, min($currentPage, $totalPages));

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => ($currentPage - 1) * $perPage,
    ];
}

function pickSeasonId(array $rows, int $requested, int $fallback, string $key = 'season_id'): int
{
    $ids = array_map(static fn (array $row): int => (int) $row[$key], $rows);
    if (in_array($requested, $ids, true)) {
        return $requested;
    }

    return in_array($fallback, $ids, true) ? $fallback : (int) ($ids[0] ?? $fallback);
}

function findSeasonRow(array $rows, int $seasonId, string $key = 'season_id'): ?array
{
    foreach ($rows as $row) {
        if ((int) $row[$key] === $seasonId) {
            return $row;
        }
    }

    return null;
}

function filterRowsByText(array $rows, string $term, array $fields): array
{
    $needle = strtolower($term);

    return array_values(array_filter(
        $rows,
        static function (array $row) use ($needle, $fields): bool {
            foreach ($fields as $field) {
                if (isset($row[$field]) && stripos((string) $row[$field], $needle) !== false) {
                    return true;
                }
            }

            return false;
        }
    ));
}

function pageUrl(string $page, array $params = []): string
{
    return appUrl(array_merge(['page' => $page], $params));
}

function formatGameDate(?string $date): string
{
    if ($date === null || $date === '') {
        return 'TBD';
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('M j, Y', $timestamp);
}

function formatLargeNumber(mixed $value): string
{
    if ($value === null || $value === '') {
        return '0';
    }

    return number_format((float) $value, 0, '.', ',');
}

function formatStat(mixed $value, int $decimals = 1): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((float) $value, $decimals, '.', '');
}

function formatRecord(array $row): string
{
    return (string) $row['wins'] . '-' . (string) $row['losses'];
}

function winnerClass(array $game): string
{
    return (int) $game['home_score'] > (int) $game['away_score'] ? 'winner-home' : 'winner-away';
}

function clubMark(array $team, string $size = 'md'): string
{
    $primary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($team['primary_color'] ?? '')) ? (string) $team['primary_color'] : '#0f172a';
    $secondary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($team['secondary_color'] ?? '')) ? (string) $team['secondary_color'] : '#f5c242';
    $label = strtoupper((string) ($team['short_name'] ?? 'EL'));

    return '<span class="club-mark club-mark-' . h($size) . '" style="--club-primary:' . h($primary) . ';--club-secondary:' . h($secondary) . ';"><span>' . h($label) . '</span></span>';
}

function assetHref(?string $path): ?string
{
    $trimmed = trim((string) $path);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('#^(?:https?:)?//#', $trimmed) === 1) {
        return $trimmed;
    }

    return assetUrl($trimmed);
}

function clubIdentityMarkup(array $team, string $size = 'md'): string
{
    $logo = assetHref((string) ($team['logo_url'] ?? ''));
    if ($logo === null) {
        return clubMark($team, $size);
    }

    $alt = trim((string) ($team['team_name'] ?? $team['short_name'] ?? 'Club')) . ' logo';
    return '<span class="club-logo club-logo-' . h($size) . '"><img src="' . h($logo) . '" alt="' . h($alt) . '" loading="lazy"></span>';
}

function playerInitials(array $player): string
{
    $name = trim((string) (($player['player_name'] ?? '') !== ''
        ? $player['player_name']
        : trim((string) ($player['first_name'] ?? '') . ' ' . (string) ($player['last_name'] ?? ''))));

    if ($name === '') {
        return 'PL';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = strtoupper(substr((string) ($parts[0] ?? 'P'), 0, 1));
    $last = strtoupper(substr((string) ($parts[count($parts) - 1] ?? 'L'), 0, 1));

    return $first . $last;
}

function playerPortraitMarkup(array $player, ?array $team = null, string $size = 'lg'): string
{
    $photo = assetHref((string) ($player['photo_url'] ?? ''));
    $style = '';
    if ($team !== null) {
        $style = ' style="' . h(clubThemeStyle($team, 0.18, 0.1)) . '"';
    }

    if ($photo !== null) {
        return '<span class="player-portrait player-portrait-' . h($size) . '"' . $style . '><img src="' . h($photo) . '" alt="' . h((string) ($player['player_name'] ?? 'Player portrait')) . '" loading="lazy"></span>';
    }

    return '<span class="player-portrait player-portrait-' . h($size) . '"' . $style . '><span>' . h(playerInitials($player)) . '</span></span>';
}

function clubMotifClass(array $team): string
{
    $shortName = strtolower((string) ($team['short_name'] ?? 'atlas'));
    $safe = preg_replace('/[^a-z0-9]+/', '-', $shortName) ?: 'atlas';

    return 'club-theme-' . $safe;
}

function postseasonStatusLabel(array $row): string
{
    if ((int) ($row['qualified_playoffs'] ?? 0) === 1) {
        return 'Direct playoff berth';
    }

    if ((int) ($row['qualified_playin'] ?? 0) === 1) {
        return 'Play-In berth';
    }

    $rank = (int) ($row['final_rank'] ?? 99);
    if ($rank === 11) {
        return 'One place outside the Play-In';
    }

    return 'Outside postseason picture';
}

function explainPlayInGame(array $game): string
{
    if ((int) ($game['winner_seed'] ?? 0) === 7) {
        return $game['winner']['team_name'] . ' take the No. 7 seed. ' . $game['loser']['team_name'] . ' drop into the last Play-In game.';
    }

    if ((int) ($game['winner_seed'] ?? 0) === 8) {
        return $game['winner']['team_name'] . ' take the No. 8 seed and move into the quarterfinals. ' . $game['loser']['team_name'] . ' are eliminated.';
    }

    if ((int) ($game['is_elimination'] ?? 0) === 1) {
        return $game['winner']['team_name'] . ' stay alive. ' . $game['loser']['team_name'] . ' are eliminated from the postseason race.';
    }

    return $game['winner']['team_name'] . ' advance.';
}

function buildPlayoffGuide(array $bracket): array
{
    $hasPlayIn = ($bracket['play_in'] ?? []) !== [];

    return [
        [
            'label' => 'Seeds 1-6',
            'title' => 'Straight into the quarterfinals',
            'detail' => 'The top six clubs skip the Play-In and go directly into best-of-five quarterfinal series.',
        ],
        [
            'label' => 'No. 7 vs No. 8',
            'title' => 'Winner locks the No. 7 seed',
            'detail' => $hasPlayIn
                ? 'The loser gets one more chance in the final Play-In game for the No. 8 seed.'
                : 'If the Play-In is not active, the table sends teams directly into the quarterfinal bracket.',
        ],
        [
            'label' => 'No. 9 vs No. 10',
            'title' => 'One game decides survival',
            'detail' => 'The winner stays alive and meets the loser of 7 vs 8. The loser is out immediately.',
        ],
        [
            'label' => 'Final Four',
            'title' => 'Single-game semifinals and final',
            'detail' => 'Quarterfinal winners advance into the Final Four, where each round is winner-take-all.',
        ],
    ];
}

function buildHomepageStoryRail(
    StatsRepository $stats,
    int $seasonId,
    array $standings,
    array $featuredGames,
    array $topPerformances,
    array $bracket
): array {
    $rail = [];
    $featured = $featuredGames[0] ?? null;
    if ($featured !== null) {
        $topTier = (int) ($featured['home_rank'] ?? 99) <= 4 && (int) ($featured['away_rank'] ?? 99) <= 4;
        $rail[] = [
            'label' => "Don't Miss",
            'title' => $featured['away_team_name'] . ' at ' . $featured['home_team_name'],
            'detail' => $topTier
                ? 'A top-tier collision between No. ' . $featured['away_rank'] . ' and No. ' . $featured['home_rank'] . ' with table position on the line.'
                : 'A standings swing game with postseason weight and a live box score already in the archive.',
            'url' => pageUrl('boxscore', ['game' => $featured['game_id']]),
        ];
    }

    $formClub = $standings[0] ?? null;
    if ($formClub !== null) {
        $form = buildRecentForm($stats->getTeamGames((int) $formClub['team_id'], $seasonId, 'date', 'desc', 5, 0), 5);
        $rail[] = [
            'label' => 'Win Streak Watch',
            'title' => $formClub['team_name'] . ' are running at ' . $form['record'],
            'detail' => 'Recent form: ' . $form['streak'] . '. The current table leader still has to close the season with pressure on every round.',
            'url' => pageUrl('team', ['team' => $formClub['team_id'], 'team_season' => $seasonId]),
        ];
    }

    $seedSix = $standings[5] ?? null;
    $seedSeven = $standings[6] ?? null;
    if ($seedSix !== null && $seedSeven !== null) {
        $gap = abs((int) $seedSix['wins'] - (int) $seedSeven['wins']);
        $rail[] = [
            'label' => 'Playoff Swing',
            'title' => $seedSix['team_name'] . ' and ' . $seedSeven['team_name'] . ' are separated by ' . $gap . ' win' . ($gap === 1 ? '' : 's'),
            'detail' => $seedSix['team_name'] . ' sit on the direct playoff line, while ' . $seedSeven['team_name'] . ' would open in the Play-In under the current table.',
            'url' => pageUrl('playoffs', ['season' => $seasonId]),
        ];
    }

    $performance = $topPerformances[0] ?? null;
    if ($performance !== null) {
        $rail[] = [
            'label' => 'Star Turn',
            'title' => $performance['player_name'] . ' posted ' . $performance['points'] . ' points',
            'detail' => $performance['rebounds'] . ' rebounds, ' . $performance['assists'] . ' assists, and one of the strongest single-game lines in the archive.',
            'url' => pageUrl('player', ['player' => $performance['person_id'], 'player_season' => $seasonId]),
        ];
    }

    if (($bracket['champion'] ?? null) !== null) {
        $rail[] = [
            'label' => 'Title Path',
            'title' => $bracket['champion']['team_name'] . ' lead the current bracket model',
            'detail' => 'Open the postseason page for the full play-in path, best-of-five quarterfinals, and Final Four projection.',
            'url' => pageUrl('playoffs', ['season' => $seasonId]),
        ];
    }

    return array_slice($rail, 0, 4);
}

function sortLink(string $page, string $label, string $sortKey, string $currentSort, string $currentDir, string $sortParam, string $dirParam, array $params): string
{
    $active = $currentSort === $sortKey;
    $nextDir = $active && $currentDir === 'asc' ? 'desc' : 'asc';
    $indicator = $active ? ($currentDir === 'asc' ? '↑' : '↓') : '↕';

    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="' . h(pageUrl($page, array_merge($params, [$sortParam => $sortKey, $dirParam => $nextDir]))) . '">' . h($label) . '<span>' . h($indicator) . '</span></a>';
}

function renderPagination(string $page, array $params, string $pageParam, array $pager): string
{
    if ($pager['total_pages'] <= 1) {
        return '';
    }

    $start = max(1, $pager['current_page'] - 2);
    $end = min($pager['total_pages'], $pager['current_page'] + 2);
    $html = '<nav class="pagination">';

    if ($pager['current_page'] > 1) {
        $html .= '<a class="page-pill" href="' . h(pageUrl($page, array_merge($params, [$pageParam => $pager['current_page'] - 1]))) . '">Previous</a>';
    }

    for ($pageNumber = $start; $pageNumber <= $end; $pageNumber++) {
        $html .= '<a class="page-pill ' . ($pageNumber === $pager['current_page'] ? 'active' : '') . '" href="' . h(pageUrl($page, array_merge($params, [$pageParam => $pageNumber]))) . '">' . h((string) $pageNumber) . '</a>';
    }

    if ($pager['current_page'] < $pager['total_pages']) {
        $html .= '<a class="page-pill" href="' . h(pageUrl($page, array_merge($params, [$pageParam => $pager['current_page'] + 1]))) . '">Next</a>';
    }

    $html .= '</nav>';
    return $html;
}

function buildSeasonNarrative(?array $season, ?array $overview, array $standings, array $leaders, array $bracket): array
{
    $leader = $standings[0] ?? null;
    $scoringLeader = $leaders['points'][0] ?? null;
    $assistLeader = $leaders['assists'][0] ?? null;
    $champion = $bracket['champion'] ?? $leader;

    $headline = $leader !== null
        ? ($leader['team_name'] . ' set the tone in ' . ($season['season_label'] ?? 'the current season'))
        : 'A season-wide archive for every club, player, and box score';

    $summary = $leader !== null && $scoringLeader !== null
        ? $leader['team_name'] . ' finished atop the regular-season table at ' . formatRecord($leader) . ', while ' . $scoringLeader['player_name'] . ' led the scoring race at ' . formatStat($scoringLeader['value']) . ' points per game.'
        : 'Browse standings, playoff context, club histories, player pages, and game-by-game results from a single archive.';

    return [
        'headline' => $headline,
        'summary' => $summary,
        'cards' => [
            [
                'label' => 'Projected Champion',
                'value' => $champion['team_name'] ?? 'TBD',
                'detail' => isset($champion['final_rank']) ? 'Seed ' . $champion['final_rank'] : 'Playoff race',
            ],
            [
                'label' => 'Scoring Leader',
                'value' => $scoringLeader['player_name'] ?? 'TBD',
                'detail' => $scoringLeader !== null ? formatStat($scoringLeader['value']) . ' PPG' : 'Season race',
            ],
            [
                'label' => 'Lead Playmaker',
                'value' => $assistLeader['player_name'] ?? 'TBD',
                'detail' => $assistLeader !== null ? formatStat($assistLeader['value']) . ' APG' : 'Season race',
            ],
            [
                'label' => 'Attendance',
                'value' => $overview !== null ? formatLargeNumber($overview['average_attendance']) : '0',
                'detail' => 'Average gate',
            ],
        ],
    ];
}

function formatSigned(mixed $value, int $decimals = 0): string
{
    $number = (float) ($value ?? 0);
    $formatted = number_format($number, $decimals, '.', '');

    if ($number > 0) {
        return '+' . $formatted;
    }

    return $formatted;
}

function hexToRgba(string $hex, float $alpha): string
{
    $clean = ltrim($hex, '#');
    if (strlen($clean) !== 6 || !ctype_xdigit($clean)) {
        $clean = '0f172a';
    }

    return sprintf(
        'rgba(%d, %d, %d, %.3f)',
        hexdec(substr($clean, 0, 2)),
        hexdec(substr($clean, 2, 2)),
        hexdec(substr($clean, 4, 2)),
        max(0.0, min(1.0, $alpha))
    );
}

function clubThemeStyle(array $team, float $primaryAlpha = 0.24, float $secondaryAlpha = 0.14): string
{
    $primary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($team['primary_color'] ?? '')) ? (string) $team['primary_color'] : '#0f172a';
    $secondary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($team['secondary_color'] ?? '')) ? (string) $team['secondary_color'] : '#f5c242';

    return '--club-primary:' . $primary . ';'
        . '--club-secondary:' . $secondary . ';'
        . '--club-primary-soft:' . hexToRgba($primary, $primaryAlpha) . ';'
        . '--club-secondary-soft:' . hexToRgba($secondary, $secondaryAlpha) . ';';
}

function bestRosterStat(array $roster, string $column): ?array
{
    $best = null;
    foreach ($roster as $player) {
        if ($best === null || (float) ($player[$column] ?? 0) > (float) ($best[$column] ?? 0)) {
            $best = $player;
        }
    }

    return $best;
}

function buildRecentForm(array $games, int $window = 5): array
{
    $sample = array_slice($games, 0, $window);
    $wins = 0;
    $losses = 0;

    foreach ($sample as $game) {
        if ((int) $game['team_score'] > (int) $game['opponent_score']) {
            $wins++;
        } else {
            $losses++;
        }
    }

    $streakType = null;
    $streakLength = 0;
    foreach ($games as $game) {
        $won = (int) $game['team_score'] > (int) $game['opponent_score'];
        if ($streakType === null) {
            $streakType = $won;
            $streakLength = 1;
            continue;
        }

        if ($won === $streakType) {
            $streakLength++;
            continue;
        }

        break;
    }

    return [
        'record' => $wins . '-' . $losses,
        'streak' => $streakType === null ? 'No recent games' : (($streakType ? 'W' : 'L') . $streakLength),
    ];
}

function naturalList(array $items, string $conjunction = 'and'): string
{
    $items = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $items), static fn ($item): bool => $item !== ''));
    $count = count($items);

    if ($count === 0) {
        return '';
    }

    if ($count === 1) {
        return $items[0];
    }

    if ($count === 2) {
        return $items[0] . ' ' . $conjunction . ' ' . $items[1];
    }

    $last = array_pop($items);

    return implode(', ', $items) . ', ' . $conjunction . ' ' . $last;
}

function buildTeamContinuityPackage(?array $teamContinuity): array
{
    if ($teamContinuity === null || (int) ($teamContinuity['current_count'] ?? 0) === 0) {
        return [
            'brief' => null,
            'fact' => null,
            'notebook_note' => null,
            'roster_note' => null,
        ];
    }

    $previousSeasonLabel = (string) ($teamContinuity['previous_season_label'] ?? 'last season');
    $returningCount = (int) ($teamContinuity['returning_count'] ?? 0);
    $currentCount = (int) ($teamContinuity['current_count'] ?? 0);
    $newcomerCount = (int) ($teamContinuity['newcomer_count'] ?? 0);
    $continuityPct = (float) ($teamContinuity['continuity_pct'] ?? 0.0);

    $newcomerLabels = [];
    foreach (array_slice($teamContinuity['newcomers'] ?? [], 0, 2) as $newcomer) {
        $label = (string) ($newcomer['player_name'] ?? '');
        $previousClub = trim((string) ($newcomer['previous_team_name'] ?? ''));
        if ($label === '') {
            continue;
        }

        $newcomerLabels[] = $previousClub !== '' ? ($label . ' from ' . $previousClub) : $label;
    }

    $notebookNote = $returningCount . ' of ' . $currentCount . ' roster spots carried over from ' . $previousSeasonLabel;
    if ($newcomerLabels !== []) {
        $notebookNote .= ', with ' . naturalList($newcomerLabels) . ' shaping the new layer of the rotation.';
    } elseif ($newcomerCount === 0) {
        $notebookNote .= ', so the shape of the group stayed largely intact.';
    } else {
        $notebookNote .= ', while the main changes came deeper in the rotation.';
    }

    $rosterNote = round($continuityPct) . '% of the current group returned from ' . $previousSeasonLabel;
    if ($newcomerCount > 0) {
        $rosterNote .= ' · ' . $newcomerCount . ' new face' . ($newcomerCount === 1 ? '' : 's');
    }

    return [
        'brief' => [
            'label' => 'Continuity',
            'value' => round($continuityPct) . '% carry-over',
            'detail' => $returningCount . ' back from ' . $previousSeasonLabel,
        ],
        'fact' => [
            'label' => 'Roster pulse',
            'value' => $returningCount . ' back · ' . $newcomerCount . ' new',
        ],
        'notebook_note' => $notebookNote,
        'roster_note' => $rosterNote,
    ];
}

function buildHomepagePackage(
    int $seasonId,
    ?array $season,
    ?array $overview,
    array $standings,
    array $leaders,
    array $bracket,
    array $featuredGames,
    array $topPerformances
): array {
    $leader = $standings[0] ?? null;
    $runnerUp = $standings[1] ?? null;
    $champion = $bracket['champion'] ?? $leader;
    $scoringLeader = $leaders['points'][0] ?? null;
    $assistLeader = $leaders['assists'][0] ?? null;
    $featuredGame = $featuredGames[0] ?? null;
    $gamesCount = $overview !== null ? (int) ($overview['total_games'] ?? 0) : 0;

    if ($leader !== null && $runnerUp !== null) {
        $headline = $leader['team_name'] . ' hold the inside lane in ' . ($season['season_label'] ?? 'the current season') . ', but the chase behind them is still live.';
        $summary = $leader['team_name'] . ' sit first at ' . formatRecord($leader)
            . ', ' . formatSigned($leader['point_diff']) . ' on point differential, with '
            . $runnerUp['team_name'] . ' close enough to keep every round meaningful.';
    } else {
        $headline = 'A season archive built around standings pressure, club identity, and every box score.';
        $summary = 'Browse the table, jump into club pages, trace playoff paths, and move through the season one game at a time.';
    }

    $briefs = [
        [
            'label' => 'Table watch',
            'title' => $runnerUp !== null ? $runnerUp['team_name'] . ' are the closest threat to the top line.' : 'The regular-season table stays in focus.',
            'detail' => $runnerUp !== null
                ? 'Only ' . abs((int) $leader['wins'] - (int) $runnerUp['wins']) . ' win' . (abs((int) $leader['wins'] - (int) $runnerUp['wins']) === 1 ? '' : 's') . ' separate the top two clubs.'
                : 'Open the full standings and playoff cut lines.',
            'url' => pageUrl('seasons', ['season' => $seasonId]),
        ],
        [
            'label' => 'Game center',
            'title' => $featuredGame !== null
                ? $featuredGame['away_short_name'] . ' at ' . $featuredGame['home_short_name']
                : 'The latest box scores are one click away.',
            'detail' => $featuredGame !== null
                ? formatGameDate((string) $featuredGame['game_date']) . ' · ' . ($featuredGame['arena_name'] ?? 'Arena TBA')
                : 'Browse full results and game detail.',
            'url' => $featuredGame !== null ? pageUrl('boxscore', ['game' => $featuredGame['game_id']]) : pageUrl('games', ['season' => $seasonId]),
        ],
        [
            'label' => 'Player watch',
            'title' => $scoringLeader !== null ? $scoringLeader['player_name'] . ' headline the scoring race.' : 'Season leaders drive the archive.',
            'detail' => $scoringLeader !== null
                ? formatStat($scoringLeader['value']) . ' PPG and a steady push from the top creation leaders behind him.'
                : 'Jump into player pages and game logs.',
            'url' => $scoringLeader !== null ? pageUrl('player', ['player' => $scoringLeader['person_id'], 'player_season' => $seasonId]) : pageUrl('players', ['season' => $seasonId]),
        ],
    ];

    if ($assistLeader !== null && $scoringLeader !== null) {
        $briefs[2]['detail'] = formatStat($scoringLeader['value']) . ' PPG, while ' . $assistLeader['player_name'] . ' set the pace as the lead creator.';
    }

    return [
        'headline' => $headline,
        'summary' => $summary,
        'meta' => [
            ['label' => 'Season', 'value' => $season['season_label'] ?? 'Current'],
            ['label' => 'Games logged', 'value' => (string) $gamesCount],
            ['label' => 'Projected champion', 'value' => $champion['team_name'] ?? 'TBD'],
        ],
        'featured_game' => $featuredGame,
        'briefs' => $briefs,
        'power_board' => array_slice($standings, 0, 5),
        'game_center' => array_slice($featuredGames, 0, 3),
        'performances' => array_slice($topPerformances, 0, 3),
    ];
}

function buildTeamStoryPackage(?array $teamProfile, ?array $teamSnapshot, array $teamRoster, array $teamGames, array $teamHistory, ?array $teamContinuity): array
{
    if ($teamProfile === null || $teamSnapshot === null) {
        return [
            'headline' => 'Club notebook',
            'summary' => 'Season context becomes available once the club and season are selected.',
            'briefs' => [],
            'facts' => [],
            'continuity_note' => null,
            'roster_note' => null,
        ];
    }

    $primaryScorer = bestRosterStat($teamRoster, 'ppg');
    $glassLeader = bestRosterStat($teamRoster, 'rpg');
    $form = buildRecentForm($teamGames, 5);
    $continuityPackage = buildTeamContinuityPackage($teamContinuity);

    $bestSeason = null;
    foreach ($teamHistory as $historyRow) {
        if ($bestSeason === null) {
            $bestSeason = $historyRow;
            continue;
        }

        $bestRank = (int) ($bestSeason['final_rank'] ?? 99);
        $currentRank = (int) ($historyRow['final_rank'] ?? 99);
        if ($currentRank < $bestRank || ($currentRank === $bestRank && (int) $historyRow['wins'] > (int) $bestSeason['wins'])) {
            $bestSeason = $historyRow;
        }
    }

    $rank = (int) ($teamSnapshot['final_rank'] ?? 99);
    $postseasonLabel = strtolower(postseasonStatusLabel($teamSnapshot));
    $headline = $rank <= 4
        ? 'Built like a top-four side, with enough control to shape the postseason race.'
        : ($rank <= 8
            ? 'Still inside the real part of the race, balancing scoring punch with playoff urgency.'
            : 'A season still defined by pressure, with every result carrying weight in the table.');

    $summary = $teamProfile['team_name'] . ' bring ' . formatRecord($teamSnapshot)
        . ' and a ' . formatSigned($teamSnapshot['point_diff']) . ' differential into ' . ($teamSnapshot['season_label'] ?? 'the current season')
        . ', playing from ' . ($teamProfile['arena_name'] ?? 'their home floor')
        . ' under ' . ($teamSnapshot['coach_name'] ?? 'the current coaching staff')
        . ' while sitting in a ' . $postseasonLabel . '.';

    $briefs = [
        [
            'label' => 'Primary scorer',
            'value' => $primaryScorer['player_name'] ?? 'Balanced scoring',
            'detail' => $primaryScorer !== null ? formatStat($primaryScorer['ppg']) . ' PPG' : 'No individual leader yet',
        ],
        [
            'label' => 'Recent form',
            'value' => $form['record'],
            'detail' => 'Last five · ' . $form['streak'],
        ],
    ];
    if ($continuityPackage['brief'] !== null) {
        $briefs[] = $continuityPackage['brief'];
    }

    $facts = [
        ['label' => 'Coach', 'value' => (string) ($teamSnapshot['coach_name'] ?? 'TBA')],
        ['label' => 'Arena', 'value' => (string) ($teamProfile['arena_name'] ?? 'Arena TBA')],
        ['label' => 'Attendance', 'value' => formatLargeNumber($teamSnapshot['avg_attendance'] ?? 0)],
        ['label' => 'Postseason', 'value' => postseasonStatusLabel($teamSnapshot)],
        ['label' => 'Best finish', 'value' => $bestSeason !== null ? '#' . (string) $bestSeason['final_rank'] . ' in ' . (string) $bestSeason['season_label'] : 'Still building'],
        ['label' => 'Glass work', 'value' => $glassLeader !== null ? $glassLeader['player_name'] . ' · ' . formatStat($glassLeader['rpg']) . ' RPG' : 'Committee rebounding'],
        ['label' => 'Founded', 'value' => isset($teamProfile['founded_year']) ? (string) $teamProfile['founded_year'] : 'N/A'],
    ];
    if ($continuityPackage['fact'] !== null) {
        array_splice($facts, 4, 0, [$continuityPackage['fact']]);
    }

    return [
        'headline' => $headline,
        'summary' => $summary,
        'briefs' => $briefs,
        'facts' => $facts,
        'continuity_note' => $continuityPackage['notebook_note'],
        'roster_note' => $continuityPackage['roster_note'],
    ];
}

function buildPlayerStoryPackage(?array $playerProfile, array $playerSeasons, array $playerAwards, array $playerGameLog, ?array $activePlayerSummary): array
{
    if ($playerProfile === null) {
        return [
            'headline' => 'Player notebook',
            'summary' => 'Select a player to load season context, awards, and game logs.',
            'facts' => [],
            'briefs' => [],
        ];
    }

    $careerGames = 0;
    $weightedPoints = 0.0;
    $weightedRebounds = 0.0;
    $weightedAssists = 0.0;
    $clubs = [];
    $bestSeason = null;
    foreach ($playerSeasons as $season) {
        $gamesPlayed = (int) ($season['games_played'] ?? 0);
        $careerGames += $gamesPlayed;
        $weightedPoints += ((float) ($season['ppg'] ?? 0.0)) * $gamesPlayed;
        $weightedRebounds += ((float) ($season['rpg'] ?? 0.0)) * $gamesPlayed;
        $weightedAssists += ((float) ($season['apg'] ?? 0.0)) * $gamesPlayed;
        if (($season['team_name'] ?? null) !== null) {
            $clubs[(string) $season['team_name']] = true;
        }

        if (
            $bestSeason === null
            || (float) ($season['ppg'] ?? 0.0) > (float) ($bestSeason['ppg'] ?? 0.0)
            || ((float) ($season['ppg'] ?? 0.0) === (float) ($bestSeason['ppg'] ?? 0.0) && (int) ($season['games_played'] ?? 0) > (int) ($bestSeason['games_played'] ?? 0))
        ) {
            $bestSeason = $season;
        }
    }

    $careerClubCount = count($clubs);
    $awardCount = count($playerAwards);
    $latestAward = $playerAwards[0] ?? null;
    $bestGame = null;
    foreach ($playerGameLog as $log) {
        $impact = ((int) ($log['points'] ?? 0) * 1.0) + ((int) ($log['rebounds'] ?? 0) * 1.15) + ((int) ($log['assists'] ?? 0) * 1.35);
        if ($bestGame === null || $impact > $bestGame['impact']) {
            $log['impact'] = $impact;
            $bestGame = $log;
        }
    }

    $careerPpg = $careerGames > 0 ? $weightedPoints / $careerGames : 0.0;
    $careerRpg = $careerGames > 0 ? $weightedRebounds / $careerGames : 0.0;
    $careerApg = $careerGames > 0 ? $weightedAssists / $careerGames : 0.0;
    $firstSeason = $playerSeasons !== [] ? $playerSeasons[count($playerSeasons) - 1] : null;
    $lastSeason = $playerSeasons[0] ?? null;

    $headline = $activePlayerSummary !== null
        ? ($playerProfile['player_name'] . ' is carrying ' . ($activePlayerSummary['team_name'] ?? 'his club') . ' through ' . ($activePlayerSummary['season_label'] ?? 'the season') . '.')
        : ($playerProfile['player_name'] . ' has a season-by-season archive built around logs, honors, and club context.');

    $summaryParts = [];
    if ($activePlayerSummary !== null) {
        $summaryParts[] = ($activePlayerSummary['team_name'] ?? 'His club') . ' are getting ' . formatStat($activePlayerSummary['ppg']) . ' points, ' . formatStat($activePlayerSummary['rpg']) . ' rebounds, and ' . formatStat($activePlayerSummary['apg']) . ' assists per night from him in ' . ($activePlayerSummary['season_label'] ?? 'the selected season') . '.';
    }
    if ($careerClubCount > 1) {
        $summaryParts[] = 'His archive runs across ' . $careerClubCount . ' clubs, which makes the season log read more like a career path than a single-club snapshot.';
    }
    if ($latestAward !== null) {
        $summaryParts[] = 'The latest honor on the page is ' . $latestAward['award_name'] . ' from ' . $latestAward['season_label'] . '.';
    }
    if ($summaryParts === []) {
        $summaryParts[] = 'This page tracks the full season log, club history, and awards footprint for the player.';
    }

    $facts = [
        ['label' => 'Career games', 'value' => (string) $careerGames],
        ['label' => 'Career average', 'value' => formatStat($careerPpg) . ' PPG'],
        ['label' => 'Career boards', 'value' => formatStat($careerRpg) . ' RPG'],
        ['label' => 'Career creation', 'value' => formatStat($careerApg) . ' APG'],
        ['label' => 'Clubs', 'value' => (string) $careerClubCount],
        ['label' => 'Honors', 'value' => (string) $awardCount],
    ];

    if ($firstSeason !== null && $lastSeason !== null) {
        $facts[] = ['label' => 'Archive span', 'value' => $firstSeason['season_label'] . ' to ' . $lastSeason['season_label']];
    }

    $briefs = [];
    if ($bestSeason !== null) {
        $briefs[] = [
            'label' => 'Best scoring year',
            'value' => $bestSeason['season_label'],
            'detail' => formatStat($bestSeason['ppg']) . ' PPG with ' . ($bestSeason['team_name'] ?? 'his club') . '.',
        ];
    }
    if ($bestGame !== null) {
        $briefs[] = [
            'label' => 'Top single-game line',
            'value' => (string) $bestGame['points'] . ' PTS · ' . (string) $bestGame['rebounds'] . ' REB · ' . (string) $bestGame['assists'] . ' AST',
            'detail' => formatGameDate((string) $bestGame['game_date']) . ' · ' . $bestGame['matchup'],
        ];
    }
    if ($latestAward !== null) {
        $briefs[] = [
            'label' => 'Latest honor',
            'value' => $latestAward['award_name'],
            'detail' => $latestAward['season_label'] . ($latestAward['team_name'] ?? null ? ' · ' . $latestAward['team_name'] : ''),
        ];
    }

    return [
        'headline' => $headline,
        'summary' => implode(' ', $summaryParts),
        'facts' => $facts,
        'briefs' => $briefs,
    ];
}

function buildAwardsPagePackage(array $seasonAwards, array $awardsArchive): array
{
    $uniqueWinners = [];
    $winnerCounts = [];
    $clubCounts = [];
    $historyByAward = [];

    foreach ($awardsArchive as $award) {
        $personId = (int) $award['person_id'];
        $uniqueWinners[$personId] = true;
        $winnerCounts[$personId] = ($winnerCounts[$personId] ?? 0) + 1;
        if (($award['team_name'] ?? null) !== null) {
            $clubCounts[(string) $award['team_name']] = ($clubCounts[(string) $award['team_name']] ?? 0) + 1;
        }
        $historyByAward[(string) $award['award_name']][] = $award;
    }

    $mostDecorated = null;
    foreach ($awardsArchive as $award) {
        $count = $winnerCounts[(int) $award['person_id']] ?? 0;
        if ($mostDecorated === null || $count > ($mostDecorated['count'] ?? 0)) {
            $award['count'] = $count;
            $mostDecorated = $award;
        }
    }

    $clubLeaderName = 'No club yet';
    $clubLeaderCount = 0;
    foreach ($clubCounts as $clubName => $count) {
        if ($count > $clubLeaderCount) {
            $clubLeaderName = $clubName;
            $clubLeaderCount = $count;
        }
    }

    return [
        'summary_cards' => [
            ['label' => 'Season honors', 'value' => (string) count($seasonAwards), 'detail' => 'Named awards on the current season page.'],
            ['label' => 'Unique winners', 'value' => (string) count($uniqueWinners), 'detail' => 'Different players represented across the archive.'],
            ['label' => 'Most decorated', 'value' => $mostDecorated['player_name'] ?? 'TBD', 'detail' => $mostDecorated !== null ? (($mostDecorated['count'] ?? 0) . ' total honors') : 'Award archive grows by season.'],
            ['label' => 'Most awarded club', 'value' => $clubLeaderName, 'detail' => $clubLeaderCount > 0 ? ($clubLeaderCount . ' award selections') : 'Club totals will build with the archive.'],
        ],
        'season_awards' => $seasonAwards,
        'history_by_award' => $historyByAward,
        'archive_rows' => $awardsArchive,
    ];
}

function buildGameStoryCards(array $games): array
{
    $stories = [];
    foreach (array_slice($games, 0, 4) as $game) {
        $homeWon = (int) $game['home_score'] > (int) $game['away_score'];
        $winner = $homeWon ? $game['home_team_name'] : $game['away_team_name'];
        $loser = $homeWon ? $game['away_team_name'] : $game['home_team_name'];
        $winnerScore = $homeWon ? (int) $game['home_score'] : (int) $game['away_score'];
        $loserScore = $homeWon ? (int) $game['away_score'] : (int) $game['home_score'];
        $margin = abs($winnerScore - $loserScore);
        $overtime = (int) ($game['overtime_count'] ?? 0);

        $label = $overtime > 0
            ? 'Overtime final'
            : ($margin <= 4 ? 'Tight finish' : ($margin >= 15 ? 'Runaway result' : 'Final'));
        $summary = $winner . ' beat ' . $loser . ' ' . $winnerScore . '-' . $loserScore . '.';
        if ($overtime > 0) {
            $summary .= ' The game needed ' . $overtime . ' extra period' . ($overtime === 1 ? '' : 's') . ' before it broke open.';
        } elseif ($margin <= 4) {
            $summary .= ' It stayed live into the last possessions, making every trip matter.';
        } elseif ($margin >= 15) {
            $summary .= ' The margin stretched early enough to turn the fourth quarter into scoreboard management.';
        } else {
            $summary .= ' The winner controlled the key stretches without ever fully shaking the chase.';
        }

        $stories[] = [
            'label' => $label,
            'headline' => $winner . ' over ' . $loser,
            'summary' => $summary,
            'meta' => formatGameDate((string) $game['game_date']) . ' · ' . ($game['arena_name'] ?? 'Arena TBA') . ' · Attendance ' . formatLargeNumber($game['attendance'] ?? 0),
            'scoreline' => $game['away_short_name'] . ' ' . $game['away_score'] . ' - ' . $game['home_score'] . ' ' . $game['home_short_name'],
            'url' => pageUrl('boxscore', ['game' => $game['game_id']]),
        ];
    }

    return $stories;
}

function buildBoxscoreStory(array $boxscore): array
{
    $game = $boxscore['game'];
    $awayTeamId = (int) $game['away_team_id'];
    $homeTeamId = (int) $game['home_team_id'];
    $awayStats = $boxscore['team_stats'][$awayTeamId] ?? [];
    $homeStats = $boxscore['team_stats'][$homeTeamId] ?? [];
    $homeWon = (int) $game['home_score'] > (int) $game['away_score'];

    $winnerName = $homeWon ? $game['home_team_name'] : $game['away_team_name'];
    $loserName = $homeWon ? $game['away_team_name'] : $game['home_team_name'];
    $winnerScore = $homeWon ? (int) $game['home_score'] : (int) $game['away_score'];
    $loserScore = $homeWon ? (int) $game['away_score'] : (int) $game['home_score'];
    $margin = abs($winnerScore - $loserScore);
    $overtime = (int) ($game['overtime_count'] ?? 0);

    $summary = $winnerName . ' beat ' . $loserName . ' ' . $winnerScore . '-' . $loserScore . '.';
    if ($overtime > 0) {
        $summary .= ' The result was settled after ' . $overtime . ' overtime period' . ($overtime === 1 ? '' : 's') . '.';
    } elseif ($margin <= 5) {
        $summary .= ' It was a one-possession type finish for most of the closing stretch.';
    } elseif ($margin >= 15) {
        $summary .= ' The winner created separation well before the final minutes.';
    } else {
        $summary .= ' The scoreline stayed within reach, but the winning side controlled the key runs.';
    }

    $performers = [];
    foreach ($boxscore['player_stats'] as $rows) {
        foreach ($rows as $row) {
            $row['impact'] = ((int) $row['points'] * 1.0) + ((int) $row['rebounds'] * 1.15) + ((int) $row['assists'] * 1.35);
            $performers[] = $row;
        }
    }
    usort(
        $performers,
        static fn (array $left, array $right): int => [$right['impact'], $right['points'], $right['rebounds'], $right['assists']] <=> [$left['impact'], $left['points'], $left['rebounds'], $left['assists']]
    );
    $performers = array_slice($performers, 0, 4);

    $fgAway = ((int) ($awayStats['field_goals_attempted'] ?? 0)) > 0
        ? round((((int) ($awayStats['field_goals_made'] ?? 0)) / (int) $awayStats['field_goals_attempted']) * 100, 1)
        : 0.0;
    $fgHome = ((int) ($homeStats['field_goals_attempted'] ?? 0)) > 0
        ? round((((int) ($homeStats['field_goals_made'] ?? 0)) / (int) $homeStats['field_goals_attempted']) * 100, 1)
        : 0.0;

    $edges = [];
    $edges[] = $fgHome >= $fgAway
        ? ['label' => 'Shooting edge', 'team' => $game['home_team_name'], 'detail' => $game['home_team_name'] . ' shot ' . number_format($fgHome, 1, '.', '') . '% from the floor against ' . number_format($fgAway, 1, '.', '') . ' for ' . $game['away_team_name'] . '.']
        : ['label' => 'Shooting edge', 'team' => $game['away_team_name'], 'detail' => $game['away_team_name'] . ' shot ' . number_format($fgAway, 1, '.', '') . '% from the floor against ' . number_format($fgHome, 1, '.', '') . ' for ' . $game['home_team_name'] . '.'];
    $edges[] = ((int) ($homeStats['rebounds'] ?? 0)) >= ((int) ($awayStats['rebounds'] ?? 0))
        ? ['label' => 'Glass', 'team' => $game['home_team_name'], 'detail' => $game['home_team_name'] . ' won the rebound count ' . $homeStats['rebounds'] . '-' . $awayStats['rebounds'] . '.']
        : ['label' => 'Glass', 'team' => $game['away_team_name'], 'detail' => $game['away_team_name'] . ' won the rebound count ' . $awayStats['rebounds'] . '-' . $homeStats['rebounds'] . '.'];
    $edges[] = ((int) ($homeStats['turnovers'] ?? 0)) <= ((int) ($awayStats['turnovers'] ?? 0))
        ? ['label' => 'Ball security', 'team' => $game['home_team_name'], 'detail' => $game['home_team_name'] . ' only gave it away ' . $homeStats['turnovers'] . ' times, compared with ' . $awayStats['turnovers'] . ' for ' . $game['away_team_name'] . '.']
        : ['label' => 'Ball security', 'team' => $game['away_team_name'], 'detail' => $game['away_team_name'] . ' only gave it away ' . $awayStats['turnovers'] . ' times, compared with ' . $homeStats['turnovers'] . ' for ' . $game['home_team_name'] . '.'];

    return [
        'headline' => $winnerName . ' over ' . $loserName,
        'summary' => $summary,
        'performers' => $performers,
        'edges' => $edges,
    ];
}

function puzzleOperatorLabel(string $operator): string
{
    return match (trim($operator)) {
        '>=', '=>' => 'At least',
        '<=', '=<' => 'At most',
        '>' => 'Above',
        '<' => 'Below',
        '=' => 'Exactly',
        default => 'Target',
    };
}

function formatPuzzleTarget(array $column): string
{
    $targetValue = $column['target_value'] ?? null;
    if ($targetValue === null || $targetValue === '') {
        return 'Open target';
    }

    $numeric = (float) $targetValue;
    $formatted = fmod($numeric, 1.0) === 0.0
        ? number_format($numeric, 0, '.', '')
        : number_format($numeric, 1, '.', '');

    $units = trim((string) ($column['units'] ?? ''));
    if ($units !== '' && str_contains($units, '%')) {
        return $formatted . '%';
    }

    return $formatted . ($units !== '' ? ' ' . $units : '');
}

function buildPuzzlePagePackage(array $board, ?array $puzzleResult): array
{
    $rowCount = count($board['rows']);
    $columnCount = count($board['columns']);
    $totalCells = $rowCount * $columnCount;
    $selectedCount = 0;
    $correctCount = 0;

    if ($puzzleResult !== null) {
        foreach (($puzzleResult['selected'] ?? []) as $selected) {
            if ((int) $selected > 0) {
                $selectedCount++;
            }
        }

        foreach (($puzzleResult['correct'] ?? []) as $isCorrect) {
            if ($isCorrect) {
                $correctCount++;
            }
        }
    }

    $units = array_values(array_filter(array_map(static fn (array $column): string => trim((string) ($column['units'] ?? '')), $board['columns'])));
    $clueCards = [];
    foreach ($board['columns'] as $column) {
        $clueCards[] = [
            'eyebrow' => 'Column ' . (string) $column['column_position'],
            'title' => (string) ($column['units'] ?? 'Clue'),
            'detail' => (string) ($column['clue_text'] ?? ''),
            'operator' => puzzleOperatorLabel((string) ($column['comparison_operator'] ?? '')),
            'target' => formatPuzzleTarget($column),
        ];
    }

    $summaryCards = [
        [
            'label' => 'Season',
            'value' => (string) ($board['puzzle']['season_label'] ?? 'Archive season'),
            'detail' => formatGameDate((string) ($board['puzzle']['puzzle_date'] ?? '')),
        ],
        [
            'label' => 'Board size',
            'value' => $totalCells . ' cells',
            'detail' => $rowCount . ' clubs x ' . $columnCount . ' clues',
        ],
        [
            'label' => 'Clue mix',
            'value' => $units !== [] ? naturalList($units) : 'Season stats',
            'detail' => 'Live current-season benchmarks pulled from the puzzle archive.',
        ],
        [
            'label' => 'Latest attempt',
            'value' => $puzzleResult !== null ? ($correctCount . ' / ' . $totalCells) : 'Not checked yet',
            'detail' => $puzzleResult !== null
                ? ($selectedCount . ' picks submitted on the latest board check')
                : 'Fill the grid, then check every cell in one pass.',
        ],
    ];

    return [
        'headline' => 'Three clubs, three live stat clues, and one roster pool per row.',
        'summary' => 'Every answer has to come from the selected club roster for this season and satisfy the stat clue at the top of the column. The board keeps the rules simple, but the clue mix changes from puzzle to puzzle.',
        'summary_cards' => $summaryCards,
        'clue_cards' => $clueCards,
        'filled_count' => $selectedCount,
        'correct_count' => $correctCount,
        'total_cells' => $totalCells,
    ];
}

function buildBreadcrumbs(
    string $page,
    int $seasonId,
    ?array $selectedSeason,
    ?array $teamProfile,
    ?array $teamSnapshot,
    ?array $playerProfile,
    ?array $activePlayerSummary,
    ?array $boxscore
): array {
    $items = [
        ['label' => 'Home', 'url' => pageUrl('home')],
    ];

    switch ($page) {
        case 'seasons':
            $items[] = ['label' => 'Season Archive', 'url' => null];
            if ($selectedSeason !== null) {
                $items[] = ['label' => (string) $selectedSeason['season_label'], 'url' => null];
            }
            break;
        case 'teams':
            $items[] = ['label' => 'Clubs', 'url' => null];
            break;
        case 'team':
            $items[] = ['label' => 'Clubs', 'url' => pageUrl('teams', ['season' => $teamSnapshot['season_id'] ?? $seasonId])];
            $items[] = ['label' => (string) ($teamProfile['team_name'] ?? 'Club'), 'url' => null];
            break;
        case 'players':
            $items[] = ['label' => 'Players', 'url' => null];
            break;
        case 'player':
            $items[] = ['label' => 'Players', 'url' => pageUrl('players', ['season' => $activePlayerSummary['season_id'] ?? $seasonId])];
            $items[] = ['label' => (string) ($playerProfile['player_name'] ?? 'Player'), 'url' => null];
            break;
        case 'awards':
            $items[] = ['label' => 'Awards', 'url' => null];
            break;
        case 'games':
            $items[] = ['label' => 'Scores', 'url' => null];
            break;
        case 'boxscore':
            $items[] = ['label' => 'Scores', 'url' => pageUrl('games', ['season' => $seasonId])];
            $items[] = ['label' => 'Box Score', 'url' => null];
            if (($boxscore['game']['away_short_name'] ?? null) !== null && ($boxscore['game']['home_short_name'] ?? null) !== null) {
                $items[] = ['label' => (string) $boxscore['game']['away_short_name'] . ' at ' . (string) $boxscore['game']['home_short_name'], 'url' => null];
            }
            break;
        case 'playoffs':
            $items[] = ['label' => 'Playoffs', 'url' => null];
            break;
        case 'matchup':
            $items[] = ['label' => 'Clubs', 'url' => pageUrl('teams', ['season' => $seasonId])];
            $items[] = ['label' => 'Matchup', 'url' => null];
            break;
        case 'play':
            $items[] = ['label' => 'Grid', 'url' => null];
            break;
        case 'admin':
            $items[] = ['label' => 'Data Desk', 'url' => null];
            break;
        case 'queries':
            $items[] = ['label' => 'Data Desk', 'url' => pageUrl('admin')];
            $items[] = ['label' => 'Advanced Queries', 'url' => null];
            break;
        case 'crud':
            $items[] = ['label' => 'Data Desk', 'url' => pageUrl('admin')];
            $items[] = ['label' => 'Table View', 'url' => null];
            break;
        default:
            return [];
    }

    return $items;
}

function isPublicPage(string $page): bool
{
    return !in_array($page, ['admin', 'queries', 'crud'], true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle); ?> | Euroleague Atlas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(assetUrl('styles.css')); ?>">
</head>
<body class="<?= h(isPublicPage($page) ? 'theme-public' : 'theme-admin'); ?>">
    <div class="page-shell">
        <header class="masthead">
            <div class="masthead-ribbon">
                <span class="ribbon-tag">Season Snapshot</span>
                <div class="ribbon-stream">
                    <span class="ribbon-item">
                        <strong><?= h($selectedSeason['season_label'] ?? 'Current season'); ?></strong>
                        <span>EuroLeague archive</span>
                    </span>
                    <?php if ($topSeed !== null): ?>
                        <span class="ribbon-item">
                            <strong>Table</strong>
                            <span><?= h($topSeed['team_name']); ?> <?= h(formatRecord($topSeed)); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ($scoringSnapshot !== null): ?>
                        <span class="ribbon-item">
                            <strong>Scoring</strong>
                            <span><?= h($scoringSnapshot['player_name']); ?> <?= h(formatStat($scoringSnapshot['value'])); ?> PPG</span>
                        </span>
                    <?php endif; ?>
                    <?php if ($latestFinal !== null): ?>
                        <a class="ribbon-item ribbon-link" href="<?= h(pageUrl('boxscore', ['game' => $latestFinal['game_id']])); ?>">
                            <strong>Latest Final</strong>
                            <span><?= h($latestFinal['away_short_name']); ?> <?= h((string) $latestFinal['away_score']); ?> - <?= h((string) $latestFinal['home_score']); ?> <?= h($latestFinal['home_short_name']); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="masthead-main">
                <a class="wordmark" href="<?= h(pageUrl('home')); ?>">
                    <span class="wordmark-badge">EA</span>
                    <span>
                        <em class="wordmark-kicker">European basketball archive</em>
                        <strong>Euroleague Atlas</strong>
                        <small>Season archive, club pages, player logs, playoff context, and box-score detail across the league.</small>
                    </span>
                </a>
                <div class="masthead-actions">
                    <form method="get" class="season-switcher">
                        <input type="hidden" name="page" value="<?= h($seasonRailPage); ?>">
                        <label>
                            <span>Browse season</span>
                            <select name="season">
                                <?php foreach ($seasons as $seasonRow): ?>
                                    <option value="<?= h((string) $seasonRow['season_id']); ?>" <?= (int) $seasonRow['season_id'] === $seasonId ? 'selected' : ''; ?>><?= h($seasonRow['season_label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="button button-small button-primary">Go</button>
                    </form>
                    <div class="quick-actions">
                        <a class="ghost-link ghost-link-subtle" href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Table</a>
                        <a class="ghost-link ghost-link-subtle" href="<?= h(pageUrl('playoffs', ['season' => $seasonId])); ?>">Bracket</a>
                    </div>
                </div>
            </div>
            <div class="masthead-nav">
                <nav class="site-nav site-nav-primary">
                    <a class="<?= $navPage === 'home' ? 'active' : ''; ?>" href="<?= h(pageUrl('home')); ?>">Home</a>
                    <a class="<?= $navPage === 'seasons' ? 'active' : ''; ?>" href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Seasons</a>
                    <a class="<?= $navPage === 'teams' ? 'active' : ''; ?>" href="<?= h(pageUrl('teams', ['season' => $seasonId])); ?>">Teams</a>
                    <a class="<?= $navPage === 'players' ? 'active' : ''; ?>" href="<?= h(pageUrl('players', ['season' => $seasonId])); ?>">Players</a>
                    <a class="<?= $navPage === 'games' ? 'active' : ''; ?>" href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">Scores</a>
                </nav>
                <nav class="utility-nav" aria-label="More sections">
                    <a class="<?= $navPage === 'awards' ? 'active' : ''; ?>" href="<?= h(pageUrl('awards', ['season' => $seasonId])); ?>">Awards</a>
                    <a class="<?= $navPage === 'playoffs' ? 'active' : ''; ?>" href="<?= h(pageUrl('playoffs', ['season' => $seasonId])); ?>">Playoffs</a>
                    <a class="<?= $navPage === 'play' ? 'active' : ''; ?>" href="<?= h(pageUrl('play')); ?>">Grid</a>
                    <?php if (!isPublicPage($page)): ?>
                        <a class="<?= $navPage === 'admin' ? 'active' : ''; ?>" href="<?= h(pageUrl('admin')); ?>">Data Desk</a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php if ($mastheadClubs !== []): ?>
                <div class="masthead-deck">
                    <?php foreach ($mastheadClubs as $club): ?>
                        <a class="snapshot-card" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">
                            <?= clubIdentityMarkup($club, 'sm'); ?>
                            <div>
                                <span>Seed <?= h((string) $club['final_rank']); ?></span>
                                <strong><?= h($club['team_name']); ?></strong>
                            </div>
                            <small><?= h(formatRecord($club)); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($flash !== null): ?>
            <section class="flash flash-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></section>
        <?php endif; ?>

        <?php if ($breadcrumbItems !== []): ?>
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <?php foreach ($breadcrumbItems as $index => $crumb): ?>
                    <?php $isLast = $index === array_key_last($breadcrumbItems); ?>
                    <?php if (!$isLast && $crumb['url'] !== null): ?>
                        <a class="crumb" href="<?= h($crumb['url']); ?>"><?= h($crumb['label']); ?></a>
                    <?php else: ?>
                        <span class="crumb <?= $isLast ? 'current' : ''; ?>"><?= h($crumb['label']); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($page === 'home'): ?>
            <section class="editorial-grid">
                <article class="lead-feature hero-panel">
                    <div class="lead-feature-head">
                        <div>
                            <p class="eyebrow">Lead Story</p>
                            <h1><?= h($homepagePackage['headline']); ?></h1>
                        </div>
                        <span class="season-chip"><?= h($selectedSeason['season_label'] ?? 'Current season'); ?></span>
                    </div>
                    <p class="lede"><?= h($homepagePackage['summary']); ?></p>
                    <div class="lead-meta-strip">
                        <?php foreach ($homepagePackage['meta'] as $meta): ?>
                            <div class="meta-pair">
                                <span><?= h($meta['label']); ?></span>
                                <strong><?= h($meta['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($homepagePackage['featured_game'] !== null): ?>
                        <?php $featureGame = $homepagePackage['featured_game']; ?>
                        <a class="feature-matchup" href="<?= h(pageUrl('boxscore', ['game' => $featureGame['game_id']])); ?>">
                            <div class="feature-matchup-copy">
                                <span>Game Center</span>
                                <strong><?= h($featureGame['away_team_name']); ?> at <?= h($featureGame['home_team_name']); ?></strong>
                                <small>
                                    <?= h(formatGameDate((string) $featureGame['game_date'])); ?>
                                    · <?= h($featureGame['arena_name'] ?? 'Arena TBA'); ?>
                                    <?php if ((int) ($featureGame['overtime_count'] ?? 0) > 0): ?>
                                        · <?= h((string) $featureGame['overtime_count']); ?> OT
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="feature-matchup-score">
                                <span><?= h((string) $featureGame['away_score']); ?></span>
                                <small>-</small>
                                <span><?= h((string) $featureGame['home_score']); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                    <div class="cta-row">
                        <a class="button button-primary" href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Open season archive</a>
                        <a class="button button-secondary" href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">Browse results</a>
                    </div>
                </article>
                <aside class="editorial-stack">
                    <?php foreach ($homepagePackage['briefs'] as $brief): ?>
                        <a class="editorial-note story-card" href="<?= h($brief['url']); ?>">
                            <span><?= h($brief['label']); ?></span>
                            <strong><?= h($brief['title']); ?></strong>
                            <small><?= h($brief['detail']); ?></small>
                        </a>
                    <?php endforeach; ?>
                </aside>
            </section>

            <?php if ($homepageStoryRail !== []): ?>
                <section class="module-panel story-rail-panel">
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Story Rail</p>
                            <h2>Don't miss the pressure points</h2>
                        </div>
                        <small>Data-driven notes from the standings, bracket, and box scores.</small>
                    </div>
                    <div class="story-rail">
                        <?php foreach ($homepageStoryRail as $railCard): ?>
                            <a class="rail-card" href="<?= h($railCard['url']); ?>">
                                <span><?= h($railCard['label']); ?></span>
                                <strong><?= h($railCard['title']); ?></strong>
                                <p><?= h($railCard['detail']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Power Index</p>
                            <h2>Table pressure at the top</h2>
                        </div>
                        <a class="text-link" href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Full standings</a>
                    </div>
                    <div class="power-grid">
                        <?php foreach ($homepagePackage['power_board'] as $club): ?>
                            <a class="power-row" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">
                                <span class="power-rank">#<?= h((string) $club['final_rank']); ?></span>
                                <?= clubIdentityMarkup($club, 'sm'); ?>
                                <div class="power-copy">
                                    <strong><?= h($club['team_name']); ?></strong>
                                    <small><?= h(formatRecord($club)); ?> · <?= h(formatSigned($club['point_diff'])); ?></small>
                                </div>
                                <div class="power-tail">
                                    <span><?= h(formatStat($club['points_per_game'])); ?> PPG</span>
                                    <small><?= h($club['country_name']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head">
                            <div>
                                <p class="eyebrow">Game Center</p>
                                <h2>Featured finals</h2>
                            </div>
                            <a class="text-link" href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">Scoreboard</a>
                        </div>
                        <div class="story-stack">
                            <?php foreach ($homepagePackage['game_center'] as $game): ?>
                                <a class="editorial-game-card" href="<?= h(pageUrl('boxscore', ['game' => $game['game_id']])); ?>">
                                    <div>
                                        <span><?= h(formatGameDate((string) $game['game_date'])); ?></span>
                                        <strong><?= h($game['away_short_name']); ?> <?= h((string) $game['away_score']); ?> - <?= h((string) $game['home_score']); ?> <?= h($game['home_short_name']); ?></strong>
                                        <small><?= h($game['arena_name'] ?? 'Arena TBA'); ?> · Margin <?= h((string) $game['margin']); ?></small>
                                    </div>
                                    <span class="mini-rank-pair">#<?= h((string) ($game['away_rank'] ?? '-')); ?> / #<?= h((string) ($game['home_rank'] ?? '-')); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="module-panel">
                        <div class="module-head">
                            <div>
                                <p class="eyebrow">Performance Watch</p>
                                <h2>Standout lines</h2>
                            </div>
                            <a class="text-link" href="<?= h(pageUrl('players', ['season' => $seasonId])); ?>">Player archive</a>
                        </div>
                        <div class="story-stack">
                            <?php foreach ($homepagePackage['performances'] as $performance): ?>
                                <a class="performance-card" href="<?= h(pageUrl('player', ['player' => $performance['person_id'], 'player_season' => $seasonId])); ?>">
                                    <div>
                                        <span><?= h(formatGameDate((string) $performance['game_date'])); ?></span>
                                        <strong><?= h($performance['player_name']); ?></strong>
                                        <small><?= h($performance['team_name'] ?? 'Club'); ?> vs <?= h($performance['opponent_name']); ?></small>
                                    </div>
                                    <div class="performance-line">
                                        <strong><?= h((string) $performance['points']); ?></strong>
                                        <small>PTS</small>
                                        <span><?= h((string) $performance['rebounds']); ?> REB · <?= h((string) $performance['assists']); ?> AST</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>

            <section class="content-grid two-up">
                <article class="module-panel">
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Standings</p>
                            <h2>Top of the table</h2>
                        </div>
                        <a class="text-link" href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Full table</a>
                    </div>
                    <div class="table-scroll">
                        <table class="stats-table compact-table">
                            <thead>
                                <tr>
                                    <th>Rk</th>
                                    <th>Club</th>
                                    <th>W-L</th>
                                    <th>Diff</th>
                                    <th>PPG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($homeStandings as $club): ?>
                                    <tr>
                                        <td><?= h((string) $club['final_rank']); ?></td>
                                        <td>
                                            <a class="club-link" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">
                                                <?= clubMark($club, 'xs'); ?>
                                                <span><?= h($club['team_name']); ?></span>
                                            </a>
                                        </td>
                                        <td><?= h(formatRecord($club)); ?></td>
                                        <td><?= h(formatSigned($club['point_diff'])); ?></td>
                                        <td><?= h(formatStat($club['points_per_game'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
                <article class="module-panel">
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Roundup</p>
                            <h2>Latest results</h2>
                        </div>
                        <a class="text-link" href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">All results</a>
                    </div>
                    <div class="score-feed">
                        <?php foreach ($recentGames as $game): ?>
                            <a class="score-card <?= h(winnerClass($game)); ?>" href="<?= h(pageUrl('boxscore', ['game' => $game['game_id']])); ?>">
                                <div>
                                    <span><?= h(formatGameDate((string) $game['game_date'])); ?></span>
                                    <strong><?= h($game['away_short_name']); ?> <?= h((string) $game['away_score']); ?> at <?= h($game['home_short_name']); ?> <?= h((string) $game['home_score']); ?></strong>
                                </div>
                                <small><?= h($game['arena_name'] ?? 'Arena TBA'); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>

            <section class="content-grid three-up">
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Scoring Race</p><h2>Points leaders</h2></div></div>
                    <ol class="leader-list">
                        <?php foreach ($seasonLeaders['points'] as $leader): ?>
                            <li>
                                <a class="leader-entry" href="<?= h(pageUrl('player', ['player' => $leader['person_id'], 'player_season' => $seasonId])); ?>">
                                    <span><?= h($leader['player_name']); ?></span>
                                    <strong><?= h(formatStat($leader['value'])); ?></strong>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </article>
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Playoff Watch</p><h2>Projected bracket</h2></div></div>
                    <div class="story-stack">
                        <?php if (($playoffBracket['champion'] ?? null) !== null): ?>
                            <div class="story-card compact">
                                <span>Front-runner</span>
                                <strong><?= h($playoffBracket['champion']['team_name']); ?></strong>
                                <small><?= h(postseasonStatusLabel($playoffBracket['champion'])); ?> with the strongest projected title path from the current table.</small>
                            </div>
                        <?php endif; ?>
                        <?php foreach (array_slice($playoffBracket['quarterfinals'] ?? [], 0, 2) as $series): ?>
                            <div class="story-card compact">
                                <span><?= h($series['label']); ?></span>
                                <strong><?= h($series['winner']['team_name']); ?></strong>
                                <small><?= h($series['series_score']); ?> · <?= h($series['headline']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Club Radar</p><h2>Featured clubs</h2></div></div>
                    <div class="club-stack">
                        <?php foreach (array_slice($teamDirectory, 0, 4) as $club): ?>
                            <a class="club-slab" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">
                                <?= clubMark($club, 'sm'); ?>
                                <div>
                                    <strong><?= h($club['team_name']); ?></strong>
                                    <small>#<?= h((string) $club['final_rank']); ?> · <?= h(formatRecord($club)); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
        <?php endif; ?>

        <?php if ($page === 'seasons' && $seasonOverview !== null): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Season Archive</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?></h1>
                    <p class="lede"><?= h($seasonNarrative['summary']); ?></p>
                </div>
                <div class="cta-row compact">
                    <a class="button button-primary" href="<?= h(pageUrl('playoffs', ['season' => $seasonId])); ?>">View playoff picture</a>
                    <a class="button button-secondary" href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">Open scoreboard</a>
                </div>
            </section>

            <section class="metric-row">
                <article class="metric-card"><span>Competition</span><strong><?= h($seasonOverview['competition_name']); ?></strong></article>
                <article class="metric-card"><span>Games</span><strong><?= h((string) $seasonOverview['total_games']); ?></strong></article>
                <article class="metric-card"><span>Total Attendance</span><strong><?= h(formatLargeNumber($seasonOverview['total_attendance'])); ?></strong></article>
                <article class="metric-card"><span>Average Attendance</span><strong><?= h(formatLargeNumber($seasonOverview['average_attendance'])); ?></strong></article>
            </section>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Sortable Standings</p>
                            <h2>Regular-season table</h2>
                        </div>
                        <small><?= h((string) $standingsPager['total']); ?> clubs</small>
                    </div>
                    <div class="table-scroll">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th><?= sortLink('seasons', 'Rk', 'rank', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'Club', 'team', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'W', 'wins', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'L', 'losses', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'PF', 'pf', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'PA', 'pa', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'Diff', 'diff', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                    <th><?= sortLink('seasons', 'Att', 'attendance', $standingsSort, $standingsDir, 'standings_sort', 'standings_dir', ['season' => $seasonId, 'standings_page' => 1]); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seasonStandings as $club): ?>
                                    <tr>
                                        <td><?= h((string) $club['final_rank']); ?></td>
                                        <td>
                                            <a class="club-link" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">
                                                <?= clubMark($club, 'xs'); ?>
                                                <span><?= h($club['team_name']); ?></span>
                                            </a>
                                        </td>
                                        <td><?= h((string) $club['wins']); ?></td>
                                        <td><?= h((string) $club['losses']); ?></td>
                                        <td><?= h((string) $club['points_for']); ?></td>
                                        <td><?= h((string) $club['points_against']); ?></td>
                                        <td><?= h((string) $club['point_diff']); ?></td>
                                        <td><?= h(formatLargeNumber($club['avg_attendance'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= renderPagination('seasons', ['season' => $seasonId, 'standings_sort' => $standingsSort, 'standings_dir' => $standingsDir], 'standings_page', $standingsPager); ?>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Top Story</p><h2>Title path</h2></div></div>
                        <?php if (($playoffBracket['champion'] ?? null) !== null): ?>
                            <div class="story-card tall">
                                <?= clubIdentityMarkup($playoffBracket['champion'], 'md'); ?>
                                <div>
                                    <strong><?= h($playoffBracket['champion']['team_name']); ?></strong>
                                    <small><?= h(postseasonStatusLabel($playoffBracket['champion'])); ?> and the current projected champion from the bracket model.</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a class="button button-secondary full-width" href="<?= h(pageUrl('playoffs', ['season' => $seasonId])); ?>">Open full bracket</a>
                    </article>
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">League Leaders</p><h2>Race tracker</h2></div></div>
                        <div class="story-stack">
                            <?php foreach (['points' => 'Scoring', 'rebounds' => 'Rebounding', 'assists' => 'Playmaking'] as $key => $label): ?>
                                <?php $leader = $seasonLeaders[$key][0] ?? null; ?>
                                <?php if ($leader !== null): ?>
                                    <a class="story-card compact" href="<?= h(pageUrl('player', ['player' => $leader['person_id'], 'player_season' => $seasonId])); ?>">
                                        <span><?= h($label); ?></span>
                                        <strong><?= h($leader['player_name']); ?></strong>
                                        <small><?= h(formatStat($leader['value'])); ?> <?= h($leader['label']); ?></small>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>
        <?php endif; ?>

        <?php if ($page === 'teams'): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Club Directory</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?> clubs</h1>
                    <p class="lede">Move from the standings into club pages, rosters, recent form, and direct rivalry pages.</p>
                </div>
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="teams">
                    <input type="hidden" name="season" value="<?= h((string) $seasonId); ?>">
                    <input type="search" name="team_search" value="<?= h($teamSearch); ?>" placeholder="Search club, country, nickname, or arena">
                    <button type="submit" class="button button-primary">Search</button>
                </form>
            </section>

            <section class="club-grid">
                <?php foreach ($filteredTeams as $club): ?>
                    <article class="club-card <?= h(clubMotifClass($club)); ?>" style="<?= h(clubThemeStyle($club, 0.16, 0.08)); ?>">
                        <div class="club-card-top">
                            <?= clubIdentityMarkup($club, 'lg'); ?>
                            <div>
                                <span class="rank-pill">#<?= h((string) $club['final_rank']); ?></span>
                                <small><?= h($club['country_name']); ?></small>
                            </div>
                        </div>
                        <h2><?= h($club['team_name']); ?></h2>
                        <p><?= h($club['nickname'] ?? ''); ?> · <?= h($club['arena_name'] ?? 'Arena TBA'); ?></p>
                        <div class="club-metric-grid">
                            <div><span>Record</span><strong><?= h(formatRecord($club)); ?></strong></div>
                            <div><span>Point Diff</span><strong><?= h(formatSigned($club['point_diff'])); ?></strong></div>
                            <div><span>PPG</span><strong><?= h(formatStat($club['points_per_game'])); ?></strong></div>
                            <div><span>Attendance</span><strong><?= h(formatLargeNumber($club['avg_attendance'])); ?></strong></div>
                        </div>
                        <div class="cta-row compact">
                            <a class="button button-primary" href="<?= h(pageUrl('team', ['team' => $club['team_id'], 'team_season' => $seasonId])); ?>">Club page</a>
                            <a class="button button-secondary" href="<?= h(pageUrl('matchup', ['season' => $seasonId, 'team' => $club['team_id']])); ?>">Compare</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'team' && $teamProfile !== null): ?>
            <section class="team-hero club-hero <?= h(clubMotifClass($teamProfile)); ?>" style="<?= h(clubThemeStyle($teamProfile)); ?>">
                <div class="team-hero-main">
                    <div class="club-banner">
                        <?= clubIdentityMarkup($teamProfile, 'xl'); ?>
                        <div class="club-banner-copy">
                            <p class="eyebrow">Club Profile</p>
                            <h1><?= h($teamProfile['team_name']); ?></h1>
                            <p class="lede"><?= h($teamStoryPackage['headline'] ?? 'Club notebook'); ?></p>
                            <div class="club-pill-row">
                                <?php if ($teamSnapshot !== null): ?>
                                    <span class="season-chip"><?= h($teamSnapshot['season_label']); ?></span>
                                <?php endif; ?>
                                <span class="pill"><?= h($teamProfile['country_name']); ?></span>
                                <span class="pill"><?= h($teamProfile['arena_name'] ?? 'Arena TBA'); ?></span>
                                <span class="pill">Founded <?= h((string) $teamProfile['founded_year']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="team-hero-side club-hero-aside">
                    <?php foreach (($teamStoryPackage['briefs'] ?? []) as $brief): ?>
                        <article class="club-brief-card">
                            <span><?= h($brief['label']); ?></span>
                            <strong><?= h($brief['value']); ?></strong>
                            <small><?= h($brief['detail']); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="season-strip card-strip">
                <?php foreach ($teamHistory as $historyRow): ?>
                    <a class="season-link <?= (int) $historyRow['season_id'] === (int) ($teamSnapshot['season_id'] ?? 0) ? 'active' : ''; ?>" href="<?= h(pageUrl('team', ['team' => $teamProfile['team_id'], 'team_season' => $historyRow['season_id']])); ?>"><?= h($historyRow['season_label']); ?></a>
                <?php endforeach; ?>
            </section>

            <?php if ($teamSnapshot !== null): ?>
                <section class="metric-row">
                    <article class="metric-card"><span>Coach</span><strong><?= h($teamSnapshot['coach_name'] ?? 'TBA'); ?></strong></article>
                    <article class="metric-card"><span>Point Diff</span><strong><?= h(formatSigned($teamSnapshot['point_diff'])); ?></strong></article>
                    <article class="metric-card"><span>Attendance</span><strong><?= h(formatLargeNumber($teamSnapshot['avg_attendance'])); ?></strong></article>
                    <article class="metric-card"><span>Postseason</span><strong><?= h(postseasonStatusLabel($teamSnapshot)); ?></strong></article>
                </section>

                <section class="club-notebook-grid">
                    <article class="club-notebook-panel <?= h(clubMotifClass($teamProfile)); ?>" style="<?= h(clubThemeStyle($teamProfile, 0.28, 0.16)); ?>">
                        <p class="eyebrow">Club Notebook</p>
                        <h2><?= h($teamProfile['team_name']); ?> in <?= h($teamSnapshot['season_label']); ?></h2>
                        <p class="lede"><?= h($teamStoryPackage['summary'] ?? ''); ?></p>
                        <?php if (!empty($teamStoryPackage['continuity_note'])): ?>
                            <p class="muted club-context-note"><?= h($teamStoryPackage['continuity_note']); ?></p>
                        <?php endif; ?>
                        <div class="club-fact-grid">
                            <?php foreach (($teamStoryPackage['facts'] ?? []) as $fact): ?>
                                <div class="club-fact-card">
                                    <span><?= h($fact['label']); ?></span>
                                    <strong><?= h($fact['value']); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <aside class="club-brief-stack">
                        <?php foreach (($teamStoryPackage['briefs'] ?? []) as $brief): ?>
                            <article class="story-card compact club-brief-note">
                                <span><?= h($brief['label']); ?></span>
                                <strong><?= h($brief['value']); ?></strong>
                                <small><?= h($brief['detail']); ?></small>
                            </article>
                        <?php endforeach; ?>
                    </aside>
                </section>
            <?php endif; ?>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Roster</p><h2><?= h($teamSnapshot['season_label'] ?? 'Season'); ?> squad</h2></div></div>
                    <?php if (!empty($teamStoryPackage['roster_note'])): ?>
                        <p class="muted roster-context-note"><?= h($teamStoryPackage['roster_note']); ?></p>
                    <?php endif; ?>
                    <div class="table-scroll">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Player</th>
                                    <th>Pos</th>
                                    <th>GP</th>
                                    <th>PPG</th>
                                    <th>RPG</th>
                                    <th>APG</th>
                                    <th>FG%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamRoster as $player): ?>
                                    <tr>
                                        <td><?= h((string) $player['jersey_number']); ?></td>
                                        <td><a class="text-link" href="<?= h(pageUrl('player', ['player' => $player['person_id'], 'player_season' => $teamSnapshot['season_id'] ?? $seasonId])); ?>"><?= h($player['player_name']); ?></a></td>
                                        <td><?= h($player['position']); ?></td>
                                        <td><?= h((string) ($player['games_played'] ?? 0)); ?></td>
                                        <td><?= h(formatStat($player['ppg'])); ?></td>
                                        <td><?= h(formatStat($player['rpg'])); ?></td>
                                        <td><?= h(formatStat($player['apg'])); ?></td>
                                        <td><?= h(formatStat($player['fg_pct'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Recent Form</p><h2>Latest results</h2></div></div>
                        <div class="score-feed compact-feed">
                            <?php foreach ($teamGames as $game): ?>
                                <a class="score-card" href="<?= h(pageUrl('boxscore', ['game' => $game['game_id']])); ?>">
                                    <div>
                                        <span><?= h(formatGameDate((string) $game['game_date'])); ?> · <?= h($game['venue']); ?></span>
                                        <strong><?= h($teamProfile['short_name']); ?> <?= h((string) $game['team_score']); ?> - <?= h((string) $game['opponent_score']); ?> <?= h($game['opponent_short_name']); ?></strong>
                                    </div>
                                    <small><?= h($game['opponent_name']); ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Rival Finder</p><h2>Compare clubs</h2></div></div>
                        <div class="club-stack">
                            <?php foreach (array_slice($teamCompareLinks, 0, 4) as $club): ?>
                                <a class="club-slab" href="<?= h(pageUrl('matchup', ['season' => $teamSnapshot['season_id'] ?? $seasonId, 'team' => $teamProfile['team_id'], 'opponent' => $club['team_id']])); ?>">
                                    <?= clubMark($club, 'xs'); ?>
                                    <div>
                                        <strong><?= h($club['team_name']); ?></strong>
                                        <small><?= h(formatRecord($club)); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>
        <?php endif; ?>

        <?php if ($page === 'players'): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Player Index</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?> player stats</h1>
                    <p class="lede">Sortable leaderboards, club context, and deep game logs for every player in the archive.</p>
                </div>
                <div class="hero-actions-stack">
                    <form method="get" class="search-form">
                        <input type="hidden" name="page" value="players">
                        <input type="hidden" name="season" value="<?= h((string) $seasonId); ?>">
                        <input type="search" name="player_search" value="<?= h($playerSearch); ?>" placeholder="Search by name, club, nationality, or position">
                        <button type="submit" class="button button-primary">Search</button>
                    </form>
                    <a class="button button-secondary" href="<?= h(pageUrl('awards', ['season' => $seasonId])); ?>">Open awards archive</a>
                </div>
            </section>

            <section class="module-panel">
                <div class="module-head">
                    <div><p class="eyebrow">Sortable Leaders</p><h2>Season player table</h2></div>
                    <small><?= h((string) $playerPager['total']); ?> players</small>
                </div>
                <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th><?= sortLink('players', 'Player', 'player', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'Team', 'team', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'Pos', 'position', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'GP', 'gp', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'PPG', 'ppg', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'RPG', 'rpg', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th><?= sortLink('players', 'APG', 'apg', $playerSort, $playerDir, 'player_sort', 'player_dir', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_page' => 1]); ?></th>
                                <th>FG%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td><a class="text-link" href="<?= h(pageUrl('player', ['player' => $player['person_id'], 'player_season' => $seasonId])); ?>"><?= h($player['player_name']); ?></a></td>
                                    <td><?= h($player['team_name'] ?? 'No club'); ?></td>
                                    <td><?= h($player['position']); ?></td>
                                    <td><?= h((string) $player['games_played']); ?></td>
                                    <td><?= h(formatStat($player['ppg'])); ?></td>
                                    <td><?= h(formatStat($player['rpg'])); ?></td>
                                    <td><?= h(formatStat($player['apg'])); ?></td>
                                    <td><?= h(formatStat($player['fg_pct'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= renderPagination('players', ['season' => $seasonId, 'player_search' => $playerSearch, 'player_sort' => $playerSort, 'player_dir' => $playerDir], 'player_page', $playerPager); ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'player' && $playerProfile !== null): ?>
            <section class="page-hero player-hero player-hero-rich">
                <div class="player-hero-main">
                    <?= playerPortraitMarkup($playerProfile, $activePlayerSummary, 'xl'); ?>
                    <div class="player-hero-copy">
                        <p class="eyebrow">Player Page</p>
                        <h1><?= h($playerProfile['player_name']); ?></h1>
                        <p class="lede"><?= h($playerStoryPackage['headline'] ?? ($playerProfile['nationality'] . ' · ' . $playerProfile['position'])); ?></p>
                        <div class="club-pill-row player-pill-row">
                            <span class="pill"><?= h($playerProfile['nationality']); ?></span>
                            <span class="pill"><?= h($playerProfile['position']); ?></span>
                            <span class="pill"><?= h((string) $playerProfile['height']); ?> cm</span>
                            <span class="pill"><?= h((string) $playerProfile['weight']); ?> kg</span>
                        </div>
                    </div>
                </div>
                <div class="hero-aside compact-aside player-hero-aside-grid">
                    <?php if ($activePlayerSummary !== null): ?>
                        <article class="story-card compact"><span>Current Team</span><strong><?= h($activePlayerSummary['team_name'] ?? 'No club'); ?></strong><small><?= h($activePlayerSummary['season_label']); ?></small></article>
                        <article class="story-card compact"><span>Season line</span><strong><?= h(formatStat($activePlayerSummary['ppg'])); ?> PPG</strong><small><?= h(formatStat($activePlayerSummary['rpg'])); ?> REB · <?= h(formatStat($activePlayerSummary['apg'])); ?> AST</small></article>
                    <?php endif; ?>
                    <article class="story-card compact"><span>Honors</span><strong><?= h((string) count($playerAwards)); ?></strong><small>Total awards in the archive</small></article>
                    <article class="story-card compact"><span>Awards archive</span><strong>Open by season</strong><small><a class="text-link" href="<?= h(pageUrl('awards', ['season' => $playerSelectedSeason ?? $seasonId])); ?>">See all honors</a></small></article>
                </div>
            </section>

            <section class="season-strip card-strip">
                <?php foreach ($playerSeasons as $summary): ?>
                    <a class="season-link <?= (int) $summary['season_id'] === (int) $playerSelectedSeason ? 'active' : ''; ?>" href="<?= h(pageUrl('player', ['player' => $playerProfile['person_id'], 'player_season' => $summary['season_id']])); ?>"><?= h($summary['season_label']); ?></a>
                <?php endforeach; ?>
            </section>

            <section class="content-grid two-up wide-main">
                <article class="module-panel player-notebook-panel <?= $activePlayerSummary !== null ? h(clubMotifClass($activePlayerSummary)) : ''; ?>" <?= $activePlayerSummary !== null ? 'style="' . h(clubThemeStyle($activePlayerSummary, 0.24, 0.12)) . '"' : ''; ?>>
                    <div class="module-head"><div><p class="eyebrow">Player Notebook</p><h2>What defines the archive</h2></div></div>
                    <p class="lede"><?= h($playerStoryPackage['summary'] ?? ''); ?></p>
                    <div class="club-fact-grid player-fact-grid">
                        <?php foreach (($playerStoryPackage['facts'] ?? []) as $fact): ?>
                            <div class="club-fact-card">
                                <span><?= h($fact['label']); ?></span>
                                <strong><?= h($fact['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <aside class="stack-column">
                    <?php foreach (($playerStoryPackage['briefs'] ?? []) as $brief): ?>
                        <article class="story-card compact player-note-card">
                            <span><?= h($brief['label']); ?></span>
                            <strong><?= h($brief['value']); ?></strong>
                            <small><?= h($brief['detail']); ?></small>
                        </article>
                    <?php endforeach; ?>
                </aside>
            </section>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Season Splits</p><h2>Career summary</h2></div></div>
                    <div class="table-scroll">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Season</th>
                                    <th>Team</th>
                                    <th>GP</th>
                                    <th>PPG</th>
                                    <th>RPG</th>
                                    <th>APG</th>
                                    <th>FG%</th>
                                    <th>3P%</th>
                                    <th>FT%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($playerSeasons as $summary): ?>
                                    <tr>
                                        <td><?= h($summary['season_label']); ?></td>
                                        <td><?= h($summary['team_name'] ?? 'No club'); ?></td>
                                        <td><?= h((string) $summary['games_played']); ?></td>
                                        <td><?= h(formatStat($summary['ppg'])); ?></td>
                                        <td><?= h(formatStat($summary['rpg'])); ?></td>
                                        <td><?= h(formatStat($summary['apg'])); ?></td>
                                        <td><?= h(formatStat($summary['fg_pct'])); ?></td>
                                        <td><?= h(formatStat($summary['three_pct'])); ?></td>
                                        <td><?= h(formatStat($summary['ft_pct'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Awards</p><h2>Honors</h2></div><a class="text-link" href="<?= h(pageUrl('awards', ['season' => $playerSelectedSeason ?? $seasonId])); ?>">Awards archive</a></div>
                        <div class="story-stack awards-stack">
                            <?php foreach ($playerAwards as $award): ?>
                                <article class="award-card compact">
                                    <div class="award-card-topline">
                                        <span><?= h($award['season_label']); ?></span>
                                        <?php if (($award['team_name'] ?? null) !== null): ?>
                                            <small><?= h($award['team_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <strong><?= h($award['award_name']); ?></strong>
                                    <?php if (($award['ppg'] ?? null) !== null): ?>
                                        <small><?= h(formatStat($award['ppg'])); ?> PPG · <?= h(formatStat($award['rpg'])); ?> RPG · <?= h(formatStat($award['apg'])); ?> APG</small>
                                    <?php endif; ?>
                                    <p><?= h($award['notes'] ?? ''); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>

            <section class="module-panel">
                <div class="module-head">
                    <div><p class="eyebrow">Game Log</p><h2><?= h($activePlayerSummary['season_label'] ?? 'Selected season'); ?></h2></div>
                    <small><?= h((string) $logPager['total']); ?> games</small>
                </div>
                <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th><?= sortLink('player', 'Date', 'date', $logSort, $logDir, 'log_sort', 'log_dir', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_page' => 1]); ?></th>
                                <th><?= sortLink('player', 'Matchup', 'opponent', $logSort, $logDir, 'log_sort', 'log_dir', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_page' => 1]); ?></th>
                                <th><?= sortLink('player', 'PTS', 'points', $logSort, $logDir, 'log_sort', 'log_dir', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_page' => 1]); ?></th>
                                <th><?= sortLink('player', 'REB', 'rebounds', $logSort, $logDir, 'log_sort', 'log_dir', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_page' => 1]); ?></th>
                                <th><?= sortLink('player', 'AST', 'assists', $logSort, $logDir, 'log_sort', 'log_dir', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_page' => 1]); ?></th>
                                <th>TOV</th>
                                <th>FG</th>
                                <th>3P</th>
                                <th>FT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($playerGameLog as $log): ?>
                                <tr>
                                    <td><?= h(formatGameDate((string) $log['game_date'])); ?></td>
                                    <td><a class="text-link" href="<?= h(pageUrl('boxscore', ['game' => $log['game_id']])); ?>"><?= h($log['matchup']); ?></a></td>
                                    <td><?= h((string) $log['points']); ?></td>
                                    <td><?= h((string) $log['rebounds']); ?></td>
                                    <td><?= h((string) $log['assists']); ?></td>
                                    <td><?= h((string) $log['turnovers']); ?></td>
                                    <td><?= h($log['field_goals_made'] . '/' . $log['field_goals_attempted']); ?></td>
                                    <td><?= h($log['three_points_made'] . '/' . $log['three_points_attempted']); ?></td>
                                    <td><?= h($log['free_throws_made'] . '/' . $log['free_throws_attempted']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= renderPagination('player', ['player' => $playerProfile['person_id'], 'player_season' => $playerSelectedSeason, 'log_sort' => $logSort, 'log_dir' => $logDir], 'log_page', $logPager); ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'awards' && $awardsPagePackage !== null): ?>
            <section class="page-hero awards-hero">
                <div>
                    <p class="eyebrow">Awards Archive</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?> honors</h1>
                    <p class="lede">Season awards, repeat winners, and the stat lines that shaped each race across the archive.</p>
                </div>
                <div class="hero-aside compact-aside awards-summary-grid">
                    <?php foreach ($awardsPagePackage['summary_cards'] as $card): ?>
                        <article class="story-card compact">
                            <span><?= h($card['label']); ?></span>
                            <strong><?= h($card['value']); ?></strong>
                            <small><?= h($card['detail']); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Current Season</p><h2>Named honors</h2></div><small><?= h((string) count($awardsPagePackage['season_awards'])); ?> awards</small></div>
                    <div class="awards-grid">
                        <?php foreach ($awardsPagePackage['season_awards'] as $award): ?>
                            <article class="award-card award-card-featured">
                                <div class="award-card-topline">
                                    <span><?= h($award['award_name']); ?></span>
                                    <?php if (($award['team_name'] ?? null) !== null): ?>
                                        <small><?= h($award['team_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="award-winner-row">
                                    <?= playerPortraitMarkup($award, $award, 'md'); ?>
                                    <div>
                                        <strong><?= h($award['player_name']); ?></strong>
                                        <small><?= h($award['nationality'] ?? ''); ?><?= ($award['position'] ?? null) !== null ? ' · ' . h($award['position']) : ''; ?></small>
                                    </div>
                                </div>
                                <p><?= h($award['notes'] ?? ''); ?></p>
                                <small><?= h(formatStat($award['ppg'])); ?> PPG · <?= h(formatStat($award['rpg'])); ?> RPG · <?= h(formatStat($award['apg'])); ?> APG</small>
                                <a class="text-link" href="<?= h(pageUrl('player', ['player' => $award['person_id'], 'player_season' => $award['season_id']])); ?>">Open player page</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Award Roll Call</p><h2>Recent winners by category</h2></div></div>
                        <div class="story-stack awards-roll-call">
                            <?php foreach ($awardsPagePackage['history_by_award'] as $awardName => $historyRows): ?>
                                <?php $latest = $historyRows[0] ?? null; ?>
                                <?php if ($latest !== null): ?>
                                    <article class="story-card compact">
                                        <span><?= h($awardName); ?></span>
                                        <strong><?= h($latest['player_name']); ?></strong>
                                        <small><?= h($latest['season_label']); ?><?php if (($latest['team_name'] ?? null) !== null): ?> · <?= h($latest['team_name']); ?><?php endif; ?></small>
                                    </article>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>

            <section class="module-panel">
                <div class="module-head"><div><p class="eyebrow">Archive Log</p><h2>Every award winner</h2></div><small><?= h((string) count($awardsPagePackage['archive_rows'])); ?> rows</small></div>
                <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Season</th>
                                <th>Award</th>
                                <th>Winner</th>
                                <th>Club</th>
                                <th>PPG</th>
                                <th>RPG</th>
                                <th>APG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($awardsPagePackage['archive_rows'] as $award): ?>
                                <tr>
                                    <td><?= h($award['season_label']); ?></td>
                                    <td><?= h($award['award_name']); ?></td>
                                    <td><a class="text-link" href="<?= h(pageUrl('player', ['player' => $award['person_id'], 'player_season' => $award['season_id']])); ?>"><?= h($award['player_name']); ?></a></td>
                                    <td><?= h($award['team_name'] ?? 'No club'); ?></td>
                                    <td><?= h(formatStat($award['ppg'])); ?></td>
                                    <td><?= h(formatStat($award['rpg'])); ?></td>
                                    <td><?= h(formatStat($award['apg'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'games'): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Scoreboard</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?> results</h1>
                    <p class="lede">Sortable results, attendance, and direct access to every team and player box score.</p>
                </div>
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="games">
                    <input type="hidden" name="season" value="<?= h((string) $seasonId); ?>">
                    <input type="search" name="game_search" value="<?= h($gameSearch); ?>" placeholder="Search by team, arena, or date">
                    <button type="submit" class="button button-primary">Search</button>
                </form>
            </section>

            <?php if ($gameStoryCards !== []): ?>
                <section class="games-showcase">
                    <?php foreach ($gameStoryCards as $story): ?>
                        <a class="game-story-card" href="<?= h($story['url']); ?>">
                            <div class="game-story-head">
                                <span><?= h($story['label']); ?></span>
                                <strong><?= h($story['headline']); ?></strong>
                            </div>
                            <p><?= h($story['summary']); ?></p>
                            <div class="game-story-foot">
                                <small><?= h($story['scoreline']); ?></small>
                                <small><?= h($story['meta']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head">
                        <div><p class="eyebrow">Results Table</p><h2>Full season ledger</h2></div>
                        <small><?= h((string) $gamePager['total']); ?> games</small>
                    </div>
                    <div class="table-scroll">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th><?= sortLink('games', 'Date', 'date', $gameSort, $gameDir, 'game_sort', 'game_dir', ['season' => $seasonId, 'game_search' => $gameSearch, 'game_page' => 1]); ?></th>
                                    <th><?= sortLink('games', 'Away', 'away', $gameSort, $gameDir, 'game_sort', 'game_dir', ['season' => $seasonId, 'game_search' => $gameSearch, 'game_page' => 1]); ?></th>
                                    <th><?= sortLink('games', 'Home', 'home', $gameSort, $gameDir, 'game_sort', 'game_dir', ['season' => $seasonId, 'game_search' => $gameSearch, 'game_page' => 1]); ?></th>
                                    <th>Away</th>
                                    <th>Home</th>
                                    <th><?= sortLink('games', 'Attendance', 'attendance', $gameSort, $gameDir, 'game_sort', 'game_dir', ['season' => $seasonId, 'game_search' => $gameSearch, 'game_page' => 1]); ?></th>
                                    <th>Box</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($games as $game): ?>
                                    <tr>
                                        <td><?= h(formatGameDate((string) $game['game_date'])); ?></td>
                                        <td><?= h($game['away_team_name']); ?></td>
                                        <td><?= h($game['home_team_name']); ?></td>
                                        <td><?= h((string) $game['away_score']); ?></td>
                                        <td><?= h((string) $game['home_score']); ?></td>
                                        <td><?= h(formatLargeNumber($game['attendance'])); ?></td>
                                        <td><a class="text-link" href="<?= h(pageUrl('boxscore', ['game' => $game['game_id']])); ?>">Open</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= renderPagination('games', ['season' => $seasonId, 'game_search' => $gameSearch, 'game_sort' => $gameSort, 'game_dir' => $gameDir], 'game_page', $gamePager); ?>
                </article>

                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Result Notes</p><h2>What stood out</h2></div></div>
                        <div class="story-stack">
                            <?php foreach ($gameStoryCards as $story): ?>
                                <a class="story-card compact" href="<?= h($story['url']); ?>">
                                    <span><?= h($story['label']); ?></span>
                                    <strong><?= h($story['headline']); ?></strong>
                                    <small><?= h($story['summary']); ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Night Leaders</p><h2>Stat lines to chase</h2></div></div>
                        <div class="story-stack">
                            <?php foreach (array_slice($topPerformances, 0, 3) as $performance): ?>
                                <a class="story-card compact" href="<?= h(pageUrl('player', ['player' => $performance['person_id'], 'player_season' => $seasonId])); ?>">
                                    <span><?= h($performance['team_name'] ?? 'Club'); ?></span>
                                    <strong><?= h($performance['player_name']); ?></strong>
                                    <small><?= h((string) $performance['points']); ?> PTS · <?= h((string) $performance['rebounds']); ?> REB · <?= h((string) $performance['assists']); ?> AST</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </aside>
            </section>
        <?php endif; ?>

        <?php if ($page === 'boxscore' && $boxscore !== null): ?>
            <?php $game = $boxscore['game']; ?>
            <section class="boxscore-hero">
                <div class="boxscore-team">
                    <?= clubMark(['short_name' => $game['away_short_name'], 'primary_color' => $game['away_primary_color'], 'secondary_color' => $game['away_secondary_color']], 'md'); ?>
                    <div>
                        <span>Away</span>
                        <h2><?= h($game['away_team_name']); ?></h2>
                    </div>
                    <strong><?= h((string) $game['away_score']); ?></strong>
                </div>
                <div class="boxscore-meta">
                    <p class="eyebrow">Game Center</p>
                    <h1><?= h($game['season_label']); ?></h1>
                    <p><?= h(formatGameDate((string) $game['game_date'])); ?> · <?= h($game['arena_name'] ?? 'Arena TBA'); ?> · <?= h($game['city'] ?? ''); ?></p>
                </div>
                <div class="boxscore-team home">
                    <strong><?= h((string) $game['home_score']); ?></strong>
                    <div>
                        <span>Home</span>
                        <h2><?= h($game['home_team_name']); ?></h2>
                    </div>
                    <?= clubMark(['short_name' => $game['home_short_name'], 'primary_color' => $game['home_primary_color'], 'secondary_color' => $game['home_secondary_color']], 'md'); ?>
                </div>
            </section>

            <section class="metric-row">
                <article class="metric-card"><span>Attendance</span><strong><?= h(formatLargeNumber($game['attendance'])); ?></strong></article>
                <article class="metric-card"><span>Tipoff</span><strong><?= h((string) $game['tipoff_time']); ?></strong></article>
                <article class="metric-card"><span>Overtime</span><strong><?= h((string) $game['overtime_count']); ?></strong></article>
                <article class="metric-card"><span>Referee</span><strong><?= h($game['referee_name'] ?? 'TBA'); ?></strong></article>
            </section>

            <?php if ($boxscoreStory !== null): ?>
                <section class="content-grid two-up wide-main">
                    <article class="module-panel boxscore-story-panel">
                        <div class="module-head"><div><p class="eyebrow">Game Story</p><h2><?= h($boxscoreStory['headline']); ?></h2></div></div>
                        <p class="lede"><?= h($boxscoreStory['summary']); ?></p>
                        <div class="boxscore-edge-grid">
                            <?php foreach ($boxscoreStory['edges'] as $edge): ?>
                                <article class="edge-card">
                                    <span><?= h($edge['label']); ?></span>
                                    <strong><?= h($edge['team']); ?></strong>
                                    <small><?= h($edge['detail']); ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <aside class="stack-column">
                        <article class="module-panel">
                            <div class="module-head"><div><p class="eyebrow">Standout Performers</p><h2>Best lines in the game</h2></div></div>
                            <div class="story-stack">
                                <?php foreach ($boxscoreStory['performers'] as $performer): ?>
                                    <a class="story-card compact performer-story" href="<?= h(pageUrl('player', ['player' => $performer['person_id'], 'player_season' => $game['season_id']])); ?>">
                                        <span><?= h($performer['team_name'] ?? 'Club'); ?></span>
                                        <strong><?= h($performer['player_name']); ?></strong>
                                        <small><?= h((string) $performer['points']); ?> PTS · <?= h((string) $performer['rebounds']); ?> REB · <?= h((string) $performer['assists']); ?> AST</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    </aside>
                </section>
            <?php endif; ?>

            <section class="content-grid two-up wide-main">
                <article class="module-panel">
                    <div class="module-head"><div><p class="eyebrow">Team Comparison</p><h2>Box score</h2></div></div>
                    <div class="table-scroll">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Stat</th>
                                    <th><?= h($game['away_short_name']); ?></th>
                                    <th><?= h($game['home_short_name']); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $awayTeamStats = $boxscore['team_stats'][(int) $game['away_team_id']] ?? [];
                                $homeTeamStats = $boxscore['team_stats'][(int) $game['home_team_id']] ?? [];
                                $compareFields = [
                                    'points' => 'Points',
                                    'rebounds' => 'Rebounds',
                                    'assists' => 'Assists',
                                    'turnovers' => 'Turnovers',
                                    'fouls' => 'Fouls',
                                    'field_goals_made' => 'FG Made',
                                    'field_goals_attempted' => 'FG Att',
                                    'three_points_made' => '3P Made',
                                    'three_points_attempted' => '3P Att',
                                    'free_throws_made' => 'FT Made',
                                    'free_throws_attempted' => 'FT Att',
                                ];
                                ?>
                                <?php foreach ($compareFields as $field => $label): ?>
                                    <tr>
                                        <td><?= h($label); ?></td>
                                        <td><?= h((string) ($awayTeamStats[$field] ?? '-')); ?></td>
                                        <td><?= h((string) ($homeTeamStats[$field] ?? '-')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
                <aside class="stack-column">
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Keep Exploring</p><h2>Related pages</h2></div></div>
                        <div class="story-stack">
                            <a class="story-card compact" href="<?= h(pageUrl('team', ['team' => $game['home_team_id'], 'team_season' => $game['season_id']])); ?>"><span>Club page</span><strong><?= h($game['home_team_name']); ?></strong><small>Open the home club profile.</small></a>
                            <a class="story-card compact" href="<?= h(pageUrl('team', ['team' => $game['away_team_id'], 'team_season' => $game['season_id']])); ?>"><span>Club page</span><strong><?= h($game['away_team_name']); ?></strong><small>Open the away club profile.</small></a>
                            <a class="story-card compact" href="<?= h(pageUrl('matchup', ['season' => $game['season_id'], 'team' => $game['home_team_id'], 'opponent' => $game['away_team_id']])); ?>"><span>Matchup page</span><strong><?= h($game['home_short_name']); ?> vs <?= h($game['away_short_name']); ?></strong><small>View the rivalry page for this pairing.</small></a>
                        </div>
                    </article>
                </aside>
            </section>

            <section class="content-grid two-up">
                <?php foreach ([(int) $game['away_team_id'], (int) $game['home_team_id']] as $teamId): ?>
                    <?php $rows = $boxscore['player_stats'][$teamId] ?? []; ?>
                    <article class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">Player Box</p><h2><?= h($rows[0]['team_name'] ?? 'Team'); ?></h2></div></div>
                        <div class="table-scroll">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Pos</th>
                                        <th>PTS</th>
                                        <th>REB</th>
                                        <th>AST</th>
                                        <th>TOV</th>
                                        <th>FG</th>
                                        <th>3P</th>
                                        <th>FT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><a class="text-link" href="<?= h(pageUrl('player', ['player' => $row['person_id'], 'player_season' => $game['season_id']])); ?>"><?= h($row['player_name']); ?></a></td>
                                            <td><?= h($row['position']); ?></td>
                                            <td><?= h((string) $row['points']); ?></td>
                                            <td><?= h((string) $row['rebounds']); ?></td>
                                            <td><?= h((string) $row['assists']); ?></td>
                                            <td><?= h((string) $row['turnovers']); ?></td>
                                            <td><?= h($row['field_goals_made'] . '/' . $row['field_goals_attempted']); ?></td>
                                            <td><?= h($row['three_points_made'] . '/' . $row['three_points_attempted']); ?></td>
                                            <td><?= h($row['free_throws_made'] . '/' . $row['free_throws_attempted']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'playoffs'): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Postseason</p>
                    <h1><?= h($selectedSeason['season_label'] ?? 'Season'); ?> playoff picture</h1>
                    <p class="lede">The top six clubs move straight into the quarterfinals. Seeds seven through ten funnel through the Play-In, then the field narrows through best-of-five quarterfinals and the single-game Final Four.</p>
                </div>
            </section>

            <section class="format-grid">
                <?php foreach ($playoffGuide as $guideCard): ?>
                    <article class="module-panel format-card">
                        <span><?= h($guideCard['label']); ?></span>
                        <strong><?= h($guideCard['title']); ?></strong>
                        <p><?= h($guideCard['detail']); ?></p>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="bracket-grid">
                <?php if (($playoffBracket['play_in'] ?? []) !== []): ?>
                    <article class="module-panel bracket-column">
                        <div class="module-head"><div><p class="eyebrow">Play-In</p><h2>How the last two berths are decided</h2></div></div>
                        <?php foreach ($playoffBracket['play_in'] as $game): ?>
                            <div class="bracket-card">
                                <span><?= h($game['label']); ?></span>
                                <strong><?= h($game['team_a']['team_name']); ?> vs <?= h($game['team_b']['team_name']); ?></strong>
                                <p><?= h($game['winner']['team_name']); ?> beat <?= h($game['loser']['team_name']); ?> <?= h($game['score']); ?>.</p>
                                <small><?= h(explainPlayInGame($game)); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </article>
                <?php endif; ?>

                <article class="module-panel bracket-column">
                    <div class="module-head"><div><p class="eyebrow">Quarterfinals</p><h2>Best-of-five</h2></div></div>
                    <?php foreach ($playoffBracket['quarterfinals'] ?? [] as $series): ?>
                        <div class="bracket-card">
                            <span><?= h($series['label']); ?></span>
                            <strong>#<?= h((string) $series['higher_seed']['final_rank']); ?> <?= h($series['higher_seed']['team_name']); ?> vs #<?= h((string) $series['lower_seed']['final_rank']); ?> <?= h($series['lower_seed']['team_name']); ?></strong>
                            <p><?= h($series['winner']['team_name']); ?> advance <?= h($series['series_score']); ?>.</p>
                            <small>Best-of-five series. Winner moves into the Final Four.</small>
                        </div>
                    <?php endforeach; ?>
                </article>

                <article class="module-panel bracket-column">
                    <div class="module-head"><div><p class="eyebrow">Final Four</p><h2>Semifinals</h2></div></div>
                    <?php foreach ($playoffBracket['semifinals'] ?? [] as $series): ?>
                        <div class="bracket-card">
                            <span><?= h($series['label']); ?></span>
                            <strong><?= h($series['higher_seed']['team_name']); ?> vs <?= h($series['lower_seed']['team_name']); ?></strong>
                            <p><?= h($series['winner']['team_name']); ?> move on with a projected <?= h($series['series_score']); ?> result.</p>
                            <small>Single-game Final Four setting. <?= h($series['headline']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </article>

                <article class="module-panel bracket-column champion-column">
                    <div class="module-head"><div><p class="eyebrow">Championship</p><h2>Projected winner</h2></div></div>
                    <?php if (($playoffBracket['final'] ?? null) !== null): ?>
                        <div class="champion-card">
                            <?= clubIdentityMarkup($playoffBracket['champion'], 'lg'); ?>
                            <strong><?= h($playoffBracket['champion']['team_name']); ?></strong>
                            <small><?= h($playoffBracket['champion']['team_name']); ?> are projected to beat <?= h($playoffBracket['final']['loser']['team_name']); ?> <?= h($playoffBracket['final']['series_score']); ?> in the title game.</small>
                        </div>
                    <?php endif; ?>
                </article>
            </section>

            <section class="module-panel">
                <div class="module-head"><div><p class="eyebrow">Bubble Watch</p><h2>Seeds one through ten</h2></div></div>
                <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Rk</th>
                                <th>Club</th>
                                <th>W-L</th>
                                <th>Status</th>
                                <th>What it means</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($playoffBracket['standings'] ?? [] as $club): ?>
                                <tr>
                                    <td><?= h((string) $club['final_rank']); ?></td>
                                    <td><?= h($club['team_name']); ?></td>
                                    <td><?= h(formatRecord($club)); ?></td>
                                    <td><?= h(postseasonStatusLabel($club)); ?></td>
                                    <td><?= h((int) ($club['qualified_playoffs'] ?? 0) === 1 ? 'Straight into the quarterfinals.' : ((int) ($club['qualified_playin'] ?? 0) === 1 ? 'Must survive the Play-In to reach the quarterfinals.' : 'Would miss the postseason if the table ended today.')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'matchup' && $matchupData !== null): ?>
            <section class="page-hero matchup-hero">
                <div>
                    <p class="eyebrow">Head to Head</p>
                    <h1><?= h($matchupData['team']['team_name']); ?> vs <?= h($matchupData['opponent']['team_name']); ?></h1>
                    <p class="lede">Season series view with record split, scoring margin, and the full result trail.</p>
                </div>
                <form method="get" class="compare-form">
                    <input type="hidden" name="page" value="matchup">
                    <input type="hidden" name="season" value="<?= h((string) $seasonId); ?>">
                    <select name="team">
                        <?php foreach ($allTeamsBySeason as $club): ?>
                            <option value="<?= h((string) $club['team_id']); ?>" <?= (int) $club['team_id'] === $matchupTeamId ? 'selected' : ''; ?>><?= h($club['team_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="opponent">
                        <?php foreach ($allTeamsBySeason as $club): ?>
                            <?php if ((int) $club['team_id'] !== $matchupTeamId): ?>
                                <option value="<?= h((string) $club['team_id']); ?>" <?= (int) $club['team_id'] === $matchupOpponentId ? 'selected' : ''; ?>><?= h($club['team_name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button button-primary">Compare</button>
                </form>
            </section>

            <section class="metric-row">
                <article class="metric-card"><span>Games</span><strong><?= h((string) $matchupData['summary']['games']); ?></strong></article>
                <article class="metric-card"><span><?= h($matchupData['team']['short_name']); ?> wins</span><strong><?= h((string) $matchupData['summary']['team_wins']); ?></strong></article>
                <article class="metric-card"><span><?= h($matchupData['opponent']['short_name']); ?> wins</span><strong><?= h((string) $matchupData['summary']['opponent_wins']); ?></strong></article>
                <article class="metric-card"><span>Avg Margin</span><strong><?= h(formatStat($matchupData['summary']['average_margin'])); ?></strong></article>
            </section>

            <section class="module-panel">
                <div class="module-head"><div><p class="eyebrow">Series Ledger</p><h2>Results</h2></div></div>
                <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Season</th>
                                <th>Venue</th>
                                <th><?= h($matchupData['team']['short_name']); ?></th>
                                <th><?= h($matchupData['opponent']['short_name']); ?></th>
                                <th>Arena</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matchupData['games'] as $game): ?>
                                <tr>
                                    <td><a class="text-link" href="<?= h(pageUrl('boxscore', ['game' => $game['game_id']])); ?>"><?= h(formatGameDate((string) $game['game_date'])); ?></a></td>
                                    <td><?= h($game['season_label']); ?></td>
                                    <td><?= h($game['venue']); ?></td>
                                    <td><?= h((string) $game['team_score']); ?></td>
                                    <td><?= h((string) $game['opponent_score']); ?></td>
                                    <td><?= h($game['arena_name'] ?? 'Arena TBA'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'play' && $board !== null): ?>
            <section class="page-hero play-hero">
                <div>
                    <p class="eyebrow">Interactive Grid</p>
                    <h1><?= h($board['puzzle']['puzzle_name']); ?></h1>
                    <p class="lede"><?= h($puzzlePagePackage['headline'] ?? 'Three clubs, three stat clues, and one roster pool per row.'); ?></p>
                    <p class="muted play-hero-note"><?= h($puzzlePagePackage['summary'] ?? 'Pick one player for each club-stat intersection and check the whole grid in a single shot.'); ?></p>
                </div>
                <?php if (($puzzlePagePackage['summary_cards'] ?? []) !== []): ?>
                    <div class="hero-aside compact-aside play-hero-aside">
                        <?php foreach ($puzzlePagePackage['summary_cards'] as $card): ?>
                            <article class="story-card compact">
                                <span><?= h($card['label']); ?></span>
                                <strong><?= h($card['value']); ?></strong>
                                <small><?= h($card['detail']); ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="play-layout">
                <aside class="stack-column play-sidebar-stack">
                    <section class="module-panel">
                        <div class="module-head"><div><p class="eyebrow">How It Works</p><h2>Board rules</h2></div></div>
                        <div class="story-stack play-guide-stack">
                            <article class="story-card compact play-guide-card">
                                <span>Roster rule</span>
                                <strong>One club pool per row</strong>
                                <small>Every dropdown only shows the selected-season roster for that club, so each pick starts from the right team context.</small>
                            </article>
                            <article class="story-card compact play-guide-card">
                                <span>Clue rule</span>
                                <strong>Hit the stat threshold</strong>
                                <small>Every column uses a real season benchmark, so the player you pick has to satisfy the clue at the top of that column.</small>
                            </article>
                            <article class="story-card compact play-guide-card">
                                <span>Check flow</span>
                                <strong>Grade the full board at once</strong>
                                <small>The board keeps your latest attempt in view so you can compare misses without getting kicked away from the grid.</small>
                            </article>
                        </div>
                    </section>

                    <section class="module-panel sidebar-panel">
                        <div class="module-head"><div><p class="eyebrow">Puzzle Deck</p><h2>Archive</h2></div></div>
                        <div class="season-strip vertical-strip query-list">
                            <?php foreach ($puzzles as $puzzle): ?>
                                <a class="season-link query-link puzzle-link <?= (int) $puzzle['grid_puzzle_id'] === $selectedPuzzleId ? 'active' : ''; ?>" href="<?= h(pageUrl('play', ['puzzle' => $puzzle['grid_puzzle_id']])); ?>">
                                    <span><?= h($puzzle['season_label'] ?? 'Season'); ?></span>
                                    <strong><?= h($puzzle['puzzle_name']); ?></strong>
                                    <small><?= h(formatGameDate((string) ($puzzle['puzzle_date'] ?? ''))); ?> · <?= h(ucfirst((string) ($puzzle['status'] ?? 'active'))); ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </aside>
                <section class="module-panel grid-workspace-panel">
                    <div id="grid-board" class="grid-anchor"></div>
                    <div class="module-head">
                        <div>
                            <p class="eyebrow">Board</p>
                            <h2>Cross each club with each clue</h2>
                        </div>
                        <div class="query-feature-list play-utility-pills">
                            <span class="feature-pill"><?= h((string) ($puzzlePagePackage['filled_count'] ?? 0)); ?> filled</span>
                            <span class="feature-pill"><?= h((string) ($puzzlePagePackage['total_cells'] ?? 0)); ?> total cells</span>
                        </div>
                    </div>

                    <form method="post" class="grid-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()); ?>">
                        <input type="hidden" name="action" value="play_puzzle">
                        <input type="hidden" name="redirect_page" value="play">
                        <input type="hidden" name="puzzle_id" value="<?= h((string) $selectedPuzzleId); ?>">

                        <div class="puzzle-board-wrap">
                            <div class="puzzle-board">
                                <div class="board-corner">
                                    <span class="board-corner-label">Club / clue</span>
                                    <strong>Roster first, stat second</strong>
                                    <small>Each row locks the team pool. Each column tests the stat profile.</small>
                                </div>
                                <?php foreach ($board['columns'] as $column): ?>
                                    <div class="board-header">
                                        <span>Column <?= h((string) $column['column_position']); ?></span>
                                        <strong><?= h($column['units']); ?></strong>
                                        <small><?= h($column['clue_text']); ?></small>
                                    </div>
                                <?php endforeach; ?>

                                <?php foreach ($board['rows'] as $row): ?>
                                    <div class="board-row-label">
                                        <div class="board-row-head">
                                            <?= clubIdentityMarkup($row, 'sm'); ?>
                                            <div>
                                                <strong><?= h($row['team_name']); ?></strong>
                                                <span><?= h($row['clue_text']); ?></span>
                                            </div>
                                        </div>
                                        <div class="board-chip-row">
                                            <span class="board-chip"><?= h($row['short_name'] ?? 'Club'); ?></span>
                                            <span class="board-chip board-chip-muted"><?= h((string) count($board['team_player_options'][(int) $row['team_id']] ?? [])); ?> roster options</span>
                                        </div>
                                    </div>
                                    <?php foreach ($board['columns'] as $column): ?>
                                        <?php
                                        $cellKey = $row['row_position'] . '-' . $column['column_position'];
                                        $selectedPersonId = (int) ($puzzleResult['selected'][$cellKey] ?? 0);
                                        $hasSelection = $selectedPersonId > 0;
                                        $cellState = $hasSelection ? 'cell-selected' : '';
                                        if ($puzzleResult !== null && isset($puzzleResult['correct'][$cellKey])) {
                                            $cellState = $puzzleResult['correct'][$cellKey] ? 'cell-correct' : 'cell-wrong';
                                        }
                                        ?>
                                        <div class="board-cell <?= h($cellState); ?>">
                                            <div class="board-cell-top">
                                                <span class="board-chip"><?= h($row['short_name'] ?? 'Club'); ?></span>
                                                <span class="board-chip board-chip-muted"><?= h($column['units']); ?></span>
                                            </div>
                                            <select class="board-select" name="cell[<?= h($cellKey); ?>]">
                                                <option value="">Choose player</option>
                                                <?php foreach ($board['team_player_options'][(int) $row['team_id']] as $option): ?>
                                                    <?php $selected = $selectedPersonId === (int) $option['value'] ? 'selected' : ''; ?>
                                                    <option value="<?= h((string) $option['value']); ?>" <?= $selected; ?>><?= h($option['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($puzzleResult !== null): ?>
                                                <small class="board-note"><?= $puzzleResult['correct'][$cellKey] ? 'Correct fit for this clue.' : 'That pick does not clear this stat filter.'; ?></small>
                                            <?php else: ?>
                                                <small class="board-note">Use the <?= h($row['team_name']); ?> roster to satisfy <?= h($column['units']); ?>.</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="cta-row">
                            <button type="submit" class="button button-primary">Check grid</button>
                            <a class="button button-secondary" href="<?= h(pageUrl('play', ['puzzle' => $selectedPuzzleId, 'reset' => 1, '_anchor' => 'grid-board'])); ?>">Reset picks</a>
                        </div>
                    </form>
                </section>
            </section>
        <?php endif; ?>

        <?php if ($page === 'admin'): ?>
            <section class="page-hero">
                <div>
                    <p class="eyebrow">Data Desk</p>
                    <h1>Archive operations</h1>
                    <p class="lede">Maintenance, row counts, and direct table editing live here without taking over the public site.</p>
                </div>
                <form method="post" class="cta-row compact">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()); ?>">
                    <input type="hidden" name="action" value="reset_database">
                    <input type="hidden" name="redirect_page" value="admin">
                    <button type="submit" class="button button-secondary">Refresh archive</button>
                    <a class="button button-secondary" href="<?= h(pageUrl('queries')); ?>">Advanced queries</a>
                    <a class="button button-primary" href="<?= h(pageUrl('crud', ['table' => 'teams'])); ?>">Open table editor</a>
                </form>
            </section>

            <section class="admin-grid">
                <article class="module-panel stat-panel stat-panel-wide">
                    <span>Advanced query pack</span>
                    <strong><?= h((string) count($queryCatalog->all())); ?></strong>
                    <small>Run the rubric query set, inspect SQL, and preview live results in MySQL mode.</small>
                    <a class="text-link" href="<?= h(pageUrl('queries')); ?>">Open query desk</a>
                </article>
                <?php foreach ($dashboardCounts as $countTable => $count): ?>
                    <article class="module-panel stat-panel">
                        <span><?= h($definitions[$countTable]['label']); ?></span>
                        <strong><?= h((string) $count); ?></strong>
                        <small><?= $count >= 10 ? 'Coverage ready' : 'Needs more rows'; ?></small>
                        <a class="text-link" href="<?= h(pageUrl('crud', ['table' => $countTable])); ?>">Manage</a>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'queries' && $selectedAdvancedQuery !== null): ?>
            <?php $previewColumns = $advancedQueryPreview !== [] ? array_keys($advancedQueryPreview[0]) : []; ?>
            <section class="crud-layout queries-layout">
                <aside class="module-panel sidebar-panel query-sidebar">
                    <div class="module-head"><div><p class="eyebrow">Data Desk</p><h2>Advanced Queries</h2></div></div>
                    <p class="module-copy">All 15 rubric queries are listed here with the original SQL, explanation, and a live preview when the app is running in MySQL mode.</p>
                    <div class="season-strip vertical-strip query-list">
                        <?php foreach ($advancedQueries as $query): ?>
                            <a class="season-link query-link <?= (int) $query['id'] === (int) $selectedAdvancedQuery['id'] ? 'active' : ''; ?>" href="<?= h(pageUrl('queries', ['query_id' => $query['id']])); ?>">
                                <span>Query <?= h((string) $query['id']); ?></span>
                                <strong><?= h($query['title']); ?></strong>
                                <small><?= h($query['summary']); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="crud-main">
                    <section class="page-hero compact-hero">
                        <div>
                            <p class="eyebrow">Assignment Query Desk</p>
                            <h1>Query <?= h((string) $selectedAdvancedQuery['id']); ?>. <?= h($selectedAdvancedQuery['title']); ?></h1>
                            <p class="lede"><?= h($selectedAdvancedQuery['explanation']); ?></p>
                        </div>
                        <div class="cta-row compact">
                            <a class="button button-secondary" href="<?= h(pageUrl('admin')); ?>">Back to desk</a>
                            <a class="button button-primary" href="<?= h(pageUrl('queries', ['query_id' => $selectedAdvancedQuery['id']])); ?>">Refresh result</a>
                        </div>
                    </section>

                    <section class="query-metrics-grid">
                        <article class="module-panel metric-card">
                            <span>Pack size</span>
                            <strong><?= h((string) count($advancedQueries)); ?></strong>
                            <small>Documented SQL queries</small>
                        </article>
                        <article class="module-panel metric-card">
                            <span>Runtime</span>
                            <strong><?= h(strtoupper($db->driver())); ?></strong>
                            <small>Live execution enabled</small>
                        </article>
                        <article class="module-panel metric-card">
                            <span>Rows returned</span>
                            <strong><?= h((string) count($advancedQueryRows)); ?></strong>
                            <small><?= h(count($advancedQueryPreview) === count($advancedQueryRows) ? 'Full result visible below' : 'Previewing the first ' . count($advancedQueryPreview) . ' rows'); ?></small>
                        </article>
                    </section>

                    <section class="module-panel query-detail-panel">
                        <div class="module-head">
                            <div>
                                <p class="eyebrow">Query brief</p>
                                <h2>Why this query matters</h2>
                            </div>
                            <div class="query-feature-list">
                                <?php foreach ($selectedAdvancedQuery['features'] as $feature): ?>
                                    <span class="feature-pill"><?= h($feature); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="query-note"><?= h($selectedAdvancedQuery['summary']); ?></p>
                        <p class="query-note muted"><?= h($selectedAdvancedQuery['screenshot_note']); ?></p>
                        <pre class="query-code"><code><?= h($selectedAdvancedQuery['sql']); ?></code></pre>
                    </section>

                    <section class="module-panel query-results-panel">
                        <div class="module-head">
                            <div>
                                <p class="eyebrow">Live result</p>
                                <h2>Execution preview</h2>
                            </div>
                        </div>

                        <?php if ($advancedQueryError !== null): ?>
                            <p class="empty-state">The query could not be executed: <?= h($advancedQueryError); ?></p>
                        <?php elseif ($advancedQueryPreview === []): ?>
                            <p class="empty-state">This query ran successfully but returned no rows for the current database state.</p>
                        <?php else: ?>
                            <div class="table-scroll">
                                <table class="stats-table data-table-wide">
                                    <thead>
                                        <tr>
                                            <?php foreach ($previewColumns as $column): ?>
                                                <th><?= h(humanize((string) $column)); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($advancedQueryPreview as $row): ?>
                                            <tr>
                                                <?php foreach ($previewColumns as $column): ?>
                                                    <td>
                                                        <?php $cellValue = $row[$column] ?? null; ?>
                                                        <?= $cellValue === null || $cellValue === '' ? '<span class="muted">-</span>' : h((string) $cellValue); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'crud' && $crudSchema !== null): ?>
            <section class="crud-layout">
                <aside class="module-panel sidebar-panel">
                    <div class="module-head"><div><p class="eyebrow">Tables</p><h2>Data Desk</h2></div></div>
                    <div class="season-strip vertical-strip">
                        <?php foreach ($tableList as $tableName): ?>
                            <a class="season-link <?= $tableName === $table ? 'active' : ''; ?>" href="<?= h(pageUrl('crud', ['table' => $tableName])); ?>">
                                <?= h($definitions[$tableName]['label']); ?>
                                <strong><?= h((string) $dashboardCounts[$tableName]); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="crud-main">
                    <section class="page-hero compact-hero">
                        <div>
                            <p class="eyebrow">Table Editor</p>
                            <h1><?= h($definitions[$table]['label']); ?></h1>
                            <p class="lede"><?= h($definitions[$table]['description']); ?></p>
                        </div>
                        <div class="cta-row compact">
                            <a class="button button-primary" href="<?= h(pageUrl('crud', ['table' => $table, 'action' => 'create'])); ?>">Create record</a>
                            <a class="button button-secondary" href="<?= h(pageUrl('admin')); ?>">Back to desk</a>
                        </div>
                    </section>

                    <?php if ($action === 'create' || $action === 'edit'): ?>
                        <section class="module-panel form-panel">
                            <h2><?= $action === 'create' ? 'Create new record' : 'Edit record'; ?></h2>
                            <form method="post" class="record-form">
                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()); ?>">
                                <input type="hidden" name="action" value="save_record">
                                <input type="hidden" name="redirect_page" value="crud">
                                <input type="hidden" name="table" value="<?= h($table); ?>">
                                <input type="hidden" name="mode" value="<?= h($action); ?>">
                                <?php if ($action === 'edit' && $editingKey !== null): ?>
                                    <input type="hidden" name="record_key" value="<?= h(recordKeyToken($editingKey)); ?>">
                                <?php endif; ?>
                                <div class="form-grid">
                                    <?php foreach ($crudSchema['columns'] as $column): ?>
                                        <?= renderField($db, $crudSchema, $editingRecord, $column, $action); ?>
                                    <?php endforeach; ?>
                                </div>
                                <div class="cta-row compact">
                                    <button type="submit" class="button button-primary">Save record</button>
                                    <a class="button button-secondary" href="<?= h(pageUrl('crud', ['table' => $table])); ?>">Cancel</a>
                                </div>
                            </form>
                        </section>
                    <?php endif; ?>

                    <section class="module-panel">
                        <div class="table-scroll">
                            <table class="stats-table data-table-wide">
                                <thead>
                                    <tr>
                                        <?php foreach ($crudSchema['columns'] as $column): ?>
                                            <th><?= h(humanize($column['name'])); ?></th>
                                        <?php endforeach; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($crudRows as $row): ?>
                                        <?php $rowKey = recordKeyFromRow($crudSchema, $row); ?>
                                        <tr>
                                            <?php foreach ($crudSchema['columns'] as $column): ?>
                                                <?php $cellValue = $row[$column['name']]; ?>
                                                <td>
                                                    <?php if ($cellValue === null || $cellValue === ''): ?>
                                                        <span class="muted">-</span>
                                                    <?php elseif (isBooleanType(strtoupper((string) $column['type']))): ?>
                                                        <span class="pill <?= (string) $cellValue === '1' ? 'pill-true' : 'pill-false'; ?>"><?= (string) $cellValue === '1' ? 'True' : 'False'; ?></span>
                                                    <?php else: ?>
                                                        <?= h((string) $cellValue); ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <div class="cta-row compact table-actions">
                                                    <a class="button button-secondary button-small" href="<?= h(pageUrl('crud', ['table' => $table, 'action' => 'edit', 'key' => recordKeyToken($rowKey)])); ?>">Edit</a>
                                                    <form method="post" onsubmit="return confirm('Delete this record?');">
                                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()); ?>">
                                                        <input type="hidden" name="action" value="delete_record">
                                                        <input type="hidden" name="redirect_page" value="crud">
                                                        <input type="hidden" name="table" value="<?= h($table); ?>">
                                                        <input type="hidden" name="record_key" value="<?= h(recordKeyToken($rowKey)); ?>">
                                                        <button type="submit" class="button button-danger button-small">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </section>
        <?php endif; ?>

        <footer class="site-footer">
            <div class="footer-brand">
                <strong>Euroleague Atlas</strong>
                <small>Daily scoreboard, club pages, player logs, head-to-head archives, and postseason context across every season in the database.</small>
            </div>
            <div class="footer-columns">
                <div>
                    <span class="footer-heading">Explore</span>
                    <div class="footer-links">
                        <a href="<?= h(pageUrl('seasons', ['season' => $seasonId])); ?>">Season archive</a>
                        <a href="<?= h(pageUrl('teams', ['season' => $seasonId])); ?>">Clubs</a>
                        <a href="<?= h(pageUrl('players', ['season' => $seasonId])); ?>">Players</a>
                        <a href="<?= h(pageUrl('awards', ['season' => $seasonId])); ?>">Awards</a>
                    </div>
                </div>
                <div>
                    <span class="footer-heading">Follow</span>
                    <div class="footer-links">
                        <a href="<?= h(pageUrl('games', ['season' => $seasonId])); ?>">Scores</a>
                        <a href="<?= h(pageUrl('playoffs', ['season' => $seasonId])); ?>">Playoffs</a>
                        <a href="<?= h(pageUrl('play')); ?>">Grid</a>
                    </div>
                </div>
                <div>
                    <span class="footer-heading">Operations</span>
                    <div class="footer-links">
                        <a href="<?= h(pageUrl('admin')); ?>">Data Desk</a>
                        <a href="<?= h(pageUrl('queries')); ?>">Advanced queries</a>
                        <a href="<?= h(pageUrl('crud', ['table' => 'teams'])); ?>">Tables</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>