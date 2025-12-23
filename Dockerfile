FROM gfcg/vesica-php73:dev

RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/php/public#g' /etc/apache2/sites-enabled/000-default.conf \
 && printf '\n<Directory /var/www/html/php/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf
