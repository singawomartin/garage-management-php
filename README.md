# Garage Management System

A simple garage workshop dashboard built with PHP, MySQL, HTML, CSS and JavaScript.

## Features
- Dashboard totals for customers, vehicles and service jobs.
- Add customers and their contact details.
- Register vehicles and link them to owners.
- Create repair/service jobs with a cost and status.
- View recent service records.

## Run locally with XAMPP
1. Copy this folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Select **Import** and import `database/database.sql`.
5. Open `api.php` and check the database settings at the top. Default: MySQL user `root`, blank password. Change only if your local MySQL account has a password.
6. Visit `http://localhost/garage-management-php/`.

Do not open `index.html` by double-clicking it from File Explorer. Use the localhost URL so its JavaScript can call PHP through Apache.

## Structure
- `api.php` - PHP API and database queries.
- `index.html` - page layout and browser JavaScript.
- `assets/css/style.css` - styling.
- `database/database.sql` - database and table definitions.


## Important
This is a learning project, not ready for real customer use. Add login/authentication, user roles, CSRF protection, audit logs, backups and production security before deploying for real use.
