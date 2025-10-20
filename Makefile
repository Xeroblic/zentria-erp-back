up:
	docker compose up -d db db_test mailpit

test-pg:
	docker compose run --rm tester

up-all:
	docker compose up --build

down:
	docker compose down -v
