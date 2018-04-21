FROM mhart/alpine-node:6
MAINTAINER Charles du Jeu <charles@pydio.com>

RUN apk --update add wget curl git php5 php5-curl php5-openssl php5-json php5-phar php5-dom php5-gd zip && rm /var/cache/apk/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN npm install -g grunt-cli

WORKDIR /app
COPY ./build.sh .

USER root
# Set up the command arguments
CMD ["./build.sh"]