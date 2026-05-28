#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
/usr/local/bin/wait-for-it.sh postgres 5432

echo "Waiting for RabbitMQ..."
/usr/local/bin/wait-for-it.sh rabbitmq 5672

echo "Starting Supervisor..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
