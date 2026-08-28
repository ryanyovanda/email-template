# syntax=docker/dockerfile:1.7

# =============================================================================
# 1. PHP dependencies
# =============================================================================
FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# Scripts are deferred because they boot Laravel, which needs the app code.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
 && composer run-script post-autoload-dump --no-dev

# =============================================================================
# 2. Front-end assets
#
# Needs PHP as well as Node: the Wayfinder Vite plugin shells out to artisan to
# generate resources/js/routes and resources/js/actions, which every page
# imports. Building with Node alone fails on unresolved imports.
# =============================================================================
FROM php:8.3-cli-alpine AS assets

RUN apk add --no-cache nodejs npm

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Artisan needs a bootable environment; this file never reaches the runtime image.
RUN cp .env.example .env

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# --with-form matches `formVariants: true` in vite.config.ts; without it the
# generated helpers lack .form and the type check fails.
RUN php artisan wayfinder:generate --with-form --no-interaction \
 && npm run build \
 && rm -f .env

# =============================================================================
# 3. Runtime
# =============================================================================
FROM php:8.3-fpm-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# zip is required to read .docx CVs, gd for image handling, pcntl for the
# queue worker's signal handling, intl for locale-aware formatting.
RUN install-php-extensions \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
        exif \
        pcntl \
        opcache \
 && apk add --no-cache nginx supervisor tini curl \
 && rm -rf /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# nginx workers run as www-data (see docker/nginx.conf), but Alpine ships
# /var/lib/nginx owned by nginx:nginx with mode 700. Any request body larger
# than client_body_buffer_size is spilled to a temp file there, so without this
# every file upload dies with "Permission denied" at the nginx layer — before
# PHP is reached, which means nothing appears in the application log. The
# fastcgi path matters for the same reason on large responses.
RUN chmod +x /usr/local/bin/entrypoint \
 && mkdir -p /run/nginx \
        /var/lib/nginx/tmp/client_body \
        /var/lib/nginx/tmp/proxy \
        /var/lib/nginx/tmp/fastcgi \
        /var/lib/nginx/tmp/uwsgi \
        /var/lib/nginx/tmp/scgi \
        storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data /var/lib/nginx /run/nginx \
 && chown -R www-data:www-data storage bootstrap/cache \
 && rm -rf /var/www/html/.env

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=45s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
