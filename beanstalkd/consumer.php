<?php
$beanstalk = require_once('./beanstalk_client/beanstalkObj.php');

$beanstalk->watch('andy');    //监控andy这个tube
//$beanstalk->ignore('andy'); //取消监控andy这个tube

while(true){
    $job = $beanstalk->reserve(2);
    $result = touch($job['body']);

    print_r($job);

    if($result){
        $beanstalk->delete($job['id']);
    }else{
        $beanstalk->bury($job['id'], 11);
    }
}

$beanstalk->disconnect();

?>
