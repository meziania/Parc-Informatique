FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js tsconfig.json ./
RUN npm run build

FROM php:8.4-cli-bookworm
WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo_pgsql zip gd pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

CMD sh -c "php artisan storage:link --force || true; php artisan migrate --force; php artisan db:seed --force; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
