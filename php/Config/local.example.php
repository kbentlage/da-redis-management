<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 12/10/2016
 * Time: 21:13
 */

/**
 * In this file you can override all configurations that are defined in the "main.php" configuration file.
 *
 * For example you can define where the Redis data is stored, and under which user redis runs.
 *
 * Please define this in this "local.php" file because of the "main.php" file can be overwritten by new versions.
 */

return [
    'redis' => [
        'user'      => 'myRedisUser',
        'group'     => 'myRedisGroup',
        'dataDir'   => '/home/redis',
    ],
];