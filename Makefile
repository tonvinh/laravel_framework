DOCKER_COMPOSE = docker compose
PHP = $(DOCKER_COMPOSE) exec app php
ARTISAN = $(DOCKER_COMPOSE) exec app php artisan
COMPOSER = $(DOCKER_COMPOSE) exec app composer
PEST = $(DOCKER_COMPOSE) exec app ./vendor/bin/pest

.PHONY: help up down build restart logs shell \
        install migrate seed fresh key test lint

help:
	@echo "Usage: make <target>"
	@echo ""
	@echo "  up        Start all containers"
	@echo "  down      Stop all containers"
	@echo "  build     Build/rebuild images"
	@echo "  restart   Restart all containers"
	@echo "  logs      Follow container logs"
	@echo "  shell     Open shell in app container"
	@echo "  install   Run first-time setup"
	@echo "  migrate   Run database migrations"
	@echo "  seed      Run database seeders"
	@echo "  fresh     Fresh migrate + seed"
	@echo "  key       Generate app key"
	@echo "  test      Run Pest tests"
	@echo "  lint      Run Laravel Pint"

## Container management
up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

build:
	$(DOCKER_COMPOSE) build --no-cache

restart:
	$(DOCKER_COMPOSE) restart

logs:
	$(DOCKER_COMPOSE) logs -f

shell:
	$(DOCKER_COMPOSE) exec app bash

## First-time setup
install:
	cp .env.docker .env
	$(DOCKER_COMPOSE) up -d --build
	$(COMPOSER) install
	$(ARTISAN) key:generate
	$(ARTISAN) migrate
	$(ARTISAN) storage:link
	@echo ""
	@echo "✓ Setup complete — http://localhost:${APP_PORT:-8000}"

## Laravel
migrate:
	$(ARTISAN) migrate

seed:
	$(ARTISAN) db:seed

fresh:
	$(ARTISAN) migrate:fresh --seed

key:
	$(ARTISAN) key:generate

## Quality
test:
	$(PEST)

lint:
	$(DOCKER_COMPOSE) exec app ./vendor/bin/pint
