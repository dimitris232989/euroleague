<?php
declare(strict_types=1);

final class PuzzleService
{
    public function __construct(private readonly AppDbConnection $db)
    {
    }

    public function listPuzzles(): array
    {
        $sql = 'SELECT gp.grid_puzzle_id, gp.puzzle_name, gp.puzzle_date, gp.status, s.season_label
                FROM grid_puzzles gp
                JOIN seasons s ON s.season_id = gp.season_id
                ORDER BY gp.puzzle_date DESC, gp.grid_puzzle_id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function loadBoard(int $puzzleId): ?array
    {
        $puzzle = $this->db->prepare('SELECT gp.*, s.season_label FROM grid_puzzles gp JOIN seasons s ON s.season_id = gp.season_id WHERE gp.grid_puzzle_id = ?');
        $puzzle->execute([$puzzleId]);
        $puzzleRow = $puzzle->fetch();
        if ($puzzleRow === false) {
            return null;
        }

        $rowStatement = $this->db->prepare(
            'SELECT gpr.row_position, gpr.team_id, t.team_name, t.short_name, t.logo_url, t.primary_color, t.secondary_color, gpr.clue_text
             FROM grid_puzzle_rows gpr
             JOIN teams t ON t.team_id = gpr.team_id
             WHERE gpr.grid_puzzle_id = ?
             ORDER BY gpr.row_position'
        );
        $rowStatement->execute([$puzzleId]);
        $rows = $rowStatement->fetchAll();

        $columnStatement = $this->db->prepare(
            'SELECT column_position, stat_name, comparison_operator, target_value, units, clue_text
             FROM grid_puzzle_columns
             WHERE grid_puzzle_id = ?
             ORDER BY column_position'
        );
        $columnStatement->execute([$puzzleId]);
        $columns = $columnStatement->fetchAll();

        $answerStatement = $this->db->prepare(
            'SELECT gpa.row_position, gpa.column_position, gpa.person_id, CONCAT(p.first_name, " ", p.last_name) AS player_name
             FROM grid_puzzle_answers gpa
             JOIN people p ON p.person_id = gpa.person_id
             WHERE gpa.grid_puzzle_id = ?'
        );
        $answerStatement->execute([$puzzleId]);
        $answerMap = [];
        foreach ($answerStatement->fetchAll() as $answer) {
            $answerMap[(int) $answer['row_position']][(int) $answer['column_position']][] = $answer;
        }

        $teamPlayerOptions = [];
        foreach ($rows as $row) {
            $statement = $this->db->prepare(
                'SELECT ra.person_id AS value, CONCAT(p.first_name, " ", p.last_name) AS label
                 FROM roster_assignments ra
                 JOIN people p ON p.person_id = ra.person_id
                 WHERE ra.team_id = ? AND ra.season_id = ?
                 ORDER BY p.last_name, p.first_name'
            );
            $statement->execute([(int) $row['team_id'], (int) $puzzleRow['season_id']]);
            $teamPlayerOptions[(int) $row['team_id']] = $statement->fetchAll();
        }

        return [
            'puzzle' => $puzzleRow,
            'rows' => $rows,
            'columns' => $columns,
            'answer_map' => $answerMap,
            'team_player_options' => $teamPlayerOptions,
        ];
    }

    public function grade(int $puzzleId, array $selections): array
    {
        $board = $this->loadBoard($puzzleId);
        if ($board === null) {
            throw new InvalidArgumentException('Puzzle not found.');
        }

        $result = [
            'selected' => [],
            'correct' => [],
            'score' => 0,
            'total' => count($board['rows']) * count($board['columns']),
        ];

        foreach ($board['rows'] as $row) {
            foreach ($board['columns'] as $column) {
                $cellKey = $row['row_position'] . '-' . $column['column_position'];
                $selected = isset($selections[$cellKey]) ? (int) $selections[$cellKey] : 0;
                $accepted = $board['answer_map'][(int) $row['row_position']][(int) $column['column_position']] ?? [];
                $acceptedIds = array_map(static fn (array $answer): int => (int) $answer['person_id'], $accepted);
                $isCorrect = $selected > 0 && in_array($selected, $acceptedIds, true);

                $result['selected'][$cellKey] = $selected;
                $result['correct'][$cellKey] = $isCorrect;
                if ($isCorrect) {
                    $result['score']++;
                }
            }
        }

        return $result;
    }
}