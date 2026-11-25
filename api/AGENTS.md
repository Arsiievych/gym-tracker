# Laravel application

Follow the [GymTracker project conventions](../AGENTS.md). Run PHP, Composer, and Artisan in the Laravel Docker environment.

Laravel Boost is installed as a Composer development dependency. Codex connects to the `laravel-boost` MCP server defined in [the repository configuration](../.codex/config.toml). The server runs `php artisan boost:mcp` in the running Docker Compose `app` service.
