#!/bin/bash
#4FKokt0SRM

CONTROLLER=/var/www2/controller
SOURCE=/var/www2/start

#Make common kernel
cd $CONTROLLER/bitrix/clients && rm -rf `ls -a`
cd $SOURCE/bitrix
cp -a -t $CONTROLLER/bitrix/clients/ \
    admin \
    components \
    header.php \
    images \
    js \
    p3p.xml \
    redirect.php \
    rss.php \
    urlrewrite.php \
    bx.php \
    footer.php \
    help \
    index.php \
    modules \
    rk.php \
    spread.php \
    themes \
    tools \
    wizards
cp -a $CONTROLLER/bitrix/wizards/bitrix/controller_site $CONTROLLER/bitrix/clients/wizards/bitrix/
rm -rf $CONTROLLER/bitrix/clients/modules/search

#Make database dump
rm -rf $CONTROLLER/client/db && mkdir $CONTROLLER/client/db && cd $CONTROLLER/client/db && mysqldump start6 >dump.sql

#Make common public
rm -rf $CONTROLLER/client/public
mkdir $CONTROLLER/client/public
cd $SOURCE
cp -a -t $CONTROLLER/client/public/ \
    404.php \
    index.php \
    license.html \
    readme.html \
    upload \
    .htaccess \
    .access.php
cd $SOURCE/bitrix
mkdir $CONTROLLER/client/public/bitrix_personal
cp -a -t $CONTROLLER/client/public/bitrix_personal/ php_interface
mkdir $CONTROLLER/client/public/bitrix_personal/templates && cd templates && cp -a -t $CONTROLLER/client/public/bitrix_personal/templates/ .default


cd $CONTROLLER/client/public
mv .htaccess .htaccess.controller
cp -t . ../.htaccess ../index_controller.php

#Fix permissions
cd $CONTROLLER/bitrix/clients/
chown -R www-controller:www-data .
chmod -R u=rw,g=r,o=r .
find . -type d -exec chmod u+x,g+x,o+x {} \;

cd $CONTROLLER/client/
chown -R www-controller:www-data .
chmod -R u=rw,g=r,o=r .
find . -type d -exec chmod u+x,g+x,o+x {} \;
