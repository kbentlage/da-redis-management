# DirectAdmin SSH Key Management Plugin
Welcome to this repository of an unofficial DirectAdmin plugin for managing Redis instances. 

With this plugin end-users on an DirectAdmin server can easliy add and remove their redis instances.

I developed and used this plugin for over a year now on our own servers, but I decided to release it to the public! So everyone can use this.

# Installation
## Requirements
This plugin works on every DirectAdmin server, but the included setup script is only for RHEL/CentOS with systemctl support. Maybe I will add install scripts for Ubuntu / Debian in the future.

For enabeling, starting and stopping for redis instances it uses sudo with minimal permissions.
## Plugin installation
```
cd /usr/local/directadmin/plugins
git clone https://github.com/kbentlage/da-redis-management.git redis_management
sh redis_management/scripts/install.sh
```

## Redis installation
```
cd /usr/local/directadmin/plugins/redis_management/setup
sh install.sh
```

# Update
```
cd /usr/local/directadmin/plugins/redis_management
git pull
```

# Screenshots
List Redis instances

![List Redis instances](https://raw.githubusercontent.com/kbentlage/da-redis-management/master/screenshots/list.png)
