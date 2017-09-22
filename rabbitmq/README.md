## RabbitMq 消息队列
RabbitMQ是实现AMQP（高级消息队列协议）的消息中间件的一种，主要是为了实现系统之间的双向解耦而实现的。当生产者大量产生数据时，消费者无法快速消费，那么需要一个中间层。保存这个数据。
实际应用领域为邮件、短信的发送，订单状态的定时监控，多个应用之间的解耦等等。 而且有web管理界面（自带的插件实现的），默认账号是：guest  guest。

### 名词解释
1. 交换器（exchange）：可以理解为转发器

RabbitMQ 是信息传输的中间者。本质上，他从生产者（producers）接收消息，转发这些消息给消费者（consumers）.换句话说，他能够按根据你指定的规则进行消息转发、缓冲、和持久化。

Producing意味着无非是发送。一个发送消息的程序是一个producer(生产者)。
Queue（队列）类似邮箱。依存于RabbitMQ内部。虽然消息通过RabbitMQ在你的应用中传递，但是它们只能存储在queue中。队列不受任何限制，可以存储任何数量的消息—本质上是一个无限制的缓存。很多producers可以通过同一个队列发送消息，相同的很多consumers可以从同一个队列上接收消息。
Consuming（消费）类似于接收。consumer是基本属于等待接收消息的程序。

 “producer（生产者）,consumer（消费者）,broker（RabbitMQ服务）并不需要部署在同一台机器上，实际上在大多数实际的应用中，也不会部署在同一台机器上。” 也就是说RabbitMq是支持分布式集群部署的。



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
│   ├── amqp_consumer.php    |消费者参数解释
│   ├── amqp_ha_consumer.php |Rabbitmq集群
│   ├── amqp_publisher.php   |生产者参数解释
│   ├── basic_cancel.php     |删除队列
│   ├── basic_get.php        |基础的获取消息内容
│   ├── basic_nack.php       |演示消息不需要被“确认”
│   ├── basic_qos.php        |演示公平转发机制
│   ├── basic_return.php     |演示生产者推送消息时必须指定routing_key
│   ├── batch_publish.php    |演示批量推送消息
│   ├── config.php           |配置文件
│   ├── delayed_message.php  |延迟消息队列（必须要安装支持支持的插件）
│   ├── e2e_bindings.php     |演示交换器之间的互相绑定
│   ├── openssl.sh           |自动安装RabbitMQ需要的openssl支持
│   ├── queue_arguments.php  |queque的所有参数演示
│   ├── rabbit_ssl           |RabbitMQ 需要的openssl文件
│   ├── ssl_connection.php   |链接 RabbitMq服务 时使用ssl方式
│
│   //测试例子
│   ├── t_dl-routing-key.php
│   ├── t_exchange-type-direct.php
│   ├── t_exchange-type-fanout.php
│   ├── t_exchange-type-headers.php
│   ├── t_exchange-type-topic.php
│   ├── t_expires.php
│   ├── t_message-ttl.php
│   ├── t_m-length-bytes.php
│   ├── t_m-length.php
│   ├── t_queue-declare-exclusive.php
│   └── t_queue-priority.php
</pre>


### 6. RabbitMQ流程示图
![Markdown preferences pane](http://wx3.sinaimg.cn/large/68252c5fly1fjsg9g7ul0j20xc0go7b3.jpg)


### 7. 进一步了解RabbitMq
[RabbitMq 中文文档](https://geewu.gitbooks.io/rabbitmq-quick/content/index.html)

[图文形象解释 RabbitMQ 三种 Exchange 模式](http://www.gaort.com/index.php/archives/366)

[PHP 操作 RabbitMq 的第三方类库](https://github.com/php-amqplib/php-amqplib/blob/master/README.md)

