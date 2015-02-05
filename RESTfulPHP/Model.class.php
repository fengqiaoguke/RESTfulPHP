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
    }

    public function getAll($sql)
    {
        $query = $this->query($sql);
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $query->fetchAll();
        return $result;
    }

    public function getOne($sql)
    {
        $result = $this->getAll($sql);
        return $result[0];
    }
}

?>