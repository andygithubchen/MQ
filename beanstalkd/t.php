<?php

$beanstalk = require_once('./beanstalk_client/beanstalkObj.php');
//$beanstalk->useTube('andy');

//$info = $beanstalk->statsJob(2);
//$info = $beanstalk->statsTube('andy');
//$info = $beanstalk->stats();
//$info = $beanstalk->listTubes();
//$info = $beanstalk->listTubeUsed();
//$info = $beanstalk->listTubesWatched();


//$info = $beanstalk->peek(1);

//$info = $beanstalk->peekReady();
//$info = $beanstalk->peekDelayed();
//$info = $beanstalk->peekBuried();
//$info = $beanstalk->kick(2);
//$info = $beanstalk->kickJob(5);



//echo $beanstalk->put(10,0,30, 's- pause 1');
//$beanstalk->pauseTube('andy', 20);
//echo $beanstalk->put(10,0,30, 's- pause 2');
//echo $beanstalk->put(10,0,30, 's- pause 3');


//echo $beanstalk->release(40,2,10);
//$beanstalk->watch('andy');
//$beanstalk->reserve();
//echo $beanstalk->bury(55,10);

$beanstalk->watch('andy');
echo $beanstalk->ignore('andy');



print_r($info);

echo "\n";
$beanstalk->disconnect();
?>
