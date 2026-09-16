# LastRound

LastRound is an internal kickboxing coaching management application. The Head Coach (Admin) uses it to manage clients, packages, coaches, the training schedule, and gym settings. Coaches sign in only to view their own schedule.

This is not a public website. There is no public registration, payments, or client login in Version 1.

## Stack

- PHP 8.3
- Laravel 13 (standard Blade / controller architecture; same approach as Laravel 12)
- Blade
- MySQL / MariaDB
- Tailwind CSS 4
- Small vanilla JavaScript (navigation, session end-time suggestion)
- PHPUnit

No Filament, Livewire, Inertia, Vue, React, or DataTables.

## Roles

- **Admin / Head Coach** — full access to `/admin/*`. Can also have a coach profile and take sessions.
- **Coach** — `/coach/*` only. Read-only schedule for the signed-in coach.

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+ and npm
- MySQL or MariaDB

## Installation

```bash
cd C:\wamp64\www\lastround
composer install
copy .env.example .env
php artisan key:generate
```

On Linux/macOS use `cp .env.example .env`.

## Environment

```env
APP_NAME=LastRound
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Beirut

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lastround
DB_USERNAME=root
DB_PASSWORD=
```

Create the database:

```sql
CREATE DATABASE lastround CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`APP_TIMEZONE` is the single application timezone (default `Asia/Beirut`). Do not convert times in controllers. For production set `APP_DEBUG=false`.

## Database and storage

```bash
php artisan migrate --seed
php artisan storage:link
```

To rebuild during development:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

`storage:link` is required for coach profile images and the optional application logo.

## Frontend

```bash
npm install
npm run build
```

Watch CSS/JS:

```bash
npm run dev
```

## Run locally

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Default development credentials

Created by the seeder. Change these before using real data.

| Role  | Username | Password | Notes |
|-------|----------|----------|--------|
| Admin | `admin`  | `password` | Head Coach, also has a coach profile |
| Coach | `marcus` | `password` | Example coach |
| Coach | `sofia`  | `password` | Example coach |

Seeded catalog packages:

- 8 Sessions / $150
- 12 Sessions / $250

The demo seeder also creates a small set of clients, purchases, and sessions (Pending / Done / Cancelled) that follow the Version 1 package rules.

## Tests

```bash
php artisan test
```

## Main business flow

Client → Package → Schedule → Pending session → Done → remaining sessions deducted → Recharge (new client package; history kept)

- A client needs an active package with unreserved sessions to be scheduled.
- Pending sessions reserve package capacity.
- Cancelled sessions do not reserve capacity and do not block time slots.
- Marking a session Done consumes exactly one session from the linked client package.
- Reversing Done restores exactly one session and can reactivate a completed package.
- Old sessions stay linked to the package they were created with.

## Settings

Admin Settings (`/admin/settings`) stores:

- Application name and logo
- Business phone, email, address
- Currency (USD in Version 1)
- Default session duration (suggests end time when scheduling)
- Low-session warning threshold
- Admin name, username, email, and password

Read settings through `setting('key')` / `App\Services\SettingsService` (loaded once per request).

## Project layout notes

- Admin routes: `/admin/*` (`admin` middleware)
- Coach routes: `/coach/*` (`coach` middleware; queries always use `auth()->id()`)
- Listings use server-side `paginate()` with 10 / 20 / 50 per page (default 20)
- Session times are stored as time values and displayed as 24-hour `H:i` (for example `18:00`)
- Dates are displayed as `j M Y` (for example `10 Sep 2026`)
