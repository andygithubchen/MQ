<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'router';
$queue = 'queue';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
$channel->queue_declare($queue, false, true, false, false, false, new AMQPTable(array(
   "x-message-ttl" => 5000,
)));
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);


#2. 生产消息============================================================================
$messageBody = implode(' ', array_slice($argv, 1));
$message = new AMQPMessage($messageBody);
$channel->basic_publish($message, $exchange);


#3. 调消费者 ===========================================================================
sleep(4); //@fixme 修改这个时间为4或6就能看到效果
echo "到时间了\n";

function process_message($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue';
    echo "\n--------\n";
}
$channel->basic_consume($queue, "", false, false, false, false, 'process_message');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}




