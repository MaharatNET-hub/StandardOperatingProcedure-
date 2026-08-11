FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libsqlite3-dev libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite zip mbstring xml dom simplexml curl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Chromium لالتقاط لقطات شاشة حقيقية ضمن قسم "التحليل الفني" — تثبيت غير حرج:
# لو فشل (مثلاً غير متوفر على هذه النسخة من الصورة)، تُكمل عملية البناء وتُعطَّل
# اللقطات تلقائياً (SiteScreenshotService يتحقق من وجود الملف التنفيذي).
RUN apt-get update && apt-get install -y --no-install-recommends chromium \
    && rm -rf /var/lib/apt/lists/* \
    || echo "تحذير: تعذر تثبيت Chromium — سيتم تجاوز التقاط لقطات الشاشة"

ENV CHROME_PATH=/usr/bin/chromium

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p database storage/framework/{cache,sessions,views} storage/logs storage/app/public storage/app/mpdf-tmp storage/fonts \
    && chmod -R 775 storage bootstrap/cache database \
    && php artisan package:discover --ansi

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker-entrypoint.sh"]
