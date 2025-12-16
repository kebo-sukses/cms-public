FROM php:8.1-cli

# Install system deps needed for Composer and PHPUnit
RUN apt-get update \
    && apt-get install -y zip unzip git curl \
    && apt-get install -y libcurl4-openssl-dev \
    && apt-get install -y clamav clamav-freshclam \
    && rm -rf /var/lib/apt/lists/*

# Copy composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP extensions required for integration tests (curl)
RUN docker-php-ext-install curl || true

# Update ClamAV database (best-effort)
RUN if command -v freshclam >/dev/null 2>&1; then freshclam || true; fi

# Default: install dependencies and run PHPUnit
CMD ["bash", "-lc", "composer install --no-interaction --prefer-dist && ./vendor/bin/phpunit --configuration phpunit.xml --verbose"]
