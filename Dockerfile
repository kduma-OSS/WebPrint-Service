FROM --platform=$BUILDPLATFORM php:8.1-cli AS builder

ARG BUILD_VERSION=docker

RUN apt-get update \
	&& apt-get -y install wget unzip \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/317db84d632c1a99d8617019ad4b000026bf7d16/web/installer -O - -q | php -- --force --install-dir=/usr/local/bin --filename=composer
RUN php -v

COPY . /usr/src/wss
WORKDIR /usr/src/wss

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer install --no-interaction --no-progress --no-scripts --optimize-autoloader --ignore-platform-req=ext-sockets

RUN ./webprint-service app:build --build-version=${BUILD_VERSION} --ansi -vvv


FROM php:8.1-cli AS runtime

RUN apt-get update \
	&& apt-get -y install cups-client \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install sockets

COPY --from=builder /usr/src/wss/builds/webprint-service /usr/src/wss/webprint-service
WORKDIR /usr/src/wss
RUN mkdir /tmp/webprint-service

ENV DEBUG_OUTPUT_DIRECTORY /tmp/webprint-service-debug-output
ENV WEBPRINT_SERVER_ENDPOINT="http://webprint-server/api/print-service"
ENV WEBPRINT_SERVICE_KEY="1|DEBUG_WEBPRINT_SERVICE_KEY"
ENV CUPS_SERVER="cups:631"

#VOLUME /tmp/webprint-service-debug-output

CMD [ "php", "./webprint-service", "watch" ]


