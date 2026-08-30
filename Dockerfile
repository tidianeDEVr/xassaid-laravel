# Use PHP with Apache as the base image
FROM php:8.2-apache as web

# Install Additional System Dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    zip \
    vim \
    ffmpeg

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# yt-dlp : import de vidéos par lien (YouTube Shorts, Instagram Reels)
# depuis le back-office. Le binaire autonome `yt-dlp_linux` — l'asset `yt-dlp`
# est un script qui exige python3, absent de l'image php:8.2-apache.
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux \
    -o /usr/local/bin/yt-dlp && chmod a+rx /usr/local/bin/yt-dlp \
    && yt-dlp --version

# Enable Apache mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Install PHP extensions
# pdo_mysql : base de production (MariaDB cPanel) ; pdo_sqlite reste présent,
# la file de transcodage et les bascules de secours s'appuient dessus.
# gd : recadrage des avatars (MediaOptimizer). Sans elle, squareAvatar rend
# null et l'upload d'avatar échoue en 422 « Image illisible », aussi bien
# depuis le back-office que depuis l'application.
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install pdo_sqlite pdo_mysql zip gd

# Set PHP upload and post size limits
COPY php.ini /usr/local/etc/php/conf.d/


# Configure Apache DocumentRoot to point to Laravel's public directory
# and update Apache configuration files
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy the application code
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install project dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
