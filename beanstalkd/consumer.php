<?php
$beanstalk = require_once('./beanstalk_client/beanstalkObj.php');

$beanstalk->watch('andy');

while(true){
    $job = $beanstalk->reserve();
    $result = touch($job['body']);

    print_r($job);
    //print_r($result);

        $beanstalk->bury($job['id'], 11);
    //if($result){
    //    $beanstalk->delete($job['id']);
    //}else{
    //    $beanstalk->bury($job['id'], 11);
    //}
}

$beanstalk->disconnect();

?>
