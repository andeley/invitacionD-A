# Usa una imagen base de PHP con las últimas actualizaciones de seguridad
FROM php:8.2-fpm-alpine

# Actualizar el sistema e instalar dependencias
RUN apk add --no-cache --update libzip-dev nginx

# Instalar la extensión PHP ZIP
RUN docker-php-ext-install zip

# Crear el directorio de trabajo
WORKDIR /var/www/html

# Copiar tu aplicación al contenedor
COPY . .

# Copiar la configuración de Nginx (asegúrate de tener el archivo nginx.conf)
COPY ./nginx.conf /etc/nginx/nginx.conf

# Exponer el puerto 80
EXPOSE 80

# Configurar el usuario de PHP (opcional)
USER www-data

# Comando para iniciar Nginx y PHP-FPM
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
