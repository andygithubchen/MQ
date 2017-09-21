<?php
/*
 * 主要演示延迟队列，RabbitMq自身不支持这个功能，所以要有rabbitmq-delayed-message-exchange插件的支持，
 * 所以事先要确定是否已经安装这个插件, 值得注意的是时间是毫秒，而不是秒。
*/

include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$time = 7;

//1. 建立RabbitMq 服务链接，创建管道 -------------------------------------------
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


//2. 声明队列和转发器，并将两者做绑定 ------------------------------------------
$channel->exchange_declare('delayed_exchange', 'x-delayed-message', false, true, false, false, false, new AMQPTable(array(
   "x-delayed-type" => "fanout"
)));
$channel->queue_declare('delayed_queue', false, false, false, false, false, new AMQPTable(array(
   "x-dead-letter-exchange" => "delayed"
)));
$channel->queue_bind('delayed_queue', 'delayed_exchange');


//3. 生产者推送消息 ------------------------------------------------------------
$headers = new AMQPTable(array("x-delay" => $time*1000)); //毫秒，7秒
$message = new AMQPMessage('hello', array('delivery_mode' => 2));
$message->set('application_headers', $headers);
$channel->basic_publish($message, 'delayed_exchange');


//4. 消费者开始获取消息 --------------------------------------------------------
function process_message(AMQPMessage $message) {
    $headers = $message->get('application_headers');
    $nativeData = $headers->getNativeData();
    $time = $nativeData['x-delay'];
    $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);
    echo $message->body."我是".($time/1000)."才获取到的","\n";
}

//$channel->basic_consume('delayed_queue', '', false, true, false, false, 'process_message'); //old is error
$channel->basic_consume('delayed_queue', '', false, false, false, false, 'process_message');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
while (count($channel->callbacks)) {
    $channel->wait();
}
