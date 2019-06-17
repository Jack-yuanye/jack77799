<?php

namespace app\api\controller;

use app\common\controller\Api;

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
            if(empty($res)){

                $res=$user->where('realname',$realname)->select();
                if(!empty($res)){
                    $this->error('请求失败','用户存在，用户民与密码不对应',-1);
                }else{
                    $this->error('请求失败','用户不存在',-1);
                }

            }else{
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

            }
        }else if(isset($_GET['name'])){
            $this->error('请输入电话号码','',-1);

        }else{
            $this->error('请输入用户名','',-1);
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
