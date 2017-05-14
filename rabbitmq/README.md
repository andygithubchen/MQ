
##RabbitMq 消息队列

###1. 安装RabbitMq 

$ ./installRabbitMQ.sh  安装了RabbitMq 和 其对应PHP扩展

###2. 获取RabbitMq PHP 第三方封装类

$ composer install

###3. 演示demo

  ├── demo
     ├── amqp_consumer_exclusive.php
     ├── amqp_consumer_fanout_1.php
     ├── amqp_consumer_fanout_2.php
     ├── amqp_consumer_non_blocking.php
     ├── amqp_consumer.php
     ├── amqp_consumer_signals.php
     ├── amqp_ha_consumer.php
     ├── amqp_message_headers_recv.php
     ├── amqp_message_headers_snd.php
     ├── amqp_publisher_exclusive.php
     ├── amqp_publisher_fanout.php
     ├── amqp_publisher.php
     ├── amqp_publisher_with_confirms_mandatory.php
     ├── amqp_publisher_with_confirms.php
     ├── basic_cancel.php
     ├── basic_get.php      #基础的获取消息内容
     ├── basic_nack.php
     ├── basic_qos.php
     ├── basic_return.php
     ├── batch_publish.php  #基础的
     ├── config.php         #配置文件
     ├── delayed_message.php
     ├── e2e_bindings.php
     ├── queue_arguments.php
     └── ssl_connection.php

