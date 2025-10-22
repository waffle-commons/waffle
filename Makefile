#
# Waffle Framework - Makefile
# This file provides simple commands to manage the FrankenPHP development environment.
#

# Starts the FrankenPHP container in detached mode.
dev:
	@echo "Starting Waffle development environment with FrankenPHP..."
	@docker-compose up -d

# Stops and removes the Docker container.
down:
	@echo "Stopping Waffle development environment..."
	@docker-compose down

# Opens a shell inside the running FrankenPHP container.
shell:
	@docker-compose exec frankenphp sh

# Installs Composer dependencies inside the container.
install:
	@echo "Installing Composer dependencies..."
	@docker-compose exec frankenphp composer install

# Runs the PHPUnit test suite inside the container.
tests:
	@docker-compose exec frankenphp composer tests

# Runs the Mago static analysis tool inside the container.
mago:
	@docker-compose exec frankenphp composer mago

# A shortcut to run both tests and Mago.
ci: tests mago

.PHONY: dev down shell install tests mago ci
