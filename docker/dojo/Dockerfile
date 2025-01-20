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
WORKDIR /dojo

# Create non-root user
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN groupadd -g ${GROUP_ID} deshi && \
  useradd -u ${USER_ID} -g deshi -s /bin/bash -m deshi && \
  chown deshi:deshi /dojo

# Set environment variables for Chrome
ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV PANTHER_CHROME_BINARY=/usr/bin/chromium
ENV PANTHER_WEBDRIVER_BINARY=/usr/bin/chromedriver

# Default command to keep container running
CMD ["tail", "-f", "/dev/null"]
