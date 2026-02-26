SHELL := /bin/sh
COMPOSE := docker compose

.PHONY: start stop down restart build logs ps clean help composer-install

start: ## Démarre les conteneurs
	$(COMPOSE) up -d

stop: ## Arrête (supprime) les conteneurs
	$(COMPOSE) down

down: ## Alias de stop
	$(COMPOSE) down

restart: ## Redémarre
	$(COMPOSE) down
	$(COMPOSE) up -d

build: ## Reconstruit les images
	$(COMPOSE) build

logs: ## Logs suivis
	$(COMPOSE) logs -f

ps: ## Liste services
	$(COMPOSE) ps

clean: ## Supprime conteneurs + volumes
	$(COMPOSE) down -v

composer-install: ## Installe dépendances
	$(COMPOSE) run --rm app composer install

help: ## Aide
	@grep -E '^[a-zA-Z_-]+:.*?##' Makefile | sed 's/:.*##/: /' | column -t%