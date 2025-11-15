FROM php:8.2-cli

# Устанавливаем системные пакеты включая git, unzip и другие утилиты
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_pgsql sockets

# Проверяем что расширение установлено
RUN php -m | grep sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader

CMD ["php", "monolith-listener.php"]