#!/bin/sh
set -e

# Ждём PostgreSQL
/usr/local/bin/wait-for-it.sh postgres 5432

# Запускаем PHP-FPM
exec php-fpm -F
