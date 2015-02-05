<?php
namespace RESTful;

class Model extends \PDO
{

    public function __construct()
    {
        $confPath = APP_PATH . "Conf/config.ini";
        if (! $conf = parse_ini_file($confPath, true)) {
            \RESTfulPHP::error('Unable to open ' . $confPath . '.');
        }
        parent::__construct($conf['database']['dsn'], $conf['database']['user'], $conf['database']['pass']);
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
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $query->fetchAll();
        return $result;
    }

    protected function cache($key, $value = NULL, $time = NULL)
    {
        if(!$this->conf["memcache"]["host"]){
            return false;
        }
        $memcache = new \Memcache();
        $memcache->connect($this->conf["memcache"]["host"], $this->conf["memcache"]["port"]);
        
        if($value){
            $memcache->add($key, $value,false,$time);
        }else{
            $memcache->get($key);
        }
    }

    /**
     * 插入数据库
     *
     * @param array $data            
     */
    protected function add($data)
    {
        if (! $data) {
            \RESTfulPHP::error("更新数据不能空");
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
            \RESTfulPHP::error("更新数据不能空");
        }
        if (! $this->_where) {
            \RESTfulPHP::error("更新条件不能空");
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
            \RESTfulPHP::error("更新条件不能空");
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