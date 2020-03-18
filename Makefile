# If the first argument is "composer"...
ifeq (composer,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "composer"
  RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(RUN_ARGS):;@:)
endif

.PHONY: ci test phpunit cs stan covers composer validate-composerfile

ci: test cs validate-composerfile

test: covers phpunit

cs: phpcs stan

fix-cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcbf

phpunit:
	docker-compose run --rm app ./vendor/bin/phpunit

phpcs:
	docker-compose run --rm app ./vendor/bin/phpcs

stan:
	docker-compose run --rm app ./vendor/bin/phpstan analyse --level=1 --no-progress src/ tests/

covers:
	docker-compose run --rm app ./vendor/bin/covers-validator

validate-composerfile:
	docker run --rm --interactive --tty --volume $(shell pwd):/app -w /app\
	 --volume ~/.composer:/composer --user $(shell id -u):$(shell id -g) wikimediade/fundraising-frontend:composer composer validate --no-interaction

composer:
	docker run --rm --interactive --tty --volume $(shell pwd):/app -w /app\
	 --volume ~/.composer:/composer --user $(shell id -u):$(shell id -g) wikimediade/fundraising-frontend:composer composer --no-scripts $(filter-out $@,$(MAKECMDGOALS))
