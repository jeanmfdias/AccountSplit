.PHONY: up down shell test migrate migrate-diff cache lint cs-fix stan front-install front-dev front-build prod-up prod-down

COMPOSE      = docker compose
COMPOSE_PROD = docker compose -f docker-compose.yml -f docker-compose.prod.yml
EXEC         = $(COMPOSE) exec app
EXEC_PROD    = $(COMPOSE_PROD) exec app

## ── Docker ──────────────────────────────────────────────────────────────────

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

prod-build:
	$(COMPOSE_PROD) build --no-cache
	$(COMPOSE_PROD) up -d
	$(EXEC_PROD) php bin/console doctrine:migrations:migrate --no-interaction

prod-up:
	$(COMPOSE_PROD) up -d

prod-down:
	$(COMPOSE_PROD) down

prod-kill:
	$(COMPOSE_PROD) down --remove-orphans

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
	$(EXEC) php -d memory_limit=512M vendor/bin/phpstan analyse

lint:
	$(EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes

cs-fix:
	$(EXEC) vendor/bin/php-cs-fixer fix --allow-risky=yes

## ── Frontend ─────────────────────────────────────────────────────────────────

front-install:
	cd frontend && npm install

front-dev:
	cd frontend && npm run dev

front-build:
	cd frontend && npm run build