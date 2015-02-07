<?php
namespace RestPHP;

class Controller
{
    
    // REST允许的请求类型列表
    protected $allowMethod = array(
        'get',
        'post',
        'put',
        'delete',
        'patch',
        'copy',
        'move',
        'options',
        'head',
        'propfind'
    );

    public function __construct()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (! in_array($method, $this->allowMethod)) {
            $method = "get";
        }
        if (! method_exists($this, $method)) {
            RestPHP::error($method . "() method not exists");
        }
        $this->$method();
    }

    /**
     * 设置Http头状态信息
     *
     * @param unknown $code            
     */
    protected function setHeadStatus($code)
    {
        static $_status = array(
            
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:' . $code . ' ' . $_status[$code]);
        }
    }

    /**
     * 获取Accept
     */
    protected function getHttpAccept()
    {
        $type = array(
            'html' => 'text/html,application/xhtml+xml,*/*',
            'xml' => 'application/xml,text/xml,application/x-xml',
            'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
            'js' => 'text/javascript,application/javascript,application/x-javascript',
            'css' => 'text/css',
            'rss' => 'application/rss+xml',
            'yaml' => 'application/x-yaml,text/yaml',
            'atom' => 'application/atom+xml',
            'pdf' => 'application/pdf',
            'text' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpg,image/jpeg,image/pjpeg',
            'gif' => 'image/gif',
            'csv' => 'text/csv'
        );
        
        foreach ($type as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($_SERVER['HTTP_ACCEPT'], $v)) {
                    return $key;
                }
            }
        }
        return false;
    }

    /**
     * 输出json格式
     *
     * @param array $data
     *            内容
     * @param bool $status
     *            状态
     * @param string $message
     *            提示信息
     */
    public function json($data, $status, $message)
    {
        return RestPHP::json($data, $status, $message);
    }
}

class Model extends \PDO
{

    /**
     * 继承了PDO的方法并通过配置文件链接数据库
     */
    public function __construct()
    {
        $confPath = APP_PATH . "Conf/config.ini";
        if (! $conf = parse_ini_file($confPath, true)) {
            RestPHP::error('Unable to open ' . $confPath . '.');
        }
        try {
            parent::__construct($conf['database']['dsn'], $conf['database']['user'], $conf['database']['pass']);
        } catch (\Exception $e) {
            RestPHP::error("数据库链接失败:" . $e->getMessage());
        }
        $this->conf = $conf;
        $this->_where = "";
        $this->_field = "*";
        
        // 表名
        if (! $this->_table) {
            $table = preg_replace("/Model\\\(.*)Model/e", "$1", get_class($this));
            $this->_table = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $table), "_"));
        }
    }

    /**
     * 查询sql
     * 
     * @param unknown $sql            
     * @return multitype:
     */
    protected function select($sql)
    {
        $query = $this->query($sql);
        if (! $query) {
            RestPHP::error($sql . " 查询出错!");
        }
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $query->fetchAll();
        
        return $result;
    }

    /**
     * 缓存
     *
     * @param string $key            
     * @param string $value
     *            存在则保存;null为删除
     * @param number $expire
     *            过期时间
     * @return array $value
     */
    protected function cache($key, $value = "", $expire = "")
    {
        $expire = $expire ? $expire : intval($this->conf["cache"]["expire"]);
        
        if (! $this->conf["cache"]["open"] || ! $expire) {
            return false;
        }
        
        // memcache 缓存
        if ($this->conf["cache"]["type"] == "memcache") {
            $memcache = @new \Memcache();
            $rs = @$memcache->connect($this->conf["memcache"]["host"], $this->conf["memcache"]["port"]);
            if (! $rs) {
                RestPHP::error("memcache链接失败!(如果要关闭memcache在config.ini把缓存host设为空)");
            }
            
            if ($value) {
                $memcache->set($key, $value, false, $expire);
            } elseif ($value === null) {
                $memcache->delete($key);
            } else {
                $value = $memcache->get($key);
            }
        } else {
            // 文件缓存
            $path = APP_PATH . "~data";
            if (! file_exists($path)) {
                mkdir($path, '0777');
            }
            $path .= "/cache";
            if (! file_exists($path)) {
                mkdir($path, '0777');
            }
            $_key = md5($key);
            $path .= "/" . substr($_key, 0, 1);
            if (! file_exists($path)) {
                mkdir($path, '0777');
            }
            $file = $path . "/~" . $_key . ".txt";
            if ($value) {
                $expire = time() + $expire;
                $context = $expire . ":" . json_encode($value);
                file_put_contents($file, $context);
            } elseif ($value === null) {
                // unlink($file);
            } else {
                $_context = @file_get_contents($file);
                $expire = substr($_context, 0, 10);
                $context = substr($_context, 11);
                if (time() > intval($expire)) {
                    @unlink($file);
                }
                $value = json_decode($context, true);
            }
        }
        return $value;
    }

    /**
     * 插入数据库
     *
     * @param array $data            
     */
    protected function add($data)
    {
        if (! $data) {
            RestPHP::error("更新数据不能空");
        }
        foreach ($data as $k => $v) {
            $title .= "`" . $k . "`,";
            $value .= ":" . $k . ",";
        }
        $title = rtrim($title, ",");
        $value = rtrim($value, ",");
        
        $sql = "INSERT INTO {$this->_table} ({$title})VALUES ({$value});";
        $sth = $this->prepare($sql);
        $rs = $sth->execute($data);
        if ($rs) {
            $rs = $this->lastInsertId();
        }
        return $rs;
    }

    /**
     * 更新数据
     * 
     * @param array $data            
     * @return boolean
     */
    protected function update($data)
    {
        if (! $data) {
            RestPHP::error("更新数据不能空");
        }
        if (! $this->_where) {
            RestPHP::error("更新条件不能空");
        }
        foreach ($data as $k => $v) {
            $str .= "`" . $k . "`=:" . $k . ",";
        }
        $str = rtrim($str, ",");
        
        $sql = "UPDATE {$this->_table} SET {$str} WHERE " . $this->_where . ";";
        $sth = $this->prepare($sql);
        
        $rs = $sth->execute($data);
        
        return $rs;
    }

    /**
     * 删除数据
     * 
     * @return boolean
     */
    protected function delete()
    {
        if (! $this->_where) {
            RestPHP::error("更新条件不能空");
        }
        
        $sql = "DELETE FROM {$this->_table}  WHERE " . $this->_where . ";";
        $sth = $this->prepare($sql);
        
        $rs = $sth->execute();
        
        return $rs;
    }

    /**
     * 设置查询条件
     * 
     * @param string $str
     *            查询条件,必须符合sql规范
     * @return \RestPHP\Model
     */
    protected function where($str)
    {
        $this->_where = $str;
        return $this;
    }

    /**
     * 统计
     * 
     * @return int
     */
    protected function count()
    {
        $sql = "select count(*) as num from " . $this->_table . " where " . $this->_where . " limit 1";
        $result = $this->select($sql);
        return $result[0]["num"];
    }

    /**
     * 设置数据库表
     * 
     * @param string $table
     *            表名
     * @return \RestPHP\Model
     */
    protected function table($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 获取一条数据
     * 
     * @return array
     */
    protected function get()
    {
        $sql = "select * from " . $this->_table . " where " . $this->_where . " limit 1";
        $result = $this->select($sql);
        return $result[0];
    }
}

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
        if (! $uri) {
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