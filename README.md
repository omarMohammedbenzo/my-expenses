# my-expenses

A personal finance tracker: expenses, income, accounts and balances across
institutions, transfers, deposits, gifts, and gold holdings valued against the
live Egyptian gold market. Laravel + Inertia + Vue 3, Arabic/English UI.

## Requirements

- PHP 8.3+ with the usual Laravel extensions (plus `pdo_sqlite` for the default setup)
- Composer 2
- Node.js 20+ and npm

## Getting started

```bash
git clone <repo-url> my-expenses
cd my-expenses
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
touch database/database.sqlite   # Windows: type nul > database\database.sqlite
php artisan migrate
php artisan db:seed              # default expense categories + a test user
npm install
```

Then run everything (server, queue worker, logs, Vite) with:

```bash
composer run dev
```

The app is served at http://localhost:8000. Register a new account from the
welcome page, or sign in with the seeded user (`test@example.com`, password
`password`).

No API keys are needed. Gold prices are scraped from iSagha's public price
page at request time and cached; if the fetch fails the app falls back to the
last good snapshot, so it still works offline.

### MySQL instead of SQLite

Set these in `.env` and re-run `php artisan migrate`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_expenses
DB_USERNAME=root
DB_PASSWORD=
```

## Tests

```bash
composer run test
```

## Notes for your own data

Every row is scoped to the signed-in user, and the database itself is never
committed — `.env` and `database/*.sqlite` are gitignored. Your own figures
stay on your machine.
