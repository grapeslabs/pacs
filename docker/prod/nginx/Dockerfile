ARG APP_VERSION=latest

FROM grapeslabs/pacs:${APP_VERSION} AS pacs_source

FROM nginx:stable-alpine

ARG UID=1000
ARG GID=1000

RUN set -eux; \
    deluser nginx; \
    addgroup -g ${GID} -S app; \
    adduser -S -D -H -u ${UID} -h /var/cache/nginx -s /sbin/nologin -G app -g app app; \
    mkdir -p /var/cache/nginx /var/log/nginx /var/run; \
    chown -R app:app /var/cache/nginx /var/log/nginx /var/run;

COPY --chown=app:app docker/prod/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=app:app docker/prod/nginx/default.conf /etc/nginx/conf.d/default.conf

COPY --from=pacs_source --chown=app:app /var/www/html/public /var/www/html/public
