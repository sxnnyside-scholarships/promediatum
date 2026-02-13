APP_NAME=promediatum

start:
	php artisan serve

dev:
	npm run dev

fresh:
	php artisan migrate:fresh --seed

reset:
	rm -f database/database.sqlite
	touch database/database.sqlite
	php artisan migrate

install:
	composer install
	npm install
	cp .env.example .env
	php artisan key:generate

all:
	make install
	make reset
	make start
