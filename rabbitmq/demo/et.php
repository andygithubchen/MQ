<?php
/**
 * 批量推送消息
 * Usage:
 *  php batch_publish.php msg_count batch_size
 * The integer arguments tells the script how many messages to publish.
 */
include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'bench_exchange';
$queue = 'bench_queue';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
//$channel->queue_declare($queue, false, false, true, false);
$channel->exchange_declare($exchange, 'direct', false, false, false);
$channel->queue_bind($queue, $exchange);


#2. 生产消息============================================================================

#3. 消费信息 ===========================================================================
//die;
function process_message($message) {
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";
}
$channel->basic_consume($queue, '', true, false, false, false, 'process_message');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}


