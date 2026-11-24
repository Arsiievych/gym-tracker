# gym-tracker

Set up `api/.env` following the [Laravel Docker instructions](api/README.md) and install the frontend dependencies with `npm --prefix web ci`. Run the commands below from the repository root.

| Action | Laravel in Docker | React | Both |
| --- | --- | --- | --- |
| Start | `make backend-start` | `make frontend-start` | `make start` |
| Stop | `make backend-stop` | `make frontend-stop` | `make stop` |
| Restart | `make backend-restart` | `make frontend-restart` | |
| Test | `make backend-test` | `make frontend-test` | |
| Check code | `make backend-check` | `make frontend-check` | |
| Audit dependencies | `make backend-audit` | | |
| Run all checks and tests | | | `make pre-commit` |

React runs at <http://127.0.0.1:5173/> and proxies API requests to port 8000 by default. `make start` uses the API port published by Docker. For a standalone frontend with a custom API URL, pass it as `make frontend-start API_PROXY_TARGET=http://127.0.0.1:8001`.

The backend test target uses SQLite in memory and temporary storage. The frontend test target runs the test script when available, then checks lint and the production build.

`make backend-check` runs Pint, Larastan at PHPStan level 8, and Deptrac in Docker. `make backend-audit` checks `api/composer.lock` against Composer advisories and requires network access.

Place backend modules in `api/app/Modules/<Module>`. Deptrac checks [layer dependencies](api/deptrac.layers.yaml) and [module boundaries](api/deptrac.modules.php). To allow one module to use another, list the target module under the caller in `allowedDependencies`. The target exposes actions or interfaces under `Actions/Api` and their DTOs under `Data/Api`.

`make frontend-check` runs TypeScript, Oxlint, and Prettier. Use `npm --prefix web run format` to apply Prettier formatting.
