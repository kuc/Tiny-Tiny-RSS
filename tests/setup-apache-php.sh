#!/bin/bash

set -ev

sudo apt-get install apache2 libapache2-mod-fastcgi

# Enable php-fpm
sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
sudo a2enmod rewrite actions fastcgi alias
cat tests/conf/php.ini >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

# Fix missing PHP pool definition
if [[ ${TRAVIS_PHP_VERSION:0:3} == "7.0" ]]; then
	sudo cp tests/conf/www.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/;
fi
if [[ ${TRAVIS_PHP_VERSION:0:3} == "7.1" ]]; then
	sudo cp tests/conf/www.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/;
fi

# Run PHP
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

# Configure apache virtual hosts
sudo cp -f tests/conf/apache.conf /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart
