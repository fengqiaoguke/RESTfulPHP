<?php
namespace Controller;

use RESTfulPHP\Controller;
use Model\UserModel;

class IndexController extends Controller
{

    public function get()
    {
        $user = new UserModel();
        $user->getInfo(1);
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