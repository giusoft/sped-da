FROM php:8.4-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip libxml2-dev libzip-dev libpng-dev libjpeg-dev \
    libfreetype6-dev libcurl4-openssl-dev libonig-dev libssl-dev libgmp-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OpenSSL legacy (certificados A1 antigos)
RUN sed -i '1i openssl_conf = openssl_init' /usr/lib/ssl/openssl.cnf && \
printf "\n[openssl_init]\nproviders = provider_sect\n\n[provider_sect]\ndefault = default_sect\nlegacy = legacy_sect\n\n[default_sect]\nactivate = 1\n\n[legacy_sect]\nactivate = 1\n" >> /usr/lib/ssl/openssl.cnf

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd soap sockets xml zip curl pdo pdo_mysql mbstring gmp

RUN pecl install redis && docker-php-ext-enable redis

RUN a2enmod rewrite

# Porta 2083
RUN sed -i 's/80/2083/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Timeout (SEFAZ)
RUN echo "max_execution_time = 120" > /usr/local/etc/php/conf.d/timeout.ini

# Log PHP (vai para o storage do emitenota; o arquivo é criado no command do compose)
RUN echo "log_errors=On" > /usr/local/etc/php/conf.d/errors.ini \
 && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/errors.ini \
 && echo "error_log=/var/www/html/emitenota/giusoft/src/storage/log/php.log" >> /usr/local/etc/php/conf.d/errors.ini

# Apache custom
COPY conf/apache.conf /etc/apache2/conf-enabled/zzz-custom.conf

# Timezone
ENV TZ=America/Bahia
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "%s"\n' "$TZ" > /usr/local/etc/php/conf.d/tzone.ini

EXPOSE 2083
