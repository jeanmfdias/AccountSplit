.PHONY: up down shell test migrate migrate-diff cache lint cs-fix stan front-install front-dev front-build

COMPOSE = docker compose
EXEC    = $(COMPOSE) exec app

## ── Docker ──────────────────────────────────────────────────────────────────

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

shell:
	$(EXEC) bash

## ── Symfony ─────────────────────────────────────────────────────────────────

cache:
	$(EXEC) php bin/console cache:clear

migrate:
	$(EXEC) php bin/console doctrine:migrations:migrate --no-interaction

migrate-diff:
	$(EXEC) php bin/console doctrine:migrations:diff

migrate-fresh:
	$(EXEC) php bin/console doctrine:database:drop --force --if-exists
	$(EXEC) php bin/console doctrine:database:create
	$(EXEC) php bin/console doctrine:migrations:migrate --no-interaction

## ── Quality ──────────────────────────────────────────────────────────────────

test:
	$(EXEC) vendor/bin/phpunit

test-unit:
	$(EXEC) vendor/bin/phpunit --testsuite Unit

test-integration:
	$(EXEC) vendor/bin/phpunit --testsuite Integration

test-feature:
	$(EXEC) vendor/bin/phpunit --testsuite Feature

test-coverage:
	$(EXEC) vendor/bin/phpunit --coverage-html var/coverage

stan:
	$(EXEC) vendor/bin/phpstan analyse

lint:
	$(EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix:
	$(EXEC) vendor/bin/php-cs-fixer fix

## ── Frontend ─────────────────────────────────────────────────────────────────

front-install:
	cd frontend && npm install

front-dev:
	cd frontend && npm run dev

front-build:
	cd frontend && npm run build