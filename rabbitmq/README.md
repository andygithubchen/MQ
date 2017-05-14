## RabbitMq 消息队列
RabbitMQ是实现AMQP（高级消息队列协议）的消息中间件的一种，主要是为了实现系统之间的双向解耦而实现的。当生产者大量产生数据时，消费者无法快速消费，那么需要一个中间层。保存这个数据。
实际应用领域为邮件、短信的发送，订单状态的定时监控，多个应用之间的解耦等等。 而且有web管理界面（自带的插件实现的）。


### 1. 安装RabbitMq
``
$ ./installRabbitMQ.sh   #安装了RabbitMq 和 其对应PHP扩展
``

### 2. 获取RabbitMq PHP 第三方封装类
``
$ composer install
``


### 3. RabbitMq 基本操作

这些常用的命令都在RabbitMq的安装路径下的sbin/里, 把sbin/目录加到环境变量里就方便使用了
<pre>
启动RabbitMQ               rabbitmq-server -detached
停止RabbitMQ               rabbitmqctl stop
查看已经安装的插件           rabbitmq-plugins list
启用RabbitMq的web管理界面   rabbitmq-plugins enable rabbitmq_management
关闭监控插件                rabbitmq-plugins disable rabbitmq_management
新增一个用户                rabbitmqctl  add_user  Username  123456
删除一个用户                rabbitmqctl  delete_user  Username
修改用户的密码               rabbitmqctl  change_password  Username  54321
查看当前用户列表             rabbitmqctl  list_users
赋予超级管理员权限           rabbitmqctl set_user_tags newuser administrator
</pre>

### 4. 实现延迟队列
遗憾的是RabbitMq自身是不支持延迟队列的，但是可以借助第三方插件来实现，它就是rabbitmq-delayed-message-exchange，具体安装如下：

1. 下载插件 wget https://bintray.com/rabbitmq/community-plugins/download_file?file_path=rabbitmq_delayed_message_exchange-0.0.1.ez

2. 将下载下来的 rabbitmq_delayed_message_exchange-0.0.1.ez 剪切到rabbitmq的插件目录plugins/下

3. 启用插件 rabbitmq-plugins enable rabbitmq_delayed_message_exchange
   关闭插件 rabbitmq-plugins disable rabbitmq_delayed_message_exchange


### 5. 演示demo
<pre>
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
</pre>

### 6. 进一步了解RabbitMq
[RabbitMq 中文文档](https://geewu.gitbooks.io/rabbitmq-quick/content/index.html)

[图文形象解释 RabbitMQ 三种 Exchange 模式](http://www.gaort.com/index.php/archives/366)

[PHP 操作 RabbitMq 的第三方类库](https://github.com/php-amqplib/php-amqplib/blob/master/README.md)

