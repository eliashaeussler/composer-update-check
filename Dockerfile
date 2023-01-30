ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-alpine
LABEL maintainer="Elias Häußler <elias@haeussler.dev>"

ARG COMPOSER_VERSION=2

# Install ssh client
RUN set -eux; apk add --no-cache openssh-client

# Install Composer
ADD . /update-check
RUN /update-check/docker/install-composer.sh
RUN composer self-update --$COMPOSER_VERSION

# Require update check package
RUN composer global config repositories.update-check path /update-check
RUN if [ "$COMPOSER_VERSION" = 2 ]; then composer global config allow-plugins.eliashaeussler/composer-update-check true; fi
RUN composer global require --dev "eliashaeussler/composer-update-check:*@dev"

WORKDIR /app
ENTRYPOINT ["/update-check/docker/docker-entrypoint.sh"]
