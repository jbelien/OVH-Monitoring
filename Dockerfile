FROM php:7.4.0-apache@sha256:2cbd4626b403bde990e1b5826fb118ee1a73ad6fb24ad0ee79ee0c0ffba5d8f0

RUN apt-get update && apt-get install -y \
		git \
		zip unzip zlib1g-dev libzip-dev \
		libgettextpo-dev \
	--no-install-recommends \
	&& apt-get clean \
	&& rm -r /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) zip gettext

# FROM https://github.com/composer/docker/blob/master/1.4/Dockerfile
ENV PATH "/composer/vendor/bin:$PATH"
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php --no-ansi --install-dir=/usr/bin --filename=composer \
  && php -r "unlink('composer-setup.php');" \
  && composer --ansi --version --no-interaction \
  && composer require jbelien/ovh-monitoring

COPY . /var/www/html/

RUN composer update
