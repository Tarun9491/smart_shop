FROM php:8.2-apache

# Install required extensions
RUN docker-php-ext-install mysqli pdo_mysql && \
    a2enmod rewrite headers

# Set Apache working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy entrypoint script and set permissions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

# Expose default HTTP port
EXPOSE 80

# Run dynamic entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
