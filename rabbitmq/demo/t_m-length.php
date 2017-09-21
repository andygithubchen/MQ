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
   "x-max-length" => 2,
)));
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);


#2. 生产消息============================================================================
$messageBody = implode(' ', array_slice($argv, 1));
$channel->basic_publish(new AMQPMessage($messageBody.'1'), $exchange);
$channel->basic_publish(new AMQPMessage($messageBody.'2'), $exchange);
$channel->basic_publish(new AMQPMessage($messageBody.'3'), $exchange); //@fixme


#3. 调消费者 ===========================================================================
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




