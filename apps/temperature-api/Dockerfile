FROM php:8.2-alpine

ENV APP_ENV=production

WORKDIR /var/www/html

# Копируем исходный код приложения
COPY . .

# Настраиваем права доступа (если нужно)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081", "index.php"]