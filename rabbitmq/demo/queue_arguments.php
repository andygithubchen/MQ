<?php
/**
 * queque的所有参数演示
 *
*/

include(__DIR__ . '/config.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'router';
$queue = 'haqueue';
$specificQueue = 'specific-haqueue';

$consumerTag = 'consumer';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


/* 声明一个消息队列
 |-----------------------------------------------------------------------------
 |   queue: $queue
 |
 |   passive: false
 |   被动：
 |
 |   durable: true // the queue will survive server restarts
 |   持久：队列将在服务器重新启动后生存
 |
 |   exclusive: false // the queue can be accessed in other channels
 |   独占：可以在其他通道中访问队列
 |
 |   auto_delete: false //the queue won't be deleted once the channel is closed.
 |   自动删除: 一旦通道关闭，该队列将不会被删除。
 |
 |   nowait
 |   arguments 一堆参数
 |   ticket
 |-----------------------------------------------------------------------------
*/
//$channel->queue_declare($queue, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket)
$channel->queue_declare('test11', false, true, false, false, false, new AMQPTable(array(
   "x-message-ttl" => 15000,  // 此队列里的信息只在队列里存在15秒钟，超过就不在了。取值范围是大于等于0。 RabbitMq里的所有时间都是毫秒为单位。演示：php ./t_message-ttl.php
   "x-expires" => 16000,      // 该队列在16秒之内没有被使用（消息写入队列或消费者从队列取出信息）, 将被自动删除。这个取值范围是大于0 。演示：php ./t_expires.php 到RabbitMq 管理后台的“Queues”下查看，队列会在设置的时间内消息。
   "x-max-length" => "2",     //这个队列里最大可以放2条为被消费的消息，如果向这个队列里推送3条消息，那么只有最后两个可以推送成功。演示：php ./t_m-length.php tom
   "x-max-length-bytes" => "5", //此队列不接收超过5个字节的信息。 演示1：php ./t_m-length-bytes.php ab  演示2：php ./t_m-length-bytes.php abc
   "x-dead-letter-exchange" => "t_test1",        //此队列不存在或是因为以上几个参数造成没有成功将消息推送到此队列时，消息将被推送到改参数指定的交换器上，然后该交换器再将消息路由给另一个队列。演示1：php ./t_dl-routing-key.php ab  演示2：php ./t_dl-routing-key.php abc
   "x-dead-letter-routing-key"=>"routing_key_2", //此参数依赖于上面一个参数，为附属参数，意为将给 绑定到上一个参数指定的交换器上的队列, 并且这个队列下路由key为routing_key_2的地方。可以用上面的演示方法。
   "x-max-priority" => "5",  // 队列里的被消费顺序权重，取值范围 大于等于0小于等于255，默认为0，数值越大优先级越大。php ./t_queue-priority.php这是个例子
   "x-queue-mode" => "lazy", //队列模式，可选值default和lazy。lazy模式是将信息尽可能的都保存在磁盘上，仅在消费者请求的时候才会加载到RAM中。
)));

$channel->close();
$connection->close();


