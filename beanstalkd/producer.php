<?php
$beanstalk = require_once('./beanstalk_client/beanstalkObj.php');


$beanstalk->useTube('andy');
//echo $beanstalk->put(23,0,60, json_encode(array('tom','lili','kris')));
//echo $beanstalk->put(23,0,60, json_encode(array('delayed5','lili','kris')));
//echo $beanstalk->put(23,0,60, json_encode(array('delayed6','lili','kris')));
//echo $beanstalk->put(23,200,60, json_encode(array('delayed3','lili','kris')));
//echo $beanstalk->put(23,200,60, json_encode(array('delayed4','lili','kris')));
echo $beanstalk->put(23,0,60, '');
echo $beanstalk->put(23,0,60, 'tom');

echo "\n";
$beanstalk->disconnect();
?>
