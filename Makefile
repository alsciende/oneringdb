php = docker-compose -f docker/dev/compose.yaml exec symfony

all: public/assets/importmap.json

public/assets/importmap.json: assets/* assets/controllers/*
	rm -rf public/assets/*
	$(php) php bin/console asset-map:compile

build:
	docker-compose -f docker/dev/compose.yaml build

up:
	docker-compose -f docker/dev/compose.yaml up -d

down:
	docker-compose -f docker/dev/compose.yaml down

clean:
	rm -rf docker/dev/postgres/data
	rm -rf docker/dev/nginx/log
	rm -rf vendor

install:
	$(php) composer install

shell:
	$(php) bash

lint:
	$(php) bin/console lint:container
	$(php) php vendor/bin/ecs --fix
	$(php) php vendor/bin/rector
	$(php) php vendor/bin/phpstan -v --memory-limit=-1

test:
	$(php) php bin/phpunit

db:
#	$(php) php bin/console doctrine:database:drop --force
#	$(php) php bin/console doctrine:database:create
	$(php) php bin/console doctrine:schema:drop -f
	$(php) php bin/console doctrine:schema:create
	$(php) php bin/console doctrine:fixtures:load -n
