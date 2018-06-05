#!/bin/bash

# Install EPEL repository (if needed)
if [ ! "$(rpm -qa | grep epel-release)" ]; then
    yum -y install epel-release
fi

# Install redis (if needed)
if [ ! "$(rpm -qa | grep redis)" ]; then
    yum -y install redis
fi

# Determine PHP version
PHP_VERSION=$(php -i | grep 'PHP Version');

# Remount /tmp with execute permissions (only if /tmp partition exists and is read-only)
REMOUNT_TMP=false
if [ "$(mount | grep /tmp | grep noexec)" ]; then
    mount -o remount,exec /tmp

    REMOUNT_TMP=true
fi

# Install php-redis module (if not installed yet)
if [ ! "$(php -m | grep redis)" ]; then
    if [[ $PHP_VERSION == *"7."* ]]; then
        yes '' | pecl install -f redis
    else
        yes '' | pecl install -f redis-2.2.8
    fi
fi

# Enable redis php extension in custom php.ini (if not enabled yet)
if [ ! "$(cat /usr/local/lib/php.conf.d/20-custom.ini | grep redis.so)" ]; then
    echo -e "\n; Redis\nextension=redis.so" >> /usr/local/lib/php.conf.d/20-custom.ini
fi

# Restart apache
systemctl restart httpd

# Remount /tmp with noexec permissions (if needed)
if [ "$REMOUNT_TMP" = true ] ; then
    mount -o remount,noexec /tmp
fi

# Create instances folder for redis instances
mkdir -p /etc/redis/instances

# Chown instances folder
chown -R redis.redis /etc/redis/instances

# Remove existing systemctl script
rm -f /lib/systemd/system/redis.service

# Copy new systemctl scripts
cp -a redis@.service /lib/systemd/system/
cp -a redis.service /lib/systemd/system/

# Reload systemctl daemons
systemctl daemon-reload

# Enable main service
systemctl enable redis.service

# Copy sudoers file
cp -a redis.sudoers /etc/sudoers.d/redis

# Fix sudoers file permissions
chown root.root /etc/sudoers.d/redis