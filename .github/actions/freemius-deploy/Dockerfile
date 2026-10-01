# Replacement for buttonizer/freemius-deploy@v0.1.3, whose php:7.4 base image
# (Debian buster, end of life) can no longer apt-get install git.
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends git ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && git clone --depth 1 https://github.com/Freemius/freemius-php-sdk.git /freemius-php-api

COPY deploy.php /deploy.php

ENTRYPOINT ["php", "/deploy.php"]
