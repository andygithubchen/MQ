<?php
/**
 * 演示该消息应该没有被“确认”，nack(no ack)
 *
 * - Start this consumer in one window by calling: php demo/basic_nack.php
 * - 在一个命令行窗口中开启消费者，用：php demo/basic_nack.php
 *
 * - Then on a separate window publish a message like this: php demo/amqp_publisher.php good
 * - 在另一个命令行窗口用 “php demo/amqp_publisher.php good” 来借生产者推送消息
 *
 * - that message should be "ack'ed"
 * - 该消息应该被“确认”
 *
 * - Then publish a message like this: php demo/amqp_publisher.php bad
 * - 然后发布这样的消息：php demo / amqp_publisher.php bad
 *
 * - that message should be "nack'ed"
 * - 该消息应该没有被“确认”
 */
include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange = 'router';
$queue = 'msgs';
$consumerTag = 'consumer';

//建立RabbitMq 服务链接，创建管道
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

//声明队列和转发器，并将两者做绑定
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);



//消费者的回调函数
function process_message($message) {
    //暂时不太了解这是干什么的，只是在管理界面的“Queues” ack 栏有差异。
    if ($message->body == 'good') {
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    } else {
        $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);
    }

    // Send a message with the string "quit" to cancel the consumer.
    // 如果消息里的信息是“quit”字符串，那么就取消这个消费者
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
}

$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
while (count($channel->callbacks)) {
    $channel->wait();
}
