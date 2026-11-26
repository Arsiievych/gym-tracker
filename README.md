# GymTracker

GymTracker has a [React and TypeScript frontend](web/) and a [Laravel API](api/). For local development, Vite runs on your machine and forwards requests under `/api/` to Laravel. Docker Compose runs Laravel and its supporting services.

## Local setup

Run each command from the repository root, the directory containing the [Makefile](Makefile).

### 1. Check the prerequisites

Start Docker and check that the following tools are available. Docker Compose must support `up --wait`. Vite requires Node.js 20.19 or later in the 20.x series, or Node.js 22.12 or later. The backend image includes PHP and Composer.

```sh
docker info
docker compose version
node --version
npm --version
make --version
curl --version
```

### 2. Create the environment file

Copy the [environment template](api/.env.example). The `-n` flag preserves an existing `api/.env`.

```sh
cp -n api/.env.example api/.env
```

### 3. Configure local services

The template provides working local defaults for every required setting except `APP_KEY`, which you will generate in step 4. To use a different port or different credentials, edit these values in `api/.env` before the first startup.

| Setting | Local default | Used for |
| --- | --- | --- |
| `APP_PORT` | `8000` | Published Laravel port. The template derives `APP_URL` from this value. |
| `POSTGRES_DB` | `gymtracker` | Database name |
| `POSTGRES_USER` / `POSTGRES_PASSWORD` | `gymtracker` / `gymtracker-local` | Laravel database connection and Adminer login |
| `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` | `gymtracker` / `gymtracker-local` | S3 credentials and MinIO Console login |
| `MINIO_BUCKET` | `gymtracker` | Private bucket for application files |

Keep the template's service hostnames and internal ports. Laravel reaches PostgreSQL at `database:5432`, for example. The template derives the Laravel database and S3 settings from the values above. PostgreSQL uses the initial credentials when it creates its data volume. To change an existing database password, update it in both PostgreSQL and `api/.env`.

### 4. Generate the Laravel application key

If `APP_KEY` already has a value, keep it and continue to step 5. For an empty key, run Artisan once. The bind mount lets Artisan write the generated key to your local `api/.env`.

```sh
docker compose -f api/compose.yaml run --build --rm --no-deps --user root \
  -v "$PWD/api/.env:/app/.env" app php artisan key:generate --no-interaction
```

### 5. Install dependencies

```sh
make backend-install
npm --prefix web ci
```

### 6. Start the containers and frontend

```sh
make start
```

Make starts the backend against the local `api/` checkout. It waits for Laravel to become healthy, then starts Vite in the background with the published API address. During startup, `minio-init` creates the bucket if needed and Laravel applies pending PostgreSQL migrations.

### 7. Verify the application

Open [GymTracker](http://127.0.0.1:5173/) in your browser. To check that the frontend proxy can reach Laravel, request the health endpoint through Vite.

```sh
curl -fsS http://127.0.0.1:5173/api/health
```

The expected response is `{"status":"ok"}`. To check Laravel directly, open the [API health endpoint](http://127.0.0.1:8000/api/health).

Vite proxies `/api/` and `/sanctum/` requests to Laravel. For SPA requests, include cookies, `Accept: application/json`, and the SPA's `Origin` or `Referer`. First request `GET /sanctum/csrf-cookie`. Send the current, URL-decoded `XSRF-TOKEN` cookie value as the `X-XSRF-TOKEN` header on each write.

| Endpoint | Behavior |
| --- | --- |
| `POST /api/auth/register` | Send `email`, `password` (at least 8 characters), and `profile_type` (`athlete` or `trainer`) in the JSON body. Creates the account with an initial name such as `Trainer #123` or `Athlete #123` and the selected profile, signs the user in, and returns an empty `201`. Returns `422` for invalid fields or an email already in use, `409` for a valid request from a signed-in session, or `429` after five requests per minute per IP. |
| `POST /api/auth/login` | Send `email` and `password` in the JSON body. Login reads the body as JSON regardless of `Content-Type`. Returns `204` on login or `422` for invalid input or credentials. The route allows five requests per minute per IP as resolved by Laravel and then returns `429`. |
| `GET /api/auth/me` | Returns the current account for an authenticated session or `401` for a guest. |
| `POST /api/auth/logout` | Ends the current authenticated session and returns `204`. |

GymTracker hashes passwords with Laravel’s Argon2id driver (`HASH_DRIVER=argon2id`).

Custom registration and login error messages support English (`APP_LOCALE=en`) and Ukrainian (`APP_LOCALE=uk`), with English as the fallback. Email is normalized by the user model before saving and protected by one unique index, `users_email_unique`.

Queues use Redis. PostgreSQL stores job batch metadata and failed job records through Laravel’s standard configuration.

Compose trusts `127.0.0.1` and `localhost` on the selected Vite port. For a different SPA host behind the same-origin proxy, set `SANCTUM_STATEFUL_DOMAINS` to the comma-separated hostnames, including any nonstandard ports.

Local browser sessions through Vite share the proxy's login rate limit. For deployments behind a reverse proxy, configure the proxy to set the client IP and configure Laravel to trust that header only from the deployment's proxy addresses.

Registration rate limits and session blocking use the shared Redis service in Compose. For multi-worker deployments, keep the request deadline shorter than Laravel's default 10-second session lock lifetime so the lock remains held until the session is saved.

## Local URLs

| Service | Default address | Purpose |
| --- | --- | --- |
| React | [127.0.0.1:5173](http://127.0.0.1:5173/) | Application UI |
| Laravel | [127.0.0.1:8000](http://127.0.0.1:8000/) | Backend welcome page |
| API health | [127.0.0.1:8000/api/health](http://127.0.0.1:8000/api/health) | JSON status response |
| Laravel health | [127.0.0.1:8000/up](http://127.0.0.1:8000/up) | Endpoint used by the container health check |
| Horizon | [127.0.0.1:8000/horizon](http://127.0.0.1:8000/horizon) | Queue dashboard |
| Adminer | [127.0.0.1:8081](http://127.0.0.1:8081/) | Select PostgreSQL, enter `database` as the server, and use the database name and credentials from step 3 |
| Mailpit | [127.0.0.1:8026](http://127.0.0.1:8026/) | Inbox for mail sent by the application |
| MinIO Console | [127.0.0.1:9001](http://127.0.0.1:9001/) | Browse stored files using the MinIO credentials from step 3 |
| MinIO S3 API | `http://127.0.0.1:9000` | Endpoint for local S3 clients |
| Reverb | `ws://127.0.0.1:8082` | WebSocket server for local clients |

If a backend port is in use, change the corresponding setting in `api/.env` and rerun `make start`. The [Compose configuration](api/compose.yaml) supports `APP_PORT`, `ADMINER_PORT`, `MAILPIT_UI_PORT`, `MINIO_API_PORT`, `MINIO_CONSOLE_PORT`, and `REVERB_WS_PORT`. For a different frontend port, use `make start WEB_PORT=5174` and stop it with `make stop WEB_PORT=5174`.

## Everyday commands

| Task | Command |
| --- | --- |
| Start or stop the whole project | `make start` / `make stop` |
| Install Composer dependencies into the shared `api/vendor` | `make backend-install` |
| Start the backend containers | `make backend-start` |
| Stop or restart the backend | `make backend-stop` / `make backend-restart` |
| Start, stop, or restart Vite | `make frontend-start` / `make frontend-stop` / `make frontend-restart` |
| Inspect container status, including completed services | `docker compose -f api/compose.yaml ps -a` |
| Follow Laravel logs | `docker compose -f api/compose.yaml logs -f app` |
| Follow Vite logs (default port) | `tail -f web/.gymtracker.local/5173/vite.log` |

Laravel reads source changes from the local `api/` directory on the next HTTP request. The development containers share that directory, including `api/vendor`. Container writes appear in the checkout when file permissions allow them. `make backend-install` runs Composer with your local user ID so it can write to `api/vendor`. When Composer dependencies change, stop the backend, run `make backend-install`, and start it again. Use `make backend-restart` when long-running workers need to reload PHP code.

When starting Vite separately, you can set its API address explicitly with `make frontend-start API_PROXY_TARGET=http://127.0.0.1:8001`. Stopping the stack preserves PostgreSQL, Redis, MinIO, Mailpit, and Laravel storage data in Docker volumes.

## Development checks

| Command | What it runs |
| --- | --- |
| `make backend-test` | PHPUnit in the running app service against the separate PostgreSQL database `gymtracker_test` (created automatically) |
| `make backend-check` | Pint, Larastan (PHPStan level 8), and Deptrac in Docker |
| `make backend-audit` | Composer advisory checks against `api/composer.lock` (requires network access) |
| `make frontend-test` | The frontend test script if present, followed by linting and a production build |
| `make frontend-check` | TypeScript, Oxlint, and Prettier checks |
| `make pre-commit` | All checks, tests, and the dependency audit |

PostgreSQL is the supported database for development and tests. The name `gymtracker_test` is reserved for tests and must differ from `POSTGRES_DB`. Backend checks reuse the running Docker services and the current checkout. PHPUnit uses a separate configuration cache path and verifies the resolved PostgreSQL connection points to `gymtracker_test` before database refreshes. The committed `api/.env.testing` selects Redis databases `14` and `15` for the default and cache connections. These databases are reserved for tests and cleared before and after each test. Run one test process at a time against this shared test environment.

Run `npm --prefix web run format` to apply Prettier formatting.

Backend modules live under `api/app/Modules/<Module>`. Deptrac enforces [layer dependencies](api/deptrac.layers.yaml) and [module boundaries](api/deptrac.modules.php). To use another module, add it to the caller's `allowedDependencies`. The target module exposes actions or interfaces under `Actions/Api` and their DTOs under `Data/Api`.
