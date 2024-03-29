<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use think\Validate;
use think\captcha\Captcha;
use app\admin\model;

class Login extends Controller
{

    public function Index()
    {
        $this->assign('error', '');
        $this->assign('username', '');
        return $this->fetch();
    }


    public function  apilogin(){
        $sign = "wx_q8922030234";
        $proxyId= input("mchid");
        $token = input("token");

        $where = array("code"=>$proxyId);
        $proxy = Db::name('proxy')->where($where)->find();
        if(is_array($proxy)){
            $newtoken =md5($sign.$proxy["code"].$proxy["password"]);
            if($newtoken==$token)
            {
                Session::set('proxy', $proxy);
                Session::set('proxy_id', $proxy["code"]);
                $this->redirect('index/index');
            }
        }
    }



    public function Auth()
    {
        $error = "";
        $verify_code = trim(input('captcha'));
        $username =input('userName');
        if (Request::instance()->isPost()) {
            if ($this->check($verify_code,"login")) {

                $salt = Db::name('proxy')->where(['username' => input('userName')])->value('salt');
                $map['username'] = input('userName');
                $map['password'] = md5($salt . input('password'));
                $proxy = Db::name('proxy')->where($map)->find();
                if ($proxy) {

                    if($proxy['islock']==1){
                        $error = "您的账号已被封号，请联系客服人员";
                        $error_html = '<p style="color: red;text-align: center">' . $error . '</p>';
                        $this->assign('error', $error_html);
                        $this->assign('username', '');
                        return $this->fetch('login/index');
                    }
                    else {
                        $data= [
                            'last_login'  => Db::raw("now()"),
                            'logtimes' => Db::raw('logtimes+1'),
                            'last_ip' => get_ip()
                        ];
                        Db::name("proxy")->where("id",$proxy['id'])->update($data);
                        Session::set('proxy', $proxy);
                        Session::set('proxy_id', $proxy["code"]);
                        $this->redirect('index/index');
                    }
                } else {
                    $error = "用户名密码错";
                    $error_html = '<p style="color: red;text-align: center">' . $error . '</p>';
                    $this->assign('username', $username);
                    $this->assign('error', $error_html);
                    return $this->fetch('login/index');
                }
            } else {
                $error = "验证码错误";
                $error_html = '<p style="color: red;text-align: center">' . $error . '</p>';
                $this->assign('error', $error_html);
                $this->assign('username', $username);

                return $this->fetch('login/index');
            }
        } else {
            $this->assign('error', '');
            $this->assign('username', '');
            return $this->fetch('login/index');
        }

    }

    public function check($code = '',$id='')
    {
        if(trim($code)!=''){
            $captcha = new Captcha();
            return $captcha->check($code,$id);
        }
        else
        {
            return false;
        }

    }


    public function verifycode()
    {
        $config = [
//            'fontSize' => 12,
            'length' => 4,
//            'imageH'=> 30,
//            'imageW'=> 80,
            'useNoise' => false,
            'useCurve' => false
        ];
        ob_clean();
        $captcha = new Captcha($config);
        $captcha->codeSet = '0123456789';
        return $captcha->entry("findpwd");
    }

    public function verifycode2()
    {
        $config = [
//            'fontSize' => 12,
            'length' => 4,
            'useCurve' => false,
//            'imageH'=> 30,
//            'imageW'=> 80,
            'useNoise' => false,
        ];
        ob_clean();
        $captcha = new Captcha($config);
        $captcha->codeSet = '0123456789';
        return $captcha->entry("bindphone");
    }


    /*
     *
     */
    public function verify()
    {
        $config = [
//            'fontSize' => 25,
            'length' => 4,
//            'imageH'=> 50,
//            'imageW'=> 300,
            'useNoise' => false,
            'useCurve' => false
//            'useCurve' => true,
//            // 是否画混淆曲线
//            'reset'    => true,
        ];
        ob_clean();
        $captcha = new Captcha($config);
        $captcha->codeSet = '0123456789';
        return $captcha->entry("login");
    }


    public function sendcodenoauth(){
        $mobile = input("mobile");
//        $code = input("captcha");


        if(empty($mobile)){
            return $this->msg(0,"参数不能为空",null);
        }

//        $captcha = new Captcha();
//        if(!$captcha->check($code,"findpwd")){
//            return $this->msg(0,"验证码输入错误，请重输",null);
//        }

        $hasdata = Db::name("proxy")->where("bind_mobile",$mobile)->count();
        if($hasdata>0){
            $sms =new model\Sms();
            $smsmodel = config('smsmodel');
            if($smsmodel=='self') {
                $code = rand(1000, 9999);
                $ret = $sms->checkandsend($mobile, $code, 'bindmobile', get_ip());
                if (!$ret) {
                    return $this->msg(0, "短信发送频率过快", null);
                }
            }
            else if($smsmodel=='hff'){
                $ret = $sms->send_sms($mobile);
                if($ret->code!=0){
                    return $this->msg(0, $ret->message, null);
                }
            }
            return $this->msg(1, "短信发送成功", null);
        }
        else
        {
            return $this->msg(0,"手机号码不存在",null);
        }
    }



    public function findpassword(){
        $username =input("userName");
        $newpwd =input("password");
        $phone =input("phone");
        $code =input("code");

        $sms =new model\Sms();
        $smsmodel = config('smsmodel');

        if($sms->validateSms($phone,$code)){
            $where =["username"=>$username,"bind_mobile"=>$phone];
            $proxy = DB::name("proxy")->where($where)->find();
            if(!empty($proxy))
            {
                 $salt =$proxy["salt"];
                 $password = md5($salt.$newpwd);
                 $data = array("password"=>$password);
                 Db::name("proxy")->where($where)->update($data);
                 return $this->msg(1,"密码重置成功，请重新登录",null);
            }
            else
            {
                return $this->msg(0,"账户名不存在或手机号未绑定，请重输",null);
            }

        }
        else{
            return $this->msg(0,"短信验证码验证错误，请重输",null);

        }

    }

    public function logout()
    {
       // session('admin_auth', null);
      //  session('admin_auth_sign', null);
       // cookie('aid', null);
       // cookie('signin_token', null);
        session('proxy',null);
        session('proxy_id',null);
        $this->redirect(url('Login/index'));
    }

    public function showmsg($code,$msg,$error,$hasMore,$data){
        $data = array(
            "code"=>$code,
            "msg"=>$msg,
            "error"=>$error,
            "hasMore"=>$hasMore,
            "data"=>$data
        );
        return  json($data);
    }

    public function msg($code,$msg,$data){
        return  $this->showmsg($code,$msg,'',false,$data);
    }

}
