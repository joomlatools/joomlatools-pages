FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

COPY apache.conf /etc/apache2/apache2.conf

RUN apt-get update \
 && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y

RUN echo -e "\nsql-mode=\"NO_ENGINE_SUBSTITUTION\"" >> /etc/mysql/mysql.conf.d/mysqld.cnf

#note apache restarts and complains the folder doesn't exist
RUN mkdir /var/www/preview
RUN chown gitpod:gitpod -R /var/www/


USER gitpod

# Install joomlatools/console
RUN composer global require joomlatools/console --no-interaction

# Make sure PATH is always updated with PATH to Composer package binaries
RUN echo 'export PATH=/home/gitpod/.composer/vendor/bin/:$PATH' >> ~/.bashrc

# Install Joomla site
COPY joomla-install.sh /home/gitpod/joomla-install.sh

RUN /home/gitpod/joomla-install.sh

# Copy the Pages content files into place
COPY config.php /var/www/preview/joomlatools-pages/pages/config.php
COPY index.html.php /var/www/preview/joomlatools-pages/pages/index.html.php