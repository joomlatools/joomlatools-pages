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

RUN  git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework.git /workspace/joomlatools-framework; \
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-files.git /workspace/joomlatools-framework-files;\
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-activities.git /workspace/joomlatools-framework-activities; \
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-scheduler.git /workspace/joomlatools-framework-scheduler; \
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-migrator.git /workspace/joomlatools-framework-migrator; \
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-ckeditor.git /workspace/joomlatools-framework-ckeditor; \
    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-tags.git /workspace/joomlatools-framework-tags;

RUN /home/gitpod/.composer/vendor/bin/joomla site:download preview

RUN chown gitpod:gitpod -R /home/gitpod/.composer
RUN chown gitpod:gitpod -R /home/gitpod/.joomlatools
RUN chown gitpod:gitpod -R /var/www/