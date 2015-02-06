<?php 

use Tools\Tools;
define('APP_PATH', './Application/');
include_once 'RestPHP/Tools.class.php';

$tool = new Tools();
$tool->makeModel("blog");

?>