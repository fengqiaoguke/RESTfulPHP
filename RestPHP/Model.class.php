<?php
namespace RestPHP;

class Model extends \PDO
{

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

    protected function select($sql)
    {
        $query = $this->query($sql);
        if(!$query){
            RestPHP::error($sql." 查询出错!");
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

    protected function where($str)
    {
        $this->_where = $str;
        return $this;
    }

    protected function count()
    {
        $sql = "select count(*) as num from " . $this->_table . " where " . $this->_where . " limit 1";
        $result = $this->select($sql);
        return $result[0]["num"];
    }

    protected function table($table)
    {
        $this->_table = $table;
        return $this;
    }

    protected function get()
    {
        $sql = "select * from " . $this->_table . " where " . $this->_where . " limit 1";
        $result = $this->select($sql);
        return $result[0];
    }
}

?>