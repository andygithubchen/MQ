<?php
/*
 * 这个例子主要演示basic_publish()的第三个参数$mandatory, 当为true时,
 * 检查推送到的交换器上至少要有一个队列和这个交换器是绑定的，否则将消息返还给生产者；
 *
*/
include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$queue = 'queue_mandatory';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

$channel->exchange_declare('hidden_exchange', 'topic');
//如果不注释这两行就能看到效果
$channel->queue_declare($queue, false, true, false, false);
$channel->queue_bind($queue, 'hidden_exchange', 'rkey');


$message = new AMQPMessage("Hello World!");
//$channel->basic_publish( $msg, $exchange = '', $routing_key = '', $mandatory = false, $immediate = false, $ticket = null) {}
$channel->basic_publish($message, 'hidden_exchange', 'rkey', true);

$wait = true;
$returnListener = function ($replyCode, $replyText, $exchange, $routingKey, $message) use ($wait) {
    $GLOBALS['wait'] = false;

    echo "return: ",
    $replyCode, "\n",
    $replyText, "\n",
    $exchange, "\n",
    $routingKey, "\n",
    $message->body, "\n";
};

$channel->set_return_listener($returnListener);


while ($wait) {
    $channel->wait();
}

$channel->close();
$connection->close();
