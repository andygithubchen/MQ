<?php
$beanstalk = require_once('./beanstalk_client/beanstalkObj.php');


$beanstalk->useTube('andy');
#$beanstalk->put(23,0,60, json_encode(array('tom','lili','kris')));
$beanstalk->put(23,0,60, '');


$beanstalk->disconnect();
?>
