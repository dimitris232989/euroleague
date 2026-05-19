# Submission Checklist

Use this file as the assignment closeout list.

## Database And Data

- Keep euroleague_schema.sql as the source schema used by the app.
- Generate exports/euroleague_mysql_import.sql with scripts/export_mysql_import.php.
- Import exports/euroleague_mysql_import.sql into phpMyAdmin.
- Verify that all tables import without errors and data appears in each table.
- Export the imported MySQL database from phpMyAdmin with both structure and data.
- Submit that phpMyAdmin-exported SQL file to satisfy the professor's import/export requirement.

## Advanced Queries

- Use docs/advanced_queries.sql as the query pack.
- Use docs/advanced_queries.md in the report for explanations.
- Run all 15 queries in phpMyAdmin.
- Capture one screenshot per query output.
- Paste each screenshot into the report next to the matching explanation.

## Web Application

- Capture screenshots of the homepage, season page, club page, player page, playoffs page, data desk, and grid page.
- Mention that the site integrates complex standings, awards, games, playoffs, and player queries directly into the interface.
- Mention sessions, CRUD coverage, and seeded data counts.

## Report Sections

- Start from docs/report_template.md.
- Cover page.
- System description and objectives.
- ERD.
- SQL implementation scripts.
- Sample data description.
- 15 SQL queries with explanations and outputs.
- Web application screenshots.

## Important Note

- The professor's wording specifically says the final submitted SQL file should be exported from phpMyAdmin. The generated file in exports/euroleague_mysql_import.sql is the import source. The final submission artifact should be the phpMyAdmin export you create after importing it.
- The web app now supports strict MySQL mode through mysqli when EUROLEAGUE_DB_DRIVER=mysql is set. Use that mode for screenshots and any live demonstration tied to the rubric.