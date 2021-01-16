#!/usr/bin/env bash
set -e

# Resolve variables
ROOT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
APP_PATH="${ROOT_PATH}/tests/Build"
DOCKER_IMAGE="$1"

# Ensure Docker image is specified
if [ -z "${DOCKER_IMAGE}" ]; then
  >&2 echo "Please pass the Docker image to be tested as the first parameter to this script." && exit 1
else
  shift
fi

# Print check mark with text
_check() {
  echo -e "\xE2\x9C\x94 $1"
}

# Print selected Docker image
_check "Selected Docker image: \"${DOCKER_IMAGE}\""

# Test Docker image in test applications
for testApplication in "${APP_PATH}/test-application" "${APP_PATH}/test-application-empty"; do
  _check "Running update check for \"$(basename "${testApplication}")\" (see output below)"
  if [ $# -gt 0 ]; then
    echo "  Command options: $*"
  fi

  echo
  git clean -xdfq "${testApplication}"
  docker run --rm -v "${testApplication}:/app" "${DOCKER_IMAGE}" --ansi "$@"
  echo
done
