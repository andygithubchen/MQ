<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'router_andy';
$queue1 = 'queue1';
$queue2 = 'queue2';
$queue3 = 'queue3';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
$channel->exchange_declare($exchange, 'topic', false, false, true); //@fixme 注意这里1

$channel->queue_declare($queue1, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_declare($queue2, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_declare($queue3, false, true, false, true, false, new AMQPTable(array()));

$channel->queue_bind($queue1, $exchange, 'routing1_key.#'); //@fixme 注意这里2
$channel->queue_bind($queue2, $exchange, 'routing2_key.#');
$channel->queue_bind($queue3, $exchange, '#.routing1_key.#');

#2. 生产消息============================================================================
$channel->basic_publish(new AMQPMessage('andychen1'), $exchange, 'routing2_key.routing1_key'); //@fixme 注意这里3
$channel->basic_publish(new AMQPMessage('andychen2'), $exchange, 'routing1_key.routing2_key');


#3. 调消费者 ===========================================================================
function process_message1($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue1----routing1_key.#------'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
function process_message2($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue2----routing2_key.#------'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
function process_message3($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue3----#.routing1_key.#------'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
$channel->basic_consume($queue1, "", false, true, false, false, 'process_message1');
$channel->basic_consume($queue2, "", false, true, false, false, 'process_message2');
$channel->basic_consume($queue3, "", false, true, false, false, 'process_message3');



function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}




