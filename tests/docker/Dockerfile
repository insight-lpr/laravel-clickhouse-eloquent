FROM bitnami/laravel

RUN cd / && composer create-project laravel/laravel app
RUN composer require insight-lpr/laravel-clickhouse-eloquent
WORKDIR "/app"
