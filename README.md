# DrukInvoice

Bhutan Foundation Grant Management System built with PHP and MySQL.

## Features
- Dashboard with grant infographics (active, expiring, progress, notifications, closure).
- Full grant lifecycle management (create, update, progress logs, close).
- Portfolio filtering and search.

## Setup
1. Create a MySQL database and tables.
   ```bash
   mysql -u root -p < schema.sql
   ```
2. Configure database credentials via environment variables if needed:
   - `DB_HOST` (default: 127.0.0.1)
   - `DB_NAME` (default: druk_grants)
   - `DB_USER` (default: root)
   - `DB_PASS` (default: empty)
3. Start a PHP server from the repo root:
   ```bash
   php -S localhost:8000
   ```
4. Visit `http://localhost:8000`.

## Notes
- The system uses PDO for database access.
- Update the time zone in `db.php` if needed.
