<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'exchange_headers';
$queue1 = 'queue1';
$queue2 = 'queue2';
$queue3 = 'queue3';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
$channel->exchange_declare($exchange, 'headers', false, false, true); //@fixme 注意这里

$channel->queue_declare($queue1, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_declare($queue2, false, true, false, true, false, new AMQPTable(array()));
$channel->queue_declare($queue3, false, true, false, true, false, new AMQPTable(array()));

$channel->queue_bind($queue1, $exchange, null, false, new AMQPTable(array('x-match'=>'all', 'format'=>'pdf', 'type'=>'report'))); //@fixme 注意这里
$channel->queue_bind($queue2, $exchange, null, false, new AMQPTable(array('x-match'=>'any', 'format'=>'pdf'))); //@fixme 注意这里
$channel->queue_bind($queue3, $exchange, null, false, new AMQPTable(array('x-match'=>'all', 'format'=>'zip', 'type'=>'log'))); //@fixme 注意这里

#2. 生产消息============================================================================
$headers1 = new AMQPTable(array('format' => 'pdf', 'type'=>'report')); //@fixme 注意这里
$message1 = new AMQPMessage('hello1');
$message1->set('application_headers', $headers1);

$headers2 = new AMQPTable(array('format' => 'pdf', 'type'=>'log')); //@fixme 注意这里
$message2 = new AMQPMessage('hello2');
$message2->set('application_headers', $headers2);

$headers3 = new AMQPTable(array('format' => 'zip', 'type'=>'report')); //@fixme 注意这里
$message3 = new AMQPMessage('hello3');
$message3->set('application_headers', $headers3);

$channel->basic_publish($message1, $exchange);
$channel->basic_publish($message2, $exchange);
$channel->basic_publish($message3, $exchange);


#3. 调消费者 ===========================================================================
function process_message1($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue1----'.$message->delivery_info['routing_key'];
    print_r($message->get('application_headers')->getNativeData()); //@fixme 注意这里
    echo "\n--------\n";
}
function process_message2($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue2----'.$message->delivery_info['routing_key'];
    echo "\n--------\n";
}
function process_message3($message) {
    echo "\n--------\n";
    echo $message->body.'-----queue3----'.$message->delivery_info['routing_key'];
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




