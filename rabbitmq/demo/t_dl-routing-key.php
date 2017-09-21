<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Wire;

$exchange = 'router';
$exchange2 = 'router2';
$queue = 'queue';
$queue2 = 'queue2';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
$channel->queue_declare($queue, false, true, false, false, false, new AMQPTable(array(
   "x-max-length-bytes" => 2,
   "x-dead-letter-exchange" => "router2",
   "x-dead-letter-routing-key" => "router2_key_1",
)));
$channel->queue_declare($queue2, false, true, false, false, false, new AMQPTable(array(
)));

$channel->exchange_declare($exchange, 'topic', false, false, true);
$channel->exchange_declare($exchange2, 'direct', false, true, false);

$channel->queue_bind($queue, $exchange, 'routing1_key');
$channel->queue_bind($queue2, $exchange2, 'router2_key_1');


#2. 生产消息============================================================================
$messageBody = implode(' ', array_slice($argv, 1));
$message = new AMQPMessage($messageBody);
$channel->basic_publish($message, $exchange, 'routing1_key');


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
$channel->basic_consume($queue, "", false, true, true, false, 'process_message');
$channel->basic_consume($queue2, "", false, true, true, false, 'process_message2');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}




