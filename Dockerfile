FROM php:5.6-zts-alpine
RUN apk add --no-cache git bash curl-dev ca-certificates && update-ca-certificates
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
  php composer-setup.php && \
  mv composer.phar /usr/bin/composer
