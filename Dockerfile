FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

FROM php:8.4-apache
RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-enabled/servername.conf \
    && docker-php-ext-install opcache
COPY docker/php.ini /usr/local/etc/php/conf.d/zettelfrei.ini
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
RUN rm -rf .env tests node_modules \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr DB_CONNECTION=sqlite DB_DATABASE=/var/www/html/storage/database/database.sqlite APP_LOCALE=de APP_FALLBACK_LOCALE=de
VOLUME /var/www/html/storage
EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
