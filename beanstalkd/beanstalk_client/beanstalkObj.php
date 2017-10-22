<?php
require_once('Client.php');
$config = require_once('confing.php');

$beanstalk = new Beanstalk\Client($config);
$beanstalk->connect();
return $beanstalk;

?>
