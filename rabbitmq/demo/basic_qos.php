<?php
/*
 * 本例可以不用将队列绑定到交换器上，RabbitMq 会自动将其绑定到模式的交换器上(AMQP default)
 *
 *
*/
include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

$channel->queue_declare('qos_queue', false, true, false, false);




#1. 生产者推送消息 -------------------------------------------------------
for($i =1; $i<=20; $i++){
    echo $i,"\n";
  $message = new AMQPMessage($i.'tT', array(
        'content_type' => 'text/plain',
      'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //信息为持久化
  ));
  $channel->basic_publish($message, '', 'qos_queue');
}

echo "-------------------\n";


#2. 消费者开始消费信息 ---------------------------------------------------

/*
公平转发机制，“我们可以使用basicQos方法，传递参数为prefetchCount = 1。这样告诉RabbitMQ不要在同一时间给一个消费者超过一条消息。”
注：其实basic.qos里还有另外两个参数可进行设置（global和prefetchSize），但rabbitmq没有相应的实现。
$channel->basic_qos(int prefetchSize, int prefetchCount, boolean global)
*/
$channel->basic_qos(null, 2, null);

function process_message1($message) {
    echo $message->body,"---for 1 \n";
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    sleep(3);
}
function process_message2($message) {
    echo $message->body,"---for 2 \n";
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    sleep(1);
}

//开启两个消费者
$channel->basic_consume('qos_queue', '', false, false, false, false, 'process_message1');
$channel->basic_consume('qos_queue', '', false, false, false, false, 'process_message2');

while (count($channel->callbacks)) {
    /**
     * $allowed_methods  str   ???
     * $non_blocking     bool  为true时会取消wait()所在的事
     * $timeout          int   超时时间
     * $channel->wait(allowed_methods, $non_blocking, $timeout);
    */
    //$channel->wait(null, false, 10);
    $channel->wait();
}
