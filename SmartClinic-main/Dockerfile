FROM php:8.2-apache

# Apache solo puede tener UN modulo MPM activo a la vez. Se vio en Railway el
# error "AH00534: More than one MPM loaded" al arrancar el contenedor, pero
# la imagen base ya tenia solo mpm_prefork habilitado (a2enmod lo confirmo
# con "already enabled"), asi que forzamos el estado a mano borrando
# cualquier symlink de mpm_event/mpm_worker que pueda existir y dejando
# unicamente los de mpm_prefork. El "RUN apache2ctl -M" de abajo hace que el
# build FALLE inmediatamente (en vez de fallar horas despues en Railway) si
# sigue quedando mas de un MPM cargado, y de paso imprime en el log de build
# la lista real de modulos activos para poder diagnosticarlo.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
           /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && ls -la /etc/apache2/mods-enabled/ | grep -i mpm \
    && apache2ctl -M

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

# El log de arranque en Railway mostro que mpm_event.load/.conf seguian
# presentes en tiempo de ejecucion (con fecha de la imagen base) junto con
# mpm_prefork (con fecha del build), o sea que el "RUN rm -f" de arriba no
# quedo aplicado en el contenedor real (probablemente por cache de capas).
# En vez de pelear con la cache del build, se arregla en caliente: se borra
# mpm_event/mpm_worker y se deja solo mpm_prefork justo antes de arrancar
# Apache, en el propio arranque del contenedor, para que no dependa de nada
# que haya quedado cacheado del build.
#
# parameters.env queda en el repo con la configuracion de Docker LOCAL
# (DB_SERVER=db) para que el equipo pueda seguir corriendo el proyecto en su
# propia PC sin tocar nada. Si el contenedor arranca en Railway, ahi SI
# existen las variables DB_SERVER/DB_DATABASE/DB_PORT/DB_USER/DB_PSWD como
# variables de entorno reales (configuradas a mano en Railway, nunca en
# git), y este script las usa para pisar esas mismas lineas de
# parameters.env justo antes de arrancar Apache. Localmente esas variables
# no existen, asi que este bloque no hace nada y parameters.env se queda tal
# cual esta en el repo.
CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf; ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load; ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf; if [ -n \"$DB_SERVER\" ]; then echo 'Railway detectado: sobrescribiendo parameters.env con variables de entorno'; sed -i \"s|^DB_SERVER=.*|DB_SERVER=$DB_SERVER|\" /var/www/html/parameters.env; sed -i \"s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|\" /var/www/html/parameters.env; sed -i \"s|^DB_PORT=.*|DB_PORT=$DB_PORT|\" /var/www/html/parameters.env; sed -i \"s|^DB_USER=.*|DB_USER=$DB_USER|\" /var/www/html/parameters.env; sed -i \"s|^DB_PSWD=.*|DB_PSWD=$DB_PSWD|\" /var/www/html/parameters.env; fi; exec apache2-foreground"]
