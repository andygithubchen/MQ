<?php
require_once('/root/github/MQ/beanstalkd/beanstalk_client/Client.php');
$config = require_once('/root/github/MQ/beanstalkd/beanstalk_client/confing.php');

$beanstalk = new Beanstalk\Client($config);
$beanstalk->connect();
return $beanstalk;

?>
