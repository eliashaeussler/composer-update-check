#!/usr/bin/env bash
set -e

# Resolve variables
ROOT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION;')"
APP_PATH="${ROOT_PATH}/tests/Build/test-application/v${PHP_VERSION}"
TEMP_DIR="/tmp"

# Check if temp directory is writeable
if [ ! -w "${TEMP_DIR}" ]; then
  TEMP_DIR="$(dirname "${ROOT_PATH}")"
fi
TEMP_PATH="${TEMP_DIR}/update-check-test"

# Define cleanup function for several signals
function cleanup() {
  exitCode=$?
  rm -rf "${TEMP_PATH}"
  exit $exitCode
}
trap cleanup INT ERR EXIT

# Prepare temporary application
cp -r "${APP_PATH}" "${TEMP_PATH}"
rm -rf "${TEMP_PATH}/vendor"
composer config --working-dir "${TEMP_PATH}" repositories.local path "${ROOT_PATH}"
composer require --working-dir "${TEMP_PATH}" --quiet --dev "eliashaeussler/composer-update-check:*@dev"

# Run update check
composer update-check --working-dir "${TEMP_PATH}" --ansi "$@"
