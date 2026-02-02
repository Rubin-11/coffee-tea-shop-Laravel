# Используем официальный образ PHP с FPM для Laravel
FROM php:8.4-fpm

# Установка необходимых расширений PHP для Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    vim \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Явно включаем, на всякий случай (обычно не нужно, но безопасно)
RUN docker-php-ext-enable pdo_pgsql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию внутри контейнера
WORKDIR /var/www

# Копируем файлы приложения (Laravel) в контейнер
COPY . /var/www

# Проверяем, что папки существуют, прежде чем менять права
RUN if [ -d "/var/www/storage" ] && [ -d "/var/www/bootstrap/cache" ]; then \
      chown -R www-data:www-data /var/www && \
      chmod -R 775 /var/www/storage /var/www/bootstrap/cache; \
    else \
      echo "Warning: storage or bootstrap/cache not found. Skipping permissions."; \
    fi

# Открываем порт (необязательно для FPM, но для ясности)
EXPOSE 9000

# Запускаем PHP-FPM
CMD ["php-fpm"]