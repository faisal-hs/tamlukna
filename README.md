# Tamlukna (تملّكنا)

> اعرف عقارك قبل تشتريه

Saudi real-estate decision platform — monorepo. Current status: see [STATUS.md](STATUS.md).

| Path | Purpose |
|---|---|
| `backend/` | Laravel 12 API (`/api/v1`) |
| `infra/` | Docker and nginx configuration |
| `docs/` | API conventions, decisions, architecture |

## Requirements

Docker Engine with Compose v2, Git and `openssl`. **Host PHP is not needed** —
everything runs in containers.

## First-time setup

> Clone into a **new directory that is not inside another checkout**. The Compose
> project name is fixed (`tamlukna`), so two copies on one machine share containers
> and volumes unless you give the second copy its own name
> (`docker compose -p tamlukna2 …`) and different ports in its `.env`.

```bash
git clone https://github.com/faisal-hs/tamlukna.git && cd tamlukna

cp .env.example .env
DB_PASS="$(openssl rand -hex 24)"; ROOT_PASS="$(openssl rand -hex 24)"
sed -i "s|^MYSQL_PASSWORD=.*|MYSQL_PASSWORD=$DB_PASS|; s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=$ROOT_PASS|" .env
sed -i "s|^HOST_UID=.*|HOST_UID=$(id -u)|; s|^HOST_GID=.*|HOST_GID=$(id -g)|" .env

cp backend/.env.example backend/.env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" backend/.env
unset DB_PASS ROOT_PASS; chmod 600 .env backend/.env

docker compose build app
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

## Daily use

```bash
docker compose up -d                                  # start (background)
docker compose ps                                     # mysql/redis should be "healthy"
curl http://127.0.0.1:8080/api/v1/health              # readiness check
docker compose logs -f app nginx                      # container logs
tail -f backend/storage/logs/laravel-$(date +%F).log  # application log
docker compose stop                                   # stop, keep data
```

| Service | Address |
|---|---|
| API | `http://127.0.0.1:8080/api/v1` |
| MySQL (GUI clients) | `127.0.0.1:33061`, user `tamlukna`, password = `DB_PASSWORD` in `backend/.env` |

Redis is internal to Docker and not published to the host.

## Running commands

Always run PHP tooling **inside the container**:

```bash
docker compose exec app php artisan <command>   # migrate, route:list, tinker, ...
docker compose exec app composer test           # test suite (uses tamlukna_test)
docker compose exec app composer lint           # code style check (Pint)
docker compose exec app composer format         # fix code style
docker compose exec app composer analyse        # static analysis (Larastan)
```

Tip: `alias art='docker compose exec app php artisan'` in `~/.bashrc`.

## Configuration

| File | Purpose |
|---|---|
| `.env` | Docker Compose: ports, UID/GID, MySQL credentials |
| `backend/.env` | Laravel: app, database, Redis, CORS, rate limits, locale |

`MYSQL_PASSWORD` (`.env`) and `DB_PASSWORD` (`backend/.env`) must match.
MySQL applies the password **only when its data volume is first created** —
editing `.env` later does not change it. Keep `DB_HOST=mysql` and
`DB_USERNAME=tamlukna`. Never commit either `.env` file.

## Troubleshooting

**`Access denied for user 'tamlukna'`** — the password in `backend/.env` differs from
the one stored in MySQL. Reset MySQL's password to match, without losing data:

```bash
DB_PASS="$(grep '^DB_PASSWORD=' backend/.env | cut -d= -f2-)"
docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot' <<SQL
ALTER USER 'tamlukna'@'%' IDENTIFIED BY '${DB_PASS}';
FLUSH PRIVILEGES;
SQL
sed -i "s|^MYSQL_PASSWORD=.*|MYSQL_PASSWORD=${DB_PASS}|" .env; unset DB_PASS
```

**`Class "Redis" not found`** — you ran `php artisan …` (or `artisan serve`) on the host.
Use `docker compose exec app php artisan …` and the API on port 8080.

**`502 Bad Gateway`** — the app container is down: `docker compose logs app`.

**Port already in use** — change `APP_PORT` or `FORWARD_DB_PORT` in `.env`, then `docker compose up -d`.

**Danger:** `docker compose down -v` permanently deletes the database and Redis
volumes. Plain `docker compose down` keeps them.
