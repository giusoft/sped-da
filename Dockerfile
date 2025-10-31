# [cite_start]Usa a imagem oficial do PHP 8.4 com FPM (base Debian) [cite: 1]
FROM php:8.4-fpm

# [cite_start]Define o diretório de trabalho [cite: 1]
WORKDIR /var/www/html

# [cite_start]1. Instala todas as dependências de sistema necessárias para as extensões PHP [cite: 1]
# Adicionado: libgmp-dev para operações matemáticas de alta precisão
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libssl-dev \
    libgmp-dev \
    # [cite_start]Limpa o cache do apt no final [cite: 2]
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# [cite_start]2. Configura e instala as extensões PHP necessárias pelo sped-nfe e suas dependências [cite: 2]
# Adicionado: gmp, dom (vem com xml, mas explícito), openssl (vem com curl)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        soap \
        sockets \
        xml \
        zip \
        curl \
        pdo \
        pdo_mysql \
        mbstring \
        gmp

# [cite_start]3. Instala a extensão Redis via PECL (opcional, mas uma boa prática) [cite: 3]
RUN pecl install redis && docker-php-ext-enable redis

# [cite_start]4. Instala o Composer (versão multi-stage para manter a imagem final limpa) [cite: 3]
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# [cite_start]5. Copia os arquivos de dependência e instala as dependências do Composer [cite: 3]
# Isso aproveita o cache do Docker. O vendor só será reconstruído se o composer.json ou .lock mudar.
COPY src/Lib/composer.json src/Lib/composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

# [cite_start]6. Copia o código da sua aplicação [cite: 3, 4]
COPY . .

# 7. Atualiza os submódulos Git
RUN git config --global --add safe.directory /var/www/html \
    && git submodule update --init --recursive

# [cite_start]8. Ajusta as permissões da pasta para o usuário do servidor web [cite: 4]
RUN chown -R www-data:www-data /var/www/html

# [cite_start]Expõe a porta padrão do PHP-FPM [cite: 4]
EXPOSE 9000

# [cite_start]Inicia o PHP-FPM [cite: 4]
CMD ["php-fpm"]