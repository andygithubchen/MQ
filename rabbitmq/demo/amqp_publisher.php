<?php

include(__DIR__ . '/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'router';
$queue = 'msgs';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


#1. 声明队列和交换器====================================================================
/*
The following code is the same both in the consumer and the producer.
以下代码在消费者和生产者中都是一样的。
*/

/* 声明一个消息队列
 |-----------------------------------------------------------------------------
 |   name: $queue
 |
 |   passive: false
 |   被动：检测queue是否存在，
             设为true，若队列存在则命令成功返回（调用其他参数不会影响queue属性）， 若不存在不会创建queue，返回错误。
             设为false，如果queue不存在则创建queue。如果queue已经存在，并且匹配现在queue的话则成功返回，如果不匹配则queue声明失败。(此解释来自网络)
 |
 |   durable: true // the queue will survive server restarts
 |   持久：队列持久化，就是当RabbitMq服务端退出或者异常退出，队列将在RebbitMq服务重新启动后依然存在。
 |
 |   exclusive: false // the queue can be accessed in other channels
 |   独占：可以在其他通道中访问队列
            为true时，并且没有被消费者监听时，在管理界面里看不到队列。重要的是只有该队列的管道和消费者的管道是同一个时，消费者才能取到信息。
            即： $connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
            $connection 不能是另外实例出来的，尽管链接的参数都是相同的。 演示：php ./t_queue-declare-exclusive.php
 |
 |   auto_delete: false //the queue won't be deleted once the channel is closed.
 |   自动删除: 一旦通道关闭，该队列将不会被删除。如果没有消费者在监听这个队列时，并且该参数为true时，此队列自动删除。
 |-----------------------------------------------------------------------------
*/
$channel->queue_declare($queue, false, true, false, true);

/* 声明一个交换器
 |-----------------------------------------------------------------------------
 |  name: $exchange
 |
 |  type: direct
 |  类型：direct 直接转发(以路由键routing key为转发依据，转发到队列)
 |        x-delayed-message 延迟队列
 |        fanout  广播式的，任何发送到该转发器的消息都会被转发到与其绑定(Binding)的所有Queue上。
 |        headers
 |        topic 主题型的，支持用.来分级，如：usa.news, 可以理解为消息将会被转发到routing_key 为usa 和news 的两个队列中
 |
 |  passive: false
 |  被动：
 |
 |  durable: true // the exchange will survive server restarts
 |  持久：交换器将在服务器重新启动后生存
 |
 |  auto_delete: false //the exchange won't be deleted once the channel is closed.
 |  自动删除: 一旦通道关闭，该交换机将不会被删除。
 |-----------------------------------------------------------------------------
*/
$channel->exchange_declare($exchange, 'direct', false, true, false);

/*队列绑定，将队列绑定到交换器（转发器）上
 |-----------------------------------------------------------------------------
 |  $queue  队列
 |  $exchange 交换器
 |  $routing_key 路由标识，支持用.来分级，如：usa.news(当交换器的type为topic时支持)
 |  $nowait
 |  $arguments 其他参数集合
 |  $ticket
 |
 |  $channel->queue_bind($queue, $exchange, $routing_key = '', $nowait = false, $arguments = null, $ticket = null)
 |-----------------------------------------------------------------------------
*/
$channel->queue_bind($queue, $exchange, 'fuck');


#2. 生产消息============================================================================
$messageBody = implode(' ', array_slice($argv, 1));
//$message = new AMQPMessage('test message', array('content_type' => 'text/plain', 'delivery_mode' => 2));
$message = new AMQPMessage($messageBody, array(
                    'content_type' => 'text/plain',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //信息为持久化
                ));
/* 推送消息
 |-----------------------------------------------------------------------------
     $msg  要推送的消息内容
     $exchange 将消息推送到这个交换器上
     $routing_key 路由Key
     $mandatory 当为true时, 检查推送到的交换器上至少要有一个队列和这个交换器是绑定的，否则将消息返还给生产者；
     $immediate  为true时，所有queue都没有消费者，直接把消息返还给生产者，不用将消息入队列等待消费者了 
     $ticket = null

 |  basic_publish( $msg, $exchange = '', $routing_key = '', $mandatory = false, $immediate = false, $ticket = null) {}
 |-----------------------------------------------------------------------------
*/
$channel->basic_publish($message, $exchange);


#3. 关闭 ===============================================================================
$channel->close();
$connection->close();



