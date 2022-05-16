#!/usr/bin/env bash
set -e

# Resolve variables
ROOT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
APP_PATH="${ROOT_PATH}/tests/Build"
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION;')"
COMPOSER_VERSION=""

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
    *)
      POSITIONAL+=("$1")
      shift
      ;;
  esac
done
set -- "${POSITIONAL[@]}"

# Print check mark with text
_check() {
  echo -e "\xE2\x9C\x94 $1"
}

# Ensure Composer version is specified
if [ -z "${COMPOSER_VERSION}" ]; then
  echo >&2 "Please pass a valid Composer version using the \"--composer-version\" argument." && exit 1
fi

# Build Docker images
DOCKER_IMAGE="composer-update-check/test-${COMPOSER_VERSION}"
docker build \
  --build-arg COMPOSER_VERSION="${COMPOSER_VERSION}" \
  --tag "${DOCKER_IMAGE}" \
  --quiet \
  "${ROOT_PATH}"

# Print build Docker image
_check "Successfully built Docker image: \"${DOCKER_IMAGE}\""

# Test Docker image in test applications
for testApplication in "${APP_PATH}/test-application/v${PHP_VERSION}" "${APP_PATH}/test-application-empty"; do
  _check "Running update check for \"${testApplication#"$APP_PATH/"}\" (see output below)"
  if [ $# -gt 0 ]; then
    echo "  Command options: $*"
  fi

  echo
  git clean -xdfq "${testApplication}"
  docker run --rm -v "${testApplication}:/app" "${DOCKER_IMAGE}" --ansi "$@"
  echo
done
