#!/usr/bin/env sh
set -e

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
