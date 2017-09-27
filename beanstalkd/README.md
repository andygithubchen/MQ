*beanstalk是一个相对比RabbitMQ简单很多的MQ服务，在这里收集了beanstalk常用的工具，对Client.php里主要方法的注释整理成中文*
---
beanstalk php 操作类 beanstalk_client/Client.php里的主要方法注释
---

```php
$beanstalk = new Beanstalk\Client();
$beanstalk->connect();    //启动与Beanstalk服务器的套接字连接。 生成的流将不会有任何超时设置。
$beanstalk->disconnect(); //如果要退出，通过第一个信令关闭在beanstalk服务器的连接,

// Producer Commands ===========================================================================
$beanstalk->put($pri, $delay, $ttr, $data);//插入一个job到队列
$beanstalk->useTube($tube);                //`use`命令用于生产者。 随后put命令将作业放置到由此命令指定的管。 如果没有使用命令，作业将被放入名为`default`的管道中。
$beanstalk->pauseTube($tube, $delay);      //使指定的管道内, 今后所添加的新job在指定的$delay时间后才能被reserve()取出（预订）来处理。

// Worker Commands =============================================================================
$beanstalk->reserve($timeout = null);      //取出（预订）一个job，待处理。
$beanstalk->delete($id);                   //从队列中删除一个job
$beanstalk->release($id, $pri, $delay);    //将一个reserve()后的job放回到ready或delayed队列（也就是在reserve()后用release()，而且前后都不能用delete()）
$beanstalk->bury($id, $pri);               //将一个被reserve()取出（预订）后的job放入到buried状态，并且它会被放入FIFO链接列表中，直到客户端kick这些job，不然它们不会被处理。
$beanstalk->touch($id);                    //允许worker请求更多的时间执行job
$beanstalk->watch($tube);                  //添加监控的tube到watch list列表，reserve指令将会从监控的tube列表获取job，对于每个连接，监控的列表默认为default
$beanstalk->ignore($tube);                 //consumers消费者可以通过发送ignore()来取消监控tube（也就是在watch()之后reserve()之前做）

// Other Commands ==============================================================================
$beanstalk->peek($id);                     //让client在系统中检查job，返回id对应的job
$beanstalk->peekReady();                   //让client在系统中检查job，获取最早一个处于“Ready”状态的job、注意、只能获取当前tube的job
$beanstalk->peekDelayed();                 //让client在系统中检查job，获取最早一个处于“Delayed”状态的job、注意、只能获取当前tube的job
$beanstalk->peekBuried();                  //让client在系统中检查job，获取最早一个处于“Buried”状态的job、注意、只能获取当前tube的job
$beanstalk->kick($bound);                  //它将当前tube中状态为Buried的job迁移为ready状态，一次最多迁移$bound个。
$beanstalk->kickJob($id);                  //它将当前tube中状态为Buried或Delayed的job迁移为ready状态。

// Stats Commands ==============================================================================
$beanstalk->statsJob($id);                 //获取指定job 的所有状态信息
$beanstalk->statsTube($tube);              //获取管道的状态信息
$beanstalk->stats();                       //获取整个消息队列系统的整体信息
$beanstalk->listTubes();                   //返回所有存在的管道列表
$beanstalk->listTubeUsed();                //返回生产者当前正在使用的管道。
$beanstalk->listTubesWatched();            //返回消息消费端当前正在监视的管道列表。
```

文件说明
---
<pre>
.
├── beanstalk_client
│   ├── beanstalkObj.php // 生成beanstalk操作类对象
│   ├── Client.php       // beanstalk的php操作类，来自：https: // github.com/kr/beanstalkd/
│   └── confing.php      // beanstalk服务端配置文件
├── consumer.php         // 测试消息消费端
├── install              // beanstalk服务端自动安装脚本
├── producer.php         // 测试消息生产端
├── README.md
└── t.php                // 测试文件
</pre>

流程图
---
![1](http://wx3.sinaimg.cn/large/68252c5fly1fjycxwyk1zj20nm06ymzt.jpg)
![2](http://wx1.sinaimg.cn/large/68252c5fly1fjycy7pnu4j20p00jgq82.jpg)

其他
---
1. beanstalk 中文文档：https://github.com/kr/beanstalkd/blob/master/doc/protocol.zh-CN.md
2. beanstalk 下载地址：https://github.com/kr/beanstalkd/releases
3. 管理工具（浏览器上）：https://github.com/ptrofimov/beanstalk_console
4. beanstalk php 操作类：https://github.com/davidpersson/beanstalk
`
