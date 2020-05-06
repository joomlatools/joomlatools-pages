FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

COPY apache.conf /etc/apache2/apache2.conf

RUN apt-get update \
 && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y

RUN echo 'sql-mode="NO_ENGINE_SUBSTITUTION"' >> /etc/mysql/mysql.conf.d/mysqld.cnf

#note apache restarts and complains the folder doesn't exist
RUN mkdir /var/www/preview
RUN chown gitpod:gitpod -R /var/www/


USER gitpod

RUN composer global require joomlatools/console --no-interaction

RUN echo 'export PATH=/home/gitpod/.composer/vendor/bin/:$PATH' >> ~/.bashrc

COPY joomla-install.sh /home/gitpod/joomla-install.sh
RUN chmod +x /home/gitpod/joomla-install.sh \
 && /home/gitpod/joomla-install.sh

