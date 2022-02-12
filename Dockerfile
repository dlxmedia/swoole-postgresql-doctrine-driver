FROM openswoole/swoole:latest

RUN apt-get update && apt-get install -y libpq-dev git && pecl install pcov && docker-php-ext-enable pcov

# WORKDIR /var/www
# COPY . /var/www
# RUN composer install -n --prefer-dist --no-progress;
# ENTRYPOINT ["tail", "-f", "/dev/null"]