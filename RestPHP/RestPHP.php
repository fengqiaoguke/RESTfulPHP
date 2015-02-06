<?php
namespace RestPHP;


error_reporting(E_ERROR | E_WARNING | E_PARSE);
// error_reporting(0);
class RestPHP
{

    public function __construct()
    {
        $this->_route();
        
        
        include_once dirname(__FILE__) . '/Controller.class.php';
        include_once dirname(__FILE__) . '/Model.class.php';
        
        spl_autoload_register("RestPHP\RestPHP::autoload");
    }

    public function run()
    {
       
        $m = $_GET["m"] ? $_GET["m"] : "index";
        $m = "Controller\\" . ucfirst(strtolower($m)) . "Controller";
        
        $obj = new $m();
    }

    /**
     * 默认路由,截取第一个目录为controller,后面的传给$_GET
     */
    private function _route()
    {
        $qs = ltrim(str_replace($_SERVER[SCRIPT_NAME], "", $_SERVER[QUERY_STRING]), "/");
        if (strpos($qs, "/") !== false) {
            $arr = explode("/", $qs);
            if ($arr) {
                $_GET["m"] = $arr[0];
                foreach ($arr as $k => $v) {
                    if ($v) {
                        $_GET[$k] = $v;
                    }
                }
            }
        }
        
    }

    public static function json($data, $status, $message)
    {
        $_data['status'] = $status ? true : false;
        $_data['message'] = $message;
        $_data['data'] = $data;
        return json_encode($_data);
    }

    public static function error($message)
    {
        echo self::json(NULL, false, $message);
        exit();
    }

    public static function autoload($class)
    {
        $classpath = APP_PATH . $class . ".class.php";
        if (file_exists($classpath)) {
            require_once ($classpath);
        } else {
            $message = $classpath . " not exists ";
            RestPHP::error($message);
        }
    }
}
$app = new RestPHP();
$app->run();
?>