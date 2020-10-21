no-command:
	@echo Usage: make [scenario]

# Установка
install:
	composer install
	chmod +x ./bin/app

# Hello World
hello:
	./bin/app hello

# Создать одно событие
create-event:
	./bin/app create-event

# Извлечь одно событие
consume-event:
	./bin/app consume-event

# Проверить Redis
check-redis:
	./bin/app check-redis


# Сгенерировать 10К событий
generate-many-events:
	./bin/app generate-many-events

# Очистить очередь
clear-events:
	./bin/app clear-events

