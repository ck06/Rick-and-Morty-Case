FROM php:fpm-alpine

COPY wait-for-it.sh /usr/bin/wait-for-it

RUN chmod +x /usr/bin/wait-for-it

RUN apk --update --no-cache add git bash autoconf g++ make linux-headers
RUN git config --global --add safe.directory /var/www/current

RUN apk --update --no-cache add libzip-dev zip
RUN docker-php-ext-install zip

RUN apk --update --no-cache add freetype libjpeg-turbo libpng freetype-dev libjpeg-turbo-dev libpng-dev
RUN docker-php-ext-configure gd --with-freetype=/usr/include --with-jpeg=/usr/include
RUN docker-php-ext-install gd

RUN docker-php-ext-install pdo_mysql
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/www/current

WORKDIR /var/www/current

CMD composer install ; wait-for-it database:3306 -- bin/console doctrine:migrations:migrate ;  php-fpm

EXPOSE 9000
