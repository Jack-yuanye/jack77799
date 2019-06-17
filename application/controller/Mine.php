<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 首页接口
 */
class Mine extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    public  $user_id = '';
    public  $branch_id = '';
    public  $branch_name = '';

    public function _initialize()
    {
        $token = $_REQUEST['token'];
        $info = Db::name('cparty_token')->where("token='".$token."'")->find();
        if (!$info){
            $this->error('token无效','',-1);
        }
        if ($info['expiretime']< time()){
            $this->error('token已过期','',-1);
        }
        //验证用户是否正常
        $status = Db::name('cparty_user')->where("id=".$info['user_id'])->value('status');
        if ($status == 1){

            $branchid = Db::name('cparty_user')->where('id='.$info['user_id'])->value('branchid');
            $branch_name = Db::name('cparty_branch')->where('id='.$branchid)->value('name');

            $this->user_id = $info['user_id'];
            $this->branch_id = $branchid;
            $this->branch_name = $branch_name;
        }else{
            $this->error('该用户状态异常','',-1);
        }

        parent::_initialize();
    }


    /**
     * 个人中心
     *
     */
    public function mine_info(){

        $where =[];
        $where['id'] =$this->user_id;
        $cparty_user = Db::name('cparty_user')
                                    ->where($where)
                                    ->field('realname,sex,ulevel,headimage,integral')
                                    ->find();

        if (count($cparty_user)){
            switch ($cparty_user['ulevel']){
                case 'jiji':
                    $cparty_user['ulevel_name'] = '入党积极分子';
                    break;
                case 'fazhan':
                    $cparty_user['ulevel_name'] = '发展对象';
                    break;
                case 'yubei':
                    $cparty_user['ulevel_name'] = '预备党员';
                    break;
                case 'zhengshi':
                    $cparty_user['ulevel_name'] = '正式党员';
                    break;
                default:
                    $cparty_user['ulevel_name'] = '';
                    break;
            }
            unset($cparty_user['ulevel']);
            $cparty_user['sex'] = $cparty_user['sex']==1?'男':'女';
            $cparty_user['branch_name'] = $this->branch_name;
            $cparty_user['branch_id'] = $this->branch_id;
            $cparty_user['about_tel'] = '1888888888888';

            $cparty_notice = Db::name('cparty_notice')
                                        ->where('branchid='.$this->branch_id)
                                        ->field('createtime,title,details')
                                        ->order('id desc')
                                        ->limit(1)
                                        ->find();
            $cparty_notice1['notice'] = $cparty_notice;
            $arr = array_merge($cparty_user,$cparty_notice1);
            $this->success('请求成功',$arr,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }
    /**
     * 积分记录
     *
     */
    public  function integral_log(){
        $where =[];
        $where['id'] =$this->user_id;
        $where['channel'] =['>',1];
        $cparty_integral = Db::name('cparty_integral')
                                    ->where($where)
                                    ->order('id desc')
                                    ->field('createtime,foreignid,channel,integral,remark')
                                    ->select();
        if (count($cparty_integral)){
            $this->success('请求成功',$cparty_integral,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }
    /**
     * 学习记录
     *
     */
    public  function learn_log(){
        $where =[];
        $where['userid'] =$this->user_id;
        $cparty_edulog = Db::name('cparty_edulog')
                                        ->where($where)
                                        ->field('lessonid')
                                        ->group('lessonid')
                                        ->select();
        if (count($cparty_edulog)){
            foreach ($cparty_edulog as &$value){
                //该课程底下的章节总数
                $where1=[];
                $where1['lessonid']=$value['lessonid'];
                //获取课程信息
                $less_info = Db::name('cparty_edulesson')->where('id='.$value['lessonid'])->field('stustatus,title')->find();
                $count1 = Db::name('cparty_educhapter')->where($where1)->count();
                $count2 = Db::name('cparty_edulog')->where($where1)->order('createtime asc')->select();

                $value['createtime'] = $count2[0]['createtime'];
                $value['lesson_title'] = $less_info['title'];
                $value['lesson_stustatus'] = $less_info['stustatus'] ==1?'必修':'选修';

                if (count($count2) == $count1){
                    //查询每个章节是否都完成
                    $flag =0;
                    foreach ($count2 as &$val){
                        if ($val['status'] == 2){
                            $flag++;
                        }
                    }
                    if ($flag == $count1){
                        //该课程  完成
                        $value['status'] = '完成';
                    }else{
                        //该课程  学习中
                        $value['status'] = '学习中';
                    }
                }else{
                    //学习中
                    $value['status'] = '学习中';
                }

            }
            $this->success('请求成功',$cparty_edulog,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }
    /**
     * 我的组织详情
     *
     */
    public function branch_us(){
        $where =[];
        $where['id'] = $_REQUEST['branch_id'];
        if ($where['id'] != $this->branch_id){
            $this->error('参数错误','',-1);
        }
        $cparty_branch = Db::name('cparty_branch')
                                        ->where($where)
                                        ->find();
        if (count($cparty_branch)){
            switch ($cparty_branch['blevel']){
                case 'danwei':
                    $cparty_branch['blevel_name'] = '单位';
                    break;
                case 'dangwei':
                    $cparty_branch['blevel_name'] = '党委';
                    break;
                case 'zongzhi':
                    $cparty_branch['blevel_name'] = '党总支';
                    break;
                case 'zhibu':
                    $cparty_branch['blevel_name'] = '党支部';
                    break;
                default:
                    $cparty_branch['blevel_name'] = '';
                    break;
            }
            unset($cparty_branch['blevel']);

            $this->success('请求成功',$cparty_branch,1000);
        }else{
            $this->error('暂无数据','',-1);
        }

    }

    /**
     * 信息列表
     *
     */
    public  function mineInfo(){
        $info = Db::name('cparty_user')
                        ->where('id='.$this->user_id)
                        ->field('idnumber,realname,mobile,sex,birthdata,origin,education,nation')
                        ->find();
        if (count($info)){
            $info['sex'] = $info['sex']==0 ? '男' : '女';
            $this->success('请求成功',$info,1000);
         }else{
            $this->error('暂无数据','',-1);
         }
    }
    /**
     * 修改信息列表
     *
     */
    public  function edit_mineInfo(){
        $data=[];
        $data['idnumber'] = isset($_REQUEST['idnumber'])?:'';
        $data['realname'] = isset($_REQUEST['realname'])?:'';
        $data['mobile'] = isset($_REQUEST['mobile'])?:'';
        $data['sex'] = isset($_REQUEST['sex'])?:'';
        $data['birthdata'] = isset($_REQUEST['birthdata'])?:NULL;
        $data['origin'] = isset($_REQUEST['origin'])?:'';
        $data['education'] = isset($_REQUEST['education'])?:'';
        $data['nation'] = isset($_REQUEST['nation'])?:'';
        $info = Db::name('cparty_user')
                        ->where('id='.$this->user_id)
                        ->update($data);
        if ($info){
            $this->success('请求成功',$info,1000);
        }else{
            $this->error('请求失败','',-1);
        }
    }
    /**
     * 解綁微信
     *
     */
    public function  del_bind_wx(){

        //解绑数据
        $data=[];
        $data['wxappopenid'] = '';
        $data['wxappnickname'] = '';
        $data['wxappheadimage'] = '';
        $data['headwximage'] = '';
        $data['openid'] = '';

        $info = Db::name('cparty_user')
                        ->where('id='.$this->user_id)
                        ->update($data);
        if ($info){

            $this->success('请求成功',$info,1000);
        }else{
            $this->error('请求失败','',-1);
        }

    }

}
