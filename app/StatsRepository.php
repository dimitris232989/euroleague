<?php
declare(strict_types=1);

final class StatsRepository
{
    public function __construct(private readonly AppDbConnection $pdo)
    {
    }

    public function currentSeasonId(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(MAX(season_id), 1) FROM seasons')->fetchColumn();
    }

    public function listSeasons(): array
    {
        $statement = $this->pdo->query(
            'SELECT s.*, COUNT(g.game_id) AS total_games
             FROM seasons s
             LEFT JOIN games g ON g.season_id = s.season_id
             GROUP BY s.season_id
             ORDER BY s.season_id DESC'
        );

        return $statement->fetchAll();
    }

    public function getSeasonOverview(int $seasonId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.*, COUNT(g.game_id) AS total_games,
                    COALESCE(SUM(g.attendance), 0) AS total_attendance,
                    ROUND(AVG(g.attendance), 0) AS average_attendance,
                    MAX(g.game_date) AS latest_game_date,
                    MIN(g.game_date) AS first_game_date
             FROM seasons s
             LEFT JOIN games g ON g.season_id = s.season_id
             WHERE s.season_id = ?
             GROUP BY s.season_id'
        );
        $statement->execute([$seasonId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function countSeasonStandings(int $seasonId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM team_seasons WHERE season_id = ?');
        $statement->execute([$seasonId]);

        return (int) $statement->fetchColumn();
    }

    public function getSeasonStandings(
        int $seasonId,
        string $sort = 'rank',
        string $direction = 'asc',
        int $limit = 20,
        int $offset = 0
    ): array {
        $orderMap = [
            'rank' => 'final_rank',
            'team' => 'team_name',
            'country' => 'country_name',
            'wins' => 'wins',
            'losses' => 'losses',
            'record' => 'wins',
            'pf' => 'points_for',
            'pa' => 'points_against',
            'diff' => 'point_diff',
            'ppg' => 'points_per_game',
            'oppg' => 'points_allowed_per_game',
            'attendance' => 'avg_attendance',
        ];
        $sortKey = $orderMap[$sort] ?? $orderMap['rank'];
        $dir = $this->normalizeDirection($direction, in_array($sort, ['rank', 'team', 'country'], true) ? 'asc' : 'desc');

        $statement = $this->pdo->prepare(
            'SELECT ts.*, t.team_name, t.short_name, t.nickname, t.primary_color, t.secondary_color,
                    t.logo_url, t.website_url, c.country_name, c.country_code, a.arena_name,
                    ROUND((ts.points_for * 1.0) / NULLIF(ts.wins + ts.losses, 0), 1) AS points_per_game,
                    ROUND((ts.points_against * 1.0) / NULLIF(ts.wins + ts.losses, 0), 1) AS points_allowed_per_game,
                    ROUND((ts.wins * 1.0) / NULLIF(ts.wins + ts.losses, 0), 3) AS win_pct
             FROM team_seasons ts
             JOIN teams t ON t.team_id = ts.team_id
             JOIN countries c ON c.country_id = t.country_id
             LEFT JOIN arenas a ON a.arena_id = t.home_arena_id
             WHERE ts.season_id = ?
             ORDER BY ' . $sortKey . ' ' . $dir . ', final_rank ASC, team_name ASC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $statement->execute([$seasonId]);

        return $statement->fetchAll();
    }

    public function getSeasonLeaders(int $seasonId): array
    {
        return [
            'points' => $this->seasonLeadersByColumn($seasonId, 'points', 'PPG'),
            'rebounds' => $this->seasonLeadersByColumn($seasonId, 'rebounds', 'RPG'),
            'assists' => $this->seasonLeadersByColumn($seasonId, 'assists', 'APG'),
        ];
    }

    public function getRecentGames(int $seasonId, int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            $this->baseGamesSelect() . '
             WHERE g.season_id = ?
             ORDER BY g.game_date DESC, g.tipoff_time DESC, g.game_id DESC
             LIMIT ' . max(1, $limit)
        );
        $statement->execute([$seasonId]);

        return $statement->fetchAll();
    }

    public function listTeams(?int $seasonId = null): array
    {
        return $this->getSeasonStandings($seasonId ?? $this->currentSeasonId(), 'rank', 'asc', 50, 0);
    }

    public function getTeamProfile(int $teamId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.*, c.country_name, c.country_code, a.arena_name, a.city, a.capacity
             FROM teams t
             JOIN countries c ON c.country_id = t.country_id
             LEFT JOIN arenas a ON a.arena_id = t.home_arena_id
             WHERE t.team_id = ?'
        );
        $statement->execute([$teamId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function getTeamSeasonHistory(int $teamId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ts.*, s.season_label,
                    CONCAT(p.first_name, \' \', p.last_name) AS coach_name
             FROM team_seasons ts
             JOIN seasons s ON s.season_id = ts.season_id
             LEFT JOIN people p ON p.person_id = ts.coach_person_id
             WHERE ts.team_id = ?
             ORDER BY ts.season_id DESC'
        );
        $statement->execute([$teamId]);

        return $statement->fetchAll();
    }

    public function getTeamSeasonSnapshot(int $teamId, int $seasonId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ts.*, s.season_label,
                    CONCAT(p.first_name, \' \', p.last_name) AS coach_name
             FROM team_seasons ts
             JOIN seasons s ON s.season_id = ts.season_id
             LEFT JOIN people p ON p.person_id = ts.coach_person_id
             WHERE ts.team_id = ? AND ts.season_id = ?'
        );
        $statement->execute([$teamId, $seasonId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function getTeamRoster(int $teamId, int $seasonId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ra.person_id, ra.jersey_number, ra.role, ra.is_active,
                    CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pe.nationality, pl.position, pl.height, pl.weight,
                    avg.games_played, avg.ppg, avg.rpg, avg.apg,
                    ROUND(avg.fg_pct, 1) AS fg_pct,
                    ROUND(avg.three_pct, 1) AS three_pct,
                    ROUND(avg.ft_pct, 1) AS ft_pct
             FROM roster_assignments ra
             JOIN people pe ON pe.person_id = ra.person_id
             JOIN players pl ON pl.person_id = ra.person_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        COUNT(*) AS games_played,
                        ROUND(AVG(pgs.points), 1) AS ppg,
                        ROUND(AVG(pgs.rebounds), 1) AS rpg,
                        ROUND(AVG(pgs.assists), 1) AS apg,
                        (SUM(pgs.field_goals_made) * 100.0 / NULLIF(SUM(pgs.field_goals_attempted), 0)) AS fg_pct,
                        (SUM(pgs.three_points_made) * 100.0 / NULLIF(SUM(pgs.three_points_attempted), 0)) AS three_pct,
                        (SUM(pgs.free_throws_made) * 100.0 / NULLIF(SUM(pgs.free_throws_attempted), 0)) AS ft_pct
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = ra.person_id AND avg.season_id = ra.season_id
             WHERE ra.team_id = ? AND ra.season_id = ?
             ORDER BY COALESCE(avg.ppg, 0) DESC, ra.jersey_number ASC, pe.last_name ASC, pe.first_name ASC'
        );
        $statement->execute([$teamId, $seasonId]);

        return $statement->fetchAll();
    }

    public function getTeamContinuity(int $teamId, int $seasonId): ?array
    {
        $previousSeasonStatement = $this->pdo->prepare(
            'SELECT MAX(season_id)
             FROM team_seasons
             WHERE team_id = ? AND season_id < ?'
        );
        $previousSeasonStatement->execute([$teamId, $seasonId]);
        $previousSeasonId = (int) $previousSeasonStatement->fetchColumn();

        if ($previousSeasonId <= 0) {
            return null;
        }

        $previousSeasonLabelStatement = $this->pdo->prepare('SELECT season_label FROM seasons WHERE season_id = ?');
        $previousSeasonLabelStatement->execute([$previousSeasonId]);
        $previousSeasonLabel = (string) ($previousSeasonLabelStatement->fetchColumn() ?: 'last season');

        $currentStatement = $this->pdo->prepare(
            'SELECT ra.person_id,
                    CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    previous_assignment.team_id AS previous_team_id,
                    previous_team.team_name AS previous_team_name,
                    COALESCE(avg.ppg, 0) AS ppg
             FROM roster_assignments ra
             JOIN people pe ON pe.person_id = ra.person_id
             LEFT JOIN roster_assignments previous_assignment
                ON previous_assignment.person_id = ra.person_id
               AND previous_assignment.season_id = ?
             LEFT JOIN teams previous_team ON previous_team.team_id = previous_assignment.team_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        ROUND(AVG(pgs.points), 1) AS ppg
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = ra.person_id AND avg.season_id = ra.season_id
             WHERE ra.team_id = ? AND ra.season_id = ?
             ORDER BY COALESCE(avg.ppg, 0) DESC, pe.last_name ASC, pe.first_name ASC'
        );
        $currentStatement->execute([$previousSeasonId, $teamId, $seasonId]);
        $currentRows = $currentStatement->fetchAll();

        $previousStatement = $this->pdo->prepare(
            'SELECT ra.person_id,
                    CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    current_assignment.team_id AS current_team_id,
                    current_team.team_name AS current_team_name
             FROM roster_assignments ra
             JOIN people pe ON pe.person_id = ra.person_id
             LEFT JOIN roster_assignments current_assignment
                ON current_assignment.person_id = ra.person_id
               AND current_assignment.season_id = ?
             LEFT JOIN teams current_team ON current_team.team_id = current_assignment.team_id
             WHERE ra.team_id = ? AND ra.season_id = ?
             ORDER BY pe.last_name ASC, pe.first_name ASC'
        );
        $previousStatement->execute([$seasonId, $teamId, $previousSeasonId]);
        $previousRows = $previousStatement->fetchAll();

        $returningPlayers = [];
        $newcomers = [];
        foreach ($currentRows as $row) {
            if ((int) ($row['previous_team_id'] ?? 0) === $teamId) {
                $returningPlayers[] = $row;
                continue;
            }

            $newcomers[] = $row;
        }

        $departures = [];
        foreach ($previousRows as $row) {
            if ((int) ($row['current_team_id'] ?? 0) !== $teamId) {
                $departures[] = $row;
            }
        }

        $currentCount = count($currentRows);
        $returningCount = count($returningPlayers);

        return [
            'previous_season_id' => $previousSeasonId,
            'previous_season_label' => $previousSeasonLabel,
            'current_count' => $currentCount,
            'previous_count' => count($previousRows),
            'returning_count' => $returningCount,
            'newcomer_count' => count($newcomers),
            'departure_count' => count($departures),
            'continuity_pct' => $currentCount > 0 ? round(($returningCount / $currentCount) * 100, 1) : 0.0,
            'newcomers' => array_slice($newcomers, 0, 3),
            'departures' => array_slice($departures, 0, 3),
        ];
    }

    public function getTeamGames(
        int $teamId,
        int $seasonId,
        string $sort = 'date',
        string $direction = 'desc',
        int $limit = 12,
        int $offset = 0
    ): array {
        $orderMap = [
            'date' => 'g.game_date',
            'opponent' => 'opponent_name',
            'team_score' => 'team_score',
            'opp_score' => 'opponent_score',
            'attendance' => 'g.attendance',
            'venue' => 'venue',
        ];
        $sortKey = $orderMap[$sort] ?? $orderMap['date'];
        $dir = $this->normalizeDirection($direction, $sort === 'opponent' || $sort === 'venue' ? 'asc' : 'desc');

        $statement = $this->pdo->prepare(
            'SELECT g.*, s.season_label,
                    ht.team_name AS home_team_name, ht.short_name AS home_short_name,
                    at.team_name AS away_team_name, at.short_name AS away_short_name,
                    CASE WHEN g.home_team_id = ? THEN g.home_score ELSE g.away_score END AS team_score,
                    CASE WHEN g.home_team_id = ? THEN g.away_score ELSE g.home_score END AS opponent_score,
                    CASE WHEN g.home_team_id = ? THEN at.team_name ELSE ht.team_name END AS opponent_name,
                    CASE WHEN g.home_team_id = ? THEN at.short_name ELSE ht.short_name END AS opponent_short_name,
                    CASE WHEN g.home_team_id = ? THEN \'Home\' ELSE \'Away\' END AS venue
             FROM games g
             JOIN seasons s ON s.season_id = g.season_id
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             WHERE g.season_id = ? AND (g.home_team_id = ? OR g.away_team_id = ?)
             ORDER BY ' . $sortKey . ' ' . $dir . ', g.game_id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $statement->execute([$teamId, $teamId, $teamId, $teamId, $teamId, $seasonId, $teamId, $teamId]);

        return $statement->fetchAll();
    }

    public function countTeamGames(int $teamId, int $seasonId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM games
             WHERE season_id = ? AND (home_team_id = ? OR away_team_id = ?)'
        );
        $statement->execute([$seasonId, $teamId, $teamId]);

        return (int) $statement->fetchColumn();
    }

    public function listPlayers(
        int $seasonId,
        string $search = '',
        string $sort = 'ppg',
        string $direction = 'desc',
        int $limit = 24,
        int $offset = 0
    ): array {
        $orderMap = [
            'player' => 'player_name',
            'team' => 'team_name',
            'nationality' => 'nationality',
            'position' => 'position',
            'gp' => 'games_played',
            'ppg' => 'ppg',
            'rpg' => 'rpg',
            'apg' => 'apg',
            'height' => 'height',
        ];
        $sortKey = $orderMap[$sort] ?? $orderMap['ppg'];
        $dir = $this->normalizeDirection($direction, in_array($sort, ['player', 'team', 'nationality', 'position'], true) ? 'asc' : 'desc');
        [$searchSql, $searchParams] = $this->buildSearchClause($search, ['player_name', 'team_name', 'nationality', 'position']);

        $statement = $this->pdo->prepare(
            'SELECT *
             FROM (
                 SELECT pl.person_id, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                        pe.nationality, pl.position, pl.height, pl.weight,
                        t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color,
                        COALESCE(avg.games_played, 0) AS games_played,
                        COALESCE(avg.ppg, 0) AS ppg,
                        COALESCE(avg.rpg, 0) AS rpg,
                        COALESCE(avg.apg, 0) AS apg,
                        COALESCE(avg.fg_pct, 0) AS fg_pct,
                        COALESCE(avg.three_pct, 0) AS three_pct,
                        COALESCE(avg.ft_pct, 0) AS ft_pct
                 FROM players pl
                 JOIN people pe ON pe.person_id = pl.person_id
                 LEFT JOIN roster_assignments ra ON ra.person_id = pl.person_id AND ra.season_id = ?
                 LEFT JOIN teams t ON t.team_id = ra.team_id
                 LEFT JOIN (
                     SELECT pgs.person_id, g.season_id,
                            COUNT(*) AS games_played,
                            ROUND(AVG(pgs.points), 1) AS ppg,
                            ROUND(AVG(pgs.rebounds), 1) AS rpg,
                            ROUND(AVG(pgs.assists), 1) AS apg,
                            ROUND((SUM(pgs.field_goals_made) * 100.0) / NULLIF(SUM(pgs.field_goals_attempted), 0), 1) AS fg_pct,
                            ROUND((SUM(pgs.three_points_made) * 100.0) / NULLIF(SUM(pgs.three_points_attempted), 0), 1) AS three_pct,
                            ROUND((SUM(pgs.free_throws_made) * 100.0) / NULLIF(SUM(pgs.free_throws_attempted), 0), 1) AS ft_pct
                     FROM player_game_stats pgs
                     JOIN games g ON g.game_id = pgs.game_id
                     GROUP BY pgs.person_id, g.season_id
                 ) avg ON avg.person_id = pl.person_id AND avg.season_id = ?
             ) players
             ' . $searchSql . '
             ORDER BY ' . $sortKey . ' ' . $dir . ', player_name ASC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $statement->execute(array_merge([$seasonId, $seasonId], $searchParams));

        return $statement->fetchAll();
    }

    public function countPlayers(int $seasonId, string $search = ''): int
    {
        [$searchSql, $searchParams] = $this->buildSearchClause($search, ['player_name', 'team_name', 'nationality', 'position']);
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM (
                 SELECT pl.person_id, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                        pe.nationality, pl.position,
                        t.team_name
                 FROM players pl
                 JOIN people pe ON pe.person_id = pl.person_id
                 LEFT JOIN roster_assignments ra ON ra.person_id = pl.person_id AND ra.season_id = ?
                 LEFT JOIN teams t ON t.team_id = ra.team_id
             ) players
             ' . $searchSql
        );
        $statement->execute(array_merge([$seasonId], $searchParams));

        return (int) $statement->fetchColumn();
    }

    public function getPlayerProfile(int $personId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT pl.person_id, pe.first_name, pe.last_name, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pe.date_of_birth, pe.nationality, pe.photo_url,
                    pl.position, pl.height, pl.weight
             FROM players pl
             JOIN people pe ON pe.person_id = pl.person_id
             WHERE pl.person_id = ?'
        );
        $statement->execute([$personId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function getPlayerSeasonSummaries(int $personId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.season_id, s.season_label,
                    t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color,
                    avg.games_played, avg.ppg, avg.rpg, avg.apg,
                    ROUND(avg.fg_pct, 1) AS fg_pct,
                    ROUND(avg.three_pct, 1) AS three_pct,
                    ROUND(avg.ft_pct, 1) AS ft_pct
             FROM seasons s
             LEFT JOIN roster_assignments ra ON ra.person_id = ? AND ra.season_id = s.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        COUNT(*) AS games_played,
                        ROUND(AVG(pgs.points), 1) AS ppg,
                        ROUND(AVG(pgs.rebounds), 1) AS rpg,
                        ROUND(AVG(pgs.assists), 1) AS apg,
                        (SUM(pgs.field_goals_made) * 100.0 / NULLIF(SUM(pgs.field_goals_attempted), 0)) AS fg_pct,
                        (SUM(pgs.three_points_made) * 100.0 / NULLIF(SUM(pgs.three_points_attempted), 0)) AS three_pct,
                        (SUM(pgs.free_throws_made) * 100.0 / NULLIF(SUM(pgs.free_throws_attempted), 0)) AS ft_pct
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = ? AND avg.season_id = s.season_id
             WHERE avg.games_played IS NOT NULL
             ORDER BY s.season_id DESC'
        );
        $statement->execute([$personId, $personId]);

        return $statement->fetchAll();
    }

    public function getPlayerGameLog(
        int $personId,
        int $seasonId,
        string $sort = 'date',
        string $direction = 'desc',
        int $limit = 15,
        int $offset = 0
    ): array {
        $orderMap = [
            'date' => 'g.game_date',
            'opponent' => 'matchup',
            'points' => 'pgs.points',
            'rebounds' => 'pgs.rebounds',
            'assists' => 'pgs.assists',
            'turnovers' => 'pgs.turnovers',
        ];
        $sortKey = $orderMap[$sort] ?? $orderMap['date'];
        $dir = $this->normalizeDirection($direction, 'desc');

        $statement = $this->pdo->prepare(
            'SELECT g.game_id, g.season_id, g.game_date, g.home_score, g.away_score,
                    ht.team_name AS home_team_name, ht.short_name AS home_short_name,
                    at.team_name AS away_team_name, at.short_name AS away_short_name,
                    CASE WHEN ra.team_id = g.home_team_id THEN CONCAT(at.team_name, \' @ \', ht.team_name) ELSE CONCAT(ht.team_name, \' vs \', at.team_name) END AS matchup,
                    pgs.points, pgs.rebounds, pgs.assists, pgs.turnovers,
                    pgs.field_goals_made, pgs.field_goals_attempted,
                    pgs.three_points_made, pgs.three_points_attempted,
                    pgs.free_throws_made, pgs.free_throws_attempted
             FROM player_game_stats pgs
             JOIN games g ON g.game_id = pgs.game_id
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             LEFT JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = g.season_id
             WHERE pgs.person_id = ? AND g.season_id = ?
             ORDER BY ' . $sortKey . ' ' . $dir . ', g.game_id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $statement->execute([$personId, $seasonId]);

        return $statement->fetchAll();
    }

    public function countPlayerGameLog(int $personId, int $seasonId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM player_game_stats pgs
             JOIN games g ON g.game_id = pgs.game_id
             WHERE pgs.person_id = ? AND g.season_id = ?'
        );
        $statement->execute([$personId, $seasonId]);

        return (int) $statement->fetchColumn();
    }

    public function getPlayerAwards(int $personId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.award_name, a.award_type, a.notes, s.season_label, s.season_id,
                    t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color,
                    ROUND(avg.ppg, 1) AS ppg,
                    ROUND(avg.apg, 1) AS apg,
                    ROUND(avg.rpg, 1) AS rpg
             FROM awards a
             JOIN seasons s ON s.season_id = a.season_id
             LEFT JOIN roster_assignments ra ON ra.person_id = a.person_id AND ra.season_id = a.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        AVG(pgs.points) AS ppg,
                        AVG(pgs.assists) AS apg,
                        AVG(pgs.rebounds) AS rpg
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = a.person_id AND avg.season_id = a.season_id
             WHERE a.person_id = ?
             ORDER BY s.season_id DESC, ' . $this->awardOrderCase('a.award_name') . ', a.award_name'
        );
        $statement->execute([$personId]);

        return $statement->fetchAll();
    }

    public function getSeasonAwards(int $seasonId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.award_id, a.award_name, a.award_type, a.notes,
                    s.season_id, s.season_label,
                    pe.person_id, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pe.nationality, pe.photo_url,
                    pl.position,
                    t.team_id, t.team_name, t.short_name, t.logo_url, t.primary_color, t.secondary_color,
                    ROUND(avg.ppg, 1) AS ppg,
                    ROUND(avg.apg, 1) AS apg,
                    ROUND(avg.rpg, 1) AS rpg,
                    avg.games_played
             FROM awards a
             JOIN seasons s ON s.season_id = a.season_id
             JOIN people pe ON pe.person_id = a.person_id
             LEFT JOIN players pl ON pl.person_id = a.person_id
             LEFT JOIN roster_assignments ra ON ra.person_id = a.person_id AND ra.season_id = a.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        COUNT(*) AS games_played,
                        AVG(pgs.points) AS ppg,
                        AVG(pgs.assists) AS apg,
                        AVG(pgs.rebounds) AS rpg
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = a.person_id AND avg.season_id = a.season_id
             WHERE a.season_id = ?
             ORDER BY ' . $this->awardOrderCase('a.award_name') . ', player_name ASC'
        );
        $statement->execute([$seasonId]);

        return $statement->fetchAll();
    }

    public function getAwardsArchive(): array
    {
        $statement = $this->pdo->query(
            'SELECT a.award_id, a.award_name, a.award_type, a.notes,
                    s.season_id, s.season_label,
                    pe.person_id, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pe.nationality, pe.photo_url,
                    pl.position,
                    t.team_id, t.team_name, t.short_name, t.logo_url, t.primary_color, t.secondary_color,
                    ROUND(avg.ppg, 1) AS ppg,
                    ROUND(avg.apg, 1) AS apg,
                    ROUND(avg.rpg, 1) AS rpg,
                    avg.games_played
             FROM awards a
             JOIN seasons s ON s.season_id = a.season_id
             JOIN people pe ON pe.person_id = a.person_id
             LEFT JOIN players pl ON pl.person_id = a.person_id
             LEFT JOIN roster_assignments ra ON ra.person_id = a.person_id AND ra.season_id = a.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             LEFT JOIN (
                 SELECT pgs.person_id, g.season_id,
                        COUNT(*) AS games_played,
                        AVG(pgs.points) AS ppg,
                        AVG(pgs.assists) AS apg,
                        AVG(pgs.rebounds) AS rpg
                 FROM player_game_stats pgs
                 JOIN games g ON g.game_id = pgs.game_id
                 GROUP BY pgs.person_id, g.season_id
             ) avg ON avg.person_id = a.person_id AND avg.season_id = a.season_id
             ORDER BY s.season_id DESC, ' . $this->awardOrderCase('a.award_name') . ', player_name ASC'
        );

        return $statement->fetchAll();
    }

    public function getGamesForSeason(
        int $seasonId,
        string $search = '',
        string $sort = 'date',
        string $direction = 'desc',
        int $limit = 20,
        int $offset = 0
    ): array {
        $orderMap = [
            'date' => 'game_date',
            'home' => 'home_team_name',
            'away' => 'away_team_name',
            'attendance' => 'attendance',
            'home_score' => 'home_score',
            'away_score' => 'away_score',
        ];
        $sortKey = $orderMap[$sort] ?? $orderMap['date'];
        $dir = $this->normalizeDirection($direction, in_array($sort, ['date', 'attendance', 'home_score', 'away_score'], true) ? 'desc' : 'asc');
        [$searchSql, $searchParams] = $this->buildSearchClause($search, ['home_team_name', 'away_team_name', 'arena_name', 'game_date']);

        $statement = $this->pdo->prepare(
            'SELECT *
             FROM (
                 ' . $this->baseGamesSelect() . '
                 WHERE g.season_id = ?
             ) games
             ' . $searchSql . '
             ORDER BY ' . $sortKey . ' ' . $dir . ', game_id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $statement->execute(array_merge([$seasonId], $searchParams));

        return $statement->fetchAll();
    }

    public function countGamesForSeason(int $seasonId, string $search = ''): int
    {
        [$searchSql, $searchParams] = $this->buildSearchClause($search, ['home_team_name', 'away_team_name', 'arena_name', 'game_date']);
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM (
                 ' . $this->baseGamesSelect() . '
                 WHERE g.season_id = ?
             ) games
             ' . $searchSql
        );
        $statement->execute(array_merge([$seasonId], $searchParams));

        return (int) $statement->fetchColumn();
    }

    public function getGameDetail(int $gameId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.*, s.season_label,
                    ht.team_name AS home_team_name, ht.short_name AS home_short_name, ht.primary_color AS home_primary_color, ht.secondary_color AS home_secondary_color,
                    at.team_name AS away_team_name, at.short_name AS away_short_name, at.primary_color AS away_primary_color, at.secondary_color AS away_secondary_color,
                    a.arena_name, a.city,
                    CONCAT(p.first_name, \' \', p.last_name) AS referee_name
             FROM games g
             JOIN seasons s ON s.season_id = g.season_id
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             LEFT JOIN arenas a ON a.arena_id = g.arena_id
             LEFT JOIN people p ON p.person_id = g.referee_id
             WHERE g.game_id = ?'
        );
        $statement->execute([$gameId]);
        $game = $statement->fetch();
        if ($game === false) {
            return null;
        }

        $teamStatsStatement = $this->pdo->prepare(
            'SELECT tgs.*, t.team_name, t.short_name, t.primary_color, t.secondary_color
             FROM team_game_stats tgs
             JOIN teams t ON t.team_id = tgs.team_id
             WHERE tgs.game_id = ?'
        );
        $teamStatsStatement->execute([$gameId]);
        $teamStats = [];
        foreach ($teamStatsStatement->fetchAll() as $row) {
            $teamStats[(int) $row['team_id']] = $row;
        }

        $playerStatsStatement = $this->pdo->prepare(
            'SELECT pgs.*, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pl.position, ra.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color
             FROM player_game_stats pgs
             JOIN people pe ON pe.person_id = pgs.person_id
             JOIN players pl ON pl.person_id = pgs.person_id
             JOIN games g ON g.game_id = pgs.game_id
             LEFT JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = g.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             WHERE pgs.game_id = ?
             ORDER BY ra.team_id, pgs.points DESC, player_name ASC'
        );
        $playerStatsStatement->execute([$gameId]);
        $playerStats = [];
        foreach ($playerStatsStatement->fetchAll() as $row) {
            $playerStats[(int) $row['team_id']][] = $row;
        }

        return [
            'game' => $game,
            'team_stats' => $teamStats,
            'player_stats' => $playerStats,
        ];
    }

    public function getHeadToHead(int $teamId, int $opponentId, ?int $seasonId = null, int $limit = 20): ?array
    {
        if ($teamId === $opponentId) {
            return null;
        }

        $team = $this->getTeamProfile($teamId);
        $opponent = $this->getTeamProfile($opponentId);
        if ($team === null || $opponent === null) {
            return null;
        }

        $params = [$teamId, $teamId, $teamId, $teamId, $opponentId, $opponentId, $teamId];
        $seasonFilter = '';
        if ($seasonId !== null) {
            $seasonFilter = ' AND g.season_id = ?';
            $params[] = $seasonId;
        }

        $statement = $this->pdo->prepare(
            'SELECT g.*, s.season_label,
                    ht.team_name AS home_team_name, ht.short_name AS home_short_name,
                    at.team_name AS away_team_name, at.short_name AS away_short_name,
                    a.arena_name,
                    CASE WHEN g.home_team_id = ? THEN g.home_score ELSE g.away_score END AS team_score,
                    CASE WHEN g.home_team_id = ? THEN g.away_score ELSE g.home_score END AS opponent_score,
                    CASE WHEN g.home_team_id = ? THEN \'Home\' ELSE \'Away\' END AS venue
             FROM games g
             JOIN seasons s ON s.season_id = g.season_id
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             LEFT JOIN arenas a ON a.arena_id = g.arena_id
             WHERE ((g.home_team_id = ? AND g.away_team_id = ?) OR (g.home_team_id = ? AND g.away_team_id = ?))' . $seasonFilter . '
             ORDER BY g.game_date DESC, g.game_id DESC
             LIMIT ' . max(1, $limit)
        );
        $statement->execute($params);
        $games = $statement->fetchAll();

        if ($games === []) {
            return null;
        }

        $summary = [
            'games' => count($games),
            'team_wins' => 0,
            'opponent_wins' => 0,
            'team_points' => 0,
            'opponent_points' => 0,
            'average_margin' => 0.0,
        ];
        foreach ($games as $game) {
            $teamScore = (int) $game['team_score'];
            $opponentScore = (int) $game['opponent_score'];
            $summary['team_points'] += $teamScore;
            $summary['opponent_points'] += $opponentScore;
            if ($teamScore > $opponentScore) {
                $summary['team_wins']++;
            } else {
                $summary['opponent_wins']++;
            }
        }
        $summary['average_margin'] = $summary['games'] > 0
            ? round(($summary['team_points'] - $summary['opponent_points']) / $summary['games'], 1)
            : 0.0;

        return [
            'team' => $team,
            'opponent' => $opponent,
            'summary' => $summary,
            'games' => $games,
        ];
    }

    public function getPlayoffBracket(int $seasonId): array
    {
        $standings = $this->getSeasonStandings($seasonId, 'rank', 'asc', 10, 0);
        if (count($standings) < 8) {
            return [];
        }

        $seedMap = [];
        foreach ($standings as $team) {
            $seedMap[(int) $team['final_rank']] = $team;
        }

        $playIn = [];
        $seedSeven = $seedMap[7] ?? null;
        $seedEight = $seedMap[8] ?? null;
        $seedNine = $seedMap[9] ?? null;
        $seedTen = $seedMap[10] ?? null;

        if ($seedSeven !== null && $seedEight !== null && $seedNine !== null && $seedTen !== null) {
            $playInA = $this->buildPlayInGame($seedSeven, $seedEight, $seasonId, 'Play-In for the No. 7 seed', 7, false);
            $playInB = $this->buildPlayInGame($seedNine, $seedTen, $seasonId, 'Win-or-go-home play-in', 0, true);
            $playInC = $this->buildPlayInGame($playInA['loser'], $playInB['winner'], $seasonId, 'Play-In for the No. 8 seed', 8, false);
            $playIn = [$playInA, $playInB, $playInC];
            $seedSeven = $playInA['winner'];
            $seedEight = $playInC['winner'];
        }

        $quarterfinals = [
            $this->buildSeries($seedMap[1], $seedEight ?? $seedMap[8], $seasonId, 'Quarterfinal 1'),
            $this->buildSeries($seedMap[4], $seedMap[5], $seasonId, 'Quarterfinal 2'),
            $this->buildSeries($seedMap[3], $seedMap[6], $seasonId, 'Quarterfinal 3'),
            $this->buildSeries($seedMap[2], $seedSeven ?? $seedMap[7], $seasonId, 'Quarterfinal 4'),
        ];

        $semifinals = [
            $this->buildSeries($quarterfinals[0]['winner'], $quarterfinals[1]['winner'], $seasonId, 'Semifinal 1'),
            $this->buildSeries($quarterfinals[2]['winner'], $quarterfinals[3]['winner'], $seasonId, 'Semifinal 2'),
        ];

        $final = $this->buildSeries($semifinals[0]['winner'], $semifinals[1]['winner'], $seasonId, 'Championship Game');

        return [
            'standings' => $standings,
            'play_in' => $playIn,
            'quarterfinals' => $quarterfinals,
            'semifinals' => $semifinals,
            'final' => $final,
            'champion' => $final['winner'],
        ];
    }

    public function getFeaturedGames(int $seasonId, int $limit = 4): array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.game_id, g.game_date, g.home_score, g.away_score, g.attendance, g.overtime_count,
                    ht.team_id AS home_team_id, ht.team_name AS home_team_name, ht.short_name AS home_short_name,
                    ht.primary_color AS home_primary_color, ht.secondary_color AS home_secondary_color,
                    at.team_id AS away_team_id, at.team_name AS away_team_name, at.short_name AS away_short_name,
                    at.primary_color AS away_primary_color, at.secondary_color AS away_secondary_color,
                    a.arena_name, a.city,
                    home_ts.final_rank AS home_rank,
                    away_ts.final_rank AS away_rank,
                    ABS(g.home_score - g.away_score) AS margin
             FROM games g
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             LEFT JOIN arenas a ON a.arena_id = g.arena_id
             LEFT JOIN team_seasons home_ts ON home_ts.season_id = g.season_id AND home_ts.team_id = g.home_team_id
             LEFT JOIN team_seasons away_ts ON away_ts.season_id = g.season_id AND away_ts.team_id = g.away_team_id
             WHERE g.season_id = ?
             ORDER BY CASE
                        WHEN COALESCE(home_ts.final_rank, 99) <= 4 AND COALESCE(away_ts.final_rank, 99) <= 8 THEN 0
                        WHEN COALESCE(home_ts.final_rank, 99) <= 8 AND COALESCE(away_ts.final_rank, 99) <= 8 THEN 1
                        ELSE 2
                      END ASC,
                      ABS(g.home_score - g.away_score) ASC,
                      (COALESCE(home_ts.final_rank, 20) + COALESCE(away_ts.final_rank, 20)) ASC,
                      g.attendance DESC,
                      g.game_date DESC,
                      g.game_id DESC
             LIMIT ' . max(1, $limit)
        );
        $statement->execute([$seasonId]);

        return $statement->fetchAll();
    }

    public function getTopPerformances(int $seasonId, int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT pgs.person_id,
                    CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    pgs.points, pgs.rebounds, pgs.assists,
                    g.game_id, g.game_date,
                    t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color,
                    CASE WHEN ra.team_id = g.home_team_id THEN at.team_name ELSE ht.team_name END AS opponent_name,
                    CASE WHEN ra.team_id = g.home_team_id THEN at.short_name ELSE ht.short_name END AS opponent_short_name,
                    CASE WHEN ra.team_id = g.home_team_id THEN g.home_score ELSE g.away_score END AS team_score,
                    CASE WHEN ra.team_id = g.home_team_id THEN g.away_score ELSE g.home_score END AS opponent_score,
                    ROUND((pgs.points * 1.0) + (pgs.rebounds * 1.15) + (pgs.assists * 1.35), 1) AS performance_score
             FROM player_game_stats pgs
             JOIN games g ON g.game_id = pgs.game_id
             JOIN people pe ON pe.person_id = pgs.person_id
             JOIN teams ht ON ht.team_id = g.home_team_id
             JOIN teams at ON at.team_id = g.away_team_id
             LEFT JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = g.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             WHERE g.season_id = ?
             ORDER BY performance_score DESC, pgs.points DESC, g.game_date DESC, pgs.game_id DESC
             LIMIT ' . max(1, $limit)
        );
        $statement->execute([$seasonId]);

        return $statement->fetchAll();
    }

    public function getFranchiseLeaders(): array
    {
        $seasonId = $this->currentSeasonId();

        return [
            'points' => $this->seasonLeadersByColumn($seasonId, 'points', 'PPG', 10),
            'rebounds' => $this->seasonLeadersByColumn($seasonId, 'rebounds', 'RPG', 10),
            'assists' => $this->seasonLeadersByColumn($seasonId, 'assists', 'APG', 10),
        ];
    }

    private function seasonLeadersByColumn(int $seasonId, string $column, string $label, int $limit = 5): array
    {
        $allowedColumns = ['points', 'rebounds', 'assists'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidArgumentException('Unsupported leader column.');
        }

        $statement = $this->pdo->prepare(
            'SELECT pgs.person_id, CONCAT(pe.first_name, \' \', pe.last_name) AS player_name,
                    t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color,
                    COUNT(*) AS games_played,
                    ROUND(AVG(pgs.' . $column . '), 1) AS value
             FROM player_game_stats pgs
             JOIN games g ON g.game_id = pgs.game_id
             JOIN people pe ON pe.person_id = pgs.person_id
             LEFT JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = g.season_id
             LEFT JOIN teams t ON t.team_id = ra.team_id
             WHERE g.season_id = ?
             GROUP BY pgs.person_id, pe.first_name, pe.last_name, t.team_id, t.team_name, t.short_name, t.primary_color, t.secondary_color
             ORDER BY value DESC, games_played DESC, player_name ASC
             LIMIT ' . max(1, $limit)
        );
        $statement->execute([$seasonId]);

        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $row['label'] = $label;
        }

        return $rows;
    }

    private function normalizeDirection(string $direction, string $default = 'desc'): string
    {
        $value = strtolower($direction);
        if ($value === 'asc' || $value === 'desc') {
            return strtoupper($value);
        }

        return strtoupper($default);
    }

    private function awardOrderCase(string $column): string
    {
        return 'CASE ' . $column . '
                    WHEN "MVP" THEN 0
                    WHEN "Best Scorer" THEN 1
                    WHEN "Assist Artist" THEN 2
                    WHEN "Glass King" THEN 3
                    WHEN "Most Improved" THEN 4
                    WHEN "Best Sixth Man" THEN 5
                    WHEN "Clutch Performer" THEN 6
                    WHEN "Defensive Anchor" THEN 7
                    WHEN "Fan Favorite" THEN 8
                    WHEN "Playmaker Award" THEN 9
                    ELSE 99
                END';
    }

    private function buildSearchClause(string $search, array $columns): array
    {
        $term = trim($search);
        if ($term === '') {
            return ['', []];
        }

        $clauses = [];
        $params = [];
        foreach ($columns as $column) {
            $clauses[] = 'LOWER(COALESCE(' . $column . ', \'\')) LIKE ?';
            $params[] = '%' . strtolower($term) . '%';
        }

        return ['WHERE ' . implode(' OR ', $clauses), $params];
    }

    private function baseGamesSelect(): string
    {
        return 'SELECT g.*, s.season_label,
                       ht.team_name AS home_team_name, ht.short_name AS home_short_name,
                       ht.primary_color AS home_primary_color, ht.secondary_color AS home_secondary_color,
                       at.team_name AS away_team_name, at.short_name AS away_short_name,
                       at.primary_color AS away_primary_color, at.secondary_color AS away_secondary_color,
                       a.arena_name, a.city
                FROM games g
                JOIN seasons s ON s.season_id = g.season_id
                JOIN teams ht ON ht.team_id = g.home_team_id
                JOIN teams at ON at.team_id = g.away_team_id
                LEFT JOIN arenas a ON a.arena_id = g.arena_id';
    }

    private function getHeadToHeadResultsForSeason(int $seasonId, int $teamAId, int $teamBId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.home_team_id, g.away_team_id, g.home_score, g.away_score
             FROM games g
             WHERE g.season_id = ?
               AND ((g.home_team_id = ? AND g.away_team_id = ?) OR (g.home_team_id = ? AND g.away_team_id = ?))'
        );
        $statement->execute([$seasonId, $teamAId, $teamBId, $teamBId, $teamAId]);
        $games = $statement->fetchAll();

        $summary = [
            'team_a_wins' => 0,
            'team_b_wins' => 0,
            'margin' => 0,
        ];
        foreach ($games as $game) {
            $teamAScore = (int) $game['home_team_id'] === $teamAId ? (int) $game['home_score'] : (int) $game['away_score'];
            $teamBScore = (int) $game['home_team_id'] === $teamBId ? (int) $game['home_score'] : (int) $game['away_score'];
            if ($teamAScore > $teamBScore) {
                $summary['team_a_wins']++;
            } else {
                $summary['team_b_wins']++;
            }
            $summary['margin'] += $teamAScore - $teamBScore;
        }

        return $summary;
    }

    private function buildPlayInGame(array $teamA, array $teamB, int $seasonId, string $label, int $winnerSeed, bool $elimination): array
    {
        $headToHead = $this->getHeadToHeadResultsForSeason($seasonId, (int) $teamA['team_id'], (int) $teamB['team_id']);
        $gap = $this->compareTeams($teamA, $teamB, $headToHead);
        $winner = $gap >= 0 ? $teamA : $teamB;
        $loser = $gap >= 0 ? $teamB : $teamA;
        [$winnerScore, $loserScore] = $this->singleGameScoreFromGap(abs($gap));

        return [
            'label' => $label,
            'winner_seed' => $winnerSeed,
            'is_elimination' => $elimination,
            'team_a' => $teamA,
            'team_b' => $teamB,
            'winner' => $winner,
            'loser' => $loser,
            'score' => $winnerScore . '-' . $loserScore,
        ];
    }

    private function buildSeries(array $higherSeed, array $lowerSeed, int $seasonId, string $roundLabel): array
    {
        $headToHead = $this->getHeadToHeadResultsForSeason($seasonId, (int) $higherSeed['team_id'], (int) $lowerSeed['team_id']);
        $gap = $this->compareTeams($higherSeed, $lowerSeed, $headToHead);
        $winner = $gap >= 0 ? $higherSeed : $lowerSeed;
        $loser = $gap >= 0 ? $lowerSeed : $higherSeed;
        $loserWins = $this->seriesLossesFromGap(abs($gap));

        return [
            'label' => $roundLabel,
            'higher_seed' => $higherSeed,
            'lower_seed' => $lowerSeed,
            'winner' => $winner,
            'loser' => $loser,
            'series_score' => $gap >= 0 ? '3-' . $loserWins : $loserWins . '-3',
            'headline' => $winner['team_name'] . ' carry ' . ($gap >= 0 ? 'the higher seed and efficiency edge' : 'the upset case built on form and matchup') . ' into the round.',
        ];
    }

    private function compareTeams(array $teamA, array $teamB, ?array $headToHead = null): float
    {
        $gap = $this->teamStrength($teamA) - $this->teamStrength($teamB);
        if ($headToHead !== null) {
            $gap += (($headToHead['team_a_wins'] ?? 0) - ($headToHead['team_b_wins'] ?? 0)) * 2.4;
            $gap += (($headToHead['margin'] ?? 0) / 8.0);
        }

        return $gap;
    }

    private function teamStrength(array $team): float
    {
        return ((float) ($team['wins'] ?? 0) * 2.2)
            + ((float) ($team['point_diff'] ?? 0) / 10.0)
            + ((float) ($team['points_per_game'] ?? 0) / 6.0)
            + (((float) ($team['avg_attendance'] ?? 0)) / 8000.0);
    }

    private function seriesLossesFromGap(float $gap): int
    {
        if ($gap >= 16.0) {
            return 0;
        }
        if ($gap >= 9.0) {
            return 1;
        }

        return 2;
    }

    private function singleGameScoreFromGap(float $gap): array
    {
        $winnerScore = 82 + min(10, (int) round($gap));
        $margin = max(2, min(14, 4 + (int) round($gap / 2)));

        return [$winnerScore, $winnerScore - $margin];
    }
}