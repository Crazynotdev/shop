# Dockerfile
FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql mysqli

# Activer mod_rewrite pour les URLs propres
RUN a2enmod rewrite

# Copier les fichiers du site
COPY . /var/www/html/

# Configuration des droits
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

# Exposer le port (Render injecte automatiquement $PORT)
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf
