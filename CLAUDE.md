# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development server (server + queue + logs + vite in parallel)
composer run dev

# Run migrations
php artisan migrate

# Run migrations + seeders (creates default user, insurers, roles)
php artisan migrate --seed

# Run tests (Pest)
php artisan test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Code style (Laravel Pint)
./vendor/bin/pint

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

## Architecture

Laravel 11 API backend. No views — pure JSON API consumed by a Vue frontend.

**Two databases:**
- `default` — MySQL, local application data (admissions, invoices, devolutions, etc.)
- `external_db` — PostgreSQL on port 5433, schema `sisclin`, read-only source of truth (Sisclin hospital system). Always referenced as `DB::connection('external_db')->table('sisclin.<table>')`.

**Request flow:** `routes/api.php` → Controller → Repository → Model (Eloquent). Controllers that query `external_db` directly (like `CustomQueryController`) skip the repository layer.

**All routes require `auth:api` + `role:dev|admin`** (JWT via `tymon/jwt-auth`, roles via `spatie/laravel-permission`). The only public routes are `/api/auth/login` and `/api/auth/register`.

**Response format:** All responses go through `ApiResponseClass::sendResponse($data, $message, $code)` which wraps results as `{ success, data, status, message }`. Use `ApiResponseClass::rollback($e)` inside DB transactions to roll back and throw.

**Repository pattern:** Every model has a corresponding `Interface` and `Repository`. `BaseRepository` provides `getAll`, `getById`, `store`, `update`, `delete`, `restore`, `bulkStore`, `getPaginated`, `getDateRange`. Repositories are instantiated directly in controllers (not bound via service container, except `ApiSisclinService`).

**CompressResponse middleware** (alias `compress`) is applied to heavy `CustomQueryController` endpoints — it gzip-encodes responses when the client sends `Accept-Encoding: gzip`.

## Devolutions domain

Devoluciones have two data paths:

1. **Read-only from Sisclin** via `CustomQueryController` — `POST /api/devolutions_by_date_range` and `POST /api/devolutions_by_invoice_numbers`. These query `external_db` in real time and return computed fields (`is_paid`, `paid_admission`) calculated at the **atención (admission) level**: if any comprobante of that atención has a matching pago_seguro, all its devolutions are considered paid.

2. **Local persistence** via `DevolutionController` (`/api/devolutions`) — CRUD on the local `devolutions` table. Data arrives here through `DevolutionSyncService` (not yet exposed via HTTP or Artisan command — needs to be wired up).

The `devolutions` table has two groups of columns: own fields (`type`, `reason`, `period`, `status`, `is_paid`, `is_uncollectible`) and fields denormalized from Sisclin (`sisclin_id`, `patient_name`, `insurer_name`, `admission_number`, etc.). `sisclin_id` is the upsert key in `DevolutionSyncService`.

## Seeders

`DatabaseSeeder` seeds: one hardcoded admin user, `InsurersTableSeeder`, `UserSeeder`, `RolesSeeder`. No devolution or admission seed data exists — those tables start empty.
