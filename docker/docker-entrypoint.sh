#!/usr/bin/env sh
set -e

# Show deprecation warning
printf "\x1b[31m%s\x1b[0m\n" "Usage of this Docker image is deprecated and will be dropped with version 2.0.0."
printf "\x1b[31m%s\x1b[0m\n" "Consider using the Composer plugin \"eliashaeussler/composer-update-check\" instead."
echo

# Add private SSH keys
eval "$(ssh-agent -s)" >/dev/null
if [ -d "${HOME}/.ssh" ]; then
  for file in ${HOME}/.ssh/*; do
    if grep -q PRIVATE "${file}"; then
      ssh-add "${file}"
    fi
  done
fi

# Run update check
composer update-check "$@"
