FROM php:7.4-cli
RUN docker-php-ext-install pcntl
RUN mkdir -p /opt/app
COPY . /opt/app
WORKDIR /opt/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN make install
CMD [ "./bin/app", "hello" ]