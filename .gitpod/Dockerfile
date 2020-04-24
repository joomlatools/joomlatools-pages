FROM gitpod/workspace-mysql:latest

COPY apache.conf /etc/apache2/apache2.conf

COPY my.cnf /etc/mysql/my.cnf

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

RUN apt-get update \
    && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y