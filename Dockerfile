FROM php:8.2-cli

# Install MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set working directory
WORKDIR /var/www

# Copy all project files
COPY . .

# Expose port
EXPOSE 8080

# Start PHP built-in server pointing at public/
CMD php -S 0.0.0.0:${PORT:-8080} -t public
