current_user   := $(shell id -u)
current_group  := $(shell id -g)
BUILD_DIR      := $(PWD)
DOCKER_FLAGS   := --interactive --tty
DOCKER_IMAGE   := registry.gitlab.com/fun-tech/fundraising-frontend-docker
COVERAGE_FLAGS := --coverage-html coverage

# Show progress in shell environment, hide progress in CI environment
ifndef CI
progress_opts :=
phpcs_progress_opts :=
else
progress_opts := --no-progress
phpcs_progress_opts := -p
endif

install-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) $(DOCKER_IMAGE) composer install $(COMPOSER_FLAGS)

update-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) $(DOCKER_IMAGE) composer update $(COMPOSER_FLAGS)

ci: phpunit cs stan architecture-check

ci-with-coverage: phpunit-with-coverage cs stan architecture-check

test: phpunit

phpunit:
	docker compose run --rm --no-deps app ./vendor/bin/phpunit $(progress_opts)

phpunit-with-coverage:
	docker compose -f docker-compose.yml -f docker-compose.debug.yml run --rm --no-deps -e XDEBUG_MODE=coverage app_debug ./vendor/bin/phpunit $(COVERAGE_FLAGS) $(progress_opts)

cs:
	docker compose run --rm --no-deps app ./vendor/bin/phpcs $(phpcs_progress_opts)

fix-cs:
	docker compose run --rm --no-deps app ./vendor/bin/phpcbf

stan:
	docker compose run --rm --no-deps app ./vendor/bin/phpstan analyse --level=9 $(progress_opts) src/ tests/

architecture-check:
	docker compose run --rm --no-deps app ./vendor/bin/deptrac analyse $(progress_opts) --fail-on-uncovered --report-uncovered


setup: install-php

.PHONY: install-php update-php ci ci-with-coverage test phpunit phpunit-with-coverage cs fix-cs stan setup architecture-check
