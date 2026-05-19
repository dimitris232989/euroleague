<?php
declare(strict_types=1);

final class Seeder
{
    private int $currentSeasonId = 10;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(): void
    {
        $countries = [
            ['country_id' => 1, 'country_name' => 'Spain', 'country_code' => 'ESP', 'continent' => 'Europe'],
            ['country_id' => 2, 'country_name' => 'Greece', 'country_code' => 'GRC', 'continent' => 'Europe'],
            ['country_id' => 3, 'country_name' => 'Turkey', 'country_code' => 'TUR', 'continent' => 'Europe'],
            ['country_id' => 4, 'country_name' => 'Italy', 'country_code' => 'ITA', 'continent' => 'Europe'],
            ['country_id' => 5, 'country_name' => 'France', 'country_code' => 'FRA', 'continent' => 'Europe'],
            ['country_id' => 6, 'country_name' => 'Germany', 'country_code' => 'DEU', 'continent' => 'Europe'],
            ['country_id' => 7, 'country_name' => 'Serbia', 'country_code' => 'SRB', 'continent' => 'Europe'],
            ['country_id' => 8, 'country_name' => 'Lithuania', 'country_code' => 'LTU', 'continent' => 'Europe'],
            ['country_id' => 9, 'country_name' => 'Israel', 'country_code' => 'ISR', 'continent' => 'Europe'],
            ['country_id' => 10, 'country_name' => 'Monaco', 'country_code' => 'MCO', 'continent' => 'Europe'],
        ];

        $countryNames = [];
        foreach ($countries as $country) {
            $countryNames[(int) $country['country_id']] = $country['country_name'];
        }

        $arenas = [
            ['arena_id' => 1, 'arena_name' => 'Palau Blaugrana', 'city' => 'Barcelona', 'capacity' => 7585, 'website_url' => 'https://www.fcbarcelona.com', 'country_id' => 1],
            ['arena_id' => 2, 'arena_name' => 'Peace and Friendship Stadium', 'city' => 'Piraeus', 'capacity' => 11600, 'website_url' => 'https://www.olympiacosbc.gr', 'country_id' => 2],
            ['arena_id' => 3, 'arena_name' => 'Ulker Sports and Event Hall', 'city' => 'Istanbul', 'capacity' => 13500, 'website_url' => 'https://www.fenerbahce.org', 'country_id' => 3],
            ['arena_id' => 4, 'arena_name' => 'Mediolanum Forum', 'city' => 'Milan', 'capacity' => 12700, 'website_url' => 'https://www.olimpiamilano.com', 'country_id' => 4],
            ['arena_id' => 5, 'arena_name' => 'Adidas Arena', 'city' => 'Paris', 'capacity' => 8000, 'website_url' => 'https://www.parisbasketball.com', 'country_id' => 5],
            ['arena_id' => 6, 'arena_name' => 'Uber Arena', 'city' => 'Berlin', 'capacity' => 14500, 'website_url' => 'https://www.albaberlin.de', 'country_id' => 6],
            ['arena_id' => 7, 'arena_name' => 'Stark Arena', 'city' => 'Belgrade', 'capacity' => 18000, 'website_url' => 'https://kkcrvenazvezda.rs', 'country_id' => 7],
            ['arena_id' => 8, 'arena_name' => 'Zalgirio Arena', 'city' => 'Kaunas', 'capacity' => 15400, 'website_url' => 'https://zalgiris.lt', 'country_id' => 8],
            ['arena_id' => 9, 'arena_name' => 'Menora Mivtachim Arena', 'city' => 'Tel Aviv', 'capacity' => 10500, 'website_url' => 'https://www.maccabi.co.il', 'country_id' => 9],
            ['arena_id' => 10, 'arena_name' => 'Salle Gaston Medecin', 'city' => 'Monaco', 'capacity' => 5000, 'website_url' => 'https://asmonaco.basketball', 'country_id' => 10],
        ];

        $teams = [
            ['team_id' => 1, 'country_id' => 1, 'home_arena_id' => 1, 'team_name' => 'FC Barcelona', 'short_name' => 'BAR', 'nickname' => 'Blaugrana', 'founded_year' => 1926, 'primary_color' => '#A50044', 'secondary_color' => '#004D98', 'logo_url' => 'club-logos/bar.svg', 'website_url' => 'https://www.fcbarcelona.com', 'is_active' => 1],
            ['team_id' => 2, 'country_id' => 2, 'home_arena_id' => 2, 'team_name' => 'Olympiacos Piraeus', 'short_name' => 'OLY', 'nickname' => 'Reds', 'founded_year' => 1931, 'primary_color' => '#D71920', 'secondary_color' => '#FFFFFF', 'logo_url' => 'club-logos/oly.svg', 'website_url' => 'https://www.olympiacosbc.gr', 'is_active' => 1],
            ['team_id' => 3, 'country_id' => 3, 'home_arena_id' => 3, 'team_name' => 'Fenerbahce Beko Istanbul', 'short_name' => 'FEN', 'nickname' => 'Yellow Canaries', 'founded_year' => 1913, 'primary_color' => '#0C2340', 'secondary_color' => '#F2C300', 'logo_url' => 'club-logos/fen.svg', 'website_url' => 'https://www.fenerbahce.org', 'is_active' => 1],
            ['team_id' => 4, 'country_id' => 4, 'home_arena_id' => 4, 'team_name' => 'EA7 Emporio Armani Milano', 'short_name' => 'MIL', 'nickname' => 'Red Shoes', 'founded_year' => 1936, 'primary_color' => '#C8102E', 'secondary_color' => '#111111', 'logo_url' => 'club-logos/mil.svg', 'website_url' => 'https://www.olimpiamilano.com', 'is_active' => 1],
            ['team_id' => 5, 'country_id' => 5, 'home_arena_id' => 5, 'team_name' => 'Paris Basketball', 'short_name' => 'PAR', 'nickname' => 'Parisians', 'founded_year' => 2018, 'primary_color' => '#0F172A', 'secondary_color' => '#EAB308', 'logo_url' => 'club-logos/par.svg', 'website_url' => 'https://www.parisbasketball.com', 'is_active' => 1],
            ['team_id' => 6, 'country_id' => 6, 'home_arena_id' => 6, 'team_name' => 'ALBA Berlin', 'short_name' => 'ALB', 'nickname' => 'Albatrosse', 'founded_year' => 1991, 'primary_color' => '#FFCC00', 'secondary_color' => '#005BBB', 'logo_url' => 'club-logos/alb.svg', 'website_url' => 'https://www.albaberlin.de', 'is_active' => 1],
            ['team_id' => 7, 'country_id' => 7, 'home_arena_id' => 7, 'team_name' => 'Crvena Zvezda Meridianbet Belgrade', 'short_name' => 'CZV', 'nickname' => 'Red-Whites', 'founded_year' => 1945, 'primary_color' => '#E10600', 'secondary_color' => '#FFFFFF', 'logo_url' => 'club-logos/czv.svg', 'website_url' => 'https://kkcrvenazvezda.rs', 'is_active' => 1],
            ['team_id' => 8, 'country_id' => 8, 'home_arena_id' => 8, 'team_name' => 'Zalgiris Kaunas', 'short_name' => 'ZAL', 'nickname' => 'Greens', 'founded_year' => 1944, 'primary_color' => '#006B3F', 'secondary_color' => '#FFFFFF', 'logo_url' => 'club-logos/zal.svg', 'website_url' => 'https://zalgiris.lt', 'is_active' => 1],
            ['team_id' => 9, 'country_id' => 9, 'home_arena_id' => 9, 'team_name' => 'Maccabi Playtika Tel Aviv', 'short_name' => 'MAC', 'nickname' => 'Yellows', 'founded_year' => 1932, 'primary_color' => '#0057B8', 'secondary_color' => '#FFD100', 'logo_url' => 'club-logos/mac.svg', 'website_url' => 'https://www.maccabi.co.il', 'is_active' => 1],
            ['team_id' => 10, 'country_id' => 5, 'home_arena_id' => 10, 'team_name' => 'AS Monaco', 'short_name' => 'ASM', 'nickname' => 'Roca Team', 'founded_year' => 1924, 'primary_color' => '#B61E2E', 'secondary_color' => '#FFFFFF', 'logo_url' => 'club-logos/asm.svg', 'website_url' => 'https://asmonaco.basketball', 'is_active' => 1],
        ];

        $teamBlueprints = [
            1 => ['overall' => 91, 'offense' => 8, 'defense' => 5, 'pace' => 1, 'attendance' => 0.30],
            2 => ['overall' => 95, 'offense' => 7, 'defense' => 8, 'pace' => 0, 'attendance' => 0.37],
            3 => ['overall' => 92, 'offense' => 8, 'defense' => 6, 'pace' => 1, 'attendance' => 0.33],
            4 => ['overall' => 86, 'offense' => 4, 'defense' => 4, 'pace' => -1, 'attendance' => 0.24],
            5 => ['overall' => 84, 'offense' => 6, 'defense' => 1, 'pace' => 3, 'attendance' => 0.18],
            6 => ['overall' => 78, 'offense' => 2, 'defense' => -1, 'pace' => 2, 'attendance' => 0.15],
            7 => ['overall' => 83, 'offense' => 2, 'defense' => 4, 'pace' => -1, 'attendance' => 0.32],
            8 => ['overall' => 82, 'offense' => 1, 'defense' => 4, 'pace' => -2, 'attendance' => 0.35],
            9 => ['overall' => 89, 'offense' => 6, 'defense' => 2, 'pace' => 2, 'attendance' => 0.31],
            10 => ['overall' => 90, 'offense' => 7, 'defense' => 3, 'pace' => 1, 'attendance' => 0.27],
        ];

        $teamsById = [];
        foreach ($teams as $team) {
            $teamsById[(int) $team['team_id']] = $team;
        }

        $arenasById = [];
        foreach ($arenas as $arena) {
            $arenasById[(int) $arena['arena_id']] = $arena;
        }

        $seasons = [];
        for ($i = 1; $i <= 10; $i++) {
            $startYear = 2015 + $i;
            $endYear = $startYear + 1;
            $seasons[] = [
                'season_id' => $i,
                'season_label' => $startYear . '-' . substr((string) $endYear, -2),
                'start_date' => sprintf('%d-10-01', $startYear),
                'end_date' => sprintf('%d-05-31', $endYear),
                'competition_name' => 'EuroLeague',
                'is_completed' => $i < $this->currentSeasonId ? 1 : 0,
            ];
        }

        $playerBlueprints = $this->playerBlueprints();
        $coachProfiles = $this->coachProfiles();
        $refFirstNames = ['Carlos', 'Robert', 'Antonio', 'Sreten', 'Milan', 'Damir', 'Mehdi', 'Uros', 'Ales', 'Piotr'];
        $refLastNames = ['Peruga', 'Lottermoser', 'Conde', 'Radovic', 'Nedovic', 'Javor', 'Difallah', 'Nikolic', 'Pukl', 'Pastusiak'];
        $roleDefinitions = $this->playerRoleDefinitions();

        $people = [];
        $players = [];
        $rosterAssignments = [];
        $rosterByTeam = [];

        foreach ($teams as $teamIndex => $team) {
            $teamId = (int) $team['team_id'];
            $basePersonId = ($teamIndex * count($roleDefinitions)) + 1;
            $teamPlayers = $playerBlueprints[$teamId] ?? [];

            foreach ($roleDefinitions as $offset => $profile) {
                $personId = $basePersonId + $offset;
                $playerIdentity = $teamPlayers[$offset] ?? [
                    'first_name' => 'Euroleague',
                    'last_name' => 'Player ' . $personId,
                    'nationality' => $countryNames[(int) $team['country_id']] ?? 'Europe',
                ];
                $firstName = $playerIdentity['first_name'];
                $lastName = $playerIdentity['last_name'];

                $people[] = [
                    'person_id' => $personId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => sprintf('%d-%02d-%02d', 1988 + (($personId + $offset) % 14), (($offset + 2) % 12) + 1, 8 + (($personId + $teamId) % 18)),
                    'nationality' => $playerIdentity['nationality'],
                    'photo_url' => $playerIdentity['photo_url'] ?? $this->playerPortraitForRole($profile['role']),
                ];

                $players[] = [
                    'person_id' => $personId,
                    'position' => $profile['position'],
                    'height' => $profile['height'] + ($teamId % 3),
                    'weight' => $profile['weight'] + (($teamId + $offset) % 5),
                ];

                $rosterByTeam[$teamId][] = [
                    'person_id' => $personId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $profile['role'],
                    'position' => $profile['position'],
                    'usage' => $profile['usage'],
                    'skill' => $profile['skill'],
                    'slot_index' => $offset,
                ];
            }
        }

        $seasonRosters = $this->buildSeasonRosters(array_map(static fn (array $team): int => (int) $team['team_id'], $teams), $rosterByTeam);
        foreach ($seasonRosters as $seasonId => $seasonRoster) {
            $seasonStartYear = 2015 + $seasonId;
            foreach ($seasonRoster as $teamId => $playersForTeam) {
                foreach ($playersForTeam as $slotIndex => $playerMeta) {
                    $rosterAssignments[] = [
                        'team_id' => $teamId,
                        'season_id' => $seasonId,
                        'person_id' => $playerMeta['person_id'],
                        'jersey_number' => $this->jerseyNumberForSeason($teamId, $seasonId, $slotIndex, (int) $playerMeta['person_id']),
                        'role' => $playerMeta['role'],
                        'start_date' => sprintf('%d-09-01', $seasonStartYear),
                        'end_date' => $seasonId === $this->currentSeasonId ? null : sprintf('%d-05-31', $seasonStartYear + 1),
                        'is_active' => $seasonId === $this->currentSeasonId ? 1 : 0,
                    ];
                }
            }
        }

        $coaches = [];
        for ($i = 1; $i <= 10; $i++) {
            $personId = 100 + $i;
            $coachProfile = $coachProfiles[$i] ?? ['first_name' => 'Head', 'last_name' => 'Coach', 'nationality' => $countries[$i - 1]['country_name']];
            $people[] = [
                'person_id' => $personId,
                'first_name' => $coachProfile['first_name'],
                'last_name' => $coachProfile['last_name'],
                'date_of_birth' => sprintf('%d-06-%02d', 1960 + $i, 4 + $i),
                'nationality' => $coachProfile['nationality'],
                'photo_url' => 'portraits/coach.svg',
            ];
            $coaches[] = [
                'person_id' => $personId,
                'experience_years' => 10 + $i,
                'coaching_role' => 'Head Coach',
            ];
        }

        $referees = [];
        for ($i = 1; $i <= 10; $i++) {
            $personId = 200 + $i;
            $people[] = [
                'person_id' => $personId,
                'first_name' => $refFirstNames[$i - 1],
                'last_name' => $refLastNames[$i - 1],
                'date_of_birth' => sprintf('%d-03-%02d', 1971 + $i, 3 + $i),
                'nationality' => $countries[($i + 1) % count($countries)]['country_name'],
                'photo_url' => 'portraits/official.svg',
            ];
            $referees[] = [
                'person_id' => $personId,
                'license_level' => 'Elite ' . chr(64 + $i),
                'active_since' => sprintf('%d-09-01', 2004 + $i),
                'federation' => 'Euroleague Officials Board',
                'current_flag' => 1,
            ];
        }

        $games = [];
        $teamGameStats = [];
        $playerGameStats = [];
        $teamSeasons = [];
        $gameId = 1;

        $seasonSchedule = $this->buildSeasonSchedule(array_map(static fn (array $team): int => (int) $team['team_id'], $teams));

        for ($seasonId = 1; $seasonId <= 10; $seasonId++) {
            $seasonContexts = [];
            $teamSeasonTotals = [];
            $attendanceTotals = [];

            foreach ($teams as $team) {
                $teamId = (int) $team['team_id'];
                $seasonContexts[$teamId] = $this->buildSeasonTeamContext($seasonId, $teamId, $teamBlueprints[$teamId]);
                $teamSeasonTotals[$teamId] = ['wins' => 0, 'losses' => 0, 'points_for' => 0, 'points_against' => 0];
                $attendanceTotals[$teamId] = ['sum' => 0, 'count' => 0];
            }

            $roundLimit = $seasonId === $this->currentSeasonId ? 23 : 27;

            foreach ($seasonSchedule as $fixture) {
                if ($fixture['round'] > $roundLimit) {
                    continue;
                }

                $homeTeamId = $fixture['home_team_id'];
                $awayTeamId = $fixture['away_team_id'];
                $homeContext = $seasonContexts[$homeTeamId];
                $awayContext = $seasonContexts[$awayTeamId];
                $arenaId = (int) $teamsById[$homeTeamId]['home_arena_id'];
                $arena = $arenasById[$arenaId];
                $gameDate = date('Y-m-d', strtotime(sprintf('%d-10-04 +%d days', 2015 + $seasonId, (($fixture['round'] - 1) * 7) + $fixture['slot'])));
                $attendanceRate = min(
                    0.99,
                    max(
                        0.54,
                        0.51
                        + $homeContext['attendance']
                        + (($awayContext['overall'] - 80) * 0.004)
                        + ($fixture['round'] >= 12 ? 0.03 : 0.0)
                        + ($this->signedNoise(7, $seasonId, $fixture['round'], $homeTeamId, $awayTeamId, 91) / 100)
                    )
                );
                $attendance = (int) round(((int) $arena['capacity']) * $attendanceRate);

                $expectedMargin = (int) round(
                    (($homeContext['overall'] - $awayContext['overall']) * 0.48)
                    + (($homeContext['offense'] - $awayContext['defense']) * 0.55)
                    + 3.2
                    + ($this->signedNoise(6, $seasonId, $fixture['round'], $homeTeamId, $awayTeamId, 19) / 2)
                );

                $seasonRoster = $seasonRosters[$seasonId] ?? $rosterByTeam;
                $homeStats = $this->buildTeamStats($seasonId, $fixture['round'], $homeTeamId, $homeContext, $awayContext, true, $seasonRoster);
                $awayStats = $this->buildTeamStats($seasonId, $fixture['round'], $awayTeamId, $awayContext, $homeContext, false, $seasonRoster);

                $actualMargin = $homeStats['team']['points'] - $awayStats['team']['points'];
                if ($expectedMargin >= 0 && $actualMargin <= 0) {
                    $homeStats = $this->applyScoreDelta($homeStats, abs($actualMargin) + max(1, min(7, intdiv(abs($expectedMargin), 2) + 1)));
                }
                if ($expectedMargin < 0 && $actualMargin >= 0) {
                    $awayStats = $this->applyScoreDelta($awayStats, abs($actualMargin) + max(1, min(7, intdiv(abs($expectedMargin), 2) + 1)));
                }

                $marginAfterBias = $homeStats['team']['points'] - $awayStats['team']['points'];
                $overtime = abs($marginAfterBias) <= 3 && (($fixture['round'] + $seasonId + $homeTeamId + $awayTeamId) % 6 === 0) ? 1 : 0;
                if ($overtime === 1) {
                    $homeStats = $this->applyScoreDelta($homeStats, 3 + ($homeTeamId % 2));
                    $awayStats = $this->applyScoreDelta($awayStats, 3 + ($awayTeamId % 2));
                }

                if ($homeStats['team']['points'] === $awayStats['team']['points']) {
                    if ($expectedMargin >= 0) {
                        $homeStats = $this->applyScoreDelta($homeStats, 1);
                    } else {
                        $awayStats = $this->applyScoreDelta($awayStats, 1);
                    }
                }

                $games[] = [
                    'game_id' => $gameId,
                    'season_id' => $seasonId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'referee_id' => 201 + (($fixture['round'] + $seasonId + $homeTeamId + $awayTeamId) % 10),
                    'arena_id' => $arenaId,
                    'game_date' => $gameDate,
                    'tipoff_time' => sprintf('%02d:00:00', 18 + (($fixture['slot'] + $seasonId) % 3)),
                    'status' => 'Final',
                    'home_score' => $homeStats['team']['points'],
                    'away_score' => $awayStats['team']['points'],
                    'attendance' => $attendance,
                    'overtime_count' => $overtime,
                ];

                $teamGameStats[] = array_merge(['game_id' => $gameId, 'team_id' => $homeTeamId], $homeStats['team']);
                $teamGameStats[] = array_merge(['game_id' => $gameId, 'team_id' => $awayTeamId], $awayStats['team']);

                foreach ($homeStats['players'] as $playerRow) {
                    $playerGameStats[] = array_merge(['game_id' => $gameId], $playerRow);
                }
                foreach ($awayStats['players'] as $playerRow) {
                    $playerGameStats[] = array_merge(['game_id' => $gameId], $playerRow);
                }

                $teamSeasonTotals[$homeTeamId]['points_for'] += $homeStats['team']['points'];
                $teamSeasonTotals[$homeTeamId]['points_against'] += $awayStats['team']['points'];
                $teamSeasonTotals[$awayTeamId]['points_for'] += $awayStats['team']['points'];
                $teamSeasonTotals[$awayTeamId]['points_against'] += $homeStats['team']['points'];

                if ($homeStats['team']['points'] > $awayStats['team']['points']) {
                    $teamSeasonTotals[$homeTeamId]['wins']++;
                    $teamSeasonTotals[$awayTeamId]['losses']++;
                } else {
                    $teamSeasonTotals[$awayTeamId]['wins']++;
                    $teamSeasonTotals[$homeTeamId]['losses']++;
                }

                $attendanceTotals[$homeTeamId]['sum'] += $attendance;
                $attendanceTotals[$homeTeamId]['count']++;
                $gameId++;
            }

            $rankings = $teamSeasonTotals;
            uasort(
                $rankings,
                static fn (array $left, array $right): int => [$right['wins'], $right['points_for'] - $right['points_against'], $right['points_for']] <=> [$left['wins'], $left['points_for'] - $left['points_against'], $left['points_for']]
            );

            $rankByTeam = [];
            $rank = 1;
            foreach (array_keys($rankings) as $teamId) {
                $rankByTeam[$teamId] = $rank;
                $rank++;
            }

            foreach ($teams as $team) {
                $teamId = (int) $team['team_id'];
                $wins = $teamSeasonTotals[$teamId]['wins'];
                $losses = $teamSeasonTotals[$teamId]['losses'];
                $pointDiff = $teamSeasonTotals[$teamId]['points_for'] - $teamSeasonTotals[$teamId]['points_against'];
                $avgAttendance = $attendanceTotals[$teamId]['count'] > 0
                    ? (int) round($attendanceTotals[$teamId]['sum'] / $attendanceTotals[$teamId]['count'])
                    : null;

                $teamSeasons[] = [
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'coach_person_id' => 100 + $teamId,
                    'wins' => $wins,
                    'losses' => $losses,
                    'points_for' => $teamSeasonTotals[$teamId]['points_for'],
                    'points_against' => $teamSeasonTotals[$teamId]['points_against'],
                    'point_diff' => $pointDiff,
                    'final_rank' => $rankByTeam[$teamId],
                    'playoff_seed' => $rankByTeam[$teamId] <= 8 ? $rankByTeam[$teamId] : null,
                    'qualified_playin' => $rankByTeam[$teamId] >= 7 && $rankByTeam[$teamId] <= 10 ? 1 : 0,
                    'qualified_playoffs' => $rankByTeam[$teamId] <= 6 ? 1 : 0,
                    'final_four_flag' => $rankByTeam[$teamId] <= 4 ? 1 : 0,
                    'avg_attendance' => $avgAttendance,
                    'notes' => $this->buildSeasonNote($team['team_name'], $rankByTeam[$teamId], $wins, $losses, $pointDiff),
                ];
            }
        }

        $awards = $this->buildAwards($games, $playerGameStats, $teamSeasons, $people, $players, $rosterAssignments);

        [
            $gridPuzzles,
            $gridRows,
            $gridColumns,
            $gridCells,
            $gridAnswers,
        ] = $this->buildGridData($teamsById, $games, $playerGameStats, $rosterAssignments);

        $this->pdo->beginTransaction();

        try {
            $this->insertRows('countries', $countries);
            $this->insertRows('arenas', $arenas);
            $this->insertRows('seasons', $seasons);
            $this->insertRows('teams', $teams);
            $this->insertRows('people', $people);
            $this->insertRows('players', $players);
            $this->insertRows('coaches', $coaches);
            $this->insertRows('referees', $referees);
            $this->insertRows('team_seasons', $teamSeasons);
            $this->insertRows('roster_assignments', $rosterAssignments);
            $this->insertRows('games', $games);
            $this->insertRows('team_game_stats', $teamGameStats);
            $this->insertRows('player_game_stats', $playerGameStats);
            $this->insertRows('awards', $awards);
            $this->insertRows('grid_puzzles', $gridPuzzles);
            $this->insertRows('grid_puzzle_rows', $gridRows);
            $this->insertRows('grid_puzzle_columns', $gridColumns);
            $this->insertRows('grid_puzzle_cells', $gridCells);
            $this->insertRows('grid_puzzle_answers', $gridAnswers);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function playerBlueprints(): array
    {
        return [
            1 => [
                ['first_name' => 'Tomas', 'last_name' => 'Satoransky', 'nationality' => 'Czech Republic'],
                ['first_name' => 'Nicolas', 'last_name' => 'Laprovittola', 'nationality' => 'Argentina'],
                ['first_name' => 'Alex', 'last_name' => 'Abrines', 'nationality' => 'Spain'],
                ['first_name' => 'Dario', 'last_name' => 'Brizuela', 'nationality' => 'Spain'],
                ['first_name' => 'Jabari', 'last_name' => 'Parker', 'nationality' => 'United States'],
                ['first_name' => 'Willy', 'last_name' => 'Hernangomez', 'nationality' => 'Spain'],
                ['first_name' => 'Jan', 'last_name' => 'Vesely', 'nationality' => 'Czech Republic'],
                ['first_name' => 'Joel', 'last_name' => 'Parra', 'nationality' => 'Spain'],
                ['first_name' => 'Rokas', 'last_name' => 'Jokubaitis', 'nationality' => 'Lithuania'],
                ['first_name' => 'Chimezie', 'last_name' => 'Metu', 'nationality' => 'Nigeria'],
            ],
            2 => [
                ['first_name' => 'Thomas', 'last_name' => 'Walkup', 'nationality' => 'Greece'],
                ['first_name' => 'Nigel', 'last_name' => 'Williams-Goss', 'nationality' => 'United States'],
                ['first_name' => 'Kostas', 'last_name' => 'Papanikolaou', 'nationality' => 'Greece'],
                ['first_name' => 'Isaiah', 'last_name' => 'Canaan', 'nationality' => 'United States'],
                ['first_name' => 'Alec', 'last_name' => 'Peters', 'nationality' => 'United States'],
                ['first_name' => 'Nikola', 'last_name' => 'Milutinov', 'nationality' => 'Serbia'],
                ['first_name' => 'Moustapha', 'last_name' => 'Fall', 'nationality' => 'France'],
                ['first_name' => 'Shaquielle', 'last_name' => 'McKissic', 'nationality' => 'United States'],
                ['first_name' => 'Giannoulis', 'last_name' => 'Larentzakis', 'nationality' => 'Greece'],
                ['first_name' => 'Luke', 'last_name' => 'Sikma', 'nationality' => 'United States'],
            ],
            3 => [
                ['first_name' => 'Nick', 'last_name' => 'Calathes', 'nationality' => 'Greece'],
                ['first_name' => 'Scottie', 'last_name' => 'Wilbekin', 'nationality' => 'United States'],
                ['first_name' => 'Marko', 'last_name' => 'Guduric', 'nationality' => 'Serbia'],
                ['first_name' => 'Tarik', 'last_name' => 'Biberovic', 'nationality' => 'Bosnia and Herzegovina'],
                ['first_name' => 'Nigel', 'last_name' => 'Hayes-Davis', 'nationality' => 'United States'],
                ['first_name' => 'Johnathan', 'last_name' => 'Motley', 'nationality' => 'United States'],
                ['first_name' => 'Sertac', 'last_name' => 'Sanli', 'nationality' => 'Turkey'],
                ['first_name' => 'Dyshawn', 'last_name' => 'Pierre', 'nationality' => 'Canada'],
                ['first_name' => 'Yam', 'last_name' => 'Madar', 'nationality' => 'Israel'],
                ['first_name' => 'Nate', 'last_name' => 'Sestina', 'nationality' => 'United States'],
            ],
            4 => [
                ['first_name' => 'Maodo', 'last_name' => 'Lo', 'nationality' => 'Germany'],
                ['first_name' => 'Shavon', 'last_name' => 'Shields', 'nationality' => 'Denmark'],
                ['first_name' => 'Stefano', 'last_name' => 'Tonut', 'nationality' => 'Italy'],
                ['first_name' => 'Devon', 'last_name' => 'Hall', 'nationality' => 'United States'],
                ['first_name' => 'Nikola', 'last_name' => 'Mirotic', 'nationality' => 'Montenegro'],
                ['first_name' => 'Nicolo', 'last_name' => 'Melli', 'nationality' => 'Italy'],
                ['first_name' => 'Johannes', 'last_name' => 'Voigtmann', 'nationality' => 'Germany'],
                ['first_name' => 'Billy', 'last_name' => 'Baron', 'nationality' => 'United States'],
                ['first_name' => 'Diego', 'last_name' => 'Flaccadori', 'nationality' => 'Italy'],
                ['first_name' => 'Kyle', 'last_name' => 'Hines', 'nationality' => 'United States'],
            ],
            5 => [
                ['first_name' => 'TJ', 'last_name' => 'Shorts', 'nationality' => 'United States'],
                ['first_name' => 'Nadir', 'last_name' => 'Hifi', 'nationality' => 'France'],
                ['first_name' => 'Tyson', 'last_name' => 'Ward', 'nationality' => 'United States'],
                ['first_name' => 'Sebastian', 'last_name' => 'Herrera', 'nationality' => 'Colombia'],
                ['first_name' => 'Mikael', 'last_name' => 'Jantunen', 'nationality' => 'Finland'],
                ['first_name' => 'Kevarrius', 'last_name' => 'Hayes', 'nationality' => 'United States'],
                ['first_name' => 'Leon', 'last_name' => 'Kratzer', 'nationality' => 'Germany'],
                ['first_name' => 'Bandja', 'last_name' => 'Sy', 'nationality' => 'France'],
                ['first_name' => 'Daulton', 'last_name' => 'Hommes', 'nationality' => 'United States'],
                ['first_name' => 'Collin', 'last_name' => 'Malcolm', 'nationality' => 'United States'],
            ],
            6 => [
                ['first_name' => 'Matteo', 'last_name' => 'Spagnolo', 'nationality' => 'Italy'],
                ['first_name' => 'Sterling', 'last_name' => 'Brown', 'nationality' => 'United States'],
                ['first_name' => 'Louis', 'last_name' => 'Olinde', 'nationality' => 'Germany'],
                ['first_name' => 'Gabriele', 'last_name' => 'Procida', 'nationality' => 'Italy'],
                ['first_name' => 'Johannes', 'last_name' => 'Thiemann', 'nationality' => 'Germany'],
                ['first_name' => 'Yanni', 'last_name' => 'Wetzell', 'nationality' => 'New Zealand'],
                ['first_name' => 'Tim', 'last_name' => 'Schneider', 'nationality' => 'Germany'],
                ['first_name' => 'Matt', 'last_name' => 'Thomas', 'nationality' => 'United States'],
                ['first_name' => 'Martin', 'last_name' => 'Hermannsson', 'nationality' => 'Iceland'],
                ['first_name' => 'Jonas', 'last_name' => 'Mattisseck', 'nationality' => 'Germany'],
            ],
            7 => [
                ['first_name' => 'Milos', 'last_name' => 'Teodosic', 'nationality' => 'Serbia'],
                ['first_name' => 'Nemanja', 'last_name' => 'Nedovic', 'nationality' => 'Serbia'],
                ['first_name' => 'Rokas', 'last_name' => 'Giedraitis', 'nationality' => 'Lithuania'],
                ['first_name' => 'Ognjen', 'last_name' => 'Dobric', 'nationality' => 'Serbia'],
                ['first_name' => 'Dejan', 'last_name' => 'Davidovac', 'nationality' => 'Serbia'],
                ['first_name' => 'Joel', 'last_name' => 'Bolomboy', 'nationality' => 'Ukraine'],
                ['first_name' => 'Mike', 'last_name' => 'Tobey', 'nationality' => 'Slovenia'],
                ['first_name' => 'Yago', 'last_name' => 'dos Santos', 'nationality' => 'Brazil'],
                ['first_name' => 'Branko', 'last_name' => 'Lazic', 'nationality' => 'Serbia'],
                ['first_name' => 'Luka', 'last_name' => 'Mitrovic', 'nationality' => 'Serbia'],
            ],
            8 => [
                ['first_name' => 'Keenan', 'last_name' => 'Evans', 'nationality' => 'United States'],
                ['first_name' => 'Lukas', 'last_name' => 'Lekavicius', 'nationality' => 'Lithuania'],
                ['first_name' => 'Edgaras', 'last_name' => 'Ulanovas', 'nationality' => 'Lithuania'],
                ['first_name' => 'Tomas', 'last_name' => 'Dimsa', 'nationality' => 'Lithuania'],
                ['first_name' => 'Rolands', 'last_name' => 'Smits', 'nationality' => 'Latvia'],
                ['first_name' => 'Laurynas', 'last_name' => 'Birutis', 'nationality' => 'Lithuania'],
                ['first_name' => 'Brady', 'last_name' => 'Manek', 'nationality' => 'United States'],
                ['first_name' => 'Arnas', 'last_name' => 'Butkevicius', 'nationality' => 'Lithuania'],
                ['first_name' => 'Dovydas', 'last_name' => 'Giedraitis', 'nationality' => 'Lithuania'],
                ['first_name' => 'Edmond', 'last_name' => 'Sumner', 'nationality' => 'United States'],
            ],
            9 => [
                ['first_name' => 'Lorenzo', 'last_name' => 'Brown', 'nationality' => 'Spain'],
                ['first_name' => 'Wade', 'last_name' => 'Baldwin', 'nationality' => 'United States'],
                ['first_name' => 'Bonzie', 'last_name' => 'Colson', 'nationality' => 'United States'],
                ['first_name' => 'John', 'last_name' => 'DiBartolomeo', 'nationality' => 'Israel'],
                ['first_name' => 'Roman', 'last_name' => 'Sorkin', 'nationality' => 'Israel'],
                ['first_name' => 'Josh', 'last_name' => 'Nebo', 'nationality' => 'United States'],
                ['first_name' => 'Jasiel', 'last_name' => 'Rivero', 'nationality' => 'Cuba'],
                ['first_name' => 'Antonius', 'last_name' => 'Cleveland', 'nationality' => 'United States'],
                ['first_name' => 'Tamir', 'last_name' => 'Blatt', 'nationality' => 'Israel'],
                ['first_name' => 'Jake', 'last_name' => 'Cohen', 'nationality' => 'Israel'],
            ],
            10 => [
                ['first_name' => 'Mike', 'last_name' => 'James', 'nationality' => 'United States'],
                ['first_name' => 'Jordan', 'last_name' => 'Loyd', 'nationality' => 'United States'],
                ['first_name' => 'Alpha', 'last_name' => 'Diallo', 'nationality' => 'United States'],
                ['first_name' => 'Elie', 'last_name' => 'Okobo', 'nationality' => 'France'],
                ['first_name' => 'Donatas', 'last_name' => 'Motiejunas', 'nationality' => 'Lithuania'],
                ['first_name' => 'Mam', 'last_name' => 'Jaiteh', 'nationality' => 'France'],
                ['first_name' => 'John', 'last_name' => 'Brown', 'nationality' => 'United States'],
                ['first_name' => 'Jaron', 'last_name' => 'Blossomgame', 'nationality' => 'United States'],
                ['first_name' => 'Yakuba', 'last_name' => 'Ouattara', 'nationality' => 'France'],
                ['first_name' => 'Petr', 'last_name' => 'Cornelie', 'nationality' => 'France'],
            ],
        ];
    }

    private function coachProfiles(): array
    {
        return [
            1 => ['first_name' => 'Joan', 'last_name' => 'Penarroya', 'nationality' => 'Spain'],
            2 => ['first_name' => 'Georgios', 'last_name' => 'Bartzokas', 'nationality' => 'Greece'],
            3 => ['first_name' => 'Sarunas', 'last_name' => 'Jasikevicius', 'nationality' => 'Lithuania'],
            4 => ['first_name' => 'Ettore', 'last_name' => 'Messina', 'nationality' => 'Italy'],
            5 => ['first_name' => 'Tiago', 'last_name' => 'Splitter', 'nationality' => 'Brazil'],
            6 => ['first_name' => 'Israel', 'last_name' => 'Gonzalez', 'nationality' => 'Spain'],
            7 => ['first_name' => 'Ioannis', 'last_name' => 'Sfairopoulos', 'nationality' => 'Greece'],
            8 => ['first_name' => 'Andrea', 'last_name' => 'Trinchieri', 'nationality' => 'Italy'],
            9 => ['first_name' => 'Oded', 'last_name' => 'Kattash', 'nationality' => 'Israel'],
            10 => ['first_name' => 'Sasa', 'last_name' => 'Obradovic', 'nationality' => 'Serbia'],
        ];
    }

    private function buildSeasonRosters(array $teamIds, array $baseRosters): array
    {
        $orderedTeamIds = array_values($teamIds);
        $teamIndexById = array_flip($orderedTeamIds);
        $teamCount = count($orderedTeamIds);
        $seasonRosters = [];

        for ($seasonId = 1; $seasonId <= 10; $seasonId++) {
            foreach ($orderedTeamIds as $teamId) {
                foreach ($baseRosters[$teamId] as $slotIndex => $playerMeta) {
                    $sourceIndex = $this->seasonRosterSourceIndex((int) $teamIndexById[$teamId], $seasonId, (int) $slotIndex, $teamCount);
                    $sourceTeamId = $orderedTeamIds[$sourceIndex];
                    $seasonPlayer = $baseRosters[$sourceTeamId][$slotIndex];
                    $seasonPlayer['origin_team_id'] = $sourceTeamId;
                    $seasonPlayer['is_returning'] = $sourceTeamId === $teamId ? 1 : 0;
                    $seasonRosters[$seasonId][$teamId][] = $seasonPlayer;
                }
            }
        }

        return $seasonRosters;
    }

    private function seasonRosterSourceIndex(int $teamIndex, int $seasonId, int $slotIndex, int $teamCount): int
    {
        $seasonOffset = max(0, $seasonId - 1);
        $movement = match ($slotIndex) {
            0, 1, 2, 4, 5 => 0,
            3 => intdiv($seasonOffset, 3),
            6 => intdiv($seasonOffset, 2),
            7 => $seasonOffset,
            8 => $seasonOffset * 2,
            9 => $seasonOffset * 3,
            default => 0,
        };

        if ($movement === 0) {
            return $teamIndex;
        }

        $direction = in_array($slotIndex, [3, 7], true) ? -1 : 1;
        $sourceIndex = ($teamIndex + ($direction * $movement)) % $teamCount;

        return $sourceIndex < 0 ? $sourceIndex + $teamCount : $sourceIndex;
    }

    private function jerseyNumberForSeason(int $teamId, int $seasonId, int $slotIndex, int $personId): int
    {
        return (($teamId * 13) + ($seasonId * 5) + ($slotIndex * 7) + ($personId % 11)) % 99 + 1;
    }

    private function buildGridData(array $teamsById, array $games, array $playerGameStats, array $rosterAssignments): array
    {
        $gridPuzzles = [];
        $gridRows = [];
        $gridColumns = [];
        $gridCells = [];
        $gridAnswers = [];
        $playerSummariesByTeam = $this->buildGridPlayerSummaries($games, $playerGameStats, $rosterAssignments);
        $categoryBlueprints = $this->gridCategoryBlueprints();
        $eligibleByCategory = [];
        $categoryKeys = array_keys($categoryBlueprints);

        foreach ($categoryBlueprints as $categoryKey => $blueprint) {
            foreach ($playerSummariesByTeam as $teamId => $teamPlayers) {
                $eligiblePlayers = array_values(array_filter(
                    $teamPlayers,
                    static fn (array $summary): bool => ($blueprint['matches'])($summary)
                ));

                if ($eligiblePlayers === []) {
                    continue;
                }

                usort($eligiblePlayers, static function (array $left, array $right) use ($blueprint): int {
                    $scoreDiff = ($blueprint['rank'])($right) <=> ($blueprint['rank'])($left);
                    if ($scoreDiff !== 0) {
                        return $scoreDiff;
                    }

                    return $right['games_played'] <=> $left['games_played'];
                });

                $eligibleByCategory[$categoryKey][$teamId] = $eligiblePlayers;
            }
        }

        for ($puzzleId = 1; $puzzleId <= 10; $puzzleId++) {
            $teamIds = [];
            for ($rowPosition = 1; $rowPosition <= 3; $rowPosition++) {
                $teamIds[] = (($puzzleId + $rowPosition - 2) % 10) + 1;
            }

            $availableCategoryKeys = array_values(array_filter(
                $categoryKeys,
                static function (string $categoryKey) use ($eligibleByCategory, $teamIds): bool {
                    foreach ($teamIds as $teamId) {
                        if (!isset($eligibleByCategory[$categoryKey][$teamId])) {
                            return false;
                        }
                    }

                    return true;
                }
            ));

            if (count($availableCategoryKeys) < 3) {
                throw new RuntimeException('Unable to build a varied puzzle grid from the generated season stats.');
            }

            $selectedCategoryKeys = $this->pickGridCategoryKeys($availableCategoryKeys, $puzzleId, 3);
            $gridPuzzles[] = [
                'grid_puzzle_id' => $puzzleId,
                'season_id' => $this->currentSeasonId,
                'puzzle_name' => 'Euroleague Grid ' . str_pad((string) $puzzleId, 2, '0', STR_PAD_LEFT) . ' · ' . implode(' / ', array_map(
                    static fn (string $categoryKey): string => $categoryBlueprints[$categoryKey]['units'],
                    $selectedCategoryKeys
                )),
                'puzzle_date' => sprintf('2026-03-%02d', 10 + $puzzleId),
                'status' => 'published',
            ];

            foreach ($teamIds as $rowIndex => $teamId) {
                $rowPosition = $rowIndex + 1;
                $gridRows[] = [
                    'grid_puzzle_id' => $puzzleId,
                    'row_position' => $rowPosition,
                    'team_id' => $teamId,
                    'clue_text' => 'Choose a ' . $teamsById[$teamId]['short_name'] . ' player who matches the benchmark.',
                ];

                foreach ($selectedCategoryKeys as $columnIndex => $categoryKey) {
                    $columnPosition = $columnIndex + 1;
                    $column = $categoryBlueprints[$categoryKey];
                    $gridCells[] = [
                        'grid_puzzle_id' => $puzzleId,
                        'row_position' => $rowPosition,
                        'column_position' => $columnPosition,
                        'cell_value' => null,
                        'notes' => 'Seeded puzzle cell.',
                    ];

                    foreach ($eligibleByCategory[$categoryKey][$teamId] as $answerIndex => $summary) {
                        $gridAnswers[] = [
                            'grid_puzzle_id' => $puzzleId,
                            'row_position' => $rowPosition,
                            'column_position' => $columnPosition,
                            'person_id' => $summary['person_id'],
                            'is_primary' => $answerIndex === 0 ? 1 : 0,
                            'notes' => ($answerIndex === 0 ? 'Primary' : 'Alternate') . ' seeded answer for ' . $column['units'] . '.',
                        ];
                    }
                }
            }

            foreach ($selectedCategoryKeys as $columnIndex => $categoryKey) {
                $columnPosition = $columnIndex + 1;
                $column = $categoryBlueprints[$categoryKey];
                $gridColumns[] = [
                    'grid_puzzle_id' => $puzzleId,
                    'column_position' => $columnPosition,
                    'stat_name' => $column['stat_name'],
                    'comparison_operator' => $column['comparison_operator'],
                    'target_value' => $column['target_value'],
                    'units' => $column['units'],
                    'clue_text' => $column['clue_text'],
                ];
            }
        }

        return [$gridPuzzles, $gridRows, $gridColumns, $gridCells, $gridAnswers];
    }

    private function buildGridPlayerSummaries(array $games, array $playerGameStats, array $rosterAssignments): array
    {
        $currentSeasonGames = [];
        foreach ($games as $game) {
            if ((int) $game['season_id'] !== $this->currentSeasonId) {
                continue;
            }

            $currentSeasonGames[(int) $game['game_id']] = true;
        }

        $teamByPlayer = [];
        foreach ($rosterAssignments as $assignment) {
            if ((int) $assignment['season_id'] !== $this->currentSeasonId) {
                continue;
            }

            $teamByPlayer[(int) $assignment['person_id']] = (int) $assignment['team_id'];
        }

        $totalsByTeamPlayer = [];
        foreach ($playerGameStats as $row) {
            if (!isset($currentSeasonGames[(int) $row['game_id']])) {
                continue;
            }

            $personId = (int) $row['person_id'];
            $teamId = $teamByPlayer[$personId] ?? null;
            if ($teamId === null) {
                continue;
            }

            if (!isset($totalsByTeamPlayer[$teamId][$personId])) {
                $totalsByTeamPlayer[$teamId][$personId] = [
                    'person_id' => $personId,
                    'games_played' => 0,
                    'points' => 0,
                    'rebounds' => 0,
                    'assists' => 0,
                    'turnovers' => 0,
                    'field_goals_made' => 0,
                    'field_goals_attempted' => 0,
                    'three_points_made' => 0,
                    'three_points_attempted' => 0,
                    'free_throws_made' => 0,
                    'free_throws_attempted' => 0,
                ];
            }

            $totalsByTeamPlayer[$teamId][$personId]['games_played']++;
            foreach (['points', 'rebounds', 'assists', 'turnovers', 'field_goals_made', 'field_goals_attempted', 'three_points_made', 'three_points_attempted', 'free_throws_made', 'free_throws_attempted'] as $column) {
                $totalsByTeamPlayer[$teamId][$personId][$column] += (int) $row[$column];
            }
        }

        $summaries = [];
        foreach ($totalsByTeamPlayer as $teamId => $players) {
            foreach ($players as $personId => $totals) {
                $gamesPlayed = max(1, (int) $totals['games_played']);
                $ppg = $totals['points'] / $gamesPlayed;
                $apg = $totals['assists'] / $gamesPlayed;
                $rpg = $totals['rebounds'] / $gamesPlayed;
                $tovpg = $totals['turnovers'] / $gamesPlayed;

                $summaries[$teamId][] = [
                    'person_id' => $personId,
                    'games_played' => $gamesPlayed,
                    'ppg' => $ppg,
                    'apg' => $apg,
                    'rpg' => $rpg,
                    'tovpg' => $tovpg,
                    'fg_pct' => $totals['field_goals_attempted'] > 0 ? ($totals['field_goals_made'] / $totals['field_goals_attempted']) * 100 : 0.0,
                    'three_pct' => $totals['three_points_attempted'] > 0 ? ($totals['three_points_made'] / $totals['three_points_attempted']) * 100 : 0.0,
                    'ft_pct' => $totals['free_throws_attempted'] > 0 ? ($totals['free_throws_made'] / $totals['free_throws_attempted']) * 100 : 0.0,
                    'three_made_pg' => $totals['three_points_made'] / $gamesPlayed,
                    'assist_turnover_ratio' => $tovpg > 0 ? ($apg / $tovpg) : $apg,
                    'points_assists' => $ppg + $apg,
                    'points_rebounds' => $ppg + $rpg,
                    'field_goals_attempted' => (int) $totals['field_goals_attempted'],
                    'three_points_attempted' => (int) $totals['three_points_attempted'],
                    'free_throws_attempted' => (int) $totals['free_throws_attempted'],
                ];
            }
        }

        return $summaries;
    }

    private function gridCategoryBlueprints(): array
    {
        return [
            'volume_scoring' => [
                'stat_name' => 'avg_points',
                'comparison_operator' => '>=',
                'target_value' => 15.0,
                'units' => 'PPG',
                'clue_text' => 'Average at least 15 points per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['ppg'] >= 15.0,
                'rank' => static fn (array $summary): float => $summary['ppg'],
            ],
            'table_setting' => [
                'stat_name' => 'avg_assists',
                'comparison_operator' => '>=',
                'target_value' => 5.0,
                'units' => 'APG',
                'clue_text' => 'Average at least 5 assists per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['apg'] >= 5.0,
                'rank' => static fn (array $summary): float => $summary['apg'],
            ],
            'glass_work' => [
                'stat_name' => 'avg_rebounds',
                'comparison_operator' => '>=',
                'target_value' => 6.0,
                'units' => 'RPG',
                'clue_text' => 'Average at least 6 rebounds per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['rpg'] >= 6.0,
                'rank' => static fn (array $summary): float => $summary['rpg'],
            ],
            'sniper_touch' => [
                'stat_name' => 'three_pct',
                'comparison_operator' => '>=',
                'target_value' => 37.0,
                'units' => '3P%',
                'clue_text' => 'Shoot at least 37% from three on real volume',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['three_points_attempted'] >= 45 && $summary['three_pct'] >= 37.0,
                'rank' => static fn (array $summary): float => $summary['three_pct'],
            ],
            'paint_efficiency' => [
                'stat_name' => 'fg_pct',
                'comparison_operator' => '>=',
                'target_value' => 52.0,
                'units' => 'FG%',
                'clue_text' => 'Shoot at least 52% from the field',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['field_goals_attempted'] >= 90 && $summary['fg_pct'] >= 52.0,
                'rank' => static fn (array $summary): float => $summary['fg_pct'],
            ],
            'free_throw_poise' => [
                'stat_name' => 'ft_pct',
                'comparison_operator' => '>=',
                'target_value' => 82.0,
                'units' => 'FT%',
                'clue_text' => 'Shoot at least 82% at the line',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['free_throws_attempted'] >= 35 && $summary['ft_pct'] >= 82.0,
                'rank' => static fn (array $summary): float => $summary['ft_pct'],
            ],
            'deep_volume' => [
                'stat_name' => 'three_made_pg',
                'comparison_operator' => '>=',
                'target_value' => 1.9,
                'units' => '3PM',
                'clue_text' => 'Make at least 1.9 threes per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['three_made_pg'] >= 1.9,
                'rank' => static fn (array $summary): float => $summary['three_made_pg'],
            ],
            'steady_hand' => [
                'stat_name' => 'assist_turnover_ratio',
                'comparison_operator' => '>=',
                'target_value' => 2.4,
                'units' => 'A/T',
                'clue_text' => 'Post at least a 2.4 assist-to-turnover ratio',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['apg'] >= 3.5 && $summary['assist_turnover_ratio'] >= 2.4,
                'rank' => static fn (array $summary): float => $summary['assist_turnover_ratio'],
            ],
            'creator_load' => [
                'stat_name' => 'points_assists',
                'comparison_operator' => '>=',
                'target_value' => 21.0,
                'units' => 'P+A',
                'clue_text' => 'Combine for at least 21 points and assists per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['points_assists'] >= 21.0,
                'rank' => static fn (array $summary): float => $summary['points_assists'],
            ],
            'frontcourt_load' => [
                'stat_name' => 'points_rebounds',
                'comparison_operator' => '>=',
                'target_value' => 22.0,
                'units' => 'P+R',
                'clue_text' => 'Combine for at least 22 points and rebounds per game',
                'matches' => static fn (array $summary): bool => $summary['games_played'] >= 12 && $summary['points_rebounds'] >= 22.0,
                'rank' => static fn (array $summary): float => $summary['points_rebounds'],
            ],
        ];
    }

    private function pickGridCategoryKeys(array $availableCategoryKeys, int $puzzleId, int $count): array
    {
        sort($availableCategoryKeys);
        $selected = [];
        $startIndex = ($puzzleId - 1) % count($availableCategoryKeys);

        for ($offset = 0; $offset < count($availableCategoryKeys) && count($selected) < $count; $offset++) {
            $categoryKey = $availableCategoryKeys[($startIndex + $offset) % count($availableCategoryKeys)];
            if (in_array($categoryKey, $selected, true)) {
                continue;
            }

            $selected[] = $categoryKey;
        }

        return $selected;
    }

    private function buildAwards(
        array $games,
        array $playerGameStats,
        array $teamSeasons,
        array $people,
        array $players,
        array $rosterAssignments
    ): array {
        $gamesById = [];
        foreach ($games as $game) {
            $gamesById[(int) $game['game_id']] = (int) $game['season_id'];
        }

        $peopleById = [];
        foreach ($people as $person) {
            $peopleById[(int) $person['person_id']] = $person;
        }

        $playerMetaById = [];
        foreach ($players as $player) {
            $playerMetaById[(int) $player['person_id']] = $player;
        }

        $rosterBySeasonPerson = [];
        foreach ($rosterAssignments as $assignment) {
            $rosterBySeasonPerson[(int) $assignment['season_id']][(int) $assignment['person_id']] = [
                'team_id' => (int) $assignment['team_id'],
                'role' => $assignment['role'],
            ];
        }

        $teamSeasonBySeasonTeam = [];
        foreach ($teamSeasons as $teamSeason) {
            $teamSeasonBySeasonTeam[(int) $teamSeason['season_id']][(int) $teamSeason['team_id']] = $teamSeason;
        }

        $statsBySeasonPlayer = [];
        foreach ($playerGameStats as $row) {
            $seasonId = $gamesById[(int) $row['game_id']] ?? null;
            if ($seasonId === null) {
                continue;
            }

            $personId = (int) $row['person_id'];
            if (!isset($statsBySeasonPlayer[$seasonId][$personId])) {
                $statsBySeasonPlayer[$seasonId][$personId] = [
                    'games_played' => 0,
                    'points' => 0,
                    'rebounds' => 0,
                    'assists' => 0,
                    'turnovers' => 0,
                    'field_goals_made' => 0,
                    'field_goals_attempted' => 0,
                    'three_points_made' => 0,
                    'three_points_attempted' => 0,
                    'free_throws_made' => 0,
                    'free_throws_attempted' => 0,
                ];
            }

            $statsBySeasonPlayer[$seasonId][$personId]['games_played']++;
            foreach (['points', 'rebounds', 'assists', 'turnovers', 'field_goals_made', 'field_goals_attempted', 'three_points_made', 'three_points_attempted', 'free_throws_made', 'free_throws_attempted'] as $column) {
                $statsBySeasonPlayer[$seasonId][$personId][$column] += (int) $row[$column];
            }
        }

        $awards = [];
        $awardId = 1;
        $previousSeasonCandidates = [];
        $previousAwardWinners = [];

        for ($seasonId = 1; $seasonId <= 10; $seasonId++) {
            $seasonCandidates = [];
            foreach ($statsBySeasonPlayer[$seasonId] ?? [] as $personId => $totals) {
                $gamesPlayed = max(1, (int) $totals['games_played']);
                $rosterMeta = $rosterBySeasonPerson[$seasonId][$personId] ?? null;
                if ($rosterMeta === null) {
                    continue;
                }

                $teamId = (int) $rosterMeta['team_id'];
                $teamSeason = $teamSeasonBySeasonTeam[$seasonId][$teamId] ?? ['wins' => 0, 'losses' => 0, 'point_diff' => 0, 'avg_attendance' => 0, 'points_against' => 0];
                $person = $peopleById[$personId] ?? ['first_name' => 'Player', 'last_name' => (string) $personId];
                $playerMeta = $playerMetaById[$personId] ?? ['position' => 'G'];
                $teamGames = max(1, (int) $teamSeason['wins'] + (int) $teamSeason['losses']);

                $seasonCandidates[$personId] = [
                    'person_id' => $personId,
                    'player_name' => trim($person['first_name'] . ' ' . $person['last_name']),
                    'team_id' => $teamId,
                    'role' => (string) $rosterMeta['role'],
                    'position' => (string) $playerMeta['position'],
                    'games_played' => $gamesPlayed,
                    'ppg' => $totals['points'] / $gamesPlayed,
                    'rpg' => $totals['rebounds'] / $gamesPlayed,
                    'apg' => $totals['assists'] / $gamesPlayed,
                    'tovpg' => $totals['turnovers'] / $gamesPlayed,
                    'fg_pct' => $totals['field_goals_attempted'] > 0 ? ($totals['field_goals_made'] / $totals['field_goals_attempted']) * 100 : 0,
                    'three_pct' => $totals['three_points_attempted'] > 0 ? ($totals['three_points_made'] / $totals['three_points_attempted']) * 100 : 0,
                    'ft_pct' => $totals['free_throws_attempted'] > 0 ? ($totals['free_throws_made'] / $totals['free_throws_attempted']) * 100 : 0,
                    'wins' => (int) $teamSeason['wins'],
                    'losses' => (int) $teamSeason['losses'],
                    'team_win_pct' => $teamGames > 0 ? ((int) $teamSeason['wins'] / $teamGames) : 0.0,
                    'team_point_diff' => (int) $teamSeason['point_diff'],
                    'team_points_allowed' => $teamGames > 0 ? ((int) $teamSeason['points_against'] / $teamGames) : 0.0,
                    'avg_attendance' => (int) ($teamSeason['avg_attendance'] ?? 0),
                ];
            }

            if ($seasonCandidates === []) {
                continue;
            }

            $used = [];
            $awardDefinitions = [
                [
                    'name' => 'MVP',
                    'score' => static fn (array $candidate): float => ($candidate['ppg'] * 3.2) + ($candidate['apg'] * 2.5) + ($candidate['rpg'] * 1.7) + ($candidate['team_win_pct'] * 28),
                    'note' => static fn (array $winner): string => sprintf('Averaged %.1f points, %.1f assists, and %.1f rebounds while driving a %d-%d campaign.', $winner['ppg'], $winner['apg'], $winner['rpg'], $winner['wins'], $winner['losses']),
                ],
                [
                    'name' => 'Best Scorer',
                    'score' => static fn (array $candidate): float => ($candidate['ppg'] * 5.0) + ($candidate['fg_pct'] * 0.08),
                    'note' => static fn (array $winner): string => sprintf('Led the scoring race at %.1f points per game on %.1f%% shooting.', $winner['ppg'], $winner['fg_pct']),
                ],
                [
                    'name' => 'Assist Artist',
                    'score' => static fn (array $candidate): float => ($candidate['apg'] * 5.2) - ($candidate['tovpg'] * 0.9) + ($candidate['team_win_pct'] * 8),
                    'note' => static fn (array $winner): string => sprintf('Set the playmaking pace at %.1f assists per game with only %.1f turnovers.', $winner['apg'], $winner['tovpg']),
                ],
                [
                    'name' => 'Glass King',
                    'score' => static fn (array $candidate): float => ($candidate['rpg'] * 5.0) + ($candidate['team_point_diff'] * 0.06),
                    'note' => static fn (array $winner): string => sprintf('Owned the glass with %.1f rebounds per game and steady interior control.', $winner['rpg']),
                ],
                [
                    'name' => 'Most Improved',
                    'score' => static function (array $candidate) use ($previousSeasonCandidates): float {
                        $previous = $previousSeasonCandidates[$candidate['person_id']] ?? null;
                        if ($previous === null || $previous['games_played'] < 6) {
                            return -INF;
                        }

                        return (($candidate['ppg'] - $previous['ppg']) * 3.2)
                            + (($candidate['apg'] - $previous['apg']) * 2.4)
                            + (($candidate['rpg'] - $previous['rpg']) * 2.0)
                            + (($candidate['team_win_pct'] - $previous['team_win_pct']) * 18);
                    },
                    'note' => static function (array $winner) use ($previousSeasonCandidates): string {
                        $previous = $previousSeasonCandidates[$winner['person_id']] ?? null;
                        $previousPoints = $previous !== null ? number_format($previous['ppg'], 1, '.', '') : '0.0';
                        return sprintf('Jumped from %s to %.1f points per game and took on a much bigger share of the offense.', $previousPoints, $winner['ppg']);
                    },
                ],
                [
                    'name' => 'Best Sixth Man',
                    'filter' => static fn (array $candidate): bool => in_array($candidate['role'], ['Bench Gunner', 'Reserve Guard', 'Utility Forward', 'Rim Runner'], true),
                    'score' => static fn (array $candidate): float => ($candidate['ppg'] * 3.6) + ($candidate['apg'] * 1.5) + ($candidate['rpg'] * 1.1),
                    'note' => static fn (array $winner): string => sprintf('Powered the second unit with %.1f points per game and reliable bench production.', $winner['ppg']),
                ],
                [
                    'name' => 'Clutch Performer',
                    'score' => static fn (array $candidate): float => ($candidate['ppg'] * 2.8) + ($candidate['team_win_pct'] * 22) + (abs($candidate['team_point_diff']) < 120 ? 3.0 : 0.0),
                    'note' => static fn (array $winner): string => sprintf('Stayed productive in the tightest part of the table race while posting %.1f points per game.', $winner['ppg']),
                ],
                [
                    'name' => 'Defensive Anchor',
                    'filter' => static fn (array $candidate): bool => in_array($candidate['position'], ['PF', 'C', 'F'], true) || in_array($candidate['role'], ['Stretch Four', 'Anchor Big', 'Rim Runner', 'Utility Forward'], true),
                    'score' => static fn (array $candidate): float => ($candidate['rpg'] * 3.8) + ((100 - $candidate['team_points_allowed']) * 0.9) + ($candidate['team_win_pct'] * 12),
                    'note' => static fn (array $winner): string => sprintf('Anchored a defense that allowed %.1f points per game while collecting %.1f rebounds nightly.', $winner['team_points_allowed'], $winner['rpg']),
                ],
                [
                    'name' => 'Fan Favorite',
                    'score' => static fn (array $candidate): float => ($candidate['avg_attendance'] / 900) + ($candidate['ppg'] * 1.8) + ($candidate['team_win_pct'] * 10),
                    'note' => static fn (array $winner): string => sprintf('Became a centerpiece on one of the loudest home floors in the league, averaging %.1f points.', $winner['ppg']),
                ],
                [
                    'name' => 'Playmaker Award',
                    'score' => static fn (array $candidate): float => ($candidate['apg'] * 4.4) + (($candidate['apg'] / max(0.8, $candidate['tovpg'])) * 2.2),
                    'note' => static fn (array $winner): string => sprintf('Balanced %.1f assists per game with a %.1f assist-to-turnover ratio.', $winner['apg'], $winner['apg'] / max(0.8, $winner['tovpg'])),
                ],
            ];

            foreach ($awardDefinitions as $definition) {
                $winner = $this->pickAwardRecipient(
                    array_values($seasonCandidates),
                    $definition['score'],
                    $used,
                    $previousAwardWinners[$definition['name']] ?? null,
                    $definition['filter'] ?? null
                );

                if ($winner === null) {
                    continue;
                }

                $awards[] = [
                    'award_id' => $awardId++,
                    'season_id' => $seasonId,
                    'person_id' => $winner['person_id'],
                    'award_name' => $definition['name'],
                    'award_type' => 'Player',
                    'notes' => $definition['note']($winner),
                ];
                $used[$winner['person_id']] = true;
                $previousAwardWinners[$definition['name']] = $winner['person_id'];
            }

            $previousSeasonCandidates = $seasonCandidates;
        }

        return $awards;
    }

    private function pickAwardRecipient(
        array $candidates,
        callable $score,
        array $used,
        ?int $previousWinnerId = null,
        ?callable $filter = null
    ): ?array {
        $winner = null;
        $winnerScore = null;

        foreach ($candidates as $candidate) {
            if (isset($used[$candidate['person_id']])) {
                continue;
            }
            if ($filter !== null && !$filter($candidate)) {
                continue;
            }

            $candidateScore = (float) $score($candidate);
            if (!is_finite($candidateScore)) {
                continue;
            }
            if ($previousWinnerId !== null && $candidate['person_id'] === $previousWinnerId) {
                $candidateScore -= 1.75;
            }

            if (
                $winner === null
                || $candidateScore > $winnerScore
                || ($candidateScore === $winnerScore && $candidate['wins'] > $winner['wins'])
                || ($candidateScore === $winnerScore && $candidate['wins'] === $winner['wins'] && $candidate['ppg'] > $winner['ppg'])
            ) {
                $winner = $candidate;
                $winnerScore = $candidateScore;
            }
        }

        return $winner;
    }

    private function playerRoleDefinitions(): array
    {
        return [
            ['role' => 'Lead Guard', 'position' => 'PG', 'height' => 186.0, 'weight' => 81.0, 'skill' => 'assists', 'usage' => 1.2],
            ['role' => 'Shot Creator', 'position' => 'SG', 'height' => 193.0, 'weight' => 88.0, 'skill' => 'points', 'usage' => 1.28],
            ['role' => 'Wing Scorer', 'position' => 'SF', 'height' => 198.0, 'weight' => 92.0, 'skill' => 'points', 'usage' => 1.1],
            ['role' => 'Floor Spacer', 'position' => 'G/F', 'height' => 197.0, 'weight' => 90.0, 'skill' => 'points', 'usage' => 0.96],
            ['role' => 'Stretch Four', 'position' => 'PF', 'height' => 204.0, 'weight' => 99.0, 'skill' => 'rebounds', 'usage' => 0.92],
            ['role' => 'Anchor Big', 'position' => 'C', 'height' => 211.0, 'weight' => 108.0, 'skill' => 'rebounds', 'usage' => 0.94],
            ['role' => 'Rim Runner', 'position' => 'C', 'height' => 209.0, 'weight' => 104.0, 'skill' => 'rebounds', 'usage' => 0.82],
            ['role' => 'Bench Gunner', 'position' => 'G', 'height' => 191.0, 'weight' => 85.0, 'skill' => 'points', 'usage' => 0.84],
            ['role' => 'Reserve Guard', 'position' => 'PG', 'height' => 187.0, 'weight' => 82.0, 'skill' => 'assists', 'usage' => 0.72],
            ['role' => 'Utility Forward', 'position' => 'F', 'height' => 201.0, 'weight' => 96.0, 'skill' => 'rebounds', 'usage' => 0.68],
        ];
    }

    private function buildSeasonSchedule(array $teamIds): array
    {
        $rotation = array_values($teamIds);
        $teamCount = count($rotation);
        $half = (int) ($teamCount / 2);
        $firstLeg = [];

        for ($round = 1; $round <= $teamCount - 1; $round++) {
            for ($index = 0; $index < $half; $index++) {
                $left = $rotation[$index];
                $right = $rotation[$teamCount - 1 - $index];
                if (($round + $index) % 2 === 0) {
                    $firstLeg[] = ['round' => $round, 'slot' => $index, 'home_team_id' => $left, 'away_team_id' => $right];
                } else {
                    $firstLeg[] = ['round' => $round, 'slot' => $index, 'home_team_id' => $right, 'away_team_id' => $left];
                }
            }

            $fixed = array_shift($rotation);
            $last = array_pop($rotation);
            array_unshift($rotation, $fixed, $last);
        }

        $secondLeg = [];
        foreach ($firstLeg as $fixture) {
            $secondLeg[] = [
                'round' => $fixture['round'] + ($teamCount - 1),
                'slot' => $fixture['slot'],
                'home_team_id' => $fixture['away_team_id'],
                'away_team_id' => $fixture['home_team_id'],
            ];
        }

        $thirdLeg = [];
        foreach ($firstLeg as $fixture) {
            $thirdLeg[] = [
                'round' => $fixture['round'] + (($teamCount - 1) * 2),
                'slot' => $fixture['slot'],
                'home_team_id' => $fixture['home_team_id'],
                'away_team_id' => $fixture['away_team_id'],
            ];
        }

        return array_merge($firstLeg, $secondLeg, $thirdLeg);
    }

    private function buildSeasonTeamContext(int $seasonId, int $teamId, array $blueprint): array
    {
        $cycle = (($seasonId + $teamId) % 4) - 1.5;
        $drift = $this->signedNoise(5, $seasonId, $teamId, 31);

        return [
            'overall' => $blueprint['overall'] + $drift + (int) round($cycle),
            'offense' => $blueprint['offense'] + $this->signedNoise(3, $seasonId, $teamId, 37),
            'defense' => $blueprint['defense'] + $this->signedNoise(3, $seasonId, $teamId, 43),
            'pace' => $blueprint['pace'] + $this->signedNoise(2, $seasonId, $teamId, 47),
            'attendance' => max(0.12, min(0.42, $blueprint['attendance'] + ($this->signedNoise(3, $seasonId, $teamId, 53) / 100))),
        ];
    }

    private function buildTeamStats(
        int $seasonId,
        int $roundNumber,
        int $teamId,
        array $teamContext,
        array $opponentContext,
        bool $home,
        array $rosterByTeam
    ): array {
        $players = [];
        $roleBoxes = [
            'Lead Guard' => ['rebounds' => 3, 'assists' => 7, 'turnovers' => 2, 'fouls' => 2, 'field_goals_made' => 3, 'field_goals_attempted' => 8, 'three_points_made' => 1, 'three_points_attempted' => 4, 'free_throws_made' => 2, 'free_throws_attempted' => 2],
            'Shot Creator' => ['rebounds' => 4, 'assists' => 3, 'turnovers' => 2, 'fouls' => 2, 'field_goals_made' => 5, 'field_goals_attempted' => 12, 'three_points_made' => 2, 'three_points_attempted' => 6, 'free_throws_made' => 3, 'free_throws_attempted' => 4],
            'Wing Scorer' => ['rebounds' => 4, 'assists' => 2, 'turnovers' => 2, 'fouls' => 2, 'field_goals_made' => 4, 'field_goals_attempted' => 10, 'three_points_made' => 2, 'three_points_attempted' => 5, 'free_throws_made' => 2, 'free_throws_attempted' => 3],
            'Floor Spacer' => ['rebounds' => 3, 'assists' => 2, 'turnovers' => 1, 'fouls' => 2, 'field_goals_made' => 3, 'field_goals_attempted' => 8, 'three_points_made' => 2, 'three_points_attempted' => 5, 'free_throws_made' => 1, 'free_throws_attempted' => 2],
            'Stretch Four' => ['rebounds' => 5, 'assists' => 2, 'turnovers' => 1, 'fouls' => 3, 'field_goals_made' => 3, 'field_goals_attempted' => 7, 'three_points_made' => 1, 'three_points_attempted' => 3, 'free_throws_made' => 2, 'free_throws_attempted' => 2],
            'Anchor Big' => ['rebounds' => 8, 'assists' => 1, 'turnovers' => 2, 'fouls' => 3, 'field_goals_made' => 4, 'field_goals_attempted' => 7, 'three_points_made' => 0, 'three_points_attempted' => 0, 'free_throws_made' => 2, 'free_throws_attempted' => 3],
            'Rim Runner' => ['rebounds' => 6, 'assists' => 1, 'turnovers' => 1, 'fouls' => 3, 'field_goals_made' => 3, 'field_goals_attempted' => 5, 'three_points_made' => 0, 'three_points_attempted' => 0, 'free_throws_made' => 1, 'free_throws_attempted' => 2],
            'Bench Gunner' => ['rebounds' => 2, 'assists' => 2, 'turnovers' => 1, 'fouls' => 2, 'field_goals_made' => 3, 'field_goals_attempted' => 7, 'three_points_made' => 1, 'three_points_attempted' => 4, 'free_throws_made' => 1, 'free_throws_attempted' => 2],
            'Reserve Guard' => ['rebounds' => 2, 'assists' => 4, 'turnovers' => 1, 'fouls' => 1, 'field_goals_made' => 2, 'field_goals_attempted' => 5, 'three_points_made' => 1, 'three_points_attempted' => 3, 'free_throws_made' => 1, 'free_throws_attempted' => 2],
            'Utility Forward' => ['rebounds' => 4, 'assists' => 1, 'turnovers' => 1, 'fouls' => 2, 'field_goals_made' => 2, 'field_goals_attempted' => 4, 'three_points_made' => 0, 'three_points_attempted' => 1, 'free_throws_made' => 1, 'free_throws_attempted' => 2],
        ];

        $team = [
            'points' => 0,
            'rebounds' => 0,
            'assists' => 0,
            'turnovers' => 0,
            'fouls' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
        ];

        foreach ($rosterByTeam[$teamId] as $index => $playerMeta) {
            $base = $roleBoxes[$playerMeta['role']];
            $player = ['person_id' => $playerMeta['person_id']];
            $usageBoost = (int) round(($playerMeta['usage'] - 0.7) * 2);
            $offenseBoost = (int) round(($teamContext['offense'] - $opponentContext['defense']) / 5);
            $paceBoost = max(-1, min(2, (int) round(($teamContext['pace'] + $opponentContext['pace']) / 4)));
            $homeBoost = $home ? 1 : 0;
            $variation = $this->signedNoise(2, $seasonId, $roundNumber, $teamId, $index, 71);

            foreach ($base as $column => $value) {
                $adjustment = match ($column) {
                    'field_goals_made', 'field_goals_attempted' => $usageBoost + $offenseBoost + $homeBoost + $variation,
                    'three_points_made', 'three_points_attempted' => ($playerMeta['role'] === 'Floor Spacer' || $playerMeta['role'] === 'Bench Gunner' ? 1 : 0) + $offenseBoost + $variation,
                    'free_throws_made', 'free_throws_attempted' => max(0, $usageBoost + $homeBoost),
                    'rebounds' => ($playerMeta['skill'] === 'rebounds' ? 1 : 0) + max(-1, (int) round(($teamContext['defense'] - $opponentContext['offense']) / 6)) + $variation,
                    'assists' => ($playerMeta['skill'] === 'assists' ? 1 : 0) + max(-1, $offenseBoost) + $paceBoost,
                    'turnovers' => max(0, (int) round(($opponentContext['defense'] - $teamContext['offense']) / 8)) + max(0, -$variation),
                    'fouls' => max(0, $this->signedNoise(1, $seasonId, $roundNumber, $teamId, $index, 83)),
                    default => 0,
                };

                $player[$column] = max(0, $value + $adjustment);
            }

            $player['three_points_made'] = min($player['three_points_made'], $player['field_goals_made']);
            $player['three_points_attempted'] = max($player['three_points_made'], $player['three_points_attempted']);
            $player['field_goals_attempted'] = max($player['field_goals_made'], $player['field_goals_attempted']);
            $player['free_throws_attempted'] = max($player['free_throws_made'], $player['free_throws_attempted']);
            $player['points'] = (($player['field_goals_made'] - $player['three_points_made']) * 2) + ($player['three_points_made'] * 3) + $player['free_throws_made'];

            foreach ($team as $column => $value) {
                $team[$column] += $player[$column];
            }

            $players[] = $player;
        }

        $team['turnovers'] += max(1, 2 + $this->signedNoise(2, $seasonId, $roundNumber, $teamId, 97));
        $team['fouls'] += max(2, 3 + $this->signedNoise(2, $seasonId, $roundNumber, $teamId, 101));

        return ['team' => $team, 'players' => $players];
    }

    private function applyScoreDelta(array $stats, int $delta): array
    {
        $remaining = max(0, $delta);
        $playerCount = count($stats['players']);
        if ($remaining === 0 || $playerCount === 0) {
            return $stats;
        }

        $priority = [1, 2, 0, 7, 3, 4];
        foreach ($priority as $index) {
            if ($remaining <= 0 || !isset($stats['players'][$index])) {
                continue;
            }

            $bonus = min($remaining, $index === 1 ? 4 : 2);
            $stats['players'][$index]['free_throws_made'] += $bonus;
            $stats['players'][$index]['free_throws_attempted'] += $bonus;
            $stats['players'][$index]['points'] += $bonus;
            $stats['team']['free_throws_made'] += $bonus;
            $stats['team']['free_throws_attempted'] += $bonus;
            $stats['team']['points'] += $bonus;
            $remaining -= $bonus;
        }

        while ($remaining > 0) {
            $index = ($playerCount - 1) - (($remaining - 1) % $playerCount);
            $stats['players'][$index]['free_throws_made'] += 1;
            $stats['players'][$index]['free_throws_attempted'] += 1;
            $stats['players'][$index]['points'] += 1;
            $stats['team']['free_throws_made'] += 1;
            $stats['team']['free_throws_attempted'] += 1;
            $stats['team']['points'] += 1;
            $remaining--;
        }

        return $stats;
    }

    private function buildSeasonNote(string $teamName, int $rank, int $wins, int $losses, int $pointDiff): string
    {
        $tierLine = match (true) {
            $rank === 1 => 'set the pace from opening night and finished clear of the field',
            $rank <= 4 => 'secured home-court position with steady two-way play',
            $rank <= 6 => 'earned a direct playoff berth through the regular season',
            $rank <= 10 => 'stayed in the postseason chase deep into the spring',
            default => 'could not stay in the playoff race long enough',
        };

        return sprintf('%s %s, closing at %d-%d with a %s point differential.', $teamName, $tierLine, $wins, $losses, sprintf('%+d', $pointDiff));
    }

    private function playerPortraitForRole(string $role): string
    {
        return match ($role) {
            'Lead Guard', 'Shot Creator', 'Bench Gunner', 'Reserve Guard' => 'portraits/guard.svg',
            'Wing Scorer', 'Floor Spacer', 'Utility Forward' => 'portraits/wing.svg',
            'Stretch Four' => 'portraits/forward.svg',
            default => 'portraits/big.svg',
        };
    }

    private function signedNoise(int $range, int ...$parts): int
    {
        $seed = 0;
        foreach ($parts as $index => $part) {
            $seed += ($part + 17) * (($index + 3) * 11);
        }

        return ($seed % (($range * 2) + 1)) - $range;
    }

    private function insertRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $quote = $driver === 'mysql' ? '`' : '"';
        $columns = array_keys($rows[0]);
        $quotedColumns = array_map(static fn (string $column): string => $quote . str_replace($quote, $quote . $quote, $column) . $quote, $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $quote . str_replace($quote, $quote . $quote, $table) . $quote,
            implode(', ', $quotedColumns),
            $placeholders,
        );
        $statement = $this->pdo->prepare($sql);

        foreach ($rows as $row) {
            $statement->execute(array_values($row));
        }
    }
}
