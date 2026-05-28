FROM php:8.4-cli-alpine3.19 AS builder

# Обновляем индексы репозиториев и добавляем официальные репозитории
RUN echo "https://dl-cdn.alpinelinux.org/alpine/v3.20/main" > /etc/apk/repositories && \
    echo "https://dl-cdn.alpinelinux.org/alpine/v3.20/community" >> /etc/apk/repositories && \
    apk update --no-cache && \
    apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    autoconf \
    g++ \
    make \
    rabbitmq-c-dev

# Устанавливаем PHP расширения (без intl)
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    bcmath

# Устанавливаем Redis расширение через pecl
RUN pecl install redis && docker-php-ext-enable redis

# Устанавливаем RabbitMQ расширение (amqp) через pecl
RUN pecl install amqp && docker-php-ext-enable amqp

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем файлы composer и исходный код
COPY composer.json composer.lock ./
COPY . .

# Устанавливаем зависимости
RUN composer install --no-interaction --no-progress --optimize-autoloader \
    --ignore-platform-req=ext-sockets

# ============================================
# PHP-FPM образ
# ============================================
FROM php:8.4-fpm-alpine AS php-fpm

# Устанавливаем runtime-зависимости
RUN apk add --no-cache \
    postgresql-client \
    bash \
    rabbitmq-c \
    libzip \
    icu-dev

# Устанавливаем intl напрямую в финальном образе
RUN docker-php-ext-install intl

# Копируем ВСЕ расширения из builder (папкой целиком)
COPY --from=builder /usr/local/lib/php/extensions/no-debug-non-zts-20240924 /usr/local/lib/php/extensions/no-debug-non-zts-20240924

# Включаем расширения
RUN docker-php-ext-enable redis amqp

# Копируем конфиги расширений
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Копируем код приложения
COPY --from=builder /var/www/html /var/www/html

# Копируем конфиг PHP-FPM
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# Создаем директории для логов и кэша
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Копируем скрипты
COPY docker/php/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Копируем php.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ============================================
# Supervisor образ
# ============================================
FROM php:8.4-cli-alpine AS supervisor

# Устанавливаем runtime-зависимости
RUN apk add --no-cache \
    postgresql-client \
    bash \
    rabbitmq-c \
    supervisor \
    icu-dev

# Устанавливаем intl напрямую в финальном образе
RUN docker-php-ext-install intl

# Создаем директорию для логов supervisor
RUN mkdir -p /var/log/supervisor

# Копируем ВСЕ расширения из builder (папкой целиком)
COPY --from=builder /usr/local/lib/php/extensions/no-debug-non-zts-20240924 /usr/local/lib/php/extensions/no-debug-non-zts-20240924

# Включаем расширения
RUN docker-php-ext-enable redis amqp

# Копируем конфиги расширений
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Копируем код приложения
COPY --from=builder /var/www/html /var/www/html
COPY --from=builder /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Создаем директории для логов и кэша Laravel
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Копируем конфигурацию Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Копируем скрипты
COPY docker/php/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

# Копируем отдельный entrypoint для supervisor
COPY docker/supervisor/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Копируем php.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
