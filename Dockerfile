FROM openswoole/swoole:latest

RUN apt-get update && apt-get install -y libpq-dev git && pecl install pcov && docker-php-ext-enable pcov
