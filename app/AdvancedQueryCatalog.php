<?php
declare(strict_types=1);

final class AdvancedQueryCatalog
{
    private ?array $queries = null;

    public function __construct(
        private readonly string $sqlPath,
        private readonly string $markdownPath,
    ) {
    }

    public function all(): array
    {
        if ($this->queries !== null) {
            return $this->queries;
        }

        $sqlContent = file_get_contents($this->sqlPath);
        $markdownContent = file_get_contents($this->markdownPath);
        if ($sqlContent === false || $markdownContent === false) {
            throw new RuntimeException('Unable to read the advanced query pack.');
        }

        $sqlQueries = $this->parseSqlQueries($sqlContent);
        $markdownMeta = $this->parseMarkdownMeta($markdownContent);

        $queries = [];
        foreach ($sqlQueries as $id => $query) {
            $meta = $markdownMeta[$id] ?? [];
            $queries[$id] = [
                'id' => $id,
                'title' => $meta['title'] ?? $query['title'],
                'summary' => $meta['summary'] ?? $query['title'],
                'explanation' => $meta['explanation'] ?? 'Advanced reporting query.',
                'screenshot_note' => $meta['screenshot_note'] ?? 'Capture the result grid in phpMyAdmin after running the query.',
                'sql' => $query['sql'],
                'features' => $this->detectFeatures($query['sql']),
            ];
        }

        ksort($queries);
        $this->queries = $queries;

        return $this->queries;
    }

    public function find(int $id): ?array
    {
        $queries = $this->all();
        return $queries[$id] ?? null;
    }

    private function parseSqlQueries(string $content): array
    {
        $matches = [];
        preg_match_all('/-- Query\s+(\d+):\s*(.+?)\R(.*?)(?=\R-- Query\s+\d+:|\z)/si', $content, $matches, PREG_SET_ORDER);

        $queries = [];
        foreach ($matches as $match) {
            $id = (int) $match[1];
            $queries[$id] = [
                'title' => trim($match[2]),
                'sql' => trim($match[3]),
            ];
        }

        return $queries;
    }

    private function parseMarkdownMeta(string $content): array
    {
        $matches = [];
        preg_match_all(
            '/## Query\s+(\d+)\R\R(.+?)\R\R```sql.*?```\R\RExplanation:\s*(.+?)\R\RScreenshot:\s*(.+?)(?=\R\R## Query\s+\d+|\z)/si',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $queries = [];
        foreach ($matches as $match) {
            $id = (int) $match[1];
            $summary = trim(preg_replace('/\s+/', ' ', $match[2]) ?? $match[2]);
            $queries[$id] = [
                'title' => rtrim($summary, '.'),
                'summary' => $summary,
                'explanation' => trim(preg_replace('/\s+/', ' ', $match[3]) ?? $match[3]),
                'screenshot_note' => trim(preg_replace('/\s+/', ' ', $match[4]) ?? $match[4]),
            ];
        }

        return $queries;
    }

    private function detectFeatures(string $sql): array
    {
        $sqlUpper = strtoupper($sql);
        $features = [];
        $featureMap = [
            'JOIN' => 'JOIN',
            'LEFT JOIN' => 'LEFT JOIN',
            'RIGHT JOIN' => 'RIGHT JOIN',
            'HAVING' => 'HAVING',
            'DISTINCT' => 'DISTINCT',
            'GROUP BY' => 'GROUP BY',
            'COUNT(' => 'COUNT',
            'AVG(' => 'AVG',
            'SUM(' => 'SUM',
            'MAX(' => 'MAX',
            'MIN(' => 'MIN',
        ];

        foreach ($featureMap as $needle => $label) {
            if (str_contains($sqlUpper, $needle)) {
                $features[] = $label;
            }
        }

        if (preg_match('/\bFROM\s*\(/i', $sql) === 1 || preg_match('/\bIN\s*\(\s*SELECT\b/i', $sql) === 1) {
            $features[] = 'Subquery';
        }

        return array_values(array_unique($features));
    }
}