install:
	docker compose --env-file .env.docker build
	docker compose --env-file .env.docker up -d
	cp -n .env.example .env || true
	docker compose --env-file .env.docker exec app composer install
	docker compose --env-file .env.docker exec app php artisan key:generate
	sleep 20
	docker compose --env-file .env.docker exec app php artisan migrate
