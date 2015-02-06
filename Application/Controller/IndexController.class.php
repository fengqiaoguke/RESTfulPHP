<?php
namespace Controller;
 
use RestPHP\Controller;


use Model\UserModel;
use Model\BlogModel;
 
class IndexController extends Controller
{

    public function get()
    { 
        $blog = new UserModel();
        $rs = $blog->search("1");
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