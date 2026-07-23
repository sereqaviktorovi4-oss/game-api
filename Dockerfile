FROM php:8.1-apache

# Включаем модуль перенаправления mod_rewrite
RUN a2enmod rewrite

# Копируем все файлы проекта в веб-директорию
COPY . /var/www/html/

# Открываем 80 порт
EXPOSE 80
