#!/bin/bash

# Create log directory
mkdir -p /usr/local/directadmin/plugins/redis_management/logs

# Create data directory
mkdir -p /usr/local/directadmin/plugins/redis_management/data

# Fix ownerships
chown -R diradmin.diradmin /usr/local/directadmin/plugins/redis_management
chown -R redis.redis /usr/local/directadmin/plugins/redis_management/data

# Fix permissions
chmod -R 0775 /usr/local/directadmin/plugins/redis_management/admin/*
chmod -R 0775 /usr/local/directadmin/plugins/redis_management/user/*

# Inject user_destroy_post script
if [ ! -f "/usr/local/directadmin/scripts/custom/user_destroy_post.sh" ]; then
    echo -e "#!/bin/bash" > /usr/local/directadmin/scripts/custom/user_destroy_post.sh
    chmod +x /usr/local/directadmin/scripts/custom/user_destroy_post.sh
fi
if [ ! "$(cat /usr/local/directadmin/scripts/custom/user_destroy_post.sh | grep redis_management)" ]; then
    echo -e '\n/usr/local/directadmin/plugins/redis_management/php/Hooks/DirectAdmin/userDestroyPost.php "$username"' >> /usr/local/directadmin/scripts/custom/user_destroy_post.sh
fi

# Make userDestroyPost.php script executable
chmod +x /usr/local/directadmin/plugins/redis_management/php/Hooks/DirectAdmin/userDestroyPost.php