-- Query 1: Season standings board with club and venue context
SELECT
    s.season_label,
    ts.final_rank,
    t.team_name,
    c.country_name,
    a.arena_name,
    ts.wins,
    ts.losses,
    ROUND(ts.wins / NULLIF(ts.wins + ts.losses, 0), 3) AS win_pct,
    ts.point_diff
FROM team_seasons ts
INNER JOIN seasons s ON s.season_id = ts.season_id
INNER JOIN teams t ON t.team_id = ts.team_id
INNER JOIN countries c ON c.country_id = t.country_id
LEFT JOIN arenas a ON a.arena_id = t.home_arena_id
ORDER BY s.season_id DESC, ts.final_rank ASC, t.team_name ASC;

-- Query 2: Countries averaging at least 10,000 fans per game
SELECT
    c.country_name,
    COUNT(DISTINCT t.team_id) AS clubs,
    ROUND(AVG(ts.avg_attendance), 0) AS avg_attendance,
    ROUND(AVG(ts.wins), 1) AS avg_wins
FROM countries c
LEFT JOIN teams t ON t.country_id = c.country_id
LEFT JOIN team_seasons ts ON ts.team_id = t.team_id
GROUP BY c.country_id, c.country_name
HAVING AVG(ts.avg_attendance) >= 10000
ORDER BY avg_attendance DESC, clubs DESC;

-- Query 3: Players averaging 15+ points with minimum volume
SELECT
    s.season_label,
    p.first_name,
    p.last_name,
    t.team_name,
    ROUND(AVG(pgs.points), 1) AS ppg,
    COUNT(*) AS games_played
FROM player_game_stats pgs
INNER JOIN games g ON g.game_id = pgs.game_id
INNER JOIN seasons s ON s.season_id = g.season_id
INNER JOIN people p ON p.person_id = pgs.person_id
INNER JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = g.season_id
INNER JOIN teams t ON t.team_id = ra.team_id
GROUP BY s.season_id, s.season_label, p.person_id, p.first_name, p.last_name, t.team_name
HAVING COUNT(*) >= 20 AND AVG(pgs.points) >= 15
ORDER BY s.season_id DESC, ppg DESC, p.last_name ASC;

-- Query 4: Seasons and MVP winners using RIGHT JOIN
SELECT
    s.season_label,
    COALESCE(CONCAT(p.first_name, ' ', p.last_name), 'No MVP assigned') AS mvp_winner,
    t.team_name
FROM awards a
RIGHT JOIN seasons s ON s.season_id = a.season_id AND a.award_name = 'MVP'
LEFT JOIN people p ON p.person_id = a.person_id
LEFT JOIN roster_assignments ra ON ra.person_id = a.person_id AND ra.season_id = s.season_id
LEFT JOIN teams t ON t.team_id = ra.team_id
ORDER BY s.season_id DESC;

-- Query 5: Distinct countries represented in the Final Four
SELECT DISTINCT
    s.season_label,
    c.country_name
FROM team_seasons ts
INNER JOIN seasons s ON s.season_id = ts.season_id
INNER JOIN teams t ON t.team_id = ts.team_id
INNER JOIN countries c ON c.country_id = t.country_id
WHERE ts.final_four_flag = 1
ORDER BY s.season_id DESC, c.country_name ASC;

-- Query 6: Arenas that hosted at least 20 games
SELECT
    a.arena_name,
    a.city,
    COUNT(g.game_id) AS games_hosted,
    ROUND(AVG(g.attendance), 0) AS avg_attendance,
    MAX(g.attendance) AS peak_attendance
FROM arenas a
LEFT JOIN games g ON g.arena_id = a.arena_id
LEFT JOIN teams t ON t.home_arena_id = a.arena_id
GROUP BY a.arena_id, a.arena_name, a.city
HAVING COUNT(g.game_id) >= 20
ORDER BY games_hosted DESC, avg_attendance DESC;

-- Query 7: Head-to-head record by club pairing
SELECT
    s.season_label,
    ht.team_name AS team_a,
    at.team_name AS team_b,
    SUM(CASE WHEN g.home_team_id = ht.team_id AND g.home_score > g.away_score THEN 1
             WHEN g.away_team_id = ht.team_id AND g.away_score > g.home_score THEN 1
             ELSE 0 END) AS team_a_wins,
    SUM(CASE WHEN g.home_team_id = at.team_id AND g.home_score > g.away_score THEN 1
             WHEN g.away_team_id = at.team_id AND g.away_score > g.home_score THEN 1
             ELSE 0 END) AS team_b_wins,
    COUNT(*) AS total_games
FROM games g
INNER JOIN seasons s ON s.season_id = g.season_id
INNER JOIN teams ht ON ht.team_id = g.home_team_id
INNER JOIN teams at ON at.team_id = g.away_team_id
GROUP BY s.season_id, s.season_label, ht.team_id, ht.team_name, at.team_id, at.team_name
HAVING COUNT(*) >= 2
ORDER BY s.season_id DESC, total_games DESC, team_a ASC;

-- Query 8: Award totals by player and club
SELECT
    CONCAT(p.first_name, ' ', p.last_name) AS player_name,
    t.team_name,
    COUNT(a.award_id) AS awards_won,
    MIN(s.season_label) AS first_award_season,
    MAX(s.season_label) AS latest_award_season
FROM awards a
INNER JOIN people p ON p.person_id = a.person_id
INNER JOIN seasons s ON s.season_id = a.season_id
LEFT JOIN roster_assignments ra ON ra.person_id = a.person_id AND ra.season_id = a.season_id
LEFT JOIN teams t ON t.team_id = ra.team_id
GROUP BY p.person_id, p.first_name, p.last_name, t.team_name
HAVING COUNT(a.award_id) >= 2
ORDER BY awards_won DESC, player_name ASC;

-- Query 9: Top scorer in each season using a subquery
SELECT
    season_scores.season_label,
    season_scores.player_name,
    season_scores.team_name,
    season_scores.ppg
FROM (
    SELECT
        s.season_id,
        s.season_label,
        p.person_id,
        CONCAT(p.first_name, ' ', p.last_name) AS player_name,
        t.team_name,
        ROUND(AVG(pgs.points), 1) AS ppg
    FROM player_game_stats pgs
    INNER JOIN games g ON g.game_id = pgs.game_id
    INNER JOIN seasons s ON s.season_id = g.season_id
    INNER JOIN people p ON p.person_id = pgs.person_id
    INNER JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = s.season_id
    INNER JOIN teams t ON t.team_id = ra.team_id
    GROUP BY s.season_id, s.season_label, p.person_id, p.first_name, p.last_name, t.team_name
) AS season_scores
INNER JOIN (
    SELECT
        g.season_id,
        MAX(player_ppg.ppg) AS max_ppg
    FROM (
        SELECT g.season_id, pgs.person_id, ROUND(AVG(pgs.points), 1) AS ppg
        FROM player_game_stats pgs
        INNER JOIN games g ON g.game_id = pgs.game_id
        GROUP BY g.season_id, pgs.person_id
    ) AS player_ppg
    INNER JOIN games g ON g.season_id = player_ppg.season_id
    GROUP BY g.season_id
) AS season_max
    ON season_max.season_id = season_scores.season_id
   AND season_max.max_ppg = season_scores.ppg
ORDER BY season_scores.season_id DESC, season_scores.player_name ASC;

-- Query 10: Players above the season scoring average using a subquery
SELECT
    player_lines.season_label,
    player_lines.player_name,
    player_lines.team_name,
    player_lines.ppg,
    season_avg.avg_ppg AS season_average
FROM (
    SELECT
        s.season_id,
        s.season_label,
        p.person_id,
        CONCAT(p.first_name, ' ', p.last_name) AS player_name,
        t.team_name,
        ROUND(AVG(pgs.points), 1) AS ppg
    FROM player_game_stats pgs
    INNER JOIN games g ON g.game_id = pgs.game_id
    INNER JOIN seasons s ON s.season_id = g.season_id
    INNER JOIN people p ON p.person_id = pgs.person_id
    INNER JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = s.season_id
    INNER JOIN teams t ON t.team_id = ra.team_id
    GROUP BY s.season_id, s.season_label, p.person_id, p.first_name, p.last_name, t.team_name
) AS player_lines
INNER JOIN (
    SELECT
        season_id,
        ROUND(AVG(ppg), 1) AS avg_ppg
    FROM (
        SELECT g.season_id, pgs.person_id, AVG(pgs.points) AS ppg
        FROM player_game_stats pgs
        INNER JOIN games g ON g.game_id = pgs.game_id
        GROUP BY g.season_id, pgs.person_id
    ) AS per_player
    GROUP BY season_id
) AS season_avg
    ON season_avg.season_id = player_lines.season_id
WHERE player_lines.ppg > season_avg.avg_ppg
ORDER BY player_lines.season_id DESC, player_lines.ppg DESC;

-- Query 11: Clubs with repeated playoff qualification
SELECT
    t.team_name,
    c.country_name,
    COUNT(*) AS playoff_seasons,
    AVG(ts.playoff_seed) AS avg_seed,
    MAX(ts.final_rank) AS best_finish_rank
FROM team_seasons ts
INNER JOIN teams t ON t.team_id = ts.team_id
INNER JOIN countries c ON c.country_id = t.country_id
WHERE ts.qualified_playoffs = 1
GROUP BY t.team_id, t.team_name, c.country_name
HAVING COUNT(*) >= 4
ORDER BY playoff_seasons DESC, avg_seed ASC;

-- Query 12: Coach records by club
SELECT
    CONCAT(p.first_name, ' ', p.last_name) AS coach_name,
    t.team_name,
    COUNT(*) AS seasons_coached,
    SUM(ts.wins) AS total_wins,
    SUM(ts.losses) AS total_losses,
    ROUND(SUM(ts.wins) / NULLIF(SUM(ts.wins) + SUM(ts.losses), 0), 3) AS win_pct
FROM team_seasons ts
INNER JOIN coaches cch ON cch.person_id = ts.coach_person_id
INNER JOIN people p ON p.person_id = cch.person_id
INNER JOIN teams t ON t.team_id = ts.team_id
GROUP BY cch.person_id, p.first_name, p.last_name, t.team_name
HAVING COUNT(*) >= 2
ORDER BY win_pct DESC, total_wins DESC;

-- Query 13: Three-point specialists with minimum attempts
SELECT
    s.season_label,
    CONCAT(p.first_name, ' ', p.last_name) AS player_name,
    t.team_name,
    ROUND((SUM(pgs.three_points_made) / NULLIF(SUM(pgs.three_points_attempted), 0)) * 100, 1) AS three_pct,
    SUM(pgs.three_points_attempted) AS total_attempts
FROM player_game_stats pgs
INNER JOIN games g ON g.game_id = pgs.game_id
INNER JOIN seasons s ON s.season_id = g.season_id
INNER JOIN people p ON p.person_id = pgs.person_id
INNER JOIN roster_assignments ra ON ra.person_id = pgs.person_id AND ra.season_id = s.season_id
INNER JOIN teams t ON t.team_id = ra.team_id
GROUP BY s.season_id, s.season_label, p.person_id, p.first_name, p.last_name, t.team_name
HAVING SUM(pgs.three_points_attempted) >= 80
ORDER BY three_pct DESC, total_attempts DESC;

-- Query 14: Countries with multiple clubs and strong average records
SELECT
    c.country_name,
    COUNT(DISTINCT t.team_id) AS clubs,
    ROUND(AVG(ts.wins), 1) AS avg_wins,
    ROUND(AVG(ts.wins / NULLIF(ts.wins + ts.losses, 0)), 3) AS avg_win_pct
FROM countries c
INNER JOIN teams t ON t.country_id = c.country_id
INNER JOIN team_seasons ts ON ts.team_id = t.team_id
GROUP BY c.country_id, c.country_name
HAVING COUNT(DISTINCT t.team_id) >= 2 AND AVG(ts.wins) >= 12
ORDER BY avg_win_pct DESC, clubs DESC;

-- Query 15: Puzzle answer density by season and puzzle
SELECT
    s.season_label,
    gp.puzzle_name,
    COUNT(DISTINCT gpc.row_position, gpc.column_position) AS cells,
    COUNT(gpa.person_id) AS total_answers,
    ROUND(COUNT(gpa.person_id) / NULLIF(COUNT(DISTINCT gpc.row_position, gpc.column_position), 0), 1) AS answers_per_cell
FROM grid_puzzles gp
INNER JOIN seasons s ON s.season_id = gp.season_id
INNER JOIN grid_puzzle_cells gpc ON gpc.grid_puzzle_id = gp.grid_puzzle_id
LEFT JOIN grid_puzzle_answers gpa
    ON gpa.grid_puzzle_id = gpc.grid_puzzle_id
   AND gpa.row_position = gpc.row_position
   AND gpa.column_position = gpc.column_position
GROUP BY s.season_id, s.season_label, gp.grid_puzzle_id, gp.puzzle_name
HAVING COUNT(gpa.person_id) >= 5
ORDER BY s.season_id DESC, answers_per_cell DESC, gp.puzzle_name ASC;