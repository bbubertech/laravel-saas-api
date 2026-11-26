# laravel-saas-api

A multi-tenant REST API on Laravel 13. The goal is a solid backend for a B2B SaaS:
every company (tenant) sees only its own data, each plan unlocks a set of features and
limits, and slow work runs on queues instead of inside the request.

Work in progress. The roadmap below tracks what is done.

## Stack

- PHP 8.3, Laravel 13
- MySQL 8.4, Redis 7 (cache and queues)
- JWT authentication
- Docker Compose for local development
- PHPUnit and Pint on GitHub Actions

## Running locally

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate
```

The API is served at `http://localhost:8080/api/v1`.

Containers:

| Service   | Purpose                                    |
|-----------|--------------------------------------------|
| app       | PHP-FPM running the application            |
| nginx     | HTTP entry point on port 8080              |
| worker    | Queue worker for the `default` and `bulk` queues |
| scheduler | Runs `schedule:work` for periodic jobs     |
| mysql     | Database, exposed on 3307 for local tools  |
| redis     | Cache and queue backend                    |

## Tests

```bash
docker compose exec app php artisan test
```

Tests use an in-memory SQLite database, so they do not touch the MySQL container.

## Roadmap

- [x] Project setup, Docker environment, CI
- [x] Tenants, users and tenant scoping
- [x] JWT auth (login, refresh, logout)
- [ ] Roles and permissions
- [ ] Plans, feature flags and usage limits
- [ ] Audit log
- [ ] Bulk import and export on queues
- [ ] Webhooks with signature checks and retries
- [ ] API documentation
