<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('HOST', 'localhost');
define('PORT', 5672);
define('USER', 'andy');
define('PASS', '123456');
define('VHOST', '/tom');

//If this is enabled you can see AMQP output on the CLI
define('AMQP_DEBUG', false);
