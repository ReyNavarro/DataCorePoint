FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod headers \
    && sed -ri 's/Listen 80/Listen 8000/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:8000>/' /etc/apache2/sites-available/000-default.conf

COPY --chown=www-data:www-data agrinavarro/ /var/www/html/
COPY docker/apache-datacore.conf /etc/apache2/conf-available/datacore-security.conf

RUN a2enconf datacore-security \
    && printf 'ServerName localhost\n' >> /etc/apache2/apache2.conf

ENV PORT=8000
ENV APP_HTTPS=1

EXPOSE 8000
