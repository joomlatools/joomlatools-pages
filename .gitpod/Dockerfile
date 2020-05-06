FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

COPY apache.conf /etc/apache2/apache2.conf

RUN apt-get update \
    && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y

#note apache restarts and complains the folder doesn't exist
RUN mkdir /var/www/preview

RUN composer global require joomlatools/console --no-interaction

RUN export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

RUN /home/gitpod/.composer/vendor/bin/joomla plugin:install joomlatools/console-joomlatools:dev-master

RUN /home/gitpod/.composer/vendor/bin/joomla site:download preview

RUN chown gitpod:gitpod -R /home/gitpod/.composer
RUN chown gitpod:gitpod -R /home/gitpod/.joomlatools
RUN chown gitpod:gitpod -R /var/www/

# Quick test to see if we can create directories in /workspace during Docker build time
USER gitpod
RUN /home/gitpod/.composer/vendor/bin/joomla site:download --use-webroot-dir --www=/workspace/preview/ preview