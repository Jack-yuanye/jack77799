<?php

namespace app\api\controller;

use app\common\controller\Api;

use think\Db;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;


/**
 * 示例接口
 */
class Usercparty extends Api
{


    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public  $userid = '';




    public function _initialize()
    {


        parent::_initialize();
    }


    //登录 判断用户是否存在 可用
    public function isuser2($mobile,$realname){

        $user = new \app\admin\model\db\cparty\User;
//            $user=$user->field('id,branchid')->where('mobile',$mobile)->where('realname',$realname)->select();
//        $user=$user->field('id,branchid,status')->where('mobile',$mobile)->where('realname',$realname)->select();
        $user=$user->where('mobile',$mobile)->where('realname',$realname)->select();
        return $user;


    }

    public function isuser($mobile='',$realname=''){

        $user = new \app\admin\model\db\cparty\User;
        if($realname!=''&&$mobile!=''){
            $user=$user->where('mobile',$mobile)->where('realname',$realname)->select();
        }else if($mobile==''){
            $user=$user->where('realname',$realname)->select();
        }else{
            $user=$user->where('mobile',$mobile)->select();
        }
//        $user=$user->where('mobile',$mobile)->where('realname',$realname)->select();
        return $user;


    }


//账号绑定登录中的登录 可用
    public function login2()
    {
        //用户存在  登录
        $realname=0;
        $mobile=0;
        if(isset($_GET['name'])){
            $realname=$_GET['name'];
        }
        if(isset($_GET['mobile'])){
            $mobile=$_GET['mobile'];
        }
        $res=$this->isuser($mobile,$realname);
//        var_dump($res);exit;
//        echo $res[0]['id'];exit;

        //用户存在
        if(!empty($res)){

            //检验用户状态是否正常
            if($res[0]['status']==1){
                $usercparty = new \app\admin\model\cparty\Usercparty;
                $usercparty_uid=$usercparty->where('user_id',$res[0]['id'])->select();

                //每一次登录 都插入新的token

                $token = Random::uuid();
                $createtime=time();
                //过期时间设为1小时后
                $expiretime=$createtime+3600;
                $data =['token'=>$token,'user_id'=> $res[0]['id'],'createtime'=> $createtime,'expiretime'=>$expiretime];
                $user_res=$usercparty ->insert($data);
                if($user_res>0){

                    $this->success('返回成功写入数据成功', ['token' => $token]);
                }else{
                    $this->error('请求失败','写入数据失败',-1);
//                    $this->success('返回失败',0,0,0, ['action' =>  '插入失败']);
                }

            }else{
                $this->error('该用户状态异常','',-1);
            }


        }else{
            $this->error('请求失败','用户不存在',-1);

//            $this->success('返回失败2',0,0,0, ['action' =>  '用户不存在']);
        }


    }

    public function login()
    {
        //用户存在  登录
        $realname=0;
        $mobile=0;
        $res='';
        $user = new \app\admin\model\db\cparty\User;
//        $user=$user->where('mobile',$mobile)->where('realname',$realname)->select();
        if(isset($_GET['name'])&&isset($_GET['mobile'])){
            $realname=$_GET['name'];
            $mobile=$_GET['mobile'];
            $res=$user->where('mobile',$mobile)->where('realname',$realname)->select();
//            $this->userid=33;
            if(empty($res)){

                $res=$user->where('realname',$realname)->select();
                if(!empty($res)){
                    $this->error('请求失败','用户存在，用户民与手机号不对应',2);
                }else{
                    $this->error('请求失败','用户不存在',-1);
                }

            }else{
                //检验用户状态是否正常
                if($res[0]['status']==1){
//                    $userid=$res[0]['id'];
                    $this->userid=$res[0]['id'];
                    $_SESSION["usrid2"] =$res[0]['id'];
//                    echo $this->userid;exit;

                    $callback = urlencode("http://fax2s2.natappfree.cc");
//                    print_r($callback);exit;
                    $appid = 'wx07e5974da3426441';
//                    header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3A%2F%2Fhkdtsr.natappfree.cc%2Fweixin/oauth.php&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');
//                    header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3A%2F%2Fhkdtsr.natappfree.cc/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');








                    $usercparty = new \app\admin\model\cparty\Usercparty;
                    $usercparty_uid=$usercparty->where('user_id',$res[0]['id'])->select();

                    $cparty_token33 = Db::name('cparty_token')->where('user_id='.$this->userid)->order('id desc')->limit(1)->find();
//                    print_r($this->userid);exit;

                    //每一次登录 都插入新的token

                    $token = Random::uuid();
                    $createtime=time();
                    $diff=$cparty_token33['expiretime']-$createtime;
//                    print_r($cparty_token33['expiretime']);
//                    print_r($createtime);
//                    print_r($diff);
//                    exit;
//                    if($cparty_token33['expiretime']-$createtime>3600){
                    //token 没有过期
                    if($cparty_token33['expiretime']-$createtime>3600){

                        if($cparty_token33['islogin']==1){
//                            $this->error('已经登录，请勿重新登录','',-1);
                            $this->error('已经登录，请勿重新登录',['token' => $cparty_token33['token']],2);
                            exit;
                        }



                    }else{
//                        if($cparty_token33['islogin']==1){
////                            $this->error('已经登录，请勿重新登录','',-1);
//                            $this->error('已经登录，请勿重新登录',['token' => $cparty_token33['token']],2);
//                            exit;
//                        }
                        //过期时间设为1小时后
                        $expiretime=$createtime+3600;
                        $data =['token'=>$token,'user_id'=> $res[0]['id'],'createtime'=> $createtime,'expiretime'=>$expiretime];
                        $user_res=$usercparty ->insert($data);
                        if($user_res>0){
//                        header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3A%2F%2Fhkdtsr.natappfree.cc/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');
//                        header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$callback.'/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');
                            header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$callback.'/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state='.$this->userid.'&connect_redirect=1#wechat_redirect');

//                        print_r($userinfo['openid']);
                            //有exit才会弹出微信返回的openid 身份等信息  没有的话可以插入数据库
//                        $this->success('微信绑定登录成功', ['token' => $token]);
                            exit;

//                        $this->success('返回成功写入数据成功', ['token' => $token]);
                            $this->success('微信绑定登录成功', ['token' => $token]);
                        }else{
                            $this->error('请求失败','写入数据失败',-1);
//                    $this->success('返回失败',0,0,0, ['action' =>  '插入失败']);
                        }

                    }
//                    //过期时间设为1小时后
//                    $expiretime=$createtime+3600;
//                    $data =['token'=>$token,'user_id'=> $res[0]['id'],'createtime'=> $createtime,'expiretime'=>$expiretime];
//                    $user_res=$usercparty ->insert($data);
//                    if($user_res>0){
////                        header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3A%2F%2Fhkdtsr.natappfree.cc/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');
////                        header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$callback.'/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');
//                        header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$callback.'/zhdj/smart_party_building__cms/public/api/usercparty/blindwx&response_type=code&scope=snsapi_userinfo&state='.$this->userid.'&connect_redirect=1#wechat_redirect');
//
////                        print_r($userinfo['openid']);
//                        //有exit才会弹出微信返回的openid 身份等信息  没有的话可以插入数据库
////                        $this->success('微信绑定登录成功', ['token' => $token]);
//                        exit;
//
////                        $this->success('返回成功写入数据成功', ['token' => $token]);
//                        $this->success('微信绑定登录成功', ['token' => $token]);
//                    }else{
//                        $this->error('请求失败','写入数据失败',-1);
////                    $this->success('返回失败',0,0,0, ['action' =>  '插入失败']);
//                    }

                }else{
                    $this->error('该用户状态异常','',-1);
                }

            }
        }else if(isset($_GET['name'])){
            $this->error('请输入电话号码','',-1);

        }else{
            $this->error('请输入用户名','',-1);
        }



    }



    public function blindwx(){
//        echo 3;exit;

        // $username =  isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
        // $mobile =  isset($_REQUEST['mobile']) ? $_REQUEST['mobile'] : '';
        $code=$_GET['code'];
        // echo $code;exit;
        //用state作为where条件里的内容
        $state=$_GET['state'];
//        print_r($state);exit;
//换成自己的接口信息
        $appid="wx07e5974da3426441";$appsecret = "c34e49ddd5d29cdd8b521a3d1f7c4286";
        if (empty($code)) $this->error('授权失败');
// https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
        $tokenurl="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
// $tokenurl="https://api.weixin.qq.com/sns/oauth2/accesstoken?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
// echo $tokenurl;

        $token=json_decode(file_get_contents($tokenurl));




        // if (null !== $token−>errcode)echo"<h1>错误：</h1>".$token−>errcode;echo"<br/><h2>错误信息：</h2>".$token−>errmsg;exit;
        $access_token_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appid.'&grant_type=refresh_token&refresh_token='.$token->refresh_token;
        //转成对象
        $accesstoken=json_decode(file_get_contents($access_token_url));
        // if (null !== $accesstoken−>errcode)echo′<h1>错误：</h1>′.$accesstoken−>errcode;echo′<br/><h2>错误信息：</h2>′.$accesstoken−>errmsg;exit;
        // $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token->access_token.'&openid='.$access_token->openid.'&lang=zh_CN';
        // //转成对象
        // $userinfo=json_decode(file_get_contents(user_info_url));
        // if (null !== $userinfo−>errcode)echo′<h1>错误：</h1>′.$userinfo−>errcode;echo′<br/><h2>错误信息：</h2>′.$userinfo−>errmsg;exit;//打印用户信息echo′<pre>′;printr(user_info);
        // echo '</pre>';
        // if (null !== $accesstoken−>errcode)echo"<h1>错误：</h1>".$accesstoken−>errcode;echo"<br/><h2>错误信息：</h2>".$accesstoken−>errmsg;exit;
        // $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token->access_token.'&openid='.$access_token->openid.'&lang=zh_CN';
        $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$accesstoken->access_token.'&openid='.$accesstoken->openid.'&lang=zh_CN';
        //转成对象
//        $userinfo=json_decode(file_get_contents($user_info_url));
        $userinfo=json_decode(file_get_contents($user_info_url),true);
//        print_r($userinfo);exit;
//        print_r($userinfo['openid']);
//        print_r($this->userid);exit;

        $wxappopenid = Db::name('cparty_user')->where('id='.$state)->order('id desc')->limit(1)->value('wxappopenid');
        $token = Db::name('cparty_token')->where('user_id='.$state)->order('id desc')->limit(1)->value('token');
        if($wxappopenid){
            $this->error('已经绑定微信，可以直接登录',['token' => $token],2);
//            $this->success('微信绑定登录成功', ['token' => $token]);
        }else{
            //绑定微信 操作本地用户表
            $data=[];
            $data['wxappopenid'] = $userinfo['openid'];
            $data['wxappnickname'] = $userinfo['nickname'];
            $data['wxappheadimage'] = $userinfo['headimgurl'];



            $info = Db::name('cparty_user')
                ->where('id='.$state)
                ->update($data);


            if ($info){

                $this->success('微信绑定登录成功', ['token' => $token]);

            }else{
                $this->error('请求失败33','',-1);
            }

        }





    }










    //    public function login()
//    {
//        //用户存在  登录
//        $realname=0;
//        $mobile=0;
//        if(isset($_GET['name'])){
//            $realname=$_GET['name'];
//        }
//        if(isset($_GET['mobile'])){
//            $mobile=$_GET['mobile'];
//        }
//        $res=$this->isuser($mobile,$realname);
////        echo $res[0]['id'];exit;
//
//        //用户存在
//        if(!empty($res)){
//            $usercparty = new \app\admin\model\cparty\Usercparty;
//            $usercparty_uid=$usercparty->where('user_id',$res[0]['id'])->select();
//
//            //第一次登录  插入token
//            if(empty($usercparty_uid)){
//                $token = Random::uuid();
//                $createtime=time();
//                //过期时间设为1小时后
//                $expiretime=$createtime+3;
//                $data =['token'=>$token,'user_id'=> $res[0]['id'],'createtime'=> $createtime,'expiretime'=>$expiretime];
//                $user_res=$usercparty ->insert($data);
//                if($user_res>0){
//                    $this->success('返回成功插入成功', ['action' => $token]);
//                }else{
//                    $this->success('返回失败',0,0,0, ['action' =>  '插入失败']);
//                }
//            }else{
//                $expiretime=$usercparty_uid['0']['expiretime'];
//                $createtime=time();
//                $expiretimenew=$createtime+30;
//                $token = Random::uuid();
//                $diff=$expiretime-$createtime;
//                //过期了跟新token >3600
//                $data =['token'=>$token,'createtime'=> $createtime,'expiretime'=>$expiretimenew];
//                if($diff>3){
//                   $upda= $usercparty->where('user_id',$res[0]['id'])  ->update( $data);
//                    if($upda==1){
////                        return $token;
//                        $this->success('返回成功更新', ['action' => $token]);
//                    }
//
//                }
//
//                $this->success('返回成功', ['action' =>  $usercparty_uid['0']['token']]);
//            }
//
//        }else{
//
//            $this->success('返回失败',0,0,0, ['action' =>  '用户不存在']);
//        }
//
//
//    }


}
