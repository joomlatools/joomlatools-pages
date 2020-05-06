FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"
ENV APACHE_RUN_USER="gitpod"
ENV APACHE_RUN_GROUP="gitpod"

USER root

# Configure Apache2
COPY apache.conf /etc/apache2/apache2.conf

# Install extra PHP extensions
RUN apt-get update \
 && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y

# Configure MySQL's sql-mode
RUN echo -e "\nsql-mode=\"NO_ENGINE_SUBSTITUTION\"" >> /etc/mysql/mysql.conf.d/mysqld.cnf

# Note: Apache restarts and complains the folder doesn't exist
RUN mkdir /var/www/preview \
 && chown gitpod:gitpod /var/www/preview

# Fix file permissions
RUN chown -R gitpod:gitpod /var/www/
RUN chown -R gitpod:gitpod /etc/apache2/
RUN chown -R gitpod:gitpod /etc/mysql/

USER gitpod

# Install joomlatools/console
RUN composer global require joomlatools/console --no-interaction

# Make sure PATH is always updated with PATH to Composer package binaries
RUN echo 'export PATH=/home/gitpod/.composer/vendor/bin/:$PATH' >> ~/.bashrc

# Install Joomla site
COPY joomla-install.sh /home/gitpod/joomla-install.sh

RUN /home/gitpod/joomla-install.sh
RUN rm -f /home/gitpod/joomla-install.sh

# Copy the Pages content files into place
RUN mkdir -p /var/www/preview/joomlatools-pages/
COPY --chown=gitpod:gitpod config.php /var/www/preview/joomlatools-pages/config.php
COPY --chown=gitpod:gitpod index.html.php /var/www/preview/joomlatools-pages/pages/index.html.php

RUN ln -s /workspace/joomlatools-pages/content/ /var/www/preview/joomlatools-pages/