<?php
namespace app\api\controller;
use think\Controller;



use think\Db;
use think\Request;
use think\Session;



use think\captcha\Captcha;
use think\model;
use think\Paginator;
use think\log;
use app\admin\model\Sms;
use app\admin\model\PlayerOrder;
use app\manage\model\WithdrawBank;




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


    public function directAgent2()
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

        $where = array();
        $where["parent_id"] =  $proxy["code"];
        $where['type']     = 0;//普通代理
        if (!empty($status)) {
            $where["islock"] = $status;
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

        //查询满足要求的总的记录数
        $count = Db::table('proxy')->where($where)->count();

//        $field = "id,code,percent,type,bind_mobile,lock,balance,historyin,username,nickname,regtime,last_login,descript";
        $field = "code,nickname";
        $list = Db::table('proxy')->field($field)->where($where)->order('id desc ')->page($page, $limit)->select();
//        print_r($proxy["code"]);die;
        $today = strtotime(date('Y-m-d 00:00:00'));
        $yesterday = $today - 24*3600;
        foreach ($list as &$item) {
            //直营税收
            $selfget = Db::name("incomelog")
                ->where([
                    'proxy_id' => $item['code'],
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 1, //自己的玩家收入
                ])->sum('totaltax');
            //团队税收
            $teamget = Db::name("incomelog")
                ->where([
                    'proxy_id' => $item['code'],
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 2 //下级团队的
                ])->sum('totaltax');
            //历史总收入
            $allget = Db::name("incomelog")
                ->where([
                    'proxy_id' => $item['code'],
                    'typeid'   => ['in', [0,4]],
                ])->sum('changmoney');
            //昨日总收入
            $yesterdaytt = Db::name("incomelog")
                ->where(      [
                    'proxy_id' => $item['code'],
                    'typeid'   => ['in', [0,4]],
                    'createtime' => [['egt', $yesterday], ['lt', $today]]
                ])
                ->sum('changmoney');
            //总充值
            $recharge = Db::name('paytime')
                ->where([
                    'proxy_id' => $item['code']
                ])
                ->sum('totalfee');
            $item['yesterday'] = floor($yesterdaytt*100)/100;
            $item['selfget'] = floor($selfget*100)/100;
            $item['teamget'] = floor($teamget*100)/100;
            $item['allget'] = floor($allget*100)/100;
            $item['recharge'] = floor($recharge*100)/100;


        }

        unset($item);

        return json(['code' => 0, 'count' => $count, 'stories' => $list]);
    }

    public function checkaccount2()
    {
        $proxy = session("proxy");
        $proxy_id = session("proxy_id");
        $data = Db::name("proxy")->field("bind_mobile,check_pass")->where('id', $proxy['id'])->select();
        foreach ($data as &$v) {

            if($v['bind_mobile']!=''){
                $v['bind_mobile'] = 3;
            }
            if($v['check_pass']!=''){
                $v['check_pass'] = 4;
            }

        }

        return json(['status' => 'success','msg' => '登陆成功！','stories'=>$data]);

    }

    public function checkaccount33()
    {
        $proxy = session("proxy");
        $proxy_id = session("proxy_id");
        $data = Db::name("proxy")->field("bind_mobile,check_pass")->where('id', $proxy['id'])->select();

        foreach ($data as &$v) {
            if($v['bind_mobile']=='' && $v['check_pass']==''){
                $v['bind_mobile'] = 4;
                $v['check_pass'] = 0;

            }else
            if($v['bind_mobile']!='' && $v['check_pass']==''){
                $v['check_pass'] = 4;
                $v['bind_mobile'] = 0;

            }else

            if($v['bind_mobile']!='' && $v['check_pass']!=''){
                $v['check_pass'] = 6;


            }



        }

        return json(['status' => 'success','msg' => '登陆成功！','stories'=>$data]);

    }

    public function checkaccount()
    {
        $proxy = session("proxy");
        $proxy_id = session("proxy_id");
        $data = Db::name("proxy")->field("bind_mobile,check_pass,username,password")->where('id', $proxy['id'])->select();
        $data2 = Db::name("bankinfo")->field("cardaccount,name")->where(['proxy_id'=>$proxy['code']])->select();
//        return json(['status' => 'success','msg' => '登陆成功！','stories'=>$result]);
//        print_r($data2[0]['cardaccount']);die;

        foreach ($data as &$v) {
            $v['username']=$data2[0]['name'];
            $v['password']=$data2[0]['cardaccount'];

            if($v['bind_mobile']=='' && $v['check_pass']==''){
                $v['bind_mobile'] = 4;
                $v['check_pass'] = 0;

            }else
                if($v['bind_mobile']!='' && $v['check_pass']==''){
                    $v['check_pass'] = 4;
                    $v['bind_mobile'] = 0;

                }else

                    if($v['bind_mobile']!='' && $v['check_pass']!=''){
                        $v['check_pass'] = 6;


                    }



        }
//        $data=array_merge($data1,$data2);
//        array_push($data,$data2);

        return json(['status' => 'success','msg' => '登陆成功！','stories'=>$data]);

    }

    //首页数据
    public function main(){
        $message =Db::name("message")->order( 'id desc ')->select();

        $today=strtotime(date('Y-m-d 00:00:00'));

        $proxy_id =session("proxy_id");

        $proxy = session("proxy");
        $data['addtime'] = array('egt',$today);

        if ($proxy_id == '1') {//admin

            //总注册
            $regtotal = Db::name("player")->count();
            //今日注册
            $regtoday = Db::name("player")->where(['regtime' => ['egt', date('Y-m-d 00:00:00')]])->count();

            //团队总税收
            $totaltax = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]]
                ])->sum("totaltax");

            //总收益
            $allmoney = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]]
                ])->sum('changmoney');


            //今日税收
            $today = strtotime(date('Y-m-d 00:00:00'));
            $todaytt = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'createtime' => ['egt', $today]
                ])->sum('changmoney');

            //代理总数
            $totalproxy = Db::name('proxy')->where(['islock' => 0])->count();
            //今日新增代理数
            $todayproxy = Db::name('proxy')->where(['islock' => 0, 'regtime' => ['like', date('Y-m-d').'%']])->count();
            //今日代理提现
            $todaywithdraw = Db::name('checklog')->where(['addtime' => ['like', date('Y-m-d').'%'], 'status' => 4])->sum('amount');

            $mydata = array();
            $data=array("regtotal"=>$regtotal,"regtoday"=>$regtoday,"totaltax"=>$totaltax ,
                "alltt"=>$allmoney,"todaytax"=>$todaytt
            ,"totalproxy"=>$totalproxy,"todayproxy"=>$todayproxy
            ,"todaywithdra"=>$todaywithdraw,"list"=>$message
            );

            $mydata[]=$data;
            return json(['status' => 'success','msg' => '登陆成功！','stories'=>$mydata]);



        } else {//其他代理

            //获取所有后代代理及自己的信息
            $teamlevels = Db::name('teamlevel')->where('parent_id', session("proxy_id"))->select();
            $proxyList = array_column($teamlevels, 'proxy_id');
            array_unshift($proxyList, session('proxy_id'));

            //总注册
            $regtotal = Db::name("player")->where(['proxy_id'=>['in', $proxyList]])->count();

            //今日注册
            $regtoday = Db::name("player")->where(['proxy_id'=>['in', $proxyList],'regtime' => ['egt', date('Y-m-d 00:00:00')]])->count();

            //昨日注册
            $regyestday = Db::name("player")->where([
                'proxy_id'=>['in', $proxyList],
                'regtime' => [['egt', date('Y-m-d 00:00:00', strtotime('-1 day'))],['lt', date('Y-m-d 00:00:00')]]
            ])->count();

            //历史总收益
            $historyin = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                ])->sum("changmoney");

            //总税收
            $totaltax = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 2 //下级团队的
                ])->sum("totaltax");
            //直营总税收
            $selftax = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 1 //自己的玩家收入
                ])->sum("totaltax");

            //总收益
            $allmoney = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 2 //下级团队的
                ])->sum('changmoney');
            //直营总收益
            $selfmoney = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 1 //自己的玩家收入
                ])->sum('changmoney');

            //昨日收益
            $today = strtotime(date('Y-m-d 00:00:00'));
            $yesterday = $today - 24*3600;

//            $yesterdaymoney = Db::name("incomelog")
//                ->where([
//                    'proxy_id' => session('proxy_id'),
//                    'typeid'   => ['in', [0,4]],
//                    'createtime' => [['egt', $yesterday], ['lt', $today]]
//                ])->sum('changmoney');

            //昨日总充值
            $recharge = Db::name('paytime')
                ->where([
                    'proxy_id' => ['in', $proxyList],
                    'addtime'  => ['like', date('Y/m/d', strtotime('-1 day')).'%']
                ])
                ->sum('totalfee');


            //今日直营税收
            $selftodaytax = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 1, //自己的玩家收入
                    'createtime' => ['egt', $today]
                ])->sum('totaltax');
            //今日团队税收
            $alltodaytax = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'createtime' => ['egt', $today],
                    'fxtype'   => 2 //下级团队的
                ])->sum('totaltax');

            //今日团队收益
            $alltodaymoney = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'createtime' => ['egt', $today],
                    'fxtype'   => 2 //下级团队的
                ])->sum('changmoney');
            //今日直营总收益
            $selftodaymoney = Db::name("incomelog")
                ->where([
                    'proxy_id' => session('proxy_id'),
                    'typeid'   => ['in', [0,4]],
                    'fxtype'   => 1, //自己的玩家收入
                    'createtime' => ['egt', $today]
                ])->sum('changmoney');


//            //领取红包按钮
//            $getReward = 0;
//            $reward = Db::name("dayreward")->where(['income' => ['elt', $yesterdaymoney]])->order("income desc")->limit(1)->find();
//            if (!$reward) {
//                $getReward = 1; //未达标
//            } else {
//                if (Db::name('incomelog')->where([
//                    'proxy_id' => $proxy_id,
//                    'typeid'   => config('incomelog.day_reward'),
//                    'createday' => date('Ymd')
//                ])->find()) {
//                    $getReward = 2;
//                }
//            }
//            return 5;
            $mydata = array();
            $data=array("regtotal"=>$regtotal,"regtoday"=>$regtoday,"totaltax"=>$totaltax ,
                "alltt"=>$allmoney
            ,"historyin"=>$historyin
            ,"selftax"=>$selftax,"list"=>$message
            ,"selfmoney"=>$selfmoney,"recharge"=>$recharge
            ,"selftodaytax"=>$selftodaytax,"alltodaytax"=>$alltodaytax
            ,"alltodaymoney"=>$alltodaymoney,"selftodaymoney"=>$selftodaymoney
            ,"list"=>$message
            );

            $mydata[]=$data;
            return json(['status' => 'success','msg' => '登陆成功！','stories'=>$mydata]);

        }
        // $now =
    }
    //新增代理
    public function addProxy(Request $request)
//    public function addProxy()
    {

//        return 4;

//        return 55;
        header('Access-Control-Allow-Origin:*');
//        if($request->isPost()){
        if($request->isGet()) {
//            $data=input('get.');
//            return 55;
//            $password= input("password");
//            return $password;
//            return 4;
//        header('Content-type:text/html; Charset=utf8');
//        header( "Access-Control-Allow-Origin:*");
//        header('Access-Control-Allow-Methods:POST');
//        header('Access-Control-Allow-Headers:x-requested-with,content-type');

            //            $this->check_login();
//            $data=input('post.');
//            $data=input('get.');
//            return $_GET['username'];
//            $a=input('username');
//            return $a;
//            return input('username');
//        return 4;
//        $proxy = session("proxy");
//        $mydata[]=$proxy ;
//        return 66;
//            return json(['status' => 'success','msg' => '登陆成功！','data'=>$mydata]);



//        $type  = input("type") ? intval(input("type")) : 0;
//            $type = 0;
//        $username = input("accountForReg");
//        $passord = input("passwordForReg");
//        $nickname = input("nickNameForReg");
//        $percent = input("royaltyRate");
//        $remark = input("remark");
        $proxy = session("proxy");
        $type  =  0;


            $username= input("username");
            $passord = input("password");
            $nickname = input("nickname");
            $percent = input("percent");
            $remark = input("beizhu");
//            if ($proxy['grade'] != 1) {
//                $type = 0;
//            }
//            $a= in_array($type, [0, 1]);
//        return 7;
//        return json(['status' => $username,'msg' => '登陆成功！','data'=>5]);


            if ($username == '' || $passord == '' || $nickname == '' || $percent == '' || !in_array($type, [0, 1])) {
//            if ($username == '' || $passord == '' || $nickname == '' || $percent == '' ) {
//                return $this->showmsg(0, "参数为空", '', '', null);
                return json(['status' => '参数为空3','msg' => '登陆成功！','data'=>5]);
//                return json(['status' => $username,'msg' => '登陆成功！','data'=>5]);
            }
            if ($passord && strlen($passord) < 6) {
//                return $this->showmsg(0, "密码长度至少为6位", '', '', null);
                return json(['status' => '密码长度至少为6位','msg' => '登陆成功！','data'=>5]);
            }
            $data = Db::name("proxy")->where("username", $username)->count();
            if ($data > 0) {
//                return $this->showmsg(0, "用户名已存在", '', '', null);
                return json(['status' => '用户名已存在','msg' => '登陆成功！','data'=>5]);
            }

            $data = Db::name("proxy")->where("nickname", $nickname)->count();
            if ($data > 0) {
//                return $this->showmsg(0, "昵称已存在", '', '', null);
                return json(['status' => '昵称已存在','msg' => '登陆成功！','data'=>5]);
            }

            if (!is_numeric($percent)) {
//                return $this->msg(0, "比例有误", null);
                return json(['status' => '比例有误','msg' => '登陆成功！','data'=>5]);
            }
            //判断分成比例
            $grade = $proxy["grade"];
            $this->rateData = Db::table('proxypercent')->find(1);

//要大于50
            if ($percent < $this->rateData['lowest_rate']) {

//                return $this->showmsg(0, "提成比例不能小于" . $this->rateData['lowest_rate'], '', '', null);
                return json(['status' => '提成比例不能小于','msg' => '登陆成功！','data'=>5]);
            }
            if ($grade == 1) { //管理员
//            if ($percent != $this->rateData['general_rate']) {
//                return $this->showmsg(0, "提成比例必须为" . $this->rateData['general_rate'], '', '', null);
//            }
                //要xia于100
                if ($percent > $this->rateData['general_rate']) {
//                    return $this->showmsg(0, "提成比例不能超过" . $this->rateData['general_rate'], '', '', null);
                    return json(['status' => '提成比例不能超过','msg' => '登陆成功！','data'=>5]);
                }
            } else {
                //要xia于90
                if ($percent > $this->rateData['level1_rate']) {
//                    return $this->showmsg(0, "提成比例不能超过" . $this->rateData['level1_rate'], '', '', null);
                    return json(['status' => '提成比例不能超过','msg' => '登陆成功！','data'=>5]);
                }
                //要xia于80
                if ($percent > $proxy["percent"] - 1) {

//                    return $this->showmsg(0, "提成比例不能大于55" . $proxy["percent"], '', '', null);
                    return json(['status' => '提成比例不能超过','msg' => '登陆成功！','data'=>5]);
                }
            }


            $salt = strtolower(generateSalt());

            $data = array(
                "username" => $username,
                "salt" => $salt,
                "password" => md5($salt . $passord),
                "nickname" => $nickname,
                "regtime" => date("Y-m-d H:i:s"),
                "balance" => 0,
                "lock" => 0,
                "type" => $type,
                "historyin" => 0,
                "percent" => $percent,
                "descript" => $remark,
                "last_ip" => get_ip(),
                "grade" => $grade + 1,
                "parent_id" => $proxy['code']
            );

//        var_dump(Db::name('teamlevel')->where(['proxy_id' => $proxy['code']])->select());
//        die;
            $ret = Db::name("proxy")->insertGetId($data);

            if ($ret) {
                $proxy_code = sprintf("MT%06d", $ret);
                Db::name("proxy")->where('id', $ret)->update(["code" => $proxy_code]);

                //新增代理等级关系

                //获取当前用户分销关系
                $fxList = Db::name('teamlevel')->where(['proxy_id' => $proxy['code']])->select();
                $insertData2 = [['proxy_id' => $proxy_code, 'parent_id' => $proxy['code'], 'level' => 1]];
                //组装插入分销关系表数据
                foreach ($fxList as $fx) {
                    $insertData2[] = ['proxy_id' => $proxy_code, 'parent_id' => $fx['parent_id'], 'level' => intval($fx['level']) + 1];
                }
                Db::name('teamlevel')->insertAll($insertData2);


                //生成模板
                //$template = Db::name('template')->where('template_code',1)->find();
                $arrTemplate = Db::name('template')->select();

                $usertemp = new \app\admin\model\UserTemplate();
                $usertemp->Qrcode($proxy_code);
                $qrcode = './public/upload/Qrcode/' . $proxy_code . '.png';
                $tagetimg = $qrcode;
                thrum($tagetimg, 230, 230, $tagetimg);

                foreach ($arrTemplate as $k => $template) {
                    if (!empty($template)) {
                        $cfgname = $username . $template['template_code'];


                        // str_replace("/public/","./",$qrcode);
                        $bigimg = "." . $template["template_image"];//str_replace("/public/","./",$template["template_image"]);

                        $qrcode_url = "./public/upload/Qrcode/proxy_" . $proxy_code . $template['template_code'] . '.png';
                        $ret = combinePic($bigimg, $tagetimg, $template["x"], $template["y"], $qrcode_url);
                        Log::write($ret . ':生成二维码状态', "ERROR");
                        $qrcode2 = '/public/upload/Qrcode/proxy_' . $proxy_code . $template['template_code'] . '.png';


                        $template = array("proxy_id" => $proxy_code, "config_name" => $cfgname, "template_code" => $template['template_code'],
                            "template_name" => $template['template_name'], "qrcode" => $qrcode2, "image_url" => $qrcode2,
                            "down_url" => $template['distribute_url'] . $proxy_code, "descript" => '');


                        if (config('useshorturl') === true) {
                            $template['down_url'] = file_get_contents(config('shorturl') . $proxy_code) ? file_get_contents(config('shorturl') . $proxy_code) : '';
                        }

                        $status = Db::name("user_template")->insert($template);
                    } else {
                        //return $this->msg(0, "模板不存在", null);
                    }
                }
//                return $this->msg(1, "提交成功", null);
                return json(['status' => '提交成功','msg' => '登陆成功！','data'=>6]);

            } else {
//                return $this->msg(0, "提交失败", null);
                return json(['status' => '提交失败','msg' => '登陆成功！','data'=>5]);
            }
        }
    }

    /**
     * 推广统计(税收统计)
     */
    public function spreadStatistics()
    {
        $agentid  = input("agentId");
        $regstart = input("startTime");
        $regend   = input("endTime");
        $month    = input("month");
        $proxy    = session("proxy");


        $where    = array();
        if ($agentid) {
            $where['a.proxy_id']  = $agentid;
            $where['a.parent_id'] = $proxy['code'];
        } else {
            $where['a.parent_id'] = $proxy['code'];
        }

        if (!empty($regstart)) {
            if (!empty($regend)) {
                $where['a.day'] = array(array('egt', $regstart), array('elt', $regend));
            } else {
                $where['a.day'] = array('egt', $regstart);
            }
        }

        if (!$month) {

            $list = Db::name('spreadsum')
                ->alias('a')
                ->field('a.day time,a.parent_id,sum(a.paynum) paynum, sum(a.bindnum) bindnum,sum(a.regnum) regnum,sum(a.totaltax) totaltax,sum(a.checknum) checknum,b.nickname')
                ->join('proxy b',' a.parent_id=b.code ',"LEFT")
                ->where($where)
                ->order('a.day desc')
                ->group('a.day, a.parent_id')
                ->paginate(10,false,['query'=>request()->param()])
                ->each(function ($item, $key) {
                    $children = Db::name('spreadsum')
                        ->alias('c')
                        ->field('c.day time,c.proxy_id,c.paynum,c.bindnum,c.regnum,c.totaltax,c.checknum,d.nickname')
                        ->join('proxy d',' c.proxy_id=d.code ',"LEFT")
                        ->where([
                            'c.day' => $item['time'],
                            'c.parent_id' => $item['parent_id']
                        ])
                        ->select();


                    $item['children'] = $children;
                    $item["rows"] =count($children)+1;

                    return $item;
                });

        } else {

            $list = Db::name('spreadsum')
                ->alias('a')
                ->field('a.week time, a.weekstart,a.weekend,a.parent_id,sum(a.paynum) paynum, sum(a.bindnum) bindnum,sum(a.regnum) regnum,sum(a.totaltax) totaltax,sum(a.checknum) checknum,b.nickname')
                ->join('proxy b',' a.parent_id=b.code ',"LEFT")
                ->where($where)
                ->order('a.week desc')
                ->group('a.week, a.parent_id,a.weekstart,a.weekend')
                ->paginate(10,false,['query'=>request()->param()])
                ->each(function ($item, $key) {
                    $children = Db::name('spreadsum')
                        ->alias('c')
                        ->field('c.week time,c.proxy_id,c.weekstart,c.weekend,sum(c.paynum) paynum,sum(c.bindnum) bindnum,sum(c.regnum) regnum,sum(c.totaltax) totaltax,sum(c.checknum) checknum,d.nickname')
                        ->join('proxy d',' c.proxy_id=d.code ',"LEFT")
                        ->where([
                            'c.week' => $item['time'],
                            'c.parent_id' => $item['parent_id']
                        ])
                        ->group('c.week,c.proxy_id,c.weekstart,c.weekend')
                        ->select();

                    $item['children'] = $children;
                    $item["rows"] =count($children)+1;
                    return $item;
                });
        }

        $sumdata = Db::name('spreadsum')
            ->alias('a')
            ->field('sum(a.regnum) regnum,sum(a.totaltax) totaltax')
            ->where($where)
            ->find();

        $mydata = array();
//        $data=array("list"=>$list,"sumdata"=>$sumdata,"agentid"=>$agentid ,
//            "regstart"=>$regstart
//        ,"regend"=>$regend
//        ,"proxy"=>$proxy,"month"=>$month
//
//        );

        $data=array("list"=>$list

        );

        $mydata[]=$data;
        return json(['status' => 'success','msg' => '成功！','stories'=>$mydata]);



    }

    /*
    * 收入统计
    */
    public function incomeStatistics()
    {

        $agentid   = input("agentId");
        $starttime = input("startTime");
        $endtime   = input("endTime");
        $month     = input("month");
        $proxy     = session("proxy");


        $where    = array();
        $strwhere = " 1=1 ";
        if (!empty($agentid)) {
            $where['proxy_id']  = $agentid;
            $where['parent_id'] = $proxy["code"];
            $strwhere           .= " and proxy_id='" . $agentid . "'";
        } else {
            $where["proxy_id"] = $proxy["code"];
            $strwhere          .= " and parent_id='" . $proxy["code"] . "'";
        }


        $allmytax = 0;

        $PlayerOrder = Model("app\admin\model\PlayerOrder");
//        $PlayerOrder = Db::name('playerorder');
        if (empty($month)) {

            if (!empty($starttime)) {
                if (!empty($endtime)) {
                    $where['addtime'] = array(array('egt', $starttime), array('elt', $endtime));
                    $strwhere         .= " and addtime>='" . $starttime . "' and addtime<='" . $endtime . "'";
                } else {
                    $where['addtime'] = array('egt', $starttime);
                    $strwhere         .= " and addtime>='" . $starttime . "'";
                }
            }
//            print_r($PlayerOrder);die;
            $listdata = $PlayerOrder
                ->field(" date_format(`addtime`,'%Y-%m-%d') dt,sum(total_tax) as totaltax")
                ->where($where)
                ->whereor($strwhere)
                ->group("date_format(`addtime`,'%Y-%m-%d')  ")
                ->order("dt desc")
                ->paginate(30,false,['query'=>request()->param()])
                ->each(function ($item, $key) {
                    $select_id = session("proxy_id");
                    $child     = Db::name("playerorder")
                        ->field("date_format(`addtime`,'%Y-%m-%d') as cdate,sum(total_tax) as total, a.proxy_id,b.nickname,b.percent")
                        ->alias('a')->join('proxy b', ' a.proxy_id=b.code ', "LEFT")
                        ->where(" date_format(`addtime`,'%Y-%m-%d')='" . $item['dt'] . "' and a.parent_id='" . $select_id . "' ")
                        ->group(" a.proxy_id")->select();


                    $child0 = Db::name("playerorder")
                        ->field("date_format(`addtime`,'%Y-%m-%d') as cdate,sum(total_tax) as total, a.proxy_id,b.nickname,b.percent")
                        ->alias('a')->join('proxy b', ' a.proxy_id=b.code ', "LEFT")
                        ->where(" date_format(`addtime`,'%Y-%m-%d')='" . $item['dt'] . "' and a.proxy_id='" . $select_id . "' ")
                        ->group(" a.proxy_id")->select();

                    $child         = array_merge($child0, $child);
                    $item['child'] = $child;
                    $item["rows"]  = count($child) + 1;
                    $totaltax      = 0;
                    $mytax         = 0;
                    foreach ($child as $key => $value) {
                        if ($value['proxy_id'] == $select_id) {
                            $mytax = $mytax + ($value["total"] * $value["percent"]) / 100;
                        } else {
                            $proxy     = session("proxy");
                            $mypercent = $proxy['percent'] - $value["percent"];
                            $mytax     = $mytax + ($value["total"] * $mypercent / 100);
                            $totaltax  = $totaltax + ($value["total"] * $value["percent"]) / 100;
                        }
                    }
                    $item["mytax"] = $mytax;
                    $item["ttax"]  = $totaltax;

                });

        } else {



            //按周
            $listdata = $PlayerOrder
                ->field(" week as dt,sum(total_tax) as totaltax")
                ->where($where)
                ->whereor($strwhere)
                ->group("week")
                ->order("week desc")
                ->paginate(30,false,['query'=>request()->param()])
                ->each(function ($item, $key) {
                    $select_id = session("proxy_id");
                    $child     = Db::name("playerorder")
                        ->field("week as cdate,sum(total_tax) as total, a.weekstart,a.weekend, a.proxy_id,b.nickname,b.percent")
                        ->alias('a')->join('proxy b', ' a.proxy_id=b.code ', "LEFT")
                        ->where(" week='" . $item['dt'] . "' and a.parent_id='" . $select_id . "' ")
                        ->group(" a.proxy_id")->select();


                    $child0 = Db::name("playerorder")
                        ->field("week as cdate,sum(total_tax) as total, a.weekstart,a.weekend, a.proxy_id,b.nickname,b.percent")
                        ->alias('a')->join('proxy b', ' a.proxy_id=b.code ', "LEFT")
                        ->where(" week='" . $item['dt'] . "' and a.proxy_id='" . $select_id . "' ")
                        ->group(" a.proxy_id")->select();

                    $child         = array_merge($child0, $child);
                    $item['child'] = $child;
                    $item["rows"]  = count($child) + 1;
                    $totaltax      = 0;
                    $mytax         = 0;
                    foreach ($child as $key => $value) {
                        $totaltax = $totaltax + ($value["total"] * $value["percent"]) / 100;
                        if ($value['proxy_id'] == $select_id) {
                            $mytax = $mytax + ($value["total"] * $value["percent"]) / 100;
                        } else {
                            $proxy     = session("proxy");
                            $mypercent = $proxy['percent'] - $value["percent"];
                            $mytax     = $mytax + ($value["total"] * $mypercent / 100);
                        }
                    }
                    $item["mytax"] = $mytax;
                    $item["ttax"]  = $totaltax;

                });


            foreach ($listdata as &$v) {
                $weekInfo = getWeekDate(date('Y'), $v['dt']);
                $v['dt']  = $weekInfo[0] . " ~ " . $weekInfo[1];
            }
            unset($v);

        }

        // print_r($listdata);

        $mytax2 = 0;
        $totaltax = 0;
        foreach ($listdata as $v) {
            $mytax2 += $v['mytax'];
            $totaltax += $v['ttax']+$v['mytax'];
        }
        $sumdata = ['totaltax' => $totaltax,'changmoney' => $mytax2];

        $data=array("proxy"=>$proxy,"list"=>$listdata

        );

        $mydata[]=$data;
//        $tt=json(['status' => 'success','msg' => '成功！','stories'=>$mydata]);
//        print_r($tt);die;
        return json(['status' => 'success','msg' => '成功！','stories'=>$mydata]);

    }

    /*
 * 修改账号密码changePassSys
 */
//    public function modifierPassword()
    public function changePassSys()
//    public function changePassSys(Request $request)
    {
//        return 5;
//        header('Access-Control-Allow-Origin:*');
//        if($request->isGet()) {
            $oldPwd = input('oldpassword');

            $newPwd = input("password");
            $confirmPwd = input('passwordConfirm');


            $proxy = session("proxy");
            $decrytPwd = md5($proxy['salt'] . $oldPwd);
            if ($decrytPwd === $proxy['password']) {
                $newPwd = md5($proxy['salt'] . $newPwd);
                $data = array('password' => $newPwd);
                Db::name("proxy")->where('id', $proxy['id'])->update($data);
                session('proxy', Db::name("proxy")->where('id', $proxy['id'])->find());
//            return true;
                return json(['status' => '密码修改成功', 'msg' => '登陆成功！', 'data' => 6]);
            } else {
//            return false;
                return json(['status' => '密码修改失败', 'msg' => '登陆成功！', 'data' => 5]);
            }
//        }
    }

    ///修改结算密码
//    public function modifycheckpass()
    public function changePassFinance()
    {
        $oldPwd = input('oldpassword');
        $newPwd = input("password");
        $confirmPwd = input('passwordConfirm');
        $proxy = session("proxy");

//        return json(['status' => '密码修改失败', 'msg' => '登陆成功！', 'data' => $newPwd ]);check_pass
        $decrytPwd = md5($proxy['settle_salt'] . $oldPwd);
//        print_r($proxy);die;
//        if ($decrytPwd === $proxy['password']) {
        if ($decrytPwd === $proxy['check_pass']) {
            $newPwd = md5($proxy['settle_salt'] . $newPwd);
            $data = array('check_pass' => $newPwd);
            Db::name("proxy")->where('id', $proxy['id'])->update($data);
            session('proxy', Db::name("proxy")->where('id', $proxy['id'])->find());
            return json(['status' => '密码修改成功', 'msg' => '登陆成功！', 'data' => 6]);
        } else {
            return json(['status' => '密码修改失败', 'msg' => '登陆成功！', 'data' => 5]);
        }
    }


//发送验证码
        public function sendcode4()
    {

        $mobile = input("mobile");
//        return json(['status' => -1, 'msg' => '参数错误！', 'data' => $mobile ]);
        if(!$mobile){

            return json(['status' => -1, 'msg' => '参数错误！', 'data' => 5]);
        }

//        $sms =new model\Sms();
        $sms = Model("app\admin\model\Sms");

        $smsmodel = config('smsmodel');
        if($smsmodel=='self') {
            $code = rand(1000, 9999);
            $ret = $sms->checkandsend($mobile, $code, 'bindmobile', get_ip());
            if (!$ret) {

                return json(['status' => -2, 'msg' => '短信发送频率过快！', 'data' => 5]);
            }
        }
        else if($smsmodel=='hff'){
            $ret = $sms->send_sms($mobile);

            if($ret->code!=0){
                return $this->showmsg(0, $ret->message, "", false, null);
            }
        }

        return json(['status' => 6, 'msg' => '短信发送成功', 'data' => 6]);
    }


    /*
 * 绑定手机号
 */
    public function bindMobileSave()
    {
//        $proxy = session("proxy");
//        return json(['status' => -1, 'msg' => '短信验证码不正确，请重新输入！', 'data' => $proxy['code']]);
            $mobile = input("phone");
        $code = input("code");
//        $code = '3453';
        if (empty($mobile) || empty($code)) {
//            return false;
            return json(['status' => -1, 'msg' => '参数错误！', 'data' => 5]);
        }

        $total = Db::name("proxy")->where("bind_mobile", $mobile)->count();
        if ($total > 0) {
//            return $this->msg(0, "该手机号码已经存在，请更换号码绑定！", null);
            return json(['status' => -1, 'msg' => '该手机号码已经存在，请更换号码绑定！', 'data' => 5]);
        }
//    use app\admin\model\Sms;
//        $PlayerOrder = Model("app\admin\model\PlayerOrder");
//        $PlayerOrder = Db::name('playerorder');
//        $PlayerOrder = Db::name('playerorder');
//        $sms = new Sms();
        $sms = Model("app\admin\model\Sms");
        $smsmodel = config('smsmodel');
        if ($smsmodel == "self") {
            $status = $sms->checkcode($mobile, $code, 'bindmobile');
        } else if ($smsmodel == "hff") {
            $ret = $sms->validateSms($mobile, $code);
            $status = $ret->code == 0 ? true : false;
        }
        if (!$status) {
//            return $this->msg(0, "短信验证码不正确，请重新输入", null);

        } else {

            $proxy = session("proxy");
            $data = array("bind_mobile" => $mobile);
            $ret = Db::name("proxy")->where("code", $proxy['code'])->update($data);
            if ($ret) {
//                return $this->msg(1, "绑定成功", null);
                return json(['status' => 1, 'msg' => '绑定成功！', 'data' => 6]);
            } else {
//                return $this->msg(0, "更新失败，请重新输入", null);
                return json(['status' => -1, 'msg' => '更新失败，请重新输入！', 'data' => 5]);

            }
        }

    }

    //找回结算密码
    public function sendcode1()
    {
        $mobile = input("mobile");
        if(!$mobile){
//            return $this->showmsg(0, "参数错误", "", false, null);
            return json(['status' => -1, 'msg' => '参数错误！', 'data' => 5]);
        }

//        $sms =new model\Sms();
        $sms = Model("app\admin\model\Sms");
        $smsmodel = config('smsmodel');
        if($smsmodel=='self') {
            $code = rand(1000, 9999);
            $ret = $sms->checkandsend($mobile, $code, 'resetpwd', get_ip());
            if (!$ret) {
//                return $this->showmsg(0, "短信发送频率过快", "", false, null);
                return json(['status' => -1, 'msg' => '短信发送频率过快！', 'data' => 5]);
            }
        }
        else if($smsmodel=='hff'){
            $ret = $sms->send_sms($mobile);
            if($ret->code!=0){
//                return $this->showmsg(0, $ret->message, "", false, null);
                return json(['status' => -1, 'msg' => '短信发送失败！', 'data' => 5]);
            }
        }
//        return $this->showmsg(1, "短信发送成功", "", false, null);
        return json(['status' => 1, 'msg' => '短信发送成功！', 'data' => 6]);
    }

    public function checkresetpassword(){
        $code = input("code");
        $mobile = input("codeMsg");

        if(empty($code) || empty($mobile)){
//            return $this->showmsg(0, "参数错误", "", false, null);
            return json(['status' => -1, 'msg' => '参数错误！', 'data' => 5]);
        }

//        $sms = new Sms();
        $sms = Model("app\admin\model\Sms");
        $status = $sms->validateSms($mobile,$code);//$sms->checkcode($mobile,$code,'resetpwd');
        //print_r($status);
        $status->code=0;
        if($status->code==0){
            session("resetcheckpass","isPass");
//            return json(['code' => 1, 'msg' => "checkpasssave"]);
            return json(['status' => 1, 'msg' => 'checkpasssave！', 'data' => 6]);
        }
        else
        {
//            return $this->error("验证码不正确");
            return json(['status' => -1, 'msg' => '验证码不正确！', 'data' => 5]);
        }
    }


    //第一次登陆设置结算密码
    public function resetSettlementPassword(){
        $passord = input("password");
//        $flag = session("resetcheckpass");
        $salt = strtolower(generateSalt());
//        if(isset($flag))
//        {
            $proxy = session("proxy");
            $checkpass = md5($salt.$passord);
            $data = [
                'check_pass'=>$checkpass,
                'settle_salt' => $salt,
                "updatetime"=>time()
            ];
            $status = Db::name("proxy")->where(['code'=>$proxy['code']])->update($data);

            if($status){
                session('proxy', Db::name("proxy")->where('id', $proxy['id'])->find());
//                return $this->success("验证密码修改成功",url("index/main"));
                return json(['status' => 1, 'msg' => '密码更新成功', 'data' => 6]);
            }
            else
            {
//                return $this->error("验证密码修改失败，请重试");
                return json(['status' => -1, 'msg' => '密码更新失败', 'data' => 5]);
            }
//        }
    }

    //得到银行卡信息
    public function getBank(){

        $proxy = session("proxy");

        $result = Db::name("bankinfo")->where(['proxy_id'=>$proxy['code']])->select();
        return json(['status' => 'success','msg' => '登陆成功！','stories'=>$result]);

    }


//    public function updateSettlementAccount()
//    {
//
//        $aliAccount = input("aliAccount");
//        $realname   = input("realName");
//
//
//
//        $password = input("settlementPassword");
//        $code     = input("code");
//        $codeMsg  = input("codeMsg");
//
//        $proxy_id  = session("proxy_id");
//        $proxy     = Db::name("proxy")->where("code", $proxy_id)->find();
//        $checkpass = md5($proxy["settle_salt"] . $password);
//
//        if ($proxy['check_pass'] != $checkpass) {
////            return $this->showmsg(0, "验证结算密码不正确，请重输！", '', false, null);
//            return json(['status' => -1, 'msg' => '密码更新失败', 'data' => 5]);
//        }
//
//        $sms = new \app\admin\model\Sms();
//        if( config('smsmodel')=="self"){
//            $status = $sms->checkcode($proxy["bind_mobile"],$code,'setaccount');
//            if(!$status){
//                return $this->showmsg(0,"短信验证码输入错误，请重输！",'',false,null);
//            }
//        }
//        else if( config('smsmodel')=="hff"){
//            $status = $sms->validateSms($proxy["bind_mobile"],$code);//$sms->checkcode($mobile,$code,'resetpwd');
//            //print("smsvalidate:".json_encode($status));
//            if($status->code!=0){
////                return $this->showmsg(0,"短信验证码输入错误，请重输！",'',false,null);
//                return json(['status' => -1, 'msg' => '密码更新失败', 'data' => 5]);
//            }
//        }
//
//
//        $total = Db::name("bankinfo")->where("proxy_id", $proxy['code'])->count();
//        $data  = array("alipay" => $aliAccount, "alipay_name" => $realname);//, "cardAccount"=>$cardAccount,"bank"=>$bank,"name"=>$name
//
//        $status = false;
//        if ($total > 0) {
//            $status = Db::name("bankinfo")->where("proxy_id", $proxy["code"])->update($data);
//        } else {
//            $data["proxy_id"] = $proxy["code"];
//            $status = Db::name("bankinfo")->insertGetId($data);
//        }
//        if ($status) {
////            return $this->showmsg(1, "更新账户信息成功", '', false, null);
//            return json(['status' => -1, 'msg' => '密码更新失败', 'data' => 5]);
//        } else {
////            return $this->showmsg(0, "更新失败，请稍后重试", '', false, null);
//            return json(['status' => -1, 'msg' => '密码更新失败', 'data' => 5]);
//        }
//    }


    public function updateSettlementBank()
    {


        $cardAccount = input("cardAccount");
        $bank        = input("bank");
        $name        = input("name");

        $password = input("settlementPassword");
        //$code     = input("code");
        $codeMsg  = input("codeMsg");
        $city  = input("city");
        $province  = input("province");
        $proxy_id  = session("proxy_id");
        $proxy     = Db::name("proxy")->where("code", $proxy_id)->find();
        $checkpass = md5($proxy["settle_salt"] . $password);
        if ($proxy['check_pass'] != $checkpass) {
//            return $this->showmsg(0, "结算密码不正确，请重输！", '', false, null);
            return json(['status' => -1, 'msg' => '结算密码不正确，请重输！', 'data' => 5]);
        }



        $find  = Db::name("bankinfo")->where("proxy_id", $proxy['code'])->find();


        $data   = array("cardaccount" => $cardAccount, "bank" => $bank, "name" => $name, 'city' => $city, 'province' => $province);

        $status = false;
        $withdraw = new WithdrawBank();
        if ($find) {
            if ($find['cardaccount'] == $cardAccount && $find['bank'] ==$bank && $find['name'] ==$name && $find['city'] ==$city && $find['province'] ==$province  ) {
//                return $this->showmsg(0, "信息未做修改", '', false, null);
                return json(['status' => -1, 'msg' => '信息未做修改', 'data' => 5]);
            }

            //更新第三方银行卡信息

            $res = $withdraw->addCard($cardAccount,$name,$bank,$city,$province);
//            print_r($res);die;
            $res2 = json_decode($res, true);

            if ($res2['success'] != true) {
                $res = $withdraw->modifyCard($cardAccount,$name,$bank,$city,$province);
            }

            save_log("apidata/bindbank",$proxy["code"].$res);
            $res2 = json_decode($res, true);

            if ($res2["success"] == true) {
                $status = Db::name("bankinfo")->where("proxy_id", $proxy["code"])->update($data);

            }

        } else {
            $data["proxy_id"] = $proxy["code"];
            $res = $withdraw->addCard($cardAccount,$name,$bank,$city,$province);
            $res2 = json_decode($res, true);
            if ($res2['success'] != true) {
                $res = $withdraw->modifyCard($cardAccount,$name,$bank,$city,$province);
            }
            save_log("apidata/bindbank",$proxy["code"].$res);
            $res2 = json_decode($res, true);
            if ($res2["success"] == true) {
                $status = Db::name("bankinfo")->insert($data);
            }
        }

        if ($status) {
//            return $this->showmsg(1, "更新账户信息成功", '', false, null);
            return json(['status' => 1, 'msg' => '更新账户信息成功', 'data' => 5]);
        } else {
//            return $this->showmsg(0, "更新失败，请稍后重试", '', false, null);
            return json(['status' => -1, 'msg' => '更新失败，请稍后重试', 'data' => 5]);
        }
    }

    public function bindmobile()
    {
        $proxy_id = session("proxy_id");
        $data2 = Db::name("proxy")->field("code,bind_mobile")->where('code', $proxy_id)->find();
        if (empty($data2['bind_mobile'])) {
//            return $this->fetch();
            return json(['status' => -1, 'msg' => '未绑定手机号码', 'data' => 5]);
        } else {
            $data[]=$data2;
            return json(['status' => 'success','msg' => '手机号码！','stories'=>$data]);

        }
    }

    public function sendcode()
    {
        $mobile = input("mobile");
//        return json(['status' => -1, 'msg' => '参数错误444！', 'data' => $mobile ]);
        if(!$mobile){
//            return $this->showmsg(0, "参数错误", "", false, null);
            return json(['status' => -10, 'msg' => '参数错误', 'data' => 5]);

        }
//        return json(['status' => -1, 'msg' => '未绑定手机号码', 'data' => $mobile]);

//        $sms =new model\Sms();
        $sms = Model("app\admin\model\Sms");
        $smsmodel = config('smsmodel');
        if($smsmodel=='self') {
            $code = rand(1000, 9999);
            $ret = $sms->checkandsend($mobile, $code, 'bindmobile', get_ip());
            if (!$ret) {
//                return $this->showmsg(0, "短信发送频率过快", "", false, null);
                return json(['status' => -1, 'msg' => '短信发送频率过快', 'data' => 5]);
            }
        }
        else if($smsmodel=='hff'){
            $ret = $sms->send_sms($mobile);
            if($ret->code!=0){
//                return $this->showmsg(0, $ret->message, "", false, null);
                return json(['status' => -1, 'msg' => '短信发送失败', 'data' => 5]);
            }

        }
//        return $this->showmsg(1, "短信发送成功", "", false, null);
        return json(['status' => 1, 'msg' => '短信发送成功', 'data' => 6]);
    }

    /*
 * 解绑手机号
 */
    public  function unbindmobilesave(){
        $mobile = input("phone");
        $code = input("code");
        if(empty($mobile) || empty($code)){
            return false;
        }
//        $sms = new Sms();
        $sms = Model("app\admin\model\Sms");
        $smstype = config("smsmodel");
        if($smstype=="self"){
            $status = $sms->checkcode($mobile,$code,'bindmobile');
        }
        else if($smstype=="hff"){
            $ret = $sms->validateSms($mobile,$code);
            if($ret->code==0){
                $status =true;
            }
            else{
                $status =false;
            }
        }
        if(!$status){
//            return $this->msg(0, "短信验证码不正确，请重新输入", null);
            return json(['status' => -1, 'msg' => '短信验证码不正确，请重新输入', 'data' => 5]);
        }

        if($status){
            $proxy = session("proxy");
            $data = array("bind_mobile"=>'');
            $ret =Db::name("proxy")->where("code",$proxy['code'])->update($data);
            if($ret){
//                return $this->msg(1, "解绑成功",  null);
                return json(['status' => 1, 'msg' => '解绑成功', 'data' => 6]);
            }
            else
            {
//                return $this->msg(0, "解绑失败，请重试",  null);
                return json(['status' => -1, 'msg' => '解绑失败，请重试', 'data' => 5]);

            }
        }

    }


    /*
 * 提交结算
 */
    public function submitSettlement()
    {
        $proxy_id = session("proxy_id");
        $proxy    = Db::name("proxy")->where('code', $proxy_id)->find();
        $bankinfo = Db::name("bankinfo")->where('proxy_id', $proxy['code'])->find();
        $jsonAli  = null;
        $jsonBank = null;
        if (!empty($bankinfo['alipay'])) {
            $jsonAli = array("agentId" => $bankinfo['proxy_id'], 'aliAccount' => $bankinfo['alipay'], "realName" => $bankinfo['alipay_name']);
        }
        if (!empty($bankinfo['cardaccount'])) {
            $jsonBank = array("agentId" => $bankinfo['proxy_id'], 'cardAccount' => $bankinfo['cardaccount'], "Bank" => $bankinfo['bank'], "Name" => $bankinfo['name'], 'city' => $bankinfo['city'],'province'=>$bankinfo['province']);
        }

//        return json(['status' => 'success','msg' => '手机号码！','stories'=>$data]);
        $data=array(

            "bankinfo"=>$bankinfo,
            "proxy"=>$proxy
//            , "alipay"=>json_encode($jsonAli),"bank"=>json_encode($jsonBank),

        );

        $mydata[]=$data;

        return json(['status' => 'success','msg' => '成功！','stories'=>$mydata]);


    }

    public function doSubmitSettlement()
    {
        $proxy_id           = session("proxy_id");
        $money              = input("money");
        $settlementPassword = input("settlementPassword");
//        $code = input("code");
//        $type = input("type");
        $type = 2;


        if (empty($money) || empty($type) || empty($settlementPassword)) {
//            return $this->msg(0, "提交的参数不能为空！", null);
            return json(['status' => -1, 'msg' => '提交的参数不能为空!', 'data' => 5]);
        }

        if (!is_numeric($money)) {
//            return $this->msg(0, "提交的金额！", null);
            return json(['status' => -1, 'msg' => '提交的金额！', 'data' => 5]);
        }

        $proxy = Db::name("proxy")->where('code', $proxy_id)->find();

        $settlepwd = md5($proxy['settle_salt'] . $settlementPassword);

        if ($proxy['check_pass'] != $settlepwd) {
//            return $this->showmsg(0, "结算密码不正确，请重输！", '', false, null);
            return json(['status' => -1, 'msg' => '结算密码不正确，请重输！', 'data' => 5]);
        }



        $taxMoney = 0;
        if (config("isTax")) {
            if ($money * 0.02 < 3)
                $taxMoney = 3;
            else
                $taxMoney = $money * 0.02;
        }

        if ($proxy['balance'] < $money + $taxMoney) {
//            return $this->msg(0, "结算金额大于您账户金额，请更改金额！", '', null);
            return json(['status' => -1, 'msg' => '结算金额大于您账户金额，请更改金额', 'data' => 5]);
        }


        $orderid = date('Ymd') . rand(100000, 999999);
        $yy      = $proxy["balance"] - $money - $taxMoney;
        $data    = array("orderid"   => $orderid, "proxy_id" => $proxy_id, "amount" => $money, "balance" => $yy,
            "checktype" => $type, "status" => 1, "addtime" => time(), "tax" => $taxMoney);


        $bankinfo = Db::name("bankinfo")->where('proxy_id', $proxy_id)->find();
        if ($type == 2) {
            if (!$bankinfo['bank'] || !$bankinfo['name']|| !$bankinfo['cardaccount']|| !$bankinfo['province']|| !$bankinfo['city']) {
//                return $this->msg(0, "请点击结算账户更新银行卡信息及其省份信息", '', null);
                return json(['status' => -1, 'msg' => '请点击结算账户更新银行卡信息及其省份信息', 'data' => 5]);
            }
            $data['bank']        = $bankinfo['bank'];
            $data['name']        = $bankinfo['name'];
            $data['cardaccount'] = $bankinfo['cardaccount'];
            $data['province'] = $bankinfo['province'];
            $data['city'] = $bankinfo['city'];

        } else if ($type == 1) {
            if (!$bankinfo['alipay'] || !$bankinfo['alipay_name']) {
//                return $this->msg(0, "请先绑定支付宝账户", '', null);
                return json(['status' => -1, 'msg' => '请先绑定支付宝账户', 'data' => 5]);
            }
            $data['alipay']      = $bankinfo['alipay'];
            $data['alipay_name'] = $bankinfo['alipay_name'];
        } else {
//            return $this->msg(0, "提现方式有误！", '', null);
            return json(['status' => -1, 'msg' => '提现方式有误！', 'data' => 5]);
        }

        $data['descript']   = $proxy['nickname'] . ",id" . $proxy['code'] . '于' . date('Y-m-d H:i:s') . '提现金额' . $money . '元';
        $data['addtime']    = date("Y-m-d H:i:s");
        $data['createtime'] = time();
        Db::startTrans();
        try {
            $account_data = array("balance" => $yy);
            $ret          = Db::name("proxy")->where("code", $proxy_id)->update($account_data);
            if (!empty($ret)) {
                $ret = Db::name("checklog")->insert($data);
                if ($ret) {
                    $moneyInfo = Db::name("proxy")->where("code", $proxy_id)->field('balance,historyin')->find();
                    //金额log
                    Db::name('moneylog')->insert([
                        'type' => 1,
                        'detail' => 3,
                        'tax' => 0,
                        'money' => $money + $taxMoney,
                        'leftmoney' => $moneyInfo['balance'],
                        'historyin' => $moneyInfo['historyin'],
                        'proxy_id'  => $proxy_id,
                        'createtime' => date("Y-m-d H:i:s"),
                        'createday' => date("Ymd")
                    ]);

                    $data1 = array(
                        "typeid"     => 1,
                        "proxy_id"   => $proxy['code'],
                        "changmoney" => $money,
                        "descript"   => "用户提现，金额" . $money . ",税收." . $taxMoney,
                        "createtime" => time()
                    );
                    Db::name("incomelog")->insert($data1);
                }
                //Log::write('charu=='.$ret,"debug");
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            //Log::write('charu=='.$e->getMessage(),"debug");
            // 回滚事务
            Db::rollback();
        }
//        return $this->msg(1, "提交成功，请等待审核，审核成功后自动放款！", null);
        return json(['status' => 1, 'msg' => '提交成功，请等待审核，审核成功后自动放款!', 'data' => 6]);
    }

//结算记录
    public function settlementRecord()
    {

        $status     = input("condition");
        $startime   = input("startTime");
        $endtime    = input("endTime");
        $alipay     = input("alipayAccount");
        $alipayName = input("realName");
        $agent_id   = input("agentId");


        $proxy_id = session("proxy_id");
        $where    = array();
        if (!empty($status)) {
            $where["a.status"] = $status;
        }


        if (!empty($startime)) {
            if (!empty($endtime)) {
                $where['addtime'] = array(array('gt', strtotime($startime)), array('lt', strtotime($endtime)));
            } else {
                $where['addtime'] = array('gt', strtotime($startime));
            }
        }


        if (!empty($alipay)) {
            $where["a.alipay"] = $alipay;
        }

        if (!empty($alipayName)) {
            $where["a.alipay_name"] = array("like", '%' . $alipayName . '%');
        }

        $strwhere = "";
        if (!empty($agent_id)) {
            $strwhere = " a.proxy_id in (select `code` from proxy where parent_id='" . $proxy_id . "' and code='" . $agent_id . "') ";
            if (!empty($status)) {
                $strwhere .= " and status=" . $status;
            }
            if ($proxy_id == $agent_id) {
                $where["a.proxy_id"] = $proxy_id;
            }
            if (!empty($alipay)) {
                $strwhere .= " and a.alipay=" . $alipay;
            }
        } else {
            $where["a.proxy_id"] = $proxy_id;
            $strwhere            = " a.proxy_id in (select `code` from proxy where parent_id='" . $proxy_id . "')";
            if (!empty($status)) {
                $strwhere .= 'and a.status=' . $status;
            }
            if (!empty($alipay)) {
                $strwhere .= " and a.alipay='" . $alipay . "'";
            }
        }


        $checklist = Db::name("checklog")->alias('a')
            ->field('a.id,a.orderid,a.proxy_id,a.tax,a.amount,a.alipay,a.name,a.bank,a.cardaccount,a.alipay_name,a.checktype,a.status,a.addtime,a.createtime,a.descript,c.nickname,c.username')
            ->join('proxy c', ' a.proxy_id=c.code', "LEFT")
            ->where($where)->whereor($strwhere)
//            ->paginate();
//        ->limit(5)->select();
            ->select();
        foreach ($checklist  as &$v) {
            $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            if($v['status'] ==1){
                $v['status']='审核中';


            }else if($v['status'] ==2){
                $v['status']='审核通过';

            }else if($v['status'] ==3){
                $v['status']='支付驳回';
            }else if($v['status'] ==4){
                $v['status']='已完成';

            }else{
                $v['status']='银行处理';
            }
        }
        $data=array(

            "list"=>$checklist


        );

        $mydata[]=$data;


        return json(['status' => 'success','msg' => '成功！','stories'=>$checklist]);
//        $this->assign("status", $status);
//        $this->assign("starttime", $startime);
//        $this->assign("endtime", $endtime);
//        $this->assign("list", $checklist);
//        $this->assign("alipay", $alipay);
//        $this->assign("alipayname", $alipayName);
//        $this->assign("agentid", $agent_id);
//        return $this->fetch();
    }

    public function playerDetail()
    {

        $gamerId  = input("gamerId");
        $startime = input("startTime");
        $endTime  = input("endTime");

        $proxy_id = session("proxy_id");
        $proxy    = session("proxy");

        $startime1 = $startime . " 00:00:00";
        $endTime1  = $endTime . " 23:59:59";

        $where     = array();

        //管理员查全部
        $strwhere  = $proxy_id == 1 ? " 1=1" : "a.parent_id='" . $proxy_id . "'";
        if (!empty($gamerId)) {
            $where["a.userid"] = $gamerId;
            $strwhere          .= " and a.userid='{$gamerId}'";
        }

        if ($proxy_id != 1) {
            $where["a.proxy_id"] = $proxy_id;
        }

        if (!empty($startime1)) {
            if (!empty($endTime)) {
                $where['a.regtime'] = array(array('gt', $startime1), array('lt', $endTime1));
                $strwhere           .= " and a.regtime>'" . $startime1 . "' and a.regtime<'" . $endTime1 . "'";
            } else {
                $where['a.regtime'] = array('gt', $startime1);
                $strwhere           .= " and a.regtime>'" . $startime1 . "'";
            }
        }

        $list = Db::name("player")->alias('a')
            ->field(" a.proxy_id,a.userid,a.proxy_name,a.nickname,a.ismobile,a.regtime,a.addtime")
            ->join(" proxy b ", " a.proxy_id=b.code", "LEFT")
            ->where($where)
            ->whereor($strwhere)
            ->order("a.regtime desc")
            ->paginate('15', false, ['query'=>request()->param()])
            ->each(function ($item,$key) {
                $tax = Db::name('playerorder')
                    ->where(['userid' => $item['userid'], 'proxy_id'=>$item['proxy_id']])
                    ->sum('total_tax');
                $item['totaltax'] = $tax;
                return $item;
            });



        $data=array(

            "list"=>$list


        );

        $mydata[]=$data;


        return json(['status' => 'success','msg' => '成功！','stories'=>$mydata]);

//        $this->assign("list", $list);
//        $this->assign("gamerid", $gamerId);
//        $this->assign("proxy", $proxy);
//        $this->assign("starttime", $startime);
//        $this->assign("endTime", $endTime);
//        return $this->fetch();
    }

    public function picture()
    {

        $myid=input('myid');
        $page  = intval(input('page')) > 0 ? intval(input('page')) : 1;
        $limit = intval(input('limit')) > 0 ? intval(input('limit')) : 10;
        $proxy_id     = session("proxy_id");
        $where        = array("proxy_id" => $proxy_id);
//        $templatelist = Model("UserTemplate");
        $templatelist = Model("app\admin\model\UserTemplate");
//        $sms = Model("app\admin\model\Sms");


        //查询满足要求的总的记录数
        $list ='';
//        $myid=298;
        if($myid>0){
            $maxid = $templatelist->where($where)->max('id');
            $minid = $templatelist->where($where)->min('id');
            if($myid>$maxid){
                $myid=$minid ;
            }else if($myid<$minid){
                $myid=$maxid ;
            }else{
                $myid= $myid;
            }
            $list = $templatelist->where($where)->where('id',$myid)->order('id desc ')->limit(1)->select();
        }else{
            $list = $templatelist->where($where)->order('id desc ')->limit(1)->select();
        }


//        print_r($list );die;
        $count = $templatelist->where($where)->count();
        return json(['status' => 'success','msg' => '成功！','stories'=>$list]);
        return json(['code' => 0, 'data' => $list, 'count' => $count]);
    }
    public function picture2()
    {
        $page  = intval(input('page')) > 0 ? intval(input('page')) : 1;
        $limit = intval(input('limit')) > 0 ? intval(input('limit')) : 10;
        $proxy_id     = session("proxy_id");
        $where        = array("proxy_id" => $proxy_id);
//        $templatelist = Model("UserTemplate");
        $templatelist = Model("app\admin\model\UserTemplate");
//        $sms = Model("app\admin\model\Sms");
        //查询满足要求的总的记录数
        $list = $templatelist->where($where)->order('id desc ')->page($page, $limit)->select();
        $count = $templatelist->where($where)->count();
        return json(['status' => 'success','msg' => '成功！','stories'=>$list]);
        return json(['code' => 0, 'data' => $list, 'count' => $count]);
    }
//重新生成二维码
    public function resetpromotionsetting()
    {
        for ($templateId = 1; $templateId<=6; $templateId++) {
            $usertemp = new \app\admin\model\UserTemplate();
            $usertemp->Qrcode(session("proxy_id"));
            $template = Db::name("template")->where("template_code",$templateId)->find();
            $proxy_id = session("proxy_id");
            $target =$filename ="./public/upload/Qrcode/".$proxy_id.".png";
            thrum($target, 230,230, $target);
            if($template){
                $filename ="./public/upload/Qrcode/proxy_".$proxy_id."_".$templateId.".png";
                $source = "./".$template["template_image"];//str_replace("/public/",);
                combinePic($source,$target,$template["x"],$template["y"],$filename);
                $qrcode_url =str_replace("./","/",$filename);
                $data = array(
                    "template_code" => $template['template_code'],
                    "template_name" => $template['template_name'],
                    "qrcode" => $qrcode_url,
                    "image_url" => $qrcode_url,
                    "descript" => date('Y-m-d H:i:s').'生成',
                    "down_url" => $template['distribute_url'] . $proxy_id
                );
                //使用微信地址
                if (config('useshorturl') === true) {
                    $data['down_url'] = file_get_contents(config('shorturl').$proxy_id) ? file_get_contents(config('shorturl').$proxy_id) : '';
                }
                $find = Db::name("user_template")->where("proxy_id",$proxy_id)->where('template_code', $templateId)->find();
                if ($find) {
                    $status = Db::name("user_template")->where("proxy_id",$proxy_id)->where('template_code', $templateId)->update($data);
                } else {
                    $data['config_name'] = session('proxy')['nickname'].$template['template_code'];
                    $data['proxy_id'] = $proxy_id;
                    $status = Db::name("user_template")->insertGetId($data);
                }

            }
        }

        if($status){
//            return $this->msg(1,'生成成功',null);
            return json(['status' => 1, 'msg' => '生成成功!', 'data' => 6]);
        }
        else
        {
//            return $this->msg(0,'生成失败',null);
            return json(['status' => -1, 'msg' => '生成失败!', 'data' => 5]);
        }


    }



}