# Yamato Laravel API

![Laravel 12](https://img.shields.io/badge/Laravel-12-red)
![Docker Ready](https://img.shields.io/badge/Docker-ready-blue)
![Postgres 16](https://img.shields.io/badge/Postgres-16-blue)
![License: MIT](https://img.shields.io/badge/License-MIT-green)

A modern Laravel 12 backend intended for consumption by a React (Vite) SPA or other clients. 
This repo is Docker-friendly and ships with a dev stack using **Nginx + PHP-FPM (8.2) + Postgres 16 + Redis + Mailpit**.

---

## Contents
- [Overview](#overview)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Quick Start (Docker, dev-friendly)](#quick-start-docker-dev-friendly)
- [Environment Variables](#environment-variables-minimum)
- [Database](#database)
- [Queues & Jobs](#queues--jobs)
- [Running Tests](#running-tests)
- [Useful Make Targets](#useful-make-targets)
- [Troubleshooting](#troubleshooting)
- [Project Structure](#project-structure-typical)
- [License](#license)

---

## Overview

Yamato is a RESTful API that exposes resources for the application domain (auth, users, files, etc.). 
It targets Laravel 12 and follows familiar Laravel conventions for controllers, requests, resources, and policies.

---

## Architecture

```
Yamato-Laravel-API/
├─ app/
│  ├─ Http/
│  │  ├─ Controllers/        # API controllers (Auth, Users, etc.)
│  │  ├─ Middleware/
│  │  ├─ Requests/           # Form Request validators
│  │  └─ Resources/          # API Resources (transformers)
│  ├─ Models/                # Eloquent models
│  └─ Providers/
├─ bootstrap/
├─ config/
├─ database/
│  ├─ factories/
│  ├─ migrations/            # Schema migrations
│  └─ seeders/
├─ public/                   # Public web root (index.php)
├─ resources/
├─ routes/
│  ├─ api.php                # API routes
│  └─ web.php
├─ storage/
├─ tests/
└─ README.md
```

---

## Requirements

- Docker Engine and Compose v2
- Git
- (Optional) Node 18+ if you run the SPA locally

---

## Quick Start (Docker, dev-friendly)

This API is intended to be developed with the **bind-mounted** Docker setup so code edits are instant (no rebuilds). 

> ⚠️ This repo expects the [Yamato Docker Stack](https://github.com/abrahamVado/Yamato-Docker)  
> Or you can copy the `docker-compose.yml` and configs from that repo into your project.

### 1) Clone the API

```bash
git clone https://github.com/abrahamVado/Yamato-Laravel-API.git yamato-api
cd yamato-api
```

### 2) Create and configure `.env`

```bash
cp .env.example .env
# Then adjust DB, Redis, and Mailpit values as needed
```

### 3) Start the Docker stack

```bash
docker compose up -d --build postgres redis mailpit app nginx
```

### 4) Install & migrate

```bash
docker compose exec app bash -lc 'composer install && php artisan key:generate && php artisan migrate && php artisan storage:link || true'
```

---

## Environment Variables (minimum)

```dotenv
APP_NAME=Yamato
APP_ENV=local
APP_KEY=base64:xxx
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=yamato
DB_USERNAME=postgres
DB_PASSWORD=secret123

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_USERNAME=null
MAIL_PASSWORD=null

SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost
```

### JSON Web Key Set (JWKS)

Configure signing keys for the JWKS endpoint via `config/jwks.php` or environment variables:

- `JWKS_PUBLIC_KEY`: Inline RSA public key (PEM) surfaced by the JWKS endpoint.
- `JWKS_KID`: Optional key identifier published alongside the key.
- `JWKS_ALG`: Advertised algorithm for the key (default `RS256`).
- `JWKS_USE`: Intended usage for the key (default `sig`).

You may also reference PEM file paths in `config/jwks.php` and append additional keys by extending the `keys` array.

---

## Database

Run migrations:

```bash
docker compose exec app php artisan migrate
```

Seed data:

```bash
docker compose exec app php artisan db:seed
```

Import Postgres dump:

```bash
docker compose exec -T postgres psql -U postgres -d yamato < dump.sql
```

---

## Queues & Jobs

Start a worker:

```bash
docker compose exec -d app php artisan queue:work
```

Stop workers:

```bash
docker compose exec app php artisan queue:restart
```

---

## Running Tests

```bash
docker compose exec app bash -lc './vendor/bin/phpunit'
```

---

## Useful Make Targets

| Command      | Description                  |
|--------------|------------------------------|
| `make up`    | Build & start containers     |
| `make down`  | Stop containers              |
| `make migrate` | Run Laravel migrations     |
| `make fresh` | Fresh migrate + seed         |
| `make shell` | Enter PHP container shell    |
| `make logs`  | Tail logs from all services  |

---

## Troubleshooting

**502 Bad Gateway**  
Usually missing `APP_KEY` or misconfigured `.env`.  
Fix: `docker compose exec app php artisan key:generate`

**Permissions**  
If writes fail in `storage/` or `bootstrap/cache/`:  
```bash
docker compose exec app bash -lc "chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rw storage bootstrap/cache"
```

---

## Project Structure (typical)

```
app/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
  Policies/
bootstrap/
config/
database/
  migrations/
  seeders/
public/
resources/
routes/
  api.php
  web.php
storage/
tests/
```

---

## License

MIT
