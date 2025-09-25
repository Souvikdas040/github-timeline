FROM php:8.2-apache

# Install required PHP extensions and msmtp
RUN apt-get update && apt-get install -y \
    cron \
    msmtp \
    msmtp-mta \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Copy PHP source code
COPY src/ /var/www/html/

# Fix permissions
RUN chmod -R 755 /var/www/html && chown -R www-data:www-data /var/www/html

# Add crontab file
COPY docker-cron /etc/cron.d/github-timeline

# Give execution rights on the cron job file
RUN chmod 0644 /etc/cron.d/github-timeline

# Apply the cron job
RUN crontab /etc/cron.d/github-timeline

# Create log file for cron
RUN touch /var/log/cron.log

# Configure msmtp
COPY msmtprc /etc/msmtprc
RUN chmod 600 /etc/msmtprc && chown www-data:www-data /etc/msmtprc

# Start Apache + cron
CMD service cron start && apache2-foreground
