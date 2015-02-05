<?php
namespace Model;

use RESTful\Model;
class UserModel extends Model {
    function getInfo($id){
        $rs = $this->getOne("select * from user");
        
        print_r($rs);
    }
}