<?php

global $_POST, $_GET;

parse_str(getenv('QUERY_STRING'), $_GET);
parse_str(getenv('POST'), $_POST);

require_once dirname(__DIR__) . '/php/Controllers/RedisController.php';
?>