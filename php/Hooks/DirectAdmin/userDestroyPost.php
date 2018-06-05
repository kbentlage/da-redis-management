#!/usr/local/bin/php -c/usr/local/directadmin/plugins/redis_management/php/php.ini
<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/bootstrap.php';

$redisController = new \DirectAdmin\RedisManagement\Controllers\RedisController;

if(isset($argv[1]) && $argv[1])
{
    $username = trim($argv[1]);

    $redisController->deleteAllUserInstances($username);
}
?>