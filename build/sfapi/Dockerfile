FROM php:8.2-fpm-alpine

# Arguments définis dans le compose.yaml
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
ARG GROUP_NAME

# Installation des dépendances système et extensions PHP
RUN apk add --no-cache acl fcgi file gettext git gnu-libiconv \
    && docker-php-ext-install pdo pdo_mysql

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie et configuration du script d'entrée (entrypoint)
COPY ./build/sfapi/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Gestion de l'utilisateur (Match l'ID de votre machine hôte)
RUN addgroup -g $GROUP_ID $GROUP_NAME && \
    adduser -u $USER_ID -G $GROUP_NAME -D $USER_NAME

USER $USER_NAME

WORKDIR /app/sfapi

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]