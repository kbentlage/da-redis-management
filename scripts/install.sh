#!/bin/bash

# Create log directory
mkdir /usr/local/directadmin/plugins/redis_management/logs

# Create data directory
mkdir /usr/local/directadmin/plugins/redis_management/data

# Fix ownerships
chown -R diradmin.diradmin /usr/local/directadmin/plugins/redis_management
chown -R redis.redis /usr/local/directadmin/plugins/redis_management/data

# Fix permissions
chmod -R 0775 /usr/local/directadmin/plugins/redis_management/admin/*
chmod -R 0775 /usr/local/directadmin/plugins/redis_management/user/*
