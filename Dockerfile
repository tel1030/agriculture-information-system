FROM php:8.3-apache

RUN docker-php-ext-install pdo_pgsql \
    && a2enmod headers

COPY . /var/www/html/

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

EXPOSE 10000

CMD ["apache2-foreground"]
