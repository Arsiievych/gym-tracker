# gym-tracker

Set up `api/.env` following the [Laravel Docker instructions](api/README.md) and install the frontend dependencies with `npm --prefix web ci`. Run the commands below from the repository root.

| Action | Laravel in Docker | React | Both |
| --- | --- | --- | --- |
| Start | `make backend-start` | `make frontend-start` | `make start` |
| Stop | `make backend-stop` | `make frontend-stop` | `make stop` |
| Restart | `make backend-restart` | `make frontend-restart` | |
| Test | `make backend-test` | `make frontend-test` | |

React runs at <http://127.0.0.1:5173/> and proxies API requests to port 8000 by default. `make start` uses the API port published by Docker. For a standalone frontend with a custom API URL, pass it as `make frontend-start API_PROXY_TARGET=http://127.0.0.1:8001`.

The backend test target uses SQLite in memory and temporary storage. The frontend test target runs the test script when available, then checks lint and the production build.
