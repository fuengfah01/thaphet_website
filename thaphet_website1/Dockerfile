FROM php:8.2-apache

# ติดตั้ง mysqli
RUN docker-php-ext-install mysqli

# copy ไฟล์เว็บ
COPY . /var/www/html/

# เปิด mod_rewrite
RUN a2enmod rewrite