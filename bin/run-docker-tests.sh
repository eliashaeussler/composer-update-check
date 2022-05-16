#!/usr/bin/env bash
set -e

# Resolve variables
ROOT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
APP_PATH="${ROOT_PATH}/tests/Build"
PHP_VERSION=""
PHP_MAJOR_VERSION=""
COMPOSER_VERSION=""

# Set PHP version from input
set_php_version() {
  if [[ ! $1 =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
    echo >&2 "Please pass a valid PHP version using the \"--php-version\" argument." && exit 1
  fi

  PHP_VERSION="$(echo "$1" | cut -f1,2 -d".")"
  PHP_MAJOR_VERSION="$(echo "$PHP_VERSION" | cut -f1 -d".")"
}

# Print check mark with text
_check() {
  echo -e "\xE2\x9C\x94 $1"
}

# Resolve parameters
POSITIONAL=()
while [ $# -gt 0 ]; do
  key="$1"
  case ${key} in
    -c|--composer-version)
      COMPOSER_VERSION="$2"
      shift
      shift
      ;;
    -p|--php-version)
      set_php_version "$2"
      shift
      shift
      ;;
    *)
      POSITIONAL+=("$1")
      shift
      ;;
  esac
done
set -- "${POSITIONAL[@]}"

# Use current PHP version if it's not passed as command argument
if [ -z "$PHP_VERSION" ]; then
  set_php_version "$(php -r 'echo PHP_VERSION;')"
fi

# Ensure Composer version is specified
if [ -z "${COMPOSER_VERSION}" ]; then
  echo >&2 "Please pass a valid Composer version using the \"--composer-version\" argument." && exit 1
fi

# Build Docker images
DOCKER_IMAGE="composer-update-check/test-${COMPOSER_VERSION}"
docker build \
  --build-arg COMPOSER_VERSION="${COMPOSER_VERSION}" \
  --build-arg PHP_VERSION="${PHP_VERSION}" \
  --tag "${DOCKER_IMAGE}" \
  --quiet \
  "${ROOT_PATH}"

# Print build Docker image
_check "Successfully built Docker image: \"${DOCKER_IMAGE}\""

# Test Docker image in test applications
for testApplication in "${APP_PATH}/test-application/v${PHP_MAJOR_VERSION}" "${APP_PATH}/test-application-empty"; do
  _check "Running update check for \"${testApplication#"$APP_PATH/"}\" (see output below)"
  if [ $# -gt 0 ]; then
    echo "  Command options: $*"
  fi

  echo
  git clean -xdfq "${testApplication}"
  docker run --rm -v "${testApplication}:/app" "${DOCKER_IMAGE}" --ansi "$@"
  echo
done
