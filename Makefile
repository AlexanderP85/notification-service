.PHONY: help build up down restart logs shell status migrate fresh test clean

# Переменные
DOCKER_COMPOSE = docker compose
PHP_FPM = $(DOCKER_COMPOSE) exec php-fpm
COMPOSER_RUN = docker run --rm -v $(shell pwd):/app -w /app composer

# Цвета для вывода
GREEN = \033[0;32m
RED = \033[0;31m
YELLOW = \033[0;33m
NC = \033[0m # No Color

help: ## Показать справку
	@echo "Доступные команды:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

build: ## Собрать образы
	@echo "$(YELLOW)Сборка Docker образов...$(NC)"
	$(DOCKER_COMPOSE) build --no-cache
	@echo "$(GREEN)✅ Образы собраны$(NC)"

up: ## Запустить все контейнеры
	@echo "$(YELLOW)Запуск контейнеров...$(NC)"
	$(DOCKER_COMPOSE) up -d
	@echo "$(YELLOW)Ожидание готовности контейнеров (15 сек)...$(NC)"
	sleep 15
	@echo "$(GREEN)✅ Контейнеры запущены$(NC)"

down: ## Остановить все контейнеры
	@echo "$(YELLOW)Остановка контейнеров...$(NC)"
	$(DOCKER_COMPOSE) down
	@echo "$(GREEN)✅ Контейнеры остановлены$(NC)"

down-v: ## Остановить контейнеры и удалить volumes (очистка БД)
	@echo "$(RED)ВНИМАНИЕ: Это удалит все данные!$(NC)"
	@read -p "Вы уверены? (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		echo "$(YELLOW)Остановка и удаление volumes...$(NC)"; \
		$(DOCKER_COMPOSE) down -v; \
		echo "$(GREEN)✅ Контейнеры и volumes удалены$(NC)"; \
	else \
		echo "$(YELLOW)Отменено$(NC)"; \
	fi

composer-install: ## Установить Composer зависимости
	@echo "$(YELLOW)Установка Composer зависимостей...$(NC)"
	$(COMPOSER_RUN) install --ignore-platform-req=ext-sockets
	@echo "$(GREEN)✅ Composer зависимости установлены$(NC)"

migrate: ## Выполнить миграции
	@echo "$(YELLOW)Выполнение миграций...$(NC)"
	$(PHP_FPM) php artisan migrate --force
	@echo "$(GREEN)✅ Миграции выполнены$(NC)"

permissions: ## Установить права на storage
	@echo "$(YELLOW)Установка прав на директории...$(NC)"
	$(PHP_FPM) chmod -R 777 storage bootstrap/cache
	@echo "$(GREEN)✅ Права установлены$(NC)"

rabbitmq-queues: ## Создать очереди RabbitMQ
	@echo "$(YELLOW)Создание очередей RabbitMQ...$(NC)"
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.high durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.medium durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.low durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.retry.high durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.retry.medium durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.retry.low durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare queue name=notifications.dlq durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare exchange name=notifications.direct type=direct durable=true 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare binding source=notifications.direct destination=notifications.high routing_key=high 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare binding source=notifications.direct destination=notifications.medium routing_key=medium 2>/dev/null
	-$(DOCKER_COMPOSE) exec rabbitmq rabbitmqadmin declare binding source=notifications.direct destination=notifications.low routing_key=low 2>/dev/null
	@echo "$(GREEN)✅ Очереди RabbitMQ созданы$(NC)"

restart-workers: ## Перезапустить воркеры
	@echo "$(YELLOW)Перезапуск воркеров...$(NC)"
	-$(DOCKER_COMPOSE) exec supervisor supervisorctl -c /etc/supervisor/conf.d/supervisord.conf restart all 2>/dev/null || true
	@echo "$(GREEN)✅ Воркеры перезапущены$(NC)"

status: ## Проверить статус контейнеров
	@echo "$(YELLOW)Статус контейнеров:$(NC)"
	$(DOCKER_COMPOSE) ps
	@echo ""
	@echo "$(YELLOW)Статус воркеров:$(NC)"
	-$(DOCKER_COMPOSE) exec supervisor supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status 2>/dev/null || echo "Supervisor не запущен"

logs: ## Показать логи всех сервисов
	$(DOCKER_COMPOSE) logs -f

shell: ## Войти в контейнер php-fpm
	$(PHP_FPM) bash

test: ## Запустить тесты
	@echo "$(YELLOW)Запуск тестов...$(NC)"
	$(PHP_FPM) php artisan test
	@echo "$(GREEN)✅ Тесты выполнены$(NC)"

fresh: down-v build up composer-install migrate permissions restart-workers ## Полная переустановка проекта (очистка БД)
	@echo "$(GREEN)🎉 Проект полностью переустановлен!$(NC)"
	@echo "$(GREEN)API доступно по адресу: http://localhost/api/notifications$(NC)"

init: build up composer-install migrate permissions restart-workers ## Первоначальная настройка проекта
	@echo "$(GREEN)🎉 Проект готов к работе!$(NC)"
	@echo "$(GREEN)API доступно по адресу: http://localhost/api/notifications$(NC)"

# Установка по умолчанию (выполняется при вызове `make`)
.DEFAULT_GOAL := init
