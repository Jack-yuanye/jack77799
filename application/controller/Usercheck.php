<?php
namespace app\api\controller;
use think\Controller;
//use app\admin\model\Teamlevel;


use think\Db;
use think\Request;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 15:50
 */


class Usercheck extends Controller{
    public function _initialize() {
        $this->check_login();
    }
    protected function check_login(){
        $userid=session('userid');
        if(empty($userid)){
            $this->redirect('api/user/login');
        }
    }


}