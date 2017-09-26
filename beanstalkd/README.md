

$beanstalk = new Beanstalk\Client();
$beanstalk->connect();   //启动与Beanstalk服务器的套接字连接。 生成的流将不会有任何超时设置。
$beanstalk->disconnect() //如果要退出，通过第一个信令关闭在beanstalk服务器的连接,

// Producer Commands ===========================================================================
$beanstalk->put($pri, $delay, $ttr, $data)//插入一个job到队列
$beanstalk->useTube($tube)                //`use`命令用于生产者。 随后put命令将作业放置到由此命令指定的管。 如果没有使用命令，作业将被放入名为`default`的管道中。
$beanstalk->pauseTube($tube, $delay)      //使指定的管道内, 今后所添加的新job在指定的$delay时间后才能被reserve()取出（预订）来处理。

// Worker Commands =============================================================================
$beanstalk->reserve($timeout = null)      //取出（预订）一个job，待处理。
$beanstalk->delete($id)                   //从队列中删除一个job
$beanstalk->release($id, $pri, $delay)    //将一个保留的job放回到ready队列。 @fixme
$beanstalk->bury($id, $pri)               //将一个被reserve()取出（预订）后的job放入到buried状态，并且它会被放入FIFO链接列表中，直到客户端kick这些job，不然它们不会被处理。
$beanstalk->touch($id)                    //允许worker请求更多的时间执行job @fixme
$beanstalk->watch($tube)                  //添加监控的tube到watch list列表，reserve指令将会从监控的tube列表获取job，对于每个连接，监控的列表默认为default
$beanstalk->ignore($tube)                 //从已监控的watch list列表中移出特定的tube @fixme

// Other Commands ==============================================================================
$beanstalk->peek($id)                     //让client在系统中检查job，返回id对应的job
$beanstalk->peekReady()                   //让client在系统中检查job，获取最早一个处于“Ready”状态的job、注意、只能获取当前tube的job
@fixme $beanstalk->peekDelayed()                 //让client在系统中检查job，获取最早一个处于“Delayed”状态的job、注意、只能获取当前tube的job
$beanstalk->peekBuried()                  //让client在系统中检查job，获取最早一个处于“Buried”状态的job、注意、只能获取当前tube的job
$beanstalk->kick($bound)                  //它将当前tube中所有job的状态迁移为ready @fixme
$beanstalk->kickJob($id)                  //上面的kick()方法是操作所有的job，这是操作指定的job。

// Stats Commands ==============================================================================
$beanstalk->statsJob($id)                 //获取指定job 的所有状态信息
$beanstalk->statsTube($tube)              //获取管道的状态信息
$beanstalk->stats()                       //获取整个消息队列系统的整体信息
$beanstalk->listTubes()                   //返回所有存在的管道列表
$beanstalk->listTubeUsed()                //返回生产者当前正在使用的管道。
$beanstalk->listTubesWatched()            //返回消息消费端当前正在监视的管道列表。
