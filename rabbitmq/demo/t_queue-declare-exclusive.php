<?php
/**
 * 用于演示queue_declare()的第四个参数
 */
include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'exchange';
$queue = 'queue';

//@fixme
//$connection0 = $connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

//@fixme
$connection0 = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel0 = $connection0->channel();


#1. 声明队列和交换器====================================================================
$channel->queue_declare($queue, false, false, true, false); //@fixme
$channel->exchange_declare($exchange, 'direct', false, false, false);
$channel->queue_bind($queue, $exchange);


#2. 生产消息============================================================================
$messageBody = "abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz";
$message = new AMQPMessage($messageBody);
$channel->basic_publish($message, $exchange);


#3. 消费信息 ===========================================================================
function process_message($message) {
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";
}
$channel0->basic_consume($queue, '', true, false, false, false, 'process_message');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel0, $connection0);

while (count($channel0->callbacks)) {
    $channel0->wait();
}


