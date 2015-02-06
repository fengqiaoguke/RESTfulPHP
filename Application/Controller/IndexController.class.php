<?php
namespace Controller;
 
use RESTfulPHP\Controller;


use Model\UserModel;
 
class IndexController extends Controller
{

    public function get()
    {
        echo "run:";
        $blog = new UserModel();
        $rs = $blog->getInfo(1);
        print_r($rs);
    }

    public function put()
    {
        echo 'put';
    }

    public function delete()
    {
        echo 'delete';
    }

    public function post()
    {
        echo 'post';
    }
}
?>