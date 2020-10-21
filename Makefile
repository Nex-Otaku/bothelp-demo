no-command:
	@echo Usage: make [scenario]

# Установка
install:
	composer install
	chmod +x ./bin/app

# Hello World
hello:
	./bin/app hello