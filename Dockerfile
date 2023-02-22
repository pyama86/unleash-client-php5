FROM php:5.4
RUN apt update -qqy && DEBIAN_FRONTEND=noninteractive apt install -qq --force-yes git bash libcurl4-openssl-dev  ca-certificates
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
  php composer-setup.php && \
  mv composer.phar /usr/bin/composer
