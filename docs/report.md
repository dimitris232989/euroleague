# Euroleague Archive Information System

## Cover Page

- Course Title: [Insert course title]
- Project Title: Euroleague Archive Information System
- Student Name: [Insert student name]
- Student ID: [Insert student ID]
- Instructor Name: [Insert instructor name]
- Submission Date: [Insert submission date]

## 1. System Description And Objectives

This project is a PHP-based Euroleague archive information system supported by a normalized relational database and a browser-accessible web interface. The system was designed to store and present interconnected historical basketball data, including seasons, clubs, arenas, players, coaches, referees, games, awards, playoff outcomes, and an interactive grid puzzle feature. Rather than treating the database as a purely backend requirement, the project uses the same dataset to support public browsing, analytical querying, and structured record management inside one integrated application.

The project has two central aims. The first is database-oriented: to design and implement a relational schema that demonstrates correct entity separation, primary and foreign key usage, composite-key handling where appropriate, and sufficient data depth for meaningful SQL analysis. The second is application-oriented: to show that the stored data can be transformed into practical outputs such as standings pages, club and player profiles, awards archives, game logs, playoff summaries, and interactive tools. This dual focus ensures that the submission addresses both technical database requirements and real system usability.

From a coursework perspective, the system was built to satisfy the full workflow expected in a database project. It includes a reproducible MySQL dataset, 15 advanced SQL queries, seeded multi-season sample data, and a web application that exposes the same information through accessible pages and administrative tools. The public-facing portion demonstrates how relational data can be interpreted for ordinary users, while the Data Desk and query viewer demonstrate that the system also supports administrative inspection, CRUD operations, and SQL-based evaluation.

The choice of a Euroleague archive was deliberate because sports information naturally contains rich many-to-many and one-to-many relationships. Teams participate across seasons, players move through different rosters, coaches and referees connect to competitions, games generate both team and player statistics, and awards depend on historical performance. This makes the domain especially suitable for joins, aggregates, grouped analysis, filtering, and subqueries, while also producing a final application that is easier to evaluate visually than an abstract administrative dataset.

## 2. Entity Relationship Diagram (ERD)

Insert the final ERD image below.

[Insert ERD image here]

## 3. SQL Implementation Scripts

The project uses `euroleague_schema.sql` as the main schema definition and `exports/euroleague_mysql_import.sql` as the MySQL import file used for phpMyAdmin. The schema contains 19 relations and was designed to support seasons, clubs, players, awards, games, statistics, and puzzle data.

The project separates schema development from submission-oriented SQL delivery. The source schema is maintained in `euroleague_schema.sql`, which defines the tables, keys, and constraints used by the application. For import and submission preparation, the repository provides `exports/euroleague_mysql_import.sql`, which serves as the MySQL-compatible import source for phpMyAdmin.

The required workflow is to import this file into phpMyAdmin, verify that the structure and data load correctly, execute the advanced queries, and then export the imported database from phpMyAdmin with both structure and data. That phpMyAdmin export constitutes the final SQL artifact expected in the submission pack.

The repository also includes a verification helper. Running `php scripts/verify_submission_requirements.php` validates the archive against the stated assignment requirements. On the seeded archive, the script confirms the presence of 19 tables, verifies that each table contains at least 10 rows, confirms that 15 advanced queries are documented and packaged, and verifies that the required SQL features are present.

Insert the following evidence in the final submitted version:

- [Insert screenshot of phpMyAdmin after import]
- [Insert screenshot of the imported table list]
- [Insert screenshot of one populated table]

## 4. Sample Data Description

The sample data was prepared to support both application realism and SQL analysis. The seeded archive spans multiple seasons and includes linked clubs, venues, players, awards, games, standings, playoff indicators, and puzzle data. The resulting dataset is therefore large enough to sustain both user-facing views and non-trivial analytical queries.

Clubs are linked to countries and arenas, and team-season records store wins, losses, point totals, point differential, attendance, rankings, and playoff flags. Players are linked through the `people` base relation and assigned to clubs through `roster_assignments`, thereby supporting roster views, player histories, and game-level statistics. Games were seeded with scores, attendance, team statistics, and player statistics, while awards were distributed across seasons to create meaningful historical variation.

Puzzle data was likewise seeded and linked to the wider archive model. The verification helper confirms that every table in the archive meets the minimum row threshold, indicating that the dataset was prepared for testing, reporting, and rubric compliance rather than for minimal demonstration alone.

Insert the following evidence in the final submitted version:

- [Insert screenshot of the `seasons` table]
- [Insert screenshot of the `teams` table]
- [Insert screenshot of the `games` table]
- [Insert screenshot of the `awards` table]

## 5. SQL Queries With Explanations And Outputs

The project includes 15 advanced SQL queries packaged in `docs/advanced_queries.sql`, with corresponding explanations in `docs/advanced_queries.md`. Collectively, they demonstrate the required SQL features, including joins, `LEFT JOIN`, `RIGHT JOIN`, `DISTINCT`, `HAVING`, aggregate functions, and subqueries. Because these queries operate on the same schema used by the application, they form an integrated analytical layer rather than a detached exercise.

Query 1. Season standings board with club and venue context

Explanation: This query builds a season standings report with club, country, venue, record, and point-differential context.

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

SQL features used: `INNER JOIN`, `LEFT JOIN`, arithmetic calculation, ordering.

Output: [Insert phpMyAdmin result screenshot for Query 1]

Query 2. Countries averaging at least 10,000 fans per game

Explanation: This query identifies countries associated with consistently strong attendance figures.

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

SQL features used: `LEFT JOIN`, `COUNT(DISTINCT ...)`, `AVG`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 2]

Query 3. Players averaging 15+ points with minimum volume

Explanation: This query identifies high-scoring players while excluding statistically weak samples.

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

SQL features used: multiple `INNER JOIN`s, `AVG`, `COUNT`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 3]

Query 4. Seasons and MVP winners using RIGHT JOIN

Explanation: This query lists every season and displays the MVP winner where available.

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

SQL features used: `RIGHT JOIN`, `LEFT JOIN`, `COALESCE`, ordering.

Output: [Insert phpMyAdmin result screenshot for Query 4]

Query 5. Distinct countries represented in the Final Four

Explanation: This query identifies which countries were represented in Final Four appearances.

    SELECT DISTINCT
        s.season_label,
        c.country_name
    FROM team_seasons ts
    INNER JOIN seasons s ON s.season_id = ts.season_id
    INNER JOIN teams t ON t.team_id = ts.team_id
    INNER JOIN countries c ON c.country_id = t.country_id
    WHERE ts.final_four_flag = 1
    ORDER BY s.season_id DESC, c.country_name ASC;

SQL features used: `DISTINCT`, `INNER JOIN`, filtering, ordering.

Output: [Insert phpMyAdmin result screenshot for Query 5]

Query 6. Arenas that hosted at least 20 games

Explanation: This query compares venue utilization and attendance at heavily used arenas.

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

SQL features used: `LEFT JOIN`, `COUNT`, `AVG`, `MAX`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 6]

Query 7. Head-to-head record by club pairing

Explanation: This query compares repeated matchups between club pairs within a season.

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

SQL features used: multiple `INNER JOIN`s, `SUM(CASE ...)`, `COUNT`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 7]

Query 8. Award totals by player and club

Explanation: This query summarizes repeat award winners together with club context.

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

SQL features used: `INNER JOIN`, `LEFT JOIN`, `COUNT`, `MIN`, `MAX`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 8]

Query 9. Top scorer in each season using a subquery

Explanation: This query returns the leading scorer for every season in the archive.

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

SQL features used: subqueries, `AVG`, `MAX`, `INNER JOIN`, `GROUP BY`.

Output: [Insert phpMyAdmin result screenshot for Query 9]

Query 10. Players above the season scoring average using a subquery

Explanation: This query compares each player's scoring average with the scoring environment of the relevant season.

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

SQL features used: subqueries, `AVG`, `INNER JOIN`, comparison against computed values.

Output: [Insert phpMyAdmin result screenshot for Query 10]

Query 11. Clubs with repeated playoff qualification

Explanation: This query identifies clubs with sustained playoff participation.

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

SQL features used: `INNER JOIN`, `COUNT`, `AVG`, `MAX`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 11]

Query 12. Coach records by club

Explanation: This query summarizes multi-season coaching performance by club.

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

SQL features used: `INNER JOIN`, `SUM`, `COUNT`, calculated ratio, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 12]

Query 13. Three-point specialists with minimum attempts

Explanation: This query identifies efficient perimeter shooters subject to a meaningful volume threshold.

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

SQL features used: multiple `INNER JOIN`s, `SUM`, calculated percentage, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 13]

Query 14. Countries with multiple clubs and strong average records

Explanation: This query compares country-level strength using club counts and winning records.

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

SQL features used: `COUNT(DISTINCT ...)`, `AVG`, `INNER JOIN`, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 14]

Query 15. Puzzle answer density by season and puzzle

Explanation: This query analyzes how puzzle answer coverage varies by season and puzzle board.

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

SQL features used: `INNER JOIN`, `LEFT JOIN`, `COUNT`, calculated ratio, `GROUP BY`, `HAVING`.

Output: [Insert phpMyAdmin result screenshot for Query 15]

## 6. Web Application Screenshots

Insert screenshots from the main pages of the web application below.

Homepage screenshot. The homepage demonstrates the overall presentation layer of the system. It introduces the Euroleague archive, highlights the current or selected season context, and presents summary information that immediately shows the database is being used to generate meaningful basketball content rather than static placeholder text. This screenshot should communicate the visual identity of the application as well as the fact that multiple data sources, such as standings, featured clubs, or recent games, are being combined into a coherent landing page.

Season standings page screenshot. The season standings page demonstrates the reporting capability of the application at season level. It presents ranked clubs together with wins, losses, and other performance indicators, showing how regular-season outcomes are translated from the relational database into an organized and readable competition table. This page confirms that the archive supports comparative analysis rather than simple record storage.

Club page screenshot. The club page presents a team as a full profile within the archive. It brings together club identity, season metrics, roster context, and related performance information in one place, demonstrating how team data is connected to arenas, countries, players, and season records. This page shows the practical value of the database relationships in a user-facing format.

Player page screenshot. The player page shows how the system handles individual athlete records in a detailed and meaningful way. It combines personal identity data, basketball role information, season statistics, and related achievements into a single profile view. This page demonstrates how data from `people`, `players`, roster assignments, and statistical tables is integrated to create a complete player record.

Awards page screenshot. The awards page represents the historical recognition component of the archive. It records season honours, recipients, and related context, showing that the system supports not only fixtures and standings but also longer-term historical interpretation. This page adds narrative depth to the project by capturing season-end achievements and distinctions.

Games page screenshot. The games page displays matchup-level competition history, including clubs, scores, venues, and season references. It demonstrates that the database supports event-level detail and not only aggregated summaries. By presenting individual fixtures and outcomes, the page shows how clubs, arenas, referees, and results are linked together in the wider archive.

Playoffs page screenshot. The playoffs page demonstrates how the application interprets ranking and qualification data to represent postseason structure. It extends the archive beyond isolated season tables by showing how regular-season performance leads into seeded playoff or final-stage outcomes. This page illustrates the system's ability to represent competition logic as well as raw data.

Data Desk screenshot. The Data Desk page demonstrates the administrative dimension of the project. It provides direct access to the stored tables and supports CRUD operations through the application interface, including work with tables that use more complex keys. This page confirms that the project is operational as well as presentational, since the database can be inspected and managed without relying solely on raw SQL commands.

Advanced Queries page screenshot. The Advanced Queries page connects the SQL component of the project directly to the application interface. It presents the packaged query set together with executable previews or results, showing that the assignment queries are not separate from the system but embedded within it. This page is especially important because it demonstrates live MySQL-backed execution of the advanced SQL work required by the rubric.

Grid puzzle screenshot. The grid puzzle page highlights the custom interactive feature included in the project. It adapts the same archive data used elsewhere in the application into a row-and-column clue format where users must identify correct players from the stored dataset. This page demonstrates that the database supports not only reporting and administration, but also a more creative and engaging feature built on the same relational foundation.