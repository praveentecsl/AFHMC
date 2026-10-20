Air Force Health Management Centre (AFHMC) Web Application

Project Files:
- index.php              Main single-form PHP web application with 5 printable pages
- db.php                 PDO database connection & persistence logic (MySQL + SQLite fallback)
- schema.sql             MySQL DDL script to create database `afhmc_db` and `afhmc_applications` table
- assets/slaf-logo.png   Sri Lanka Air Force logo emblem
- assets/afhmc-bird.png  AFHMC bird logo emblem
- data/submissions.json  JSON file backup for form submissions

Database Setup:
1. MySQL / MariaDB (XAMPP phpMyAdmin):
   - Open phpMyAdmin (http://localhost/phpmyadmin/).
   - Click "Import" and select `schema.sql`, then click "Go".
   - Or run `mysql -u root < schema.sql`.
   - The application automatically connects to `afhmc_db` on `127.0.0.1` (username: root, no password by default).
2. SQLite (Automatic Zero-Config Fallback):
   - If MySQL service is stopped or unreachable, `db.php` automatically creates `data/afhmc.db` and stores submissions seamlessly.

XAMPP Deployment:
1. Ensure this `afhmc_website` directory is located in `C:\xampp\htdocs\afhmc_website`.
2. Start Apache and MySQL modules in the XAMPP Control Panel.
3. Open http://localhost/afhmc_website/ in your web browser.

Features:
- Single master form wrapping all 5 pages.
- Print button on Page 5 prints the entire 5-page document sequentially.
- Direct database insertion for both Membership Application and Health Screening data.
