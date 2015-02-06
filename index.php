<?php 
use RestPHP\RestPHP;
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define('APP_PATH', './Application/');
include_once 'RestPHP/RestPHP.php';
$app = new RestPHP();
$app->run();
?>