FROM php:8.2-apache

# Apache solo puede tener UN modulo MPM activo a la vez. La imagen base a
# veces trae mas de uno habilitado (por ejemplo mpm_event junto con
# mpm_prefork), lo cual Apache rechaza con el error "More than one MPM
# loaded" y el contenedor no arranca. PHP en modo Apache (mod_php) necesita
# mpm_prefork especificamente, asi que forzamos que sea el unico activo.
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable opcache

# OPcache: sin esto, PHP recompila cada controlador/DAO/plantilla en CADA
# request, lo cual se siente lento al navegar entre pantallas (Inventario,
# Compras, Kárdex, etc). Con OPcache el bytecode se cachea en memoria.
#
# validate_timestamps=1 es a propósito: el proyecto corre con bind mount
# (./:/var/www/html), así que los archivos se editan en vivo desde el host,
# y OPcache necesita poder detectar esos cambios sin rebuild.
#
# revalidate_freq=0 (revisar el archivo en CADA request) se cambió a 2
# segundos. En Windows, cada archivo dentro del bind mount pasa por la capa
# de virtualización de Docker Desktop (WSL2/Hyper-V), que es bastante más
# lenta que el disco nativo para operaciones pequeñas y frecuentes como
# stat(). Con revalidate_freq=0, CADA request de CADA pantalla dispara un
# stat() por cada uno de los ~20-30 archivos PHP que se cargan (controlador,
# DAOs, utilidades, etc.), y eso se siente especialmente al enviar
# formularios (guardar, filtrar), que además esperan la respuesta completa
# antes de repintar la página. Con revalidate_freq=2 ese chequeo se hace
# como mucho una vez cada 2 segundos en vez de en cada click, sin perder la
# posibilidad de editar y recargar (los cambios se ven casi al instante).
#
# realpath_cache_size/ttl: por defecto PHP resuelve la ruta real de cada
# archivo (include, file_get_contents de las plantillas .tpl, etc.) en cada
# acceso, lo cual es otro viaje al filesystem virtualizado. Cachear esas
# rutas resueltas por más tiempo reduce ese costo, sobre todo para las
# plantillas del Renderer que se leen del disco en cada render (esas no las
# cachea OPcache porque no son código PHP).
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

EXPOSE 80
