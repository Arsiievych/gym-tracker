<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## GymTracker development with Docker

Run these commands from `api/`. Compose loads Laravel settings from `api/.env` through `env_file`. It also reads the PostgreSQL and MinIO settings from that file to configure those services. Preserve the existing `APP_KEY` when editing `.env`. For a fresh checkout, create the file and generate a key.

```sh
cp .env.example .env
docker compose run --build --rm --no-deps --user root -v "$PWD/.env:/app/.env" app php artisan key:generate --no-interaction
```

Start the stack with:

```sh
docker compose up -d --build
```

| Service | Local address |
| --- | --- |
| Laravel API | [127.0.0.1:8000/api/health](http://127.0.0.1:8000/api/health) |
| Horizon | [127.0.0.1:8000/horizon](http://127.0.0.1:8000/horizon) |
| Reverb WebSocket | `ws://127.0.0.1:8082` |
| Adminer | [127.0.0.1:8081](http://127.0.0.1:8081) |
| Mailpit | [127.0.0.1:8026](http://127.0.0.1:8026) |
| MinIO console | [127.0.0.1:9001](http://127.0.0.1:9001) |
| MinIO S3 API | `http://127.0.0.1:9000` |

In Adminer, select PostgreSQL and connect to `database` with database and user `gymtracker` and password `gymtracker-local`. MinIO uses the same default username and password. Compose accepts `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`, and `MINIO_BUCKET` overrides from `api/.env`. PostgreSQL applies these initialization settings to a new volume. Update existing credentials in PostgreSQL before changing them in `.env`.

The app applies pending PostgreSQL migrations at startup. The one-shot `minio-init` service creates the private bucket. PostgreSQL, Redis, Laravel storage, Mailpit, and MinIO use named volumes.

Use port 8000 for the existing React development proxy. Stop any standalone Laravel server on that port before starting this stack. Run `docker compose up -d` after editing `.env` to recreate services with the updated settings. After Laravel source changes, rerun `docker compose up -d --build`. The Compose configuration and credentials are for local development.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
