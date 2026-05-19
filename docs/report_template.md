# Euroleague Archive Report

## 1. Cover Page

- Course Title: [Insert course title]
- Project Title: Euroleague Archive Information System
- Student Name: [Insert student name]
- Student ID: [Insert student ID]
- Instructor Name: [Insert instructor name]
- Submission Date: [Insert submission date]

## 2. System Description And Objectives

The Euroleague Archive is a PHP-based web information system designed to organize and present historical basketball data in a way that is both academically valid and easy to explore. The system focuses on Euroleague seasons, clubs, players, coaches, referees, games, awards, playoff performance, and a custom grid puzzle module. It combines a relational database backend with a browser-based interface so that users can move from raw records to meaningful basketball stories without leaving the application.

The main objective of the project was to build a normalized relational database and then use that database in two ways. The first use is analytical: the database supports advanced SQL queries, reporting, and multi-table statistics pages. The second use is operational: the same data powers a web application where users can browse the archive, inspect teams and players, review standings and awards, and manage records through a CRUD-oriented Data Desk. This dual purpose helps demonstrate both database design skills and application integration.

The basketball theme was chosen because it naturally supports rich entity relationships and useful queries. A sports archive includes people, organizations, schedules, performance statistics, rankings, awards, and historical comparisons, which makes it a strong fit for relational modeling. It also creates realistic opportunities to use joins, aggregates, distinct filtering, subqueries, and reporting pages with direct academic relevance to the rubric.

The system supports several user workflows. A general visitor can browse season standings, club profiles, player pages, awards, schedules, box scores, playoff summaries, and the puzzle screen. An evaluator or administrator can use the Data Desk to inspect tables, create records, update records, delete records, and review the packaged advanced queries. In MySQL mode, the project also supports live execution of the assignment query pack. In summary, the public-facing site and the administrative tools complement each other: one side demonstrates usability, while the other demonstrates database completeness and technical control.

## 3. Database Design

The database was designed as a normalized Euroleague archive containing 19 relations. Verification on the seeded archive confirms that all 19 user tables contain at least 10 rows, satisfying the minimum data requirement. The schema separates identity data, competition data, statistics data, and puzzle data so that each area has a clear purpose while still remaining connected through foreign keys.

Place the ERD image below in the final submitted version.

**ERD Placeholder**

[Insert ERD image here]

The identity layer includes `people`, `players`, `coaches`, `referees`, and `countries`. The `people` table stores shared identity fields, while `players`, `coaches`, and `referees` extend that identity through one-to-one subtype relationships. This design avoids repeating names and demographic fields across multiple role tables. `countries` provides shared national metadata used by teams, arenas, and people.

The competition layer includes `seasons`, `teams`, `arenas`, `team_seasons`, `games`, and `awards`. `teams` stores club identity and branding, `arenas` stores venue information, and `seasons` stores competition windows. `team_seasons` is a key bridge table because it connects clubs to a specific season and records season-level outcomes such as wins, losses, rank, playoff seed, and attendance. `games` stores scheduled and completed matchups, while `awards` stores season awards linked to players and seasons.

The statistics layer includes `roster_assignments`, `team_game_stats`, and `player_game_stats`. `roster_assignments` resolves the many-to-many relationship between players and team seasons, because one player can appear for different clubs across different seasons and one team season contains many players. `team_game_stats` and `player_game_stats` extend games with measurable performance data that supports both reporting and advanced SQL analysis.

The puzzle layer includes `grid_puzzles`, `grid_puzzle_rows`, `grid_puzzle_columns`, `grid_puzzle_cells`, and `grid_puzzle_answers`. These tables were added to show that the database can support a custom application feature outside simple reporting. A puzzle belongs to a season, contains row and column clue definitions, stores its grid cells, and maps acceptable answers to each cell.

Several important one-to-many relationships appear throughout the model. One country can have many teams and arenas. One season can have many team-season records and many games. One team can appear in many `team_seasons` records across time. One game can have many player-stat rows and team-stat rows. One puzzle can have many rows, columns, cells, and answers.

The schema also demonstrates many-to-many modeling. Players and team seasons form a many-to-many relationship resolved by `roster_assignments`. Teams and seasons form another many-to-many relationship resolved by `team_seasons`. Puzzle answers effectively create a many-to-many relationship between people and puzzle cells because multiple valid players may satisfy a given clue intersection.

Composite keys were used where they made the model more faithful to the underlying data structure. For example, `team_seasons` uses a composite primary key based on team and season, while `roster_assignments` uses team, season, and person to represent a specific membership entry. The puzzle row, column, cell, and answer tables also use composite identifiers that fit their grid-based structure. This helps preserve uniqueness at the relational level instead of relying on unnecessary surrogate keys everywhere.

## 4. SQL Implementation

The SQL implementation is split into a clean development flow and a submission flow. The schema definition is maintained in `euroleague_schema.sql`, which acts as the foundational DDL source. That file defines the tables, primary keys, foreign keys, constraints, and structural assumptions used by the system. The application can also rebuild and reseed the archive so that the environment remains reproducible.

For assignment delivery, the main import artifact is `exports/euroleague_mysql_import.sql`. This file contains the MySQL-compatible structure and data needed to import the archive into phpMyAdmin or MySQL directly. After importing that file into phpMyAdmin, the final SQL file for submission should be exported from phpMyAdmin with both structure and data included. That exported file is the actual submission artifact requested by the assignment wording, while `exports/euroleague_mysql_import.sql` is the source used before the final export step.

At runtime, the application supports MySQL through PHP `mysqli`, which is the correct mode for rubric-aligned demonstrations and live advanced-query execution. The project also includes a local SQLite fallback for preview mode when MySQL is not available. This makes the application easier to open for quick review, but the MySQL workflow remains the authoritative path for screenshots, phpMyAdmin evidence, and advanced-query submission requirements.

The project includes a verification helper that confirms the main technical requirements. On the seeded archive, the verification output confirms the following facts: 19 tables are present, all tables have at least 10 rows, 15 queries are documented in the Markdown guide, 15 queries are packaged in the SQL file, and the required SQL features are present. This verification step helps reduce submission risk before the final phpMyAdmin export is created.

Suggested evidence for the final submitted report:

- [Insert screenshot of phpMyAdmin immediately after importing the database]
- [Insert screenshot of the table list in phpMyAdmin]
- [Insert screenshot of one populated table in phpMyAdmin]

## 5. Sample Data

The sample data was designed to make the archive feel like a believable sports system instead of a minimal classroom dataset. The seeded archive spans multiple seasons and includes clubs, people, awards, games, standings, playoff indicators, and puzzle content. This creates enough historical depth for analytical queries while still being manageable inside a coursework project.

Clubs were generated with linked countries, arenas, branding information, and season-level records. Team seasons were populated with wins, losses, point differential, attendance, ranking, and playoff flags so that standings and comparison pages would have meaningful content. Players were attached to shared identity records and then assigned to clubs through `roster_assignments`, which makes it possible to display team rosters, player histories, and game-stat leaderboards.

Game history was also seeded in a way that supports realistic reporting. Each season contains matchups with scores, attendance, and related team or player statistics. This enables box scores, recent game feeds, top performance views, head-to-head comparisons, and season scoring analysis. The existence of both team-level and player-level game data also makes the advanced SQL section much stronger, because it supports aggregate queries with real business meaning.

Awards were seeded across seasons to create variation in achievements and historical recognition. This allows the archive to show MVP winners, repeat award winners, and season-based award summaries. Playoff-related fields such as seeds, qualification flags, and final-four indicators were also included so the application can render playoff summaries and long-term competitiveness analysis.

The project also seeds puzzle boards and answer sets. This adds an interactive component without breaking the relational design. The puzzle data is tied back to real players and seasons, which means it is not disconnected demo content; it reuses the same archive model in a different way.

The verification helper confirms that every table in the seeded archive contains at least 10 rows. This matters because it shows the database was populated deliberately for testing, demonstration, and reporting rather than filled with only token records.

Suggested screenshots for this section:

- [Insert screenshot of the `seasons` table]
- [Insert screenshot of the `teams` table]
- [Insert screenshot of the `games` table]
- [Insert screenshot of the `awards` table]

## 6. Advanced SQL Queries

This project includes a packaged set of 15 advanced SQL queries in `docs/advanced_queries.sql`, with matching explanations in `docs/advanced_queries.md`. Together they demonstrate joins, `LEFT JOIN`, `RIGHT JOIN`, `DISTINCT`, `GROUP BY`, `HAVING`, aggregate functions, and subqueries. These queries are not isolated exercises; they are aligned with the same archive data model used by the application.

### Query 1. Season standings board with club and venue context

```sql
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
```

Explanation: This query produces a standings table with season, rank, club, country, arena, record, win percentage, and point differential. It is a strong example of a multi-table reporting query because it combines competition, geography, and venue context in one result.

SQL features used: `INNER JOIN`, `LEFT JOIN`, arithmetic calculation, ordering.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 1]

### Query 2. Countries averaging at least 10,000 fans per game

```sql
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
```

Explanation: This query measures which countries host the strongest attendance environments by combining country, club, and season data.

SQL features used: `LEFT JOIN`, `COUNT(DISTINCT ...)`, `AVG`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 2]

### Query 3. Players averaging 15+ points with minimum volume

```sql
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
```

Explanation: This query identifies high-level scorers while filtering out small sample sizes.

SQL features used: multiple `INNER JOIN`s, `AVG`, `COUNT`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 3]

### Query 4. Seasons and MVP winners using RIGHT JOIN

```sql
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
```

Explanation: This query satisfies the `RIGHT JOIN` requirement by listing every season and showing the MVP winner when one exists.

SQL features used: `RIGHT JOIN`, `LEFT JOIN`, `COALESCE`, ordering.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 4]

### Query 5. Distinct countries represented in the Final Four

```sql
SELECT DISTINCT
	s.season_label,
	c.country_name
FROM team_seasons ts
INNER JOIN seasons s ON s.season_id = ts.season_id
INNER JOIN teams t ON t.team_id = ts.team_id
INNER JOIN countries c ON c.country_id = t.country_id
WHERE ts.final_four_flag = 1
ORDER BY s.season_id DESC, c.country_name ASC;
```

Explanation: This query uses `DISTINCT` to show which countries were represented in the Final Four without duplicate rows.

SQL features used: `DISTINCT`, `INNER JOIN`, filtering, ordering.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 5]

### Query 6. Arenas that hosted at least 20 games

```sql
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
```

Explanation: This query evaluates venue usage and crowd size, keeping arenas in scope even if their game history is incomplete.

SQL features used: `LEFT JOIN`, `COUNT`, `AVG`, `MAX`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 6]

### Query 7. Head-to-head record by club pairing

```sql
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
```

Explanation: This query compares rival clubs within a season and summarizes wins across repeated matchups.

SQL features used: multiple `INNER JOIN`s, `SUM(CASE ...)`, `COUNT`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 7]

### Query 8. Award totals by player and club

```sql
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
```

Explanation: This query summarizes repeat winners and links awards to club context.

SQL features used: `INNER JOIN`, `LEFT JOIN`, `COUNT`, `MIN`, `MAX`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 8]

### Query 9. Top scorer in each season using a subquery

```sql
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
```

Explanation: This query uses nested subqueries to return the top scorer in each season.

SQL features used: subqueries, `AVG`, `MAX`, `INNER JOIN`, `GROUP BY`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 9]

### Query 10. Players above the season scoring average using a subquery

```sql
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
```

Explanation: This query compares a player's scoring output to the average scoring environment of that same season.

SQL features used: subqueries, `AVG`, `INNER JOIN`, filtering against computed values.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 10]

### Query 11. Clubs with repeated playoff qualification

```sql
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
```

Explanation: This query identifies consistently strong clubs by counting repeated playoff appearances.

SQL features used: `INNER JOIN`, `COUNT`, `AVG`, `MAX`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 11]

### Query 12. Coach records by club

```sql
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
```

Explanation: This query aggregates coach performance by club and shows long-term effectiveness.

SQL features used: `INNER JOIN`, `SUM`, `COUNT`, calculated win percentage, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 12]

### Query 13. Three-point specialists with minimum attempts

```sql
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
```

Explanation: This query finds efficient long-range shooters while enforcing a meaningful attempt threshold.

SQL features used: multiple `INNER JOIN`s, `SUM`, calculated percentage, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 13]

### Query 14. Countries with multiple clubs and strong average records

```sql
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
```

Explanation: This query compares country-level basketball strength using distinct club counts and average winning records.

SQL features used: `COUNT(DISTINCT ...)`, `AVG`, `INNER JOIN`, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 14]

### Query 15. Puzzle answer density by season and puzzle

```sql
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
```

Explanation: This final query proves that the same archive schema can support a custom game feature while still using serious relational analysis.

SQL features used: `INNER JOIN`, `LEFT JOIN`, `COUNT`, calculated ratio, `GROUP BY`, `HAVING`.

Screenshot placeholder: [Insert phpMyAdmin result screenshot for Query 15]

## 7. Web Application

The web application was built to show that the database is not only correctly structured but also useful in a real interface. The homepage introduces the archive with season context, highlighted clubs, featured games, standings, and performance summaries. This immediately demonstrates that the project can transform database tables into user-facing insights.

The season archive pages provide standings, leaderboard content, and recent game context. Team pages present a club profile, roster, season snapshot, continuity information, and recent results. Player pages show profile details, season summaries, awards, and game logs. Awards pages summarize honors across seasons, while the games and box-score pages expose match results and detailed performance statistics.

The playoff pages extend the archive from regular-season standings into postseason storytelling. They show seeding and bracket-oriented summaries built from the season data. This helps connect ranking data to end-of-season outcomes.

The Data Desk demonstrates operational control over the schema. It includes CRUD tools that work across all tables, including tables with composite keys. This is significant because it shows that the project is not limited to read-only reporting. Records can be created, edited, and removed while preserving the database structure and validation flow.

The Advanced Queries page integrates the packaged SQL query set directly into the application. In MySQL mode, it can execute the rubric queries and preview the results live. In SQLite preview mode, the page still documents the query pack and remains useful for report preparation. This page directly connects the database deliverable to the web interface.

The grid puzzle adds an interactive feature built on top of the same database. Users can attempt puzzle answers, submit the grid, and receive a score. This module shows that the project can reuse relational data creatively instead of only displaying lists and tables.

Suggested screenshots for this section:

- [Insert homepage screenshot]
- [Insert season standings page screenshot]
- [Insert club page screenshot]
- [Insert player page screenshot]
- [Insert awards page screenshot]
- [Insert games page screenshot]
- [Insert playoffs page screenshot]
- [Insert Data Desk screenshot]
- [Insert Advanced Queries page screenshot]
- [Insert grid puzzle screenshot]

## 8. Conclusion

This project successfully delivers a complete Euroleague archive information system built on a relational database and a PHP web application. The final result includes 19 normalized relations, seeded multi-season data, CRUD support, statistics pages, awards tracking, playoff context, a custom puzzle feature, and a packaged set of 15 advanced SQL queries. The database design supports both academic SQL analysis and practical web usage.

The project meets the rubric requirements in several clear ways. It uses a structured relational model with foreign keys and composite keys where appropriate. It includes sufficient sample data, with every table containing at least 10 rows in the seeded archive. It provides 15 advanced SQL queries covering joins, `DISTINCT`, `HAVING`, aggregates, `RIGHT JOIN`, and subqueries. It also integrates database functionality into a working website rather than leaving the project at the script-only stage.

If this project were extended in the future, several improvements would be worthwhile. A next version could add authentication for the Data Desk, richer visual analytics, more detailed playoff bracket rendering, file uploads for media assets, and a stronger deployment configuration for production hosting. Even without those enhancements, the current version already demonstrates a complete and defensible coursework submission.

## Final Submission Pack

Before submitting, make sure the final package contains the following items:

- This completed report with the placeholders replaced by real cover-page details and screenshots.
- A phpMyAdmin-exported SQL file containing both structure and data.
- The project source code archive, if your instructor requested it.
- One readable screenshot for each of the 15 advanced SQL query results.
- Screenshots of the key web application pages listed above.
