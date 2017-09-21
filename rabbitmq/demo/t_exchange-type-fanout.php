<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'router_andy';
$queue = 'queue';
$queue2 = 'queue2';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
$channel->exchange_declare($exchange, 'fanout', false, false, true);

$channel->queue_declare($queue, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_bind($queue, $exchange, 'routing1_key');

$channel->queue_declare($queue2, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_bind($queue2, $exchange, 'routing2_key');

#2. 生产消息============================================================================
$messageBody = 'andychen';
$message = new AMQPMessage($messageBody);
$channel->basic_publish($message, $exchange);
//$channel->basic_publish($message, $exchange, 'routing2_key'); //@fixme 可以修改这个来演示


#3. 调消费者 ===========================================================================
function process_message($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue----'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
function process_message2($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue2----'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
$channel->basic_consume($queue, "", false, true, false, false, 'process_message');
$channel->basic_consume($queue2, "", false, true, false, false, 'process_message2');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}




