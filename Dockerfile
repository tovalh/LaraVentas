# syntax=docker/dockerfile:1

# --- Frontend assets (Vite) ---
FROM node:20-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- PHP runtime ---
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p bootstrap/cache \
        storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x deploy/start.sh

EXPOSE 8080
CMD ["bash", "deploy/start.sh"]
