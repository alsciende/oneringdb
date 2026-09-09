COMPOSER := docker run --rm --interactive --tty --volume ${PWD}:/app --workdir /app composer
DOCKER := DOCKER_CONFIG=/tmp/docker-empty-config docker
COMPOSE := DOCKER_CONFIG=/tmp/docker-empty-config docker-compose
EXEC := ${COMPOSE} exec --interactive
PHP := ${EXEC} symfony

all: public/assets/importmap.json

public/assets/importmap.json: assets/* assets/controllers/*
	rm -rf public/assets/*
	$(PHP) php bin/console asset-map:compile

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

clean:
	rm -rf docker/dev/postgres/data
	rm -rf docker/dev/nginx/log
	rm -rf vendor

install:
	$(PHP) composer install
	$(PHP) php bin/console importmap:install

shell:
	$(PHP) bash

lint:
	$(PHP) bin/console lint:container
	$(PHP) php vendor/bin/ecs --fix
	$(PHP) php vendor/bin/rector
	$(PHP) php vendor/bin/phpstan -v --memory-limit=-1

test:
	$(PHP) php bin/phpunit

ci: lint test

db:
#	$(PHP) php bin/console doctrine:database:drop --force
#	$(PHP) php bin/console doctrine:database:create
	$(PHP) php bin/console doctrine:schema:drop -f
	$(PHP) php bin/console doctrine:schema:create
	$(PHP) php bin/console doctrine:fixtures:load -n
