<?php 
namespace RESTfulPHP;

class Controller{
    
    // REST允许的请求类型列表
    protected   $allowMethod    =   array('get','post','put','delete','patch','copy','move','options','head','propfind');
    
    public function __construct(){
        $method = strtolower($_SERVER['REQUEST_METHOD']); 
        if(!in_array($method, $this->allowMethod)){
            $method = "get";
        }
        if(!method_exists($this,$method)){
            RESTfulPHP::error($method."() method not exists");
        }
        $this->$method();
    }
}

?>