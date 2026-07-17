FROM mcr.microsoft.com/devcontainers/php:1-8.2-bullseye

# Corrección para Dev Containers: algunas imágenes traen el repositorio de Yarn
# con una llave GPG vencida/no disponible. No se necesita Yarn para SmartClinic,
# por eso se elimina antes de ejecutar apt-get update.
RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/sources.list.d/yarn.list.save     && apt-get update     && export DEBIAN_FRONTEND=noninteractive     && apt-get install -y --no-install-recommends mariadb-client     && apt-get clean -y     && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql
