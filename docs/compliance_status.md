# Compliance Status

This file is the quick status view against the course brief and the extra professor note.

## Already Covered In This Repo

- 19 relations in the schema.
- At least 10 rows per table in the live archive.
- PHP web application with sessions and CRUD coverage.
- MySQL runtime support through `mysqli` when `EUROLEAGUE_DB_DRIVER=mysql` is set.
- More than 5 complex queries integrated into the site.
- 15 advanced SQL queries packaged in docs/advanced_queries.sql and docs/advanced_queries.md.
- Query pack includes INNER JOIN, LEFT JOIN, RIGHT JOIN, GROUP BY, HAVING, aggregate functions, DISTINCT, ORDER BY, and two subqueries.
- A generated MySQL import file that includes both structure and inserted data: exports/euroleague_mysql_import.sql.
- Local preview fallback to SQLite when MySQL is not configured.

## Still Manual Before Submission

- Import exports/euroleague_mysql_import.sql into phpMyAdmin.
- Run the 15 queries in phpMyAdmin and capture screenshots.
- Export the imported database from phpMyAdmin with both structure and data.
- Put that phpMyAdmin-exported SQL file into the final submission bundle.

## Remaining Strict-Rubric Risk

- The final SQL file still needs to be exported from phpMyAdmin after importing the packaged MySQL file, because the professor asked for a literal phpMyAdmin export artifact.
