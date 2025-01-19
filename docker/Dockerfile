FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    libxml2-dev \
    chromium \
    chromium-driver

# Install PHP extensions
RUN docker-php-ext-install zip dom

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Set environment variables for Chrome
ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV PANTHER_CHROME_BINARY=/usr/bin/chromium
ENV PANTHER_WEBDRIVER_BINARY=/usr/bin/chromedriver

# Default command to keep container running
CMD ["tail", "-f", "/dev/null"]