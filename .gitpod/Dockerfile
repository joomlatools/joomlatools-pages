FROM gitpod/workspace-mysql:latest

ENV APACHE_DOCROOT_IN_REPO="preview"

USER root

COPY apache.conf /etc/apache2/apache2.conf

COPY my.cnf /etc/mysql/my.cnf

RUN apt-get update \
    && apt-get install php-ldap php-xml libxml2-dev build-essential php-yaml -y