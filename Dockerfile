FROM php:8.1.7-apache@sha256:a81a9140fe2dac89a5f1c2b59b6c7076df6bf48e522f9f1e597a93ea16cac329

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
  && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php --no-ansi --install-dir=/usr/bin --filename=composer \
  && php -r "unlink('composer-setup.php');" \
  && composer --ansi --version --no-interaction \
  && composer require jbelien/ovh-monitoring

COPY . /var/www/html/

RUN composer update
