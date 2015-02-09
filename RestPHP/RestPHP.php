<?php
namespace RestPHP;

class RestPHP
{

    public function __construct()
    {
        // 执行正则路由
        $route = $this->_route();
        if (! $route) {
            // 默认路由:切割/,第一个为controller,后面的依次为$_GET[n]参数 e.g. /user/100/order $_GET["m"]=>user;$_GET[2]=>100;$_GET[3]=>order;
            $parse = parse_url($_SERVER[REQUEST_URI]);
            $arr = explode("/", $parse["path"]);
            if ($arr[1]) {
                $_GET["m"] = $arr[1];
                foreach ($arr as $k => $v) {
                    if ($v) {
                        $_GET[$k] = $v;
                    }
                }
                unset($_GET[$_SERVER[REQUEST_URI]]);
            }
        }
        
        // 包含controller和model
        include_once dirname(__FILE__) . '/Controller.class.php';
        include_once dirname(__FILE__) . '/Model.class.php';
        
        // 自定义重载方法
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
     * 解析正则路由
     */
    private function _route()
    {
        $path = APP_PATH . "/Conf/route.php";
        if (! file_exists($path)) {
            return false;
        }
        $routes = include_once $path;
        if (! is_array($routes)) {
            return false;
        }
        $parseUrl = parse_url($_SERVER[REQUEST_URI]);
        $uri = ltrim(rtrim($parseUrl["path"], '/'), '/');
        if(!$uri){
            return false;
        }
        foreach ($routes as $k => $v) {
            $str = @preg_replace($k, $v, $uri);
            if (! $str) {
                RestPHP::error("路由错误:" . $k . "=>" . $v);
            }
            if ($str != $uri) {
                unset($_GET[$_SERVER[REQUEST_URI]]);
                parse_str($str, $get);
                $_GET = array_merge($get, $_GET);
                return true;
            }
        }
        return false;
    }

    /**
     * 输出json格式
     *
     * @param array $data
     *            数据
     * @param bool $status
     *            状态值
     * @param string $message
     *            提示信息
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
     *
     * @param unknown $message            
     */
    public static function error($message)
    {
        echo self::json(NULL, false, $message);
        exit();
    }

    /**
     * 自动载入
     *
     * @param unknown $class            
     */
    public static function autoload($class)
    {
        $classpath = str_replace("\\","/",APP_PATH . $class . ".class.php");
        if (file_exists($classpath)) {
            require_once ($classpath);
        } else {
            $message = $classpath . " not exists ";
            RestPHP::error($message);
        }
    }
}

?>