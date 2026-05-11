# =============================================================
# Makefile — Todo Swarm convenience commands
# =============================================================
# Usage: make <target>
# =============================================================

.PHONY: help up down build rebuild shell migrate seed fresh logs ps \
        image push health

# ---- Colors ----
CYAN  := \033[0;36m
RESET := \033[0m

help: ## Show this help
	@echo ""
	@echo "  $(CYAN)Todo Swarm — Docker Commands$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-15s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# =============================================================
# Phase 1 — Docker Compose (local dev)
# =============================================================

up: ## Start all services (detached)
	docker-compose up -d

down: ## Stop and remove containers
	docker-compose down

build: ## Build the app image
	docker-compose build app

rebuild: ## Force rebuild (no cache)
	docker-compose build --no-cache app

shell: ## Open shell inside app container
	docker-compose exec app sh

migrate: ## Run migrations
	docker-compose exec app php artisan migrate

seed: ## Seed demo data
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migrate + seed (DESTROYS DATA)
	docker-compose exec app php artisan migrate:fresh --seed

logs: ## Follow all service logs
	docker-compose logs -f

logs-app: ## Follow app logs only
	docker-compose logs -f app

logs-horizon: ## Follow Horizon queue logs
	docker-compose logs -f horizon

ps: ## List running containers
	docker-compose ps

horizon: ## Open Horizon dashboard URL
	open http://localhost/horizon

health: ## Check app health
	curl -s http://localhost/health | python3 -m json.tool

tinker: ## Laravel Tinker REPL
	docker-compose exec app php artisan tinker

# =============================================================
# Phase 2+ — Image build & push
# =============================================================

include .env
export

image: ## Build production image with tag
	docker build \
		-t $(DOCKER_HUB_USER)/$(IMAGE_NAME):$(IMAGE_TAG) \
		-t $(DOCKER_HUB_USER)/$(IMAGE_NAME):latest \
		-f docker/php/Dockerfile \
		.

push: image ## Build and push to Docker Hub
	docker push $(DOCKER_HUB_USER)/$(IMAGE_NAME):$(IMAGE_TAG)
	docker push $(DOCKER_HUB_USER)/$(IMAGE_NAME):latest

push-sha: ## Build and push with git SHA tag
	$(eval SHA := $(shell git rev-parse --short HEAD))
	docker build -t $(DOCKER_HUB_USER)/$(IMAGE_NAME):$(SHA) -f docker/php/Dockerfile .
	docker push $(DOCKER_HUB_USER)/$(IMAGE_NAME):$(SHA)
	@echo "Pushed: $(DOCKER_HUB_USER)/$(IMAGE_NAME):$(SHA)"
