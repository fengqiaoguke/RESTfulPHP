<?php
namespace Model;

use RestPHP\Model;

class BlogModel extends Model
{

    protected $_table = "";

    /**
     * 搜索
     *
     * @param string $where
     *            查询条件
     * @param number $page
     *            要查询的页数
     * @param string $order
     *            排序
     * @param string $options
     *            其它选项 expire:缓存失效时间;num:每页显示条数;debug:调试模式
     * @return array $result 返回搜索结果
     */
    public function search($where = "1", $page = 1, $order = "id desc", $options = null)
    {
        $page = intval($page) < 1 ? 1 : $page;
        $num = isset($options["num"]) ? intval($options["num"]) : 20;
        $start = ($page - 1) * $num;
        
        $cacheTime = isset($options["expire"]) ? $options["expire"] : 300;
        
        // 读取缓存
        $cacheName = trim($this->_table . "_where:" . $where . ";page:" . $page . ";order:" . $order . ";num:" . $num);
        $result = $this->cache($cacheName);
        if ($result) {
            return $result;
        }
        $sql = "select * from user where {$where} order by {$order} limit {$start},{$num}";
        $result["list"] = $this->select($sql);
        if ($options["debug"]) {
            echo $this->_sql() . "<br>";
        }
        foreach ($result["list"] as $k => $v) {
            $result["list"][$k] = $this->getInfo($v["id"]);
        }
        $result["total_num"] = $this->where($where)->count();
        
        $result["total_page"] = ceil($result["total_num"] / $num);
        if ($result["total_page"] > $page) {
            $result["next_page"] = $page + 1;
        } else {
            $result["next_page"] = "";
        }
        if ($page > 1) {
            if ($page > $result["total_page"]) {
                $result["prev_page"] = $result["total_page"] - 1;
            } else {
                $result["prev_page"] = $page - 1;
            }
        } else {
            $result["prev_page"] = "";
        }
        
        $this->cache($cacheName, $result, $cacheTime);
        return $result;
    }

    /**
     * 获取单条信息
     *
     * @param number $id            
     * @param string $options
     *            选项 expire:缓存失效时间;
     * @return array $result 返回结果
     */
    public function getInfo($id, $options = null)
    {
        $id = intval($id);
        $cacheTime = isset($options["expire"]) ? $options["expire"] : 600;
        if (! $id) {
            return false;
        }
        // 读取缓存
        $cacheName = trim($this->_table . "_" . $id);
        $result = $this->cache($cacheName);
        if ($result) {
            return $result;
        }
        $result = $this->where("id=" . intval($id))->get();
        $result["time"] = time();
        /*
         * 可以在这组合数据然后缓存起来,以后每次读取内容都走此方法;
         * 当内容更新时候会自动清缓存,否则等待缓存自动过期
         */
        $this->cache($cacheName, $result, $cacheTime);
        
        return $result;
    }

    /**
     * 添加数据
     *
     * @param array $data            
     * @return number $id 返回自增id
     */
    public function addInfo($data)
    {
        /*
         * 这里可以放预处理数据e.g.
         * $data["createtime"] = time();
         * unset($data["id"]);
         */
        $id = $this->add($data);
        return $id;
    }

    /**
     * 删除数据
     *
     * @param number $id            
     * @return boolean
     */
    public function deleteInfo($id)
    {
        /*
         * 这里可以数据验证,比如验证子类是否还有数据
         *
         */
        $where = "id=" . intval($id);
        $id = $this->where($where)->delete();
        
        // 删除缓存
        $cacheName = trim($this->_table . "_" . $id);
        $this->cache($cacheName, NULL);
        
        return $id;
    }

    /**
     * 更新数据
     *
     * @param number $id            
     * @param array $data            
     * @return boolean
     */
    public function updateInfo($id, $data)
    {
        $id = intval($id);
        if (! $id) {
            return false;
        }
        unset($data["id"]);
        $where = "id=" . intval($id);
        $result = $this->where($where)->update($data);
        
        // 删除缓存
        $cacheName = trim($this->_table . "_" . $id);
        $this->cache($cacheName, NULL);
        
        return $result;
    }
}
        
    