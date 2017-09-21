<?php
/*
 * 演示基础的消息获取方式
 *
*/
include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'basic_get_test';
$queue = 'basic_get_queue';

//建立RabbitMq 服务链接，创建管道
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

//声明队列和转发器，并将两者做绑定
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);

//生产者推送消息
$toSend = new AMQPMessage('test message');
$channel->basic_publish($toSend, $exchange);

//获取消息
$message = $channel->basic_get($queue);

//开启时，管理界面来看不到该消息被消费者消费的反馈记录（Ready,Total ）
$channel->basic_ack($message->delivery_info['delivery_tag']);

echo $message->body,"\n";

$channel->close();
$connection->close();
