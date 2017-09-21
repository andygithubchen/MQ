<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPSSLConnection;

define('CERTS_PATH', '/opt/client/');

//为什么要这样配置，可以查看这里：http://php.net/manual/zh/context.ssl.php
$sslOptions = array(
    'cafile' => CERTS_PATH . 'cert.pem',
    'local_cert' => CERTS_PATH . 'local_cert.pem', //这个pem文件我还不太确定是怎么来的
    //'verify_peer' => true
    'verify_peer' => false
);

//$connection = new AMQPSSLConnection(HOST, PORT, USER, PASS, VHOST, $sslOptions);
$connection = new AMQPSSLConnection(HOST, 5671, USER, PASS, VHOST, $sslOptions);


function shutdown($connection) {
    $connection->close();
}

register_shutdown_function('shutdown', $connection);

while (true) {
}
