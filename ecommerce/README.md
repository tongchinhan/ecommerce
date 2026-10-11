# NORD Home — E-commerce (Pure PHP)

Web Development final project. Modern home & living storefront running on **XAMPP** with **Apache**, **MySQL/MariaDB**, and **pure PHP** (PDO). No Laravel, CodeIgniter, WordPress, React, Vue, Bootstrap, or Tailwind.

This repository is currently in the **foundation phase**: project structure, database schema, PDO, configuration, helpers, and authentication.

## Requirements

- XAMPP (Apache + PHP + MySQL/MariaDB)
- PHP 8.0 or newer (tested with the PHP bundled in XAMPP)
- A modern web browser (Chrome, Edge, Firefox, Safari)

## Installation

1. Copy the project to:

   `C:\xampp\htdocs\ecommerce`

2. Open **XAMPP Control Panel**.

3. Start:

   - Apache
   - MySQL

4. Open phpMyAdmin:

   [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

5. Create/import the database:

   - You can skip creating the database by hand. `database.sql` includes `CREATE DATABASE IF NOT EXISTS ecommerce`.
   - In phpMyAdmin click **Import**, choose `C:\xampp\htdocs\ecommerce\database.sql`, then **Go**.
   - If you already created an empty database named `ecommerce`, select it first, then import the same file.

6. Open the site:

   [http://localhost/ecommerce/](http://localhost/ecommerce/)

7. Confirm the stack on the home page and at:

   [http://localhost/ecommerce/database-test.php](http://localhost/ecommerce/database-test.php)

   The test reports whether PHP works, whether MySQL is reachable, and whether the `ecommerce` database exists. It does **not** print database passwords.

## Database credentials

Default XAMPP values live in `config/config.php`:

- `DB_HOST` = `localhost`
- `DB_NAME` = `ecommerce`
- `DB_USER` = `root`
- `DB_PASS` = *(empty)*

If you set a MySQL password or move the project to a real server, change those four constants only. Do not put credentials in other PHP files. The PDO connection is centralized in `config/database.php`.

Also update `BASE_URL` logic in `config/config.php` if the folder is not `/ecommerce`.

## Demo accounts

Passwords are stored with `password_hash()` (bcrypt), never as plain text.

| Role     | Email                 | Password      |
|----------|-----------------------|---------------|
| Admin    | admin@example.com     | Admin@123     |
| Customer | customer@example.com  | Customer@123  |

- Customer sign-in: [http://localhost/ecommerce/login.php](http://localhost/ecommerce/login.php)
- Admin sign-in: [http://localhost/ecommerce/admin/login.php](http://localhost/ecommerce/admin/login.php)

## Project layout

- `config/` — application config, constants, PDO
- `includes/` — bootstrap, header/footer, helpers, CSRF, auth guards
- `account/` — customer account routes (guarded)
- `admin/` — admin routes (admin-only)
- `assets/` — CSS and JavaScript
- `uploads/` — product, post, and banner files
- `database.sql` — schema + sample data

## Security notes (foundation)

- SQL: PDO prepared statements only
- XSS: escape output with `e()`
- CSRF: tokens on authentication forms
- Passwords: `password_hash()` / `password_verify()`
- Sessions: HTTP-only cookies, strict mode, ID regeneration on login

## What is not built yet

Storefront catalog, cart, checkout, payments, and the full admin CRUD screens are **intentionally not implemented** in this phase. Reserved PHP files exist so the structure is complete; they do not pretend to be finished features.
