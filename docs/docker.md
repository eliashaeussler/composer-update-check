# Docker image

!!! attention
    The Docker image is deprecated and will be dropped with  version 2.0.0.<br>
    Consider using the [Composer plugin](install.md) instead.

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

## Handling SSH key authentication

!!! attention
    In case you need to authenticate yourself for specific packages
    within your project, you have to add your SSH key(s) to the
    container.

Make sure to add all relevant SSH keys to the Docker container,
either by mounting the whole `.ssh` directory or by adding each
key on its own.

The target directory inside the container is `/root/.ssh`.

### Mount the whole `.ssh` directory

#### General usage

```bash
docker run --rm -it \
  -v $(pwd):/app \
  -v ~/.ssh:/root/.ssh \
  eliashaeussler/composer-update-check
```

#### Usage with docker-compose

```yaml
version: '3.6'

services:
  update-check:
    image: eliashaeussler/composer-update-check
    volumes:
      - ./:/app
      - ~/.ssh:/root/.ssh
```

### Mount only relevant SSH keys

!!! tip
    In case you need multiple SSH keys for authentication, you're
    free to mount each of them separately into the Container. This
    can be achieved by declaring multiple volumes.

#### General usage

```bash
docker run --rm -it \
  -v $(pwd):/app \
  -v ~/.ssh/id_rsa:/root/.ssh/id_rsa \
  -v ~/.ssh/another_key:/root/.ssh/another_key \
  eliashaeussler/composer-update-check
```

#### Usage with docker-compose

```yaml
version: '3.6'

services:
  update-check:
    image: eliashaeussler/composer-update-check
    volumes:
      - ./:/app
      - ~/.ssh/id_rsa:/root/.ssh/id_rsa
      - ~/.ssh/another_key:/root/.ssh/another_key
```

### Provide `known_hosts` file

Especially when running the Docker image in CI pipelines, it might
be helpful to additionally mount a `known_hosts` file into the
container. This ensures all target hosts don't need to be rescanned.

The target file inside the container is `/root/.ssh/known_hosts`.

#### General usage

```bash
docker run --rm -it \
  -v $(pwd):/app \
  -v ~/.ssh/id_rsa:/root/.ssh/id_rsa \
  -v ~/.ssh/known_hosts:/root/.ssh/known_hosts \
  eliashaeussler/composer-update-check
```

#### Usage with docker-compose

```yaml
version: '3.6'

services:
  update-check:
    image: eliashaeussler/composer-update-check
    volumes:
      - ./:/app
      - ~/.ssh/id_rsa:/root/.ssh/id_rsa
      - ~/.ssh/known_hosts:/root/.ssh/known_hosts
```
