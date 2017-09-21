<?php

include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange = 'router';
$queue = 'msgs';
$consumerTag = 'consumer';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
/*
The following code is the same both in the consumer and the producer.
以下代码在消费者和生产者中都是一样的。
*/

/* 声明一个消息队列
 |-----------------------------------------------------------------------------
 |   $name: $queue
 |
 |   $passive: false
 |   被动：检测queue是否存在，
 |           设为true，若队列存在则命令成功返回（调用其他参数不会影响queue属性）， 若不存在不会创建queue，返回错误。
 |           设为false，如果queue不存在则创建queue。如果queue已经存在，并且匹配现在queue的话则成功返回，如果不匹配则queue声明失败。(此解释来自网络)
 |
 |   $durable: true // the queue will survive server restarts
 |   持久：队列持久化，就是当RabbitMq服务端退出或者异常退出，队列将在RebbitMq服务重新启动后依然存在。
 |
 |   $exclusive: false // the queue can be accessed in other channels
 |   独占：可以在其他通道中访问队列
 |          为true时，并且没有被消费者监听时，在管理界面里看不到队列。重要的是只有该队列的管道和消费者的管道是同一个时，消费者才能取到信息。
 |          即： $connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
 |          $connection 不能是另外实例出来的，尽管链接的参数都是相同的。 演示：php ./t_queue-declare-exclusive.php
 |
 |   $auto_delete: false //the queue won't be deleted once the channel is closed.
 |   自动删除: 一旦通道关闭，该队列将不会被删除。如果没有消费者在监听这个队列时，并且该参数为true时，此队列自动删除。
 |
 |   $nowait  //@fixme
 |   $arguments 一堆参数
 |   $ticket  //@fixme

 |   $channel->queue_declare($queue, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket)
 |-----------------------------------------------------------------------------
*/
$channel->queue_declare($queue, false, true, false, true);

/* 声明一个交换器(转发器)
 |-----------------------------------------------------------------------------
 |  $name: $exchange
 |
 |  $type: direct
 |  类型：direct 直接转发(以路由键routing key为转发依据，转发到队列，如果在转发器与队列绑定和推送信息时没有指定相同的routing_key，那该信息将被丢弃。如果同时都没有指定routing_key是可以推送成功的) 演示：php ./t_exchange-type-direct.php
 |        x-delayed-message 延迟队列 演示：php ./delayed_message.php
 |        fanout  广播式的，任何发送到该转发器的消息都会被转发到与其绑定(Binding)的所有Queue上。演示：php ./t_exchange-type-fanout.php
 |               1. 如果推送信息时指定了routing_key，那么最后信息将分别被推送到各个队列的routing_key下，不管绑定时是否有指定routing_key。
 |               2. 如果推送信息时没有指定routing_key，而绑定时已经指定了routing_key。那么最后信息将分别被推送到各个队列，但不是routing_key下。
 |        headers 头部信息匹配型的交换器，有个固定的参数x-match。演示：php ./t_exchange-type-headers.php
 |        topic 主题型的，支持用.来分级，如：usa.news, 可以理解为消息将会被转发到routing_key 为usa 和news 的两个队列中。演示：php ./t_exchange-type-topic.php
 |
 |  $passive: false
 |  被动：检测exchange是否存在，
 |           设为true，若队列存在则命令成功返回（调用其他参数不会影响exchange属性）， 若不存在不会创建exchange，返回错误。
 |           设为false，如果exchange不存在则创建exchange。如果exchange已经存在，并且匹配现在exchange的话则成功返回，如果不匹配则exchange声明失败。(此解释来自网络)
 |
 |  $durable: true // the exchange will survive server restarts
 |  持久：交换器持久化，就是当RabbitMq服务端退出或者异常退出，交换器将在RebbitMq服务重新启动后依然存在。
 |
 |  $auto_delete: false //the exchange won't be deleted once the channel is closed.
 |  自动删除: 为false时，不会自动删除
 |            为true时，1. 此交换器上没有绑定任何的队列时是不会自动删除的。
 |                      2. 之前绑到此交换器上的队列都删除了之后，此交换器会自动删除。

 |  $internal: 表示这个exchange不可以被client用来推送消息，仅用来进行exchange和exchange之间的绑定。
 |  $nowait //@fixme
 |  $arguments: 其他参数集合
 |  $ticket //@fixme
 |  $exchange_declare($exchange, $type, $passive = false, $durable = false, $auto_delete = true, $internal = false, $nowait = false, $arguments = null, $ticket = null) {}
 |-----------------------------------------------------------------------------
*/
$channel->exchange_declare($exchange, 'direct', false, true, false);

/*队列绑定，将队列绑定到交换器（转发器）上
 |-----------------------------------------------------------------------------
 |  $queue  队列
 |  $exchange 交换器
 |  $routing_key 路由标识，支持用.来分级，如：usa.#(当交换器的type为topic时支持)
 |  $nowait //@fixme
 |  $arguments 其他参数集合
 |  $ticket //@fixme
 |
 |  $channel->queue_bind($queue, $exchange, $routing_key = '', $nowait = false, $arguments = null, $ticket = null)
 |-----------------------------------------------------------------------------
*/
$channel->queue_bind($queue, $exchange, 'tom');


#2. 获取队列消息 =======================================================================
/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message) {
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";

    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    // Send a message with the string "quit" to cancel the consumer.
    // 发送带有“quit”的字符串以取消消费者。
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
}

/* 调用消费者
 |-----------------------------------------------------------------------------
 |   $queue: Queue from where to get the messages
 |   队列：从哪个队列获取消息
 |
 |   $consumer_tag: Consumer identifier
 |   消费者标签：消费者标识符
 |
 |   $no_local: Don't receive messages published by this consumer.
 |   no_local：不接收此消费者发布的消息。
 |
 |   $no_ack: Tells the server if the consumer will acknowledge the messages.
 |   no_ack：通常我们要在接收到消息后，使用 basic_ack() 发送一个回馈，否则这些消息将会被其他连接到该队列的消费客户端再次收取。也可以将参数设为 True，告知 AMQP 服务器无需等待回馈。
 |
 |   $exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
 |   独占： 请求独占消费者访问，这意味着只有这个消费者可以访问队列
 |
 |   $nowait:
 |   $callback: A PHP Callback
 |-----------------------------------------------------------------------------
*/
$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection) {
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
// 只要通道已注册回调, 则循环
while (count($channel->callbacks)) {
    $channel->wait();
}



