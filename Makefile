DOCKER_COMPOSE ?= docker compose -f api/compose.yaml
BACKEND_VERIFY = $(DOCKER_COMPOSE) run --build --rm --no-deps --volume /app/storage \
	-e APP_ENV=testing -e APP_KEY=12345678901234567890123456789012 \
	-e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= \
	-e CACHE_STORE=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync \
	-e MAIL_MAILER=array -e BROADCAST_CONNECTION=null -e FILESYSTEM_DISK=local \
	app
WEB_PORT ?= 5173
API_PROXY_TARGET ?= http://127.0.0.1:8000

WEB_DIR := $(CURDIR)/web
WEB_STATE := $(WEB_DIR)/.gymtracker.local/$(WEB_PORT)
WEB_URL := http://127.0.0.1:$(WEB_PORT)
VITE := $(WEB_DIR)/node_modules/.bin/vite
REQUIRE_VITE = test -x "$(VITE)" || { echo "Install frontend dependencies with npm --prefix web ci." >&2; exit 1; }

.DEFAULT_GOAL := start
.PHONY: start stop pre-commit backend-start backend-stop backend-restart backend-test backend-check backend-audit \
	frontend-start frontend-stop frontend-restart frontend-test frontend-check

define lock_web_state
	state="$(WEB_STATE)"; \
	mkdir -p "$$state"; \
	if ! mkdir "$$state/lock" 2>/dev/null; then \
		echo "Frontend state is locked at $$state/lock." >&2; \
		echo "If no Make lifecycle command is running, remove the stale lock with rmdir." >&2; exit 1; \
	fi; \
	pending_pid=; \
	trap 'if [ -n "$$pending_pid" ]; then kill "$$pending_pid" 2>/dev/null || :; fi; rmdir "$$state/lock"' EXIT; \
	trap 'exit 130' HUP INT TERM; \
	web_pid=; web_identity=; web_target=; \
	if [ -f "$$state/process" ]; then \
		{ IFS= read -r web_pid && IFS= read -r web_identity && IFS= read -r web_target; } \
			< "$$state/process" || web_pid=; \
	fi; \
	case "$$web_pid" in ''|*[!0-9]*) web_pid= ;; esac; \
	process_identity() { ps -ww -p "$$1" -o lstart= -o command= 2>/dev/null; }; \
	web_is_running() { \
		[ -n "$$web_pid" ] && [ "$$web_pid" -gt 1 ] && [ -n "$$web_identity" ] \
			&& [ "$$(process_identity "$$web_pid")" = "$$web_identity" ]; \
	}; \
	stop_web() { \
		if web_is_running; then \
			kill "$$web_pid" 2>/dev/null || ! web_is_running; \
			attempts=0; \
			while web_is_running; do \
				attempts=$$((attempts + 1)); \
				if [ "$$attempts" -ge 10 ]; then echo "React did not stop." >&2; return 1; fi; \
				sleep 1; \
			done; \
		fi; \
		rm -f "$$state/process"; \
	};
endef

start:
	@$(REQUIRE_VITE)
	$(MAKE) backend-start
	@set -eu; \
	api_address=$$($(DOCKER_COMPOSE) port app 8000); \
	$(MAKE) frontend-start API_PROXY_TARGET="http://$$api_address"

stop:
	@result=0; \
	$(MAKE) frontend-stop || result=$$?; \
	$(MAKE) backend-stop || result=$$?; \
	exit "$$result"

backend-start:
	$(DOCKER_COMPOSE) up --build --detach --wait

backend-stop:
	$(DOCKER_COMPOSE) stop

backend-restart:
	$(MAKE) backend-stop
	$(MAKE) backend-start

backend-test:
	$(BACKEND_VERIFY) php vendor/bin/phpunit --do-not-cache-result

backend-check:
	$(BACKEND_VERIFY) composer run check --no-interaction

backend-audit:
	$(BACKEND_VERIFY) composer audit --locked --no-cache --no-interaction

frontend-start frontend-restart:
	@$(REQUIRE_VITE)
	@set -eu; \
	$(lock_web_state) \
	target="$(API_PROXY_TARGET)"; \
	if [ "$(origin API_PROXY_TARGET)" = file ] && [ -n "$$web_target" ]; then target="$$web_target"; fi; \
	if web_is_running; then \
		if [ "$@" = frontend-start ] && [ "$$web_target" = "$$target" ]; then \
			echo "React is already running at $(WEB_URL)."; exit 0; \
		fi; \
		stop_web; \
	fi; \
	if curl -s --max-time 1 -o /dev/null "$(WEB_URL)/"; then \
		echo "Port $(WEB_PORT) is already in use. Stop its server or set WEB_PORT." >&2; exit 1; \
	fi; \
	cd "$(WEB_DIR)"; \
	API_PROXY_TARGET="$$target" nohup "$(VITE)" --host 127.0.0.1 --port "$(WEB_PORT)" --strictPort \
		> "$$state/vite.log" 2>&1 < /dev/null & \
	pending_pid=$$!; \
	attempts=0; \
	until curl -fs --max-time 1 -o /dev/null "$(WEB_URL)/"; do \
		attempts=$$((attempts + 1)); \
		if [ "$$attempts" -ge 20 ] || ! kill -0 "$$pending_pid" 2>/dev/null; then \
			cat "$$state/vite.log" >&2; exit 1; \
		fi; \
		sleep 1; \
	done; \
	web_identity=$$(process_identity "$$pending_pid"); \
	printf '%s\n%s\n%s\n' "$$pending_pid" "$$web_identity" "$$target" > "$$state/process"; \
	pending_pid=; \
	printf 'React: %s\nAPI proxy: %s\n' "$(WEB_URL)" "$$target"

frontend-stop:
	@set -eu; \
	$(lock_web_state) \
	stop_web

frontend-test:
	CI=1 npm --prefix web run test --if-present
	npm --prefix web run lint
	npm --prefix web run build

frontend-check:
	npm --prefix web run typecheck
	npm --prefix web run lint
	npm --prefix web run format:check

pre-commit:
	$(MAKE) backend-check
	$(MAKE) frontend-check
	$(MAKE) backend-test
	$(MAKE) frontend-test
	$(MAKE) backend-audit
