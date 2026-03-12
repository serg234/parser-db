# Laravel Socket Test

## Run project

### 1. Update `.env.docker` in the project root

Set your user and group IDs from your system.

Example:

DOCKER_UID=1000
DOCKER_GID=1000

You can check your values with:

id -u
id -g

This is required so Docker containers create files with correct permissions.

---

### 2. Install and start the project

Run:

make install

This command will:

- build Docker containers
- start containers
- create Laravel `.env`
- install Composer dependencies
- generate application key
- run database migrations

---

### 3. Open in browser

http://localhost:8000

### 4. If `make install` does not work

```bash
docker compose --env-file .env.docker build
docker compose --env-file .env.docker up -d
cp -n .env.example .env || true
docker compose --env-file .env.docker exec app composer install
docker compose --env-file .env.docker exec app php artisan key:generate
docker compose --env-file .env.docker exec app php artisan migrate
```

---
---
### 5. Start queue worker

Run in a separate terminal:

```bash
docker compose exec app php artisan queue:work
```

---


