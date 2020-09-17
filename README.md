# Composer update check plugin

> Composer Plugin to check outdated packages, based on their requirements

## Installation

```bash
composer req --dev eliashaeussler/composer-update-check
```

## Usage

```bash
# Check all root requirements
composer update-check

# Skip dev-requirements
composer update-check --no-dev

# Exclude custom packages from update check
composer update-check -i "my-vendor/*" -i "roave/security-advisories"

# Exclude dev-requirements and custom packages
composer update-check -i "my-vendor/*" --no-dev
```

## Run tests

```bash
# Clone repository
git clone https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check.git
cd composer-update-check

# Install Composer dependencies
composer install

# Run all tests
composer test
```

## License

[GPL 3.0 or later](LICENSE)
