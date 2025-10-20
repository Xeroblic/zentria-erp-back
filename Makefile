WITH_TESTS ?= 0
COMPOSE ?= docker compose
PROFILES := $(if $(filter 1,$(WITH_TESTS)),--profile test,)

up:
	$(COMPOSE) $(PROFILES) up -d db mailpit $(if $(filter 1,$(WITH_TESTS)),db_test app_test,app)

test-pg:
	-$(COMPOSE) rm -f -s -v tester >/dev/null 2>&1 || true
	$(COMPOSE) --profile test up -d db_test
	$(COMPOSE) --profile test run --rm tester

clean-tester:
	-@docker compose rm -f -s -v tester || true

up-all:
	$(COMPOSE) $(PROFILES) up --build db mailpit $(if $(filter 1,$(WITH_TESTS)),db_test app_test,app)

down:
	$(COMPOSE) down -v
