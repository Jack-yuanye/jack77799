<?php
namespace app\api\controller;
use think\Controller;
//use app\admin\model\Teamlevel;


use think\Db;
use think\Request;
use think\Session;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 15:50
 */


class User extends Controller{
//    protected function check_login(){
//        $userid=session('userid');
//        if(empty($userid)){
//            $this->redirect('admin/login/index');
//        }
//    }
    public function login(Request $request)//登陆接口
    {

        header('Access-Control-Allow-Origin:*');
//        if($request->isPost()){
        if($request->isGet()){


//            $this->check_login();
//            $data=input('post.');
            $data=input('get.');
//            return $_GET['username'];
            $a=input('username');
//            return $a;
//            return input('username');

            $salt = Db::name('proxy')->where(['username' => input('username')])->value('salt');

            if(!$salt){

                return json(['status' => 'error','msg' => '用户名错误！']);
            }
            $map['username'] = input('username');
            $map['password'] = md5($salt . input('password'));
            $proxy = Db::name('proxy')->where($map)->find();
//            return input('password');

//            $password=substr(md5($data['password']),8,16);
//            $result=Db::name('proxy')->where('username',$data['username'])->where('password',$password)->find();
            if($proxy){
                switch ($proxy['islock']) {
//                    case '0':
                    case '0':
                        session('userid', $proxy['code']);
//return session('userid');
//                        return json(['status' => 'success','msg' => '登陆成功！']);
//                        return json(['status' => 'success','msg' => '登陆成功！']);
//                        Db::name("proxy")->where("id",$proxy['id'])->update($data);session("proxy")
//                        return $proxy['username'];
                        Session::set('proxy', $proxy);
//                        $proxy = session("proxy");
//                        return $proxy['username'];
                        Session::set('proxy_id', $proxy["code"]);
                        return json(['status' => 'success','msg' => '登陆成功！','data'=>session('userid')]);
                        break;
                    case '1':
                        return json(['status' => 'error','msg' => '用户已被禁用，请联系管理员！']);
                        break;
                }
            }else{
                return json(['status' => 'error','msg' => '密码错误！']);
            }
        }
    }


    public function proxy(Request $request)
    {


        header('Access-Control-Allow-Origin:*');
        if($request->isGet()){

            $result = Db::name('proxy')->select();

            return json(['status' => 'success','msg' => '登陆成功！','stories'=>$result]);
            if(!$result){
                return 4;

            }

        }
    }


    public function message(Request $request)
    {


        header('Access-Control-Allow-Origin:*');
        if($request->isGet()){

            $result = Db::name('message')->select();
            foreach ($result as &$v) {
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            }
//            foreach ($result as  $value){
//                return $value['title'];
//            }

            return json(['status' => 'success','msg' => '登陆成功！','stories'=>$result]);
            if(!$result){
                return 4;

            }

        }
    }


    public function directAgent()
    {

        $status = input("status");

        $agentid = input("agentId");
        $phone = input("phone");
        $regstart = input("startTime");
        $regend = input("endTime");
        $proxy = session("proxy");
        $minmoney = input("minMoney");
        $maxmoney = input("maxMoney");
        $nickname = input("nickName");
        $username = input("account");
        $page  = intval(input('page')) > 0 ? intval(input('page')) : 1;
        $limit = intval(input('limit')) > 0 ? intval(input('limit')) : 10;

// return $proxy['username'];
        $where = array();
        $where["parent_id"] =  $proxy["code"];
        if (!empty($status)) {
            $where["lock"] = $status;
        } else {
            $status = "";
        }


        if (!empty($agentid)) {
            $where['code'] = $agentid;
        }

        if (!empty($phone)) {
            $where['bind_mobile'] = $phone;
        }

        if (!empty($regstart)) {
            if (!empty($regend)) {
                $where['regtime'] = array(array('gt', $regstart), array('lt', $regend));
            } else {
                $where['regtime'] = array('gt', $regstart);
            }
        }

        if (!empty($minmoney)) {
            if (!empty($maxmoney)) {
                $where['balance'] = array(array('gt', $minmoney), array('lt', $maxmoney));
            } else {
                $where['balance'] = array('gt', $minmoney);
            }
        }

        if (!empty($nickname)) {
            $where["nickname"] = ['like', '%' . $nickname . '%'];;
        }

        if (!empty($username)) {
            $where["username"] = ['like', '%' . $username . '%'];;
        }
        //print_r($where);

        //查询满足要求的总的记录数
        $count = Db::table('proxy')->where($where)->count();

        $field = "id,code,percent,type,bind_mobile,lock,balance,historyin,username,nickname,regtime,last_login,descript";
        $list = Db::table('proxy')->field($field)->where($where)->order('id desc ')->page($page, $limit)->select();
//        print_r($list);die;

        $today = strtotime(date('Y-m-d 00:00:00'));
        $yesterday = $today - 24*3600;
        foreach ($list as &$item) {
//            if ($item["bind_ip"] == '')
//                $item["bind_ip"] = "否";
//            else
//                $item["bind_ip"] = "是";

            $yesterdaytt = Db::name("incomelog")
                ->where(      [
                    'proxy_id' => $item['code'],
                    'typeid'   => config('incomelog.income'),
                    'createtime' => [['egt', $yesterday], ['lt', $today]]
                ])
                ->sum('changmoney');
            $item['yesterday'] = floor($yesterdaytt*100)/100;

            if ($item["bind_mobile"] == '')
                $item["bind_mobile"] = "否";
            else
                $item["bind_mobile"] = "是";

            if ($item["lock"] == '1')
                $item["lock"] = "是";
            else
                $item["lock"] = "否";
        }

        unset($item);
        return json(['code' => 0, 'count' => $count, 'stories' => $list]);
    }

}