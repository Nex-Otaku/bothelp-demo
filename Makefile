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

# Проверить Redis
check-redis:
	./bin/app check-redis