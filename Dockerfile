# Dockerfile optimisé pour Render
FROM php:8.2-apache

# Mettre à jour les paquets et installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer les extensions PHP une par une (plus fiable)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Installer pdo_mysql (c'est l'extension MySQL qu'il te faut, pas mysql.sql.so)
RUN docker-php-ext-install pdo_mysql mysqli

# Installer zip et autres extensions utiles
RUN docker-php-ext-install zip

# Activer opcache (optionnel mais recommandé)
RUN docker-php-ext-install opcache

# Activer les modules Apache
RUN a2enmod rewrite headers

# Créer le dossier uploads s'il n'existe pas
RUN mkdir -p /var/www/html/uploads

# Copier tous les fichiers du site
COPY . /var/www/html/

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

# Configurer le port (Render utilise $PORT)
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf

# Health check (optionnel mais recommandé)
COPY health.php /var/www/html/health.php
RUN chmod 644 /var/www/html/health.php
