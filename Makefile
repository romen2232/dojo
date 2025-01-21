SHELL := /bin/bash
.DEFAULT_GOAL := help

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: build
build: ## Build Docker containers
	docker-compose build

.PHONY: up
up: ## Start Docker containers
	docker-compose up -d

.PHONY: down
down: ## Stop Docker containers
	docker-compose down

.PHONY: shell
shell: ## Access PHP container shell
	docker-compose exec dojo bash

.PHONY: composer-install
composer-install: ## Install composer dependencies
	docker-compose exec dojo composer install

.PHONY: composer-update
composer-update: ## Update composer dependencies
	docker-compose exec dojo composer update

.PHONY: install
install: build composer-install ## Install project dependencies

.PHONY: test
test: ## Run tests
	docker-compose exec dojo vendor/bin/phpunit

.PHONY: test-coverage
test-coverage: ## Run tests with coverage report
	docker-compose exec dojo vendor/bin/phpunit --coverage-html coverage

.PHONY: fix-cs
fix-cs: ## Fix PHP code style issues
	docker-compose exec dojo vendor/bin/php-cs-fixer fix

.PHONY: lint
lint: fix-cs ## Run PHP linting
	docker-compose exec dojo vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: scrape-kata
scrape-kata: ## Scrape a kata from Codewars (Usage: make scrape-kata URL=<kata-url>)
	docker-compose exec dojo php bin/console kata:scrape $(URL)

.PHONY: generate-kata
generate-kata: ## Generate kata files (Usage: make generate-kata PATH=<kata-json-path>)
	docker-compose exec dojo php bin/console kata:generate $(PATH)

.PHONY: kata
kata: ## Scrape kata and generate files (Usage: make kata-full URL=<kata-url> [DIR=<custom-dir>])
	docker-compose exec dojo php bin/console kata:full $(URL) $(if $(DIR),--katas-dir=$(DIR))

.PHONY: logs
logs: ## View Docker container logs
	docker-compose logs -f

.PHONY: restart
restart: down up ## Restart Docker containers

.PHONY: clean
clean: ## Clean up generated files and Docker containers
	docker-compose down -v
	rm -rf vendor
	rm -rf coverage
	rm -rf var/cache/*

.PHONY: validate
validate: lint test ## Run all validation (linting and tests)

