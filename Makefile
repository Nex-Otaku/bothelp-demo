no-command:
	@echo Usage: make [scenario]

# --------------------------------------------

# Установка
install:
	composer install
	chmod +x ./bin/app

# Сгенерировать 10К событий (по 10 событий в секунду)
generate-events:
	./bin/app generate-events

# Очистить очередь
clear-events:
	./bin/app clear-events

# Запустить воркер
run-worker:
	./bin/app run-worker

# --------------------------------------------

# Сгенерировать 10К событий без задержек
generate-events-pack:
	./bin/app generate-events-pack

# Проверить Redis
check-redis:
	./bin/app check-redis

# Hello World
hello:
	./bin/app hello

# Создать одно событие
create-event:
	./bin/app create-event

# Извлечь одно событие
consume-event:
	./bin/app consume-event


# Показать 10 последних событий
show-tail:
	./bin/app show-tail

# Прочесть блокировку
read-account-lock:
	./bin/app read-account-lock

# Установить блокировку
set-account-lock:
	./bin/app set-account-lock

# Сбросить блокировку
reset-account-lock:
	./bin/app reset-account-lock

# Прочесть ID последнего события
read-last-event:
	./bin/app read-last-event

# Установить ID последнего события
set-last-event:
	./bin/app set-last-event

# Сбросить ID последнего события
reset-last-event:
	./bin/app reset-last-event

# -------------------------
# Docker
# -------------------------

build-generator:
	docker build -t test_generator_image -f ./docker/event_generator/Dockerfile .

generator-bash:
	docker run -it --rm test_generator_image bash

generator-tail:
	docker exec -it bothelp_demo_event_generator make show-tail


