#!/bin/bash

pkill mysqld
sleep 5 # we need to make sure has MySQL shut down
mysqld --daemonize