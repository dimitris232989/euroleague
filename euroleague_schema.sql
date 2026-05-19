-- Derived from the uploaded Euroleague ERD and normalized into executable SQL DDL.
-- Assumptions: surrogate IDs use INTEGER, subtype tables share the people PK,
-- and duplicated ERD labels such as three_points_attempted were reduced to one column.

CREATE TABLE people (
    person_id INTEGER NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    nationality VARCHAR(100),
    photo_url VARCHAR(255),
    PRIMARY KEY (person_id)
);

CREATE TABLE countries (
    country_id INTEGER NOT NULL,
    country_name VARCHAR(100) NOT NULL,
    country_code VARCHAR(3) NOT NULL,
    continent VARCHAR(50),
    PRIMARY KEY (country_id),
    UNIQUE (country_code)
);

CREATE TABLE players (
    person_id INTEGER NOT NULL,
    position VARCHAR(30),
    height DECIMAL(5,2),
    weight DECIMAL(6,2),
    PRIMARY KEY (person_id),
    FOREIGN KEY (person_id) REFERENCES people(person_id)
);

CREATE TABLE coaches (
    person_id INTEGER NOT NULL,
    experience_years SMALLINT,
    coaching_role VARCHAR(50),
    PRIMARY KEY (person_id),
    FOREIGN KEY (person_id) REFERENCES people(person_id)
);

CREATE TABLE referees (
    person_id INTEGER NOT NULL,
    license_level VARCHAR(50),
    active_since DATE,
    federation VARCHAR(100),
    current_flag BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (person_id),
    FOREIGN KEY (person_id) REFERENCES people(person_id)
);

CREATE TABLE arenas (
    arena_id INTEGER NOT NULL,
    arena_name VARCHAR(120) NOT NULL,
    city VARCHAR(100),
    capacity INTEGER,
    website_url VARCHAR(255),
    country_id INTEGER NOT NULL,
    PRIMARY KEY (arena_id),
    FOREIGN KEY (country_id) REFERENCES countries(country_id),
    CHECK (capacity IS NULL OR capacity >= 0)
);

CREATE TABLE seasons (
    season_id INTEGER NOT NULL,
    season_label VARCHAR(30) NOT NULL,
    start_date DATE,
    end_date DATE,
    competition_name VARCHAR(100) NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (season_id),
    UNIQUE (season_label)
);

CREATE TABLE teams (
    team_id INTEGER NOT NULL,
    country_id INTEGER NOT NULL,
    home_arena_id INTEGER,
    team_name VARCHAR(120) NOT NULL,
    short_name VARCHAR(50),
    nickname VARCHAR(80),
    founded_year SMALLINT,
    primary_color VARCHAR(20),
    secondary_color VARCHAR(20),
    logo_url VARCHAR(255),
    website_url VARCHAR(255),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (team_id),
    FOREIGN KEY (country_id) REFERENCES countries(country_id),
    FOREIGN KEY (home_arena_id) REFERENCES arenas(arena_id)
);

CREATE TABLE team_seasons (
    team_id INTEGER NOT NULL,
    season_id INTEGER NOT NULL,
    coach_person_id INTEGER,
    wins SMALLINT NOT NULL DEFAULT 0,
    losses SMALLINT NOT NULL DEFAULT 0,
    points_for INTEGER NOT NULL DEFAULT 0,
    points_against INTEGER NOT NULL DEFAULT 0,
    point_diff INTEGER NOT NULL DEFAULT 0,
    final_rank SMALLINT,
    playoff_seed SMALLINT,
    qualified_playin BOOLEAN NOT NULL DEFAULT FALSE,
    qualified_playoffs BOOLEAN NOT NULL DEFAULT FALSE,
    final_four_flag BOOLEAN NOT NULL DEFAULT FALSE,
    avg_attendance INTEGER,
    notes TEXT,
    PRIMARY KEY (team_id, season_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id),
    FOREIGN KEY (season_id) REFERENCES seasons(season_id),
    FOREIGN KEY (coach_person_id) REFERENCES coaches(person_id),
    CHECK (wins >= 0),
    CHECK (losses >= 0),
    CHECK (avg_attendance IS NULL OR avg_attendance >= 0)
);

CREATE TABLE roster_assignments (
    team_id INTEGER NOT NULL,
    season_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    jersey_number SMALLINT,
    role VARCHAR(50),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (team_id, season_id, person_id),
    FOREIGN KEY (team_id, season_id) REFERENCES team_seasons(team_id, season_id),
    FOREIGN KEY (person_id) REFERENCES players(person_id),
    CHECK (jersey_number IS NULL OR jersey_number >= 0)
);

CREATE TABLE games (
    game_id INTEGER NOT NULL,
    season_id INTEGER NOT NULL,
    home_team_id INTEGER NOT NULL,
    away_team_id INTEGER NOT NULL,
    referee_id INTEGER,
    arena_id INTEGER,
    game_date DATE NOT NULL,
    tipoff_time TIME,
    status VARCHAR(30),
    home_score SMALLINT,
    away_score SMALLINT,
    attendance INTEGER,
    overtime_count SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (game_id),
    FOREIGN KEY (season_id) REFERENCES seasons(season_id),
    FOREIGN KEY (home_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (away_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (referee_id) REFERENCES referees(person_id),
    FOREIGN KEY (arena_id) REFERENCES arenas(arena_id),
    CHECK (home_team_id <> away_team_id),
    CHECK (attendance IS NULL OR attendance >= 0),
    CHECK (overtime_count >= 0)
);

CREATE TABLE team_game_stats (
    game_id INTEGER NOT NULL,
    team_id INTEGER NOT NULL,
    points SMALLINT NOT NULL DEFAULT 0,
    rebounds SMALLINT NOT NULL DEFAULT 0,
    assists SMALLINT NOT NULL DEFAULT 0,
    turnovers SMALLINT NOT NULL DEFAULT 0,
    fouls SMALLINT NOT NULL DEFAULT 0,
    field_goals_made SMALLINT NOT NULL DEFAULT 0,
    field_goals_attempted SMALLINT NOT NULL DEFAULT 0,
    three_points_made SMALLINT NOT NULL DEFAULT 0,
    three_points_attempted SMALLINT NOT NULL DEFAULT 0,
    free_throws_made SMALLINT NOT NULL DEFAULT 0,
    free_throws_attempted SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (game_id, team_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
);

CREATE TABLE player_game_stats (
    game_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    points SMALLINT NOT NULL DEFAULT 0,
    rebounds SMALLINT NOT NULL DEFAULT 0,
    assists SMALLINT NOT NULL DEFAULT 0,
    turnovers SMALLINT NOT NULL DEFAULT 0,
    fouls SMALLINT NOT NULL DEFAULT 0,
    field_goals_made SMALLINT NOT NULL DEFAULT 0,
    field_goals_attempted SMALLINT NOT NULL DEFAULT 0,
    three_points_made SMALLINT NOT NULL DEFAULT 0,
    three_points_attempted SMALLINT NOT NULL DEFAULT 0,
    free_throws_made SMALLINT NOT NULL DEFAULT 0,
    free_throws_attempted SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (game_id, person_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (person_id) REFERENCES players(person_id)
);

CREATE TABLE awards (
    award_id INTEGER NOT NULL,
    season_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    award_name VARCHAR(120) NOT NULL,
    award_type VARCHAR(50),
    notes TEXT,
    PRIMARY KEY (award_id),
    FOREIGN KEY (season_id) REFERENCES seasons(season_id),
    FOREIGN KEY (person_id) REFERENCES people(person_id)
);

CREATE TABLE grid_puzzles (
    grid_puzzle_id INTEGER NOT NULL,
    season_id INTEGER NOT NULL,
    puzzle_name VARCHAR(120) NOT NULL,
    puzzle_date DATE,
    status VARCHAR(30),
    PRIMARY KEY (grid_puzzle_id),
    FOREIGN KEY (season_id) REFERENCES seasons(season_id)
);

CREATE TABLE grid_puzzle_rows (
    grid_puzzle_id INTEGER NOT NULL,
    row_position SMALLINT NOT NULL,
    team_id INTEGER NOT NULL,
    clue_text VARCHAR(255),
    PRIMARY KEY (grid_puzzle_id, row_position),
    FOREIGN KEY (grid_puzzle_id) REFERENCES grid_puzzles(grid_puzzle_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id),
    CHECK (row_position >= 1)
);

CREATE TABLE grid_puzzle_columns (
    grid_puzzle_id INTEGER NOT NULL,
    column_position SMALLINT NOT NULL,
    stat_name VARCHAR(80) NOT NULL,
    comparison_operator VARCHAR(4) NOT NULL,
    target_value DECIMAL(10,2),
    units VARCHAR(20),
    clue_text VARCHAR(255),
    PRIMARY KEY (grid_puzzle_id, column_position),
    FOREIGN KEY (grid_puzzle_id) REFERENCES grid_puzzles(grid_puzzle_id),
    CHECK (column_position >= 1),
    CHECK (comparison_operator IN ('>', '>=', '=', '<=', '<', '<>'))
);

CREATE TABLE grid_puzzle_cells (
    grid_puzzle_id INTEGER NOT NULL,
    row_position SMALLINT NOT NULL,
    column_position SMALLINT NOT NULL,
    cell_value VARCHAR(100),
    notes TEXT,
    PRIMARY KEY (grid_puzzle_id, row_position, column_position),
    FOREIGN KEY (grid_puzzle_id, row_position)
        REFERENCES grid_puzzle_rows(grid_puzzle_id, row_position),
    FOREIGN KEY (grid_puzzle_id, column_position)
        REFERENCES grid_puzzle_columns(grid_puzzle_id, column_position)
);

CREATE TABLE grid_puzzle_answers (
    grid_puzzle_id INTEGER NOT NULL,
    row_position SMALLINT NOT NULL,
    column_position SMALLINT NOT NULL,
    person_id INTEGER NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    notes TEXT,
    PRIMARY KEY (grid_puzzle_id, row_position, column_position, person_id),
    FOREIGN KEY (grid_puzzle_id, row_position, column_position)
        REFERENCES grid_puzzle_cells(grid_puzzle_id, row_position, column_position),
    FOREIGN KEY (person_id) REFERENCES players(person_id)
);