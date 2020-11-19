ARG COMPOSER_VERSION=2
FROM composer:$COMPOSER_VERSION
LABEL maintainer="Elias Häußler <elias@haeussler.dev>"

ADD . /update-check
RUN composer global config repositories.update-check path /update-check
RUN composer global require --dev "eliashaeussler/composer-update-check:*@dev"

WORKDIR /app
ENTRYPOINT ["composer", "update-check"]
