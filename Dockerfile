FROM php:7.2.9-apache@sha256:34d20a90946b2a2e05ca6d2fe71f1394168af6dc1d18ab8211cc43f1679410cb

RUN apt-get update && apt-get install -y \
		git \
		zip unzip zlib1g-dev \
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
 && php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
 && php composer-setup.php --no-ansi --install-dir=/usr/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && composer --ansi --version --no-interaction \
 && composer require jbelien/ovh-monitoring

COPY . /var/www/html/

RUN composer update
