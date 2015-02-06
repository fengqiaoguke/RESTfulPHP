<?php
namespace RestPHP;

class RestPHP
{

    public function __construct()
    {
        //执行路由
        $this->_route();
        
        //包含controller和model
        include_once dirname(__FILE__) . '/Controller.class.php';
        include_once dirname(__FILE__) . '/Model.class.php';
        
        //自定义重载方法
        spl_autoload_register("RestPHP\RestPHP::autoload");
    }

    /**
     * 框架执行入口
     */
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

    /**
     * 输出json格式
     * @param array $data 数据
     * @param bool $status 状态值
     * @param string $message 提示信息
     * @return string
     */
    public static function json($data, $status, $message)
    {
        $_data['status'] = $status ? true : false;
        $_data['message'] = $message;
        $_data['data'] = $data;
        return json_encode($_data);
    }

    /**
     * 输出json格式错误信息
     * @param unknown $message
     */
    public static function error($message)
    {
        echo self::json(NULL, false, $message);
        exit();
    }

    /**
     * 自动载入
     * @param unknown $class
     */
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

?>