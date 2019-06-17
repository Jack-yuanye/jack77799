<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
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
        if ($info['expiretime']<time()){
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
     * 首页banner列表
     *
     */
    public function banner(){
        $where =[];
        $where['positiondata'] ='平台首页';
        $list = Db::name('cparty_slide')
                        ->where($where)
                        ->field('tilpicfile,id')
                        ->order('priority desc,id desc')
                        ->limit(5)
                        ->select();
        if (count($list)){
            $this->success('请求成功',$list,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }
    /**
     * 通知公告列表/通知公告标题
     *
     */
    public function notice(){
        $page = isset($_REQUEST['page'])?:1;
        $limit = isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $where =[];
        $where['branchid'] =$this->branch_id;
        $cparty_notice = Db::name('cparty_notice')
                                ->where($where)
                                ->order('priority desc,id desc')
                                ->limit($number,$limit)
                                ->select();
        if (count($cparty_notice)){
            $this->success('请求成功',$cparty_notice,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }
    /**
     * 通知公告详情
     *
     */
    public function notice_detail(){
        $notice_id = $_REQUEST['notice_id'];
        $where =[];
        $where['id'] =$notice_id;
        $cparty_notice = Db::name('cparty_notice')
                                ->where($where)
                                ->find();
        if ($cparty_notice){
            $this->success('请求成功',$cparty_notice,1000);
        }else{
            $this->error('请求失败','',-1);
        }
    }
    /**
     * 活动列表
     *
     */
    public function activity(){
        $page = isset($_REQUEST['page'])?:1;
        $limit = isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $where =[];
        $where['branchid'] =$this->branch_id;
        $cparty_activity = Db::name('cparty_activity')
                                ->where($where)
                                ->order('priority desc,id desc')
                                ->limit($number,$limit)
                                ->select();

        if (count($cparty_activity)){
            foreach ($cparty_activity as &$value){
                $value['branch_name'] = $this->branch_name;
            }
            $this->success('请求成功',$cparty_activity,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 活动详情
     *
     */
    public function activity_detail(){
        $activity_id = $_REQUEST['activity_id'];
        $where =[];
        $where['id'] =$activity_id;
        $cparty_activity = Db::name('cparty_activity')
                                    ->where($where)
                                    ->find();
        if (count($cparty_activity)){
            $cparty_activity['branch_name'] = $this->branch_name;
            $this->success('请求成功',$cparty_activity,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 课程列表
     *
     */
    public function course(){
        $page =isset($_REQUEST['page'])?:1;
        $limit =isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $cparty_edulesson = Db::name('cparty_edulesson')
                                    ->order('priority desc,id desc')
                                    ->limit($number,$limit)
                                    ->select();

        if (count($cparty_edulesson)){
            foreach ($cparty_edulesson as &$value){
                $value['stustatus'] = $value['stustatus']==1 ? '必修' : '选修' ;
                $value['status'] = $value['status']==1 ? '已结课' : '待续中' ;
            }
            $this->success('请求成功',$cparty_edulesson,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 课程详情
     *
     */
    public function course_detail(){
        $edulesson_id = $_REQUEST['edulesson_id'];
        $where =[];
        $where['id'] =$edulesson_id;
        $cparty_edulesson = Db::name('cparty_edulesson')
                                    ->where($where)
                                    ->find();

        if (count($cparty_edulesson)){
            $cparty_edulesson['stustatus'] = $cparty_edulesson['stustatus']==1 ? '必修' : '选修' ;
            $cparty_edulesson['status'] = $cparty_edulesson['status']==1 ? '已结课' : '待续中' ;
            $this->success('请求成功',$cparty_edulesson,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 动态列表
     *
     */
    public function article(){
        $page =isset($_REQUEST['page'])?:1;
        $limit =isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $where =[];
        $where['branchid'] =$this->branch_id;
        $where['status'] =1;
        $where['isslide'] =1;
        $cparty_article = Db::name('cparty_article')
                                    ->where($where)
                                    ->order('priority desc,id desc')
                                    ->limit($number,$limit)
                                    ->select();
        if (count($cparty_article)){
            foreach ($cparty_article as &$value){
                $value['branch_name'] = $this->branch_name;
            }
            $this->success('请求成功',$cparty_article,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 动态详情
     *
     */
    public function article_detail(){
        $article_id = $_REQUEST['article_id'];
        $where =[];
        $where['id'] =$article_id;
        $cparty_article = Db::name('cparty_article')
                                    ->where($where)
                                    ->find();

        if (count($cparty_article)){
            $cparty_article['branch_name'] = $this->branch_name;
            $this->success('请求成功',$cparty_article,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 积分列表
     *
     */
    public function integral(){
        $page =isset($_REQUEST['page'])?:1;
        $limit =isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $where =[];
        $where['branchid'] =$this->branch_id;
        $where['status'] =1;
        $cparty_user = Db::name('cparty_user')
                                    ->where($where)
                                    ->field('realname,integral')
                                    ->order('integral desc,id desc')
                                    ->limit($number,$limit)
                                    ->select();
        if (count($cparty_user)){

            $this->success('请求成功',$cparty_user,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    /**
     * 组织成员列表
     *
     */
    public function branch(){
        $page =isset($_REQUEST['page'])?:1;
        $limit =isset($_REQUEST['limit'])?:10;
        $number = ($page-1)*$limit;
        $where =[];
        $where['branchid'] =$this->branch_id;
//        $where['status'] =1;
        $cparty_user = Db::name('cparty_user')
                                    ->where($where)
                                    ->order('id desc')
                                    ->limit($number,$limit)
                                    ->select();

        if (count($cparty_user)){
            foreach ($cparty_user as &$value){
                switch ($value['ulevel']){
                    case 'jiji':
                        $value['ulevel_name'] = '入党积极分子';
                        break;
                    case 'fazhan':
                        $value['ulevel_name'] = '发展对象';
                        break;
                    case 'yubei':
                        $value['ulevel_name'] = '预备党员';
                        break;
                    case 'zhengshi':
                        $value['ulevel_name'] = '正式党员';
                        break;
                    default:
                        $value['ulevel_name'] = '';
                        break;
                }
                $value['status'] = $value['status']==1?'正常':($value['status']==2?'禁用':'审核');
            }

            $this->success('请求成功',$cparty_user,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }


}
