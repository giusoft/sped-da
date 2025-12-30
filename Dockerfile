FROM php:8.4-apache

WORKDIR /var/www/html

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git unzip libxml2-dev libzip-dev libpng-dev libjpeg-dev \
    libfreetype6-dev libcurl4-openssl-dev libonig-dev libssl-dev libgmp-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd soap sockets xml zip curl pdo pdo_mysql mbstring gmp

# Instala Redis
RUN pecl install redis && docker-php-ext-enable redis

# --- CONFIGURAÇÃO DO APACHE ---

# 1. Habilita URLs amigáveis
RUN a2enmod rewrite

# 2. Configura Document Root para 'src'
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

RUN sed -i 's/80/2083/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. Ajusta Timeout (SEFAZ)
RUN echo "max_execution_time = 120" > /usr/local/etc/php/conf.d/timeout.ini

# 5. Salva o log de erros
RUN echo "error_log = /var/www/html/src/storage/log/php.log" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/errors.ini
# -----------------------------

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY src/Lib/composer.json src/Lib/composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

COPY . .

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Fuso Horário
ENV TZ=America/Bahia
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "%s"\n' "$TZ" > /usr/local/etc/php/conf.d/tzone.ini

EXPOSE 2083