FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

# Configure Apache2
COPY apache.conf /etc/apache2/apache2.conf

# Install extra PHP extensions
RUN apt-get update \
 && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y

# Configure MySQL's sql-mode
RUN echo -e "\nsql-mode=\"NO_ENGINE_SUBSTITUTION\"" >> /etc/mysql/mysql.conf.d/mysqld.cnf

# Note: Apache restarts and complains the folder doesn't exist
RUN mkdir /var/www/preview

# Fix file permissions
RUN chown gitpod:gitpod -R /var/www/
RUN chown gitpod:gitpod -R /etc/apache2/
RUN chown gitpod:gitpod -R /etc/mysql/

USER gitpod

# Install joomlatools/console
RUN composer global require joomlatools/console --no-interaction

# Make sure PATH is always updated with PATH to Composer package binaries
RUN echo 'export PATH=/home/gitpod/.composer/vendor/bin/:$PATH' >> ~/.bashrc

# Install Joomla site
COPY joomla-install.sh /home/gitpod/joomla-install.sh

RUN /home/gitpod/joomla-install.sh
RUN rm -f /home/gitpod/joomla-install.sh

