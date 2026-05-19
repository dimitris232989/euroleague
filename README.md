# euroleague

PHP Euroleague archive with:

- MySQL plus `mysqli` runtime support for assignment submission
- 15 packaged advanced SQL queries plus live stats pages built on multi-table queries
- an in-app Advanced Queries desk in the Data Desk for browsing the 15-query pack and running it in MySQL mode
- a public-facing stats site, a data desk, and a playable grid puzzle
- assignment support files for phpMyAdmin import/export and advanced-query submission

## Project structure

- `app/` contains the PHP bootstrap, metadata, seeder, and puzzle service
- `docs/` contains the advanced query pack and submission checklist
- `scripts/` contains export and verification helpers
- `public/index.php` is the entry point
- `public/assets/styles.css` contains the UI styling
- `exports/euroleague_mysql_import.sql` is the MySQL/phpMyAdmin import file

## Project requirements

- PHP 8.1 or newer
- MySQL Server or MariaDB Server
- PHP with the `mysqli` extension enabled

Before starting the application, the reviewer should have the following installed on the machine:

- PHP 8.1 or newer, with the `php` command available in the terminal
- MySQL Server or MariaDB Server running locally or on an accessible host
- The PHP `mysqli` extension enabled so PHP can connect to the project database

`phpMyAdmin` is not required to launch the web application. It is only needed for the separate assignment workflow that involves importing `exports/euroleague_mysql_import.sql`, running the packaged SQL queries there, and producing a phpMyAdmin export for submission.

This project does not require Composer, Node.js, or npm.

## Running the application

### Quick start

The easiest way to run the project is from the repository root with:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start_mysql_app.ps1
```

This is the recommended startup path. It:

- starts MariaDB if port `3306` is not already in use
- sets the `EUROLEAGUE_DB_*` environment variables for the session
- starts the PHP development server on `http://127.0.0.1:8000`

When the script is running successfully, open `http://127.0.0.1:8000` in a browser.

To stop the program, press `Ctrl+C` in the terminal running the script. If MariaDB was started by the script and you want to stop it as well, end the `mariadbd.exe` process or close it from Task Manager.

### Manual start

Use this only if you do not want to use the startup script.

1. Start MariaDB in one PowerShell window and leave that window open:

```powershell
& 'C:\Program Files\MariaDB 12.2\bin\mariadbd.exe' --defaults-file='C:\Program Files\MariaDB 12.2\data\my.ini' --console
```

2. Open a second PowerShell window in the project root and set the database environment variables in that same window:

```powershell
$env:EUROLEAGUE_DB_DRIVER = 'mysql'
$env:EUROLEAGUE_DB_HOST = '127.0.0.1'
$env:EUROLEAGUE_DB_PORT = '3306'
$env:EUROLEAGUE_DB_NAME = 'euroleague'
$env:EUROLEAGUE_DB_USER = 'root'
$env:EUROLEAGUE_DB_PASS = ''
```

3. In that same second PowerShell window, start PHP:

```powershell
php -S 127.0.0.1:8000 -t public
```

4. Open `http://127.0.0.1:8000` in a browser.

The configured database user must be able to create and drop the database named in `EUROLEAGUE_DB_NAME`.

The project runs in MySQL mode only. If the configured MySQL or MariaDB server cannot be reached, the application stops at startup with a connection error.

On first load, the application connects to the configured database, creates the target database if required, and rebuilds it from `exports/euroleague_mysql_import.sql` when the archive tables are missing or empty.

### Common startup issue

If the browser says `127.0.0.1 refused to connect`, MariaDB may be running but the PHP server is not. In that case, start PHP from the project root with:

```powershell
php -S 127.0.0.1:8000 -t public
```

### Runtime notes

- The recommended document root is `public`, with `public/index.php` as the entry point.
- The `Reset Fake Data` button in the Data Desk rebuilds the active database.
- Environment variables set with `$env:...` apply only to the current PowerShell window, so set them in the same terminal where you run `php -S`.
- If MySQL or MariaDB is unavailable, the application will not start.

## Deploying live with MySQL

This repository can be deployed as a normal PHP app with a managed MySQL database. No Supabase rewrite is needed.

The repository now includes a `Dockerfile` that serves the `public/` folder directly through PHP's built-in web server, and the PHP bootstrap accepts either the project-specific `EUROLEAGUE_DB_*` variables or common managed-MySQL variables such as:

- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQL_URL`

### Recommended option: Railway + MySQL

Railway is the simplest fit for this codebase because it can host both the PHP container and a MySQL service in one project.

1. Push this repository to GitHub.
2. Create a new Railway project.
3. Add a MySQL service to that Railway project.
4. Add a second service from your GitHub repository.
5. Railway will detect the `Dockerfile` and deploy the PHP app automatically.
6. In the PHP app service, add either one URL variable or the five explicit MySQL variables by referencing the MySQL service variables.

Single-variable option:

```text
MYSQL_URL=${{MySQL.MYSQL_URL}}
```

Explicit-variable option:

```text
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
```

7. Open the generated Railway domain for the PHP service.

On the first request, the app checks whether the target database already contains the archive tables. If the database is empty, it imports `exports/euroleague_mysql_import.sql` automatically. After that, the hosted MySQL database persists your data across restarts and redeploys.

### Important deployment note

Managed MySQL providers often do not allow `CREATE DATABASE` or `DROP DATABASE` for the application user. The app now handles that correctly by seeding inside the configured database instead of requiring database-level admin access. The configured database should still exist before the first deployment.

## Assignment helpers

Generate a MySQL/phpMyAdmin import file from the seeded archive:

```powershell
php scripts/export_mysql_import.php
```

Validate the main submission support files and row counts:

```powershell
php scripts/verify_submission_requirements.php
```

Use the report scaffold when assembling the written submission:

```text
docs/report_template.md
```

## Notes

- Use the `Reset Fake Data` button in the Data Desk to rebuild the active database.
- Use the `Advanced Queries` page in the Data Desk to inspect the assignment query pack and execute it live when the app is running in MySQL mode.
- The CRUD interface works across all tables, including composite-key tables.
- The puzzle screen uses `grid_puzzles`, `grid_puzzle_rows`, `grid_puzzle_columns`, `grid_puzzle_cells`, and `grid_puzzle_answers` directly.
- The assignment brief asks for at least 5 complex queries integrated into the web app. The public stats pages already exceed that, and the full 15-query pack is documented in `docs/advanced_queries.sql` and `docs/advanced_queries.md`.
- `docs/advanced_queries.sql` and `docs/advanced_queries.md` package the 15-query assignment deliverable.
- `docs/report_template.md` provides a submission-ready outline for the written report.
- `docs/submission_checklist.md` includes the extra phpMyAdmin export requirement.