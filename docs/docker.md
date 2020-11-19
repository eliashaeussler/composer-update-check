# Docker image

The Composer plugin is also available as Docker image. This allows
its usage without explicitly requiring it in your project. It also
ensures the plugin can be safely used regardless of any requirements.

## Available images

You can choose from one of the following images, depending on the
Composer version you're using in your project:

| Image | Composer version | Plugin version |
| ----- | ---------------- | -------------- |
| `eliashaeussler/composer-update-check:latest` | v2 | latest |
| `eliashaeussler/composer-update-check:<version>-v2` | v2 | `<version>` |
| `eliashaeussler/composer-update-check:v2` | v2 | latest |
| `eliashaeussler/composer-update-check:<version>-v1` | v1 | `<version>` |
| `eliashaeussler/composer-update-check:v1` | v1 | latest |

## Usage

!!! important
    Make sure to mount your project into the `/app` directory of the container.

### General usage

```bash
docker run --rm -it -v $(pwd):/app eliashaeussler/composer-update-check [options]
```

[:octicons-link-external-16: Available options](usage.md#command-line-usage)

### Usage with docker-compose

Example `docker-compose.yaml` file:

```yaml
version: '3.6'

services:
  update-check:
    image: eliashaeussler/composer-update-check
    command: [<options>]
    volumes:
      - ./:/app
```

Usage:

```bash
docker-compose run --rm update-check [options]
```
