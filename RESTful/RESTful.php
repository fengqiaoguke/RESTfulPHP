<?php 
error_reporting(E_ERROR | E_WARNING | E_PARSE);
class RESTful{
     public function __construct(){
         include_once dirname(__FILE__).'/Model.class.php';
         include_once dirname(__FILE__).'/Controller.class.php';
     }
     public function run(){
         
         $m = $_GET["m"]?$_GET["m"]:"index";
         $m = ucfirst(strtolower($m))."Controller";
         $a = strtolower($a);
         $a = $_GET["a"]?$_GET["a"]:"index";
         
         include_once   APP_PATH."Controller/".$m.".class.php";
         $obj = new $m();
         $obj->$a();
     }
}

$app = new RESTful();
$app->run();
?>