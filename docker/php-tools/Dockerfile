FROM php:8.2-cli-bookworm

ARG WP_CLI_VERSION=2.11.0

RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    ca-certificates \
    curl \
    default-mysql-client \
    git \
    jq \
    less \
    rsync \
    unzip \
    zip \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" mysqli

COPY --from=composer:2.8.12 /usr/bin/composer /usr/local/bin/composer

RUN curl -fsSL -o /usr/local/bin/wp \
    "https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar" \
  && chmod +x /usr/local/bin/wp

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="/work/vendor/bin:${PATH}"

WORKDIR /work

CMD ["bash"]
