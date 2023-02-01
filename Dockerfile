#syntax=docker/dockerfile:1.4

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=8.1

# Prod image
FROM php:${PHP_VERSION}-fpm AS app_php

ENV APP_ENV=prod

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# persistent / runtime deps
RUN set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		git \
		libfcgi-bin \
		unzip \
	;

RUN set -eux; \
	install-php-extensions \
		intl \
		zip \
		apcu \
		opcache \
	;

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

COPY --link docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php

COPY --link docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.* symfony.* ./
RUN set -eux; \
	if [ -f composer.json ]; then \
		composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
		composer clear-cache; \
	fi

# copy sources
COPY --link  . .
RUN rm -Rf docker/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	if [ -f composer.json ]; then \
		composer dump-autoload --classmap-authoritative --no-dev; \
		composer dump-env prod; \
		composer run-script --no-dev post-install-cmd; \
		chmod +x bin/console; sync; \
	fi

# Dev image
FROM app_php AS app_php_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN rm $PHP_INI_DIR/conf.d/app.prod.ini; \
	mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini-production"; \
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --link docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/conf.d/zz_php.ini $PHP_INI_DIR/conf.d/

RUN set -eux; \
	install-php-extensions xdebug

# Add a non-root user to prevent files being created with root permissions on host machine.
ARG USERNAME=vscode
ENV USERNAME ${USERNAME}
ARG USER_UID=1000
ENV USER_UID ${USER_UID}
ARG USER_GID=1000
ENV USER_GID ${USER_GID}

RUN groupadd --gid $USER_GID $USERNAME \
	&& useradd -s /bin/bash --uid $USER_UID --gid $USER_GID -m $USERNAME

RUN groupmod -o -g $USER_GID www-data && \
	usermod -o -u $USER_UID -g www-data www-data

FROM nginx:latest AS app_nginx

# Install openssl
RUN set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		openssl \
	;

# Creating a self-signed SSL certificate.
RUN set -eux; \
	mkdir -p /etc/nginx/ssl/; \
	openssl req -new -newkey rsa:4096 -days 3650 -nodes -x509 \
		-subj "/C=US/ST=NC/L=Local/O=Dev/CN=localhost" \
		-keyout /etc/nginx/ssl/server.key \
		-out /etc/nginx/ssl/server.crt \
	;

COPY --link docker/nginx/nginx.conf /etc/nginx/
COPY --link docker/nginx/default.conf /etc/nginx/conf.d/

EXPOSE 80 443
