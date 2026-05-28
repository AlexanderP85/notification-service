# Notification Service

Микросервис для массовой рассылки SMS и Email-уведомлений с поддержкой приоритетов (high/medium/low),
асинхронной обработкой через RabbitMQ и отслеживанием статусов доставки.

## 🚀 Технологический стек
- PHP 8.4 + Laravel 12
- PostgreSQL 16 — основная база данных
- RabbitMQ 3.13 — брокер сообщений
- Supervisor — управление воркерами
- Nginx — веб-сервер
- Docker Compose — оркестрация

## 📋 Требования
- Docker 20.10+
- Docker Compose 2.0+

## 🚀 Запуск проекта

### 1. Клонировать репозиторий
git clone <repository-url>
cd notification-service

### 2. Настроить окружение
cp .env.example .env
docker compose run --rm php-fpm php artisan key:generate

### 3. Собрать и запустить контейнеры
docker compose build --no-cache
docker compose up -d
docker compose ps

### 4. Выполнить миграции
docker compose exec php-fpm php artisan migrate --force

### 5. Проверить API
curl -X POST http://localhost/api/notifications -H "Content-Type: application/json" -d '{"channel":"sms","message":"Test","recipient_ids":["+79991234567"],"priority":"high"}'

## 🧪 Запуск тестов
docker compose exec php-fpm php artisan test

## 🔧 Полезные команды

### Управление сервисами
docker compose down
docker compose restart
docker compose logs -f

### Управление воркерами
docker compose exec supervisor supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status
docker compose exec supervisor supervisorctl -c /etc/supervisor/conf.d/supervisord.conf restart all

### Управление очередями RabbitMQ
docker compose exec rabbitmq rabbitmqctl list_queues
docker compose exec rabbitmq rabbitmqctl purge_queue notifications.high
Web UI: http://localhost:15672 (guest/guest)

### Управление базой данных
docker compose exec postgres psql -U app -d notifications
docker compose exec php-fpm php artisan migrate:fresh --force

## 📊 Статусы уведомлений
queued - В очереди
sent - Отправлено провайдеру
delivered - Доставлено
failed - Ошибка доставки

## 🐛 Устранение неполадок
docker compose logs --tail=50
docker compose restart
docker compose build --no-cache

## 📁 Структура проекта
notification-service/
├── app/
├── database/migrations/
├── docker/
├── tests/
├── docker-compose.yml
└── .env.example

## 📝 Лицензия
MIT
