install:
	docker compose --env-file .env.docker build
	docker compose --env-file .env.docker up -d
	cp -n src/.env.example src/.env || true
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	sleep 5
	docker compose exec app php artisan migrate
