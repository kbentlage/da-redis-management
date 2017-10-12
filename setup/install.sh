#!/bin/bash

# Install EPEL repository
yum -y install epel-release

# Install redis
yum -y install redis

# Determine PHP version
PHP_VERSION=$(php -i | grep 'PHP Version');

# Remount /tmp with execute permissions
mount -o remount,exec /tmp

if [[ $PHP_VERSION == *"7.0"* ]]
then
        pecl install redis
else
        pecl install redis-2.2.8
fi

# Enable redis php extension in custom php.ini
echo -e "\n; Redis\nextension=redis.so" >> /usr/local/lib/php.conf.d/20-custom.ini

# Restart apache
systemctl restart httpd

# Remount /tmp with noexec permissions
mount -o remount,noexec /tmp

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