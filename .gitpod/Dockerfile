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

#RUN  git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework.git /workspace/joomlatools-framework; \
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-files.git /workspace/joomlatools-framework-files;\
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-activities.git /workspace/joomlatools-framework-activities; \
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-scheduler.git /workspace/joomlatools-framework-scheduler; \
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-migrator.git /workspace/joomlatools-framework-migrator; \
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-ckeditor.git /workspace/joomlatools-framework-ckeditor; \
#    git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-tags.git /workspace/joomlatools-framework-tags;

##ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
#RUN mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

#RUN /home/gitpod/.composer/vendor/bin/joomla site:download preview

#RUN /home/gitpod/.composer/vendor/bin/joomla site:install preview --mysql-login=root:

#RUN mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

RUN chown gitpod:gitpod -R /var/www/