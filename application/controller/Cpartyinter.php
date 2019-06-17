<?php

namespace app\api\controller;

use app\common\controller\Api;

use think\Db;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use think\Config;


/**
 * 示例接口
 */
class Cpartyinter extends Api
{


    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


//    public  $user_id = '';
//    public  $branch_id = '';
//    public  $branch_name = '';
    public  $userid = '';
    public  $branchid = '';
    public  $branch_name = '';



    public function _initialize()
    {
        $token = $_REQUEST['token'];
//        $info = Db::name('cparty_token')->where("token='".$token."'")->find();
        $info = Db::name('cparty_token')->where("token='".$token."'")->order('id desc')->find();
//        ->order('id desc')
//        return $info;
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

            $this->userid = $info['user_id'];
            $this->branchid = $branchid;
            $this->branchname = $branch_name;
        }else{
            $this->error('该用户状态异常','',-1);
        }

        parent::_initialize();
    }




    //党建动态
    public function dynamic(){



//        $cparty_notice = Db::name('cparty_notice')
//            ->where('branchid='.$this->branch_id)
//            ->field('createtime,title,details')
//            ->order('id desc')
//            ->limit(1)
//            ->find();

        //        $title = Db::name('cparty_article')->where($where)->value('title');
//        print_r($title);
//
//        $where =[];
//        $where['branchid'] =$this->branchid;

//        $cparty_article=Db::name('cparty_article')
//
////            ->field('a.id,a.title,b.sid,b.price')
//            ->alias('a')
//            ->join('cparty_artmessage b','a.id = b.articleid')
//            ->where('a.branchid='.$this->branchid)
//
//            ->select(false);
//        print_r($cparty_article);exit;
        //文章信息
//        $cparty_article = Db::name('cparty_article')
//            ->where('branchid='.$this->branchid)
////            ->field('createtime,title,details')
//            ->order('id desc')
//            ->limit(5)
//            ->select();

//        $data['articleid'] =  isset($_REQUEST['articleid']) ? $_REQUEST['articleid'] : '';
//        $page = isset($_REQUEST['page'])?:1;
//        $limit = isset($_REQUEST['limit'])?:5;
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 5;
        $number = ($page-1)*$limit;
        $cparty_article = Db::name('cparty_article')
            ->where('branchid='.$this->branchid)
//            ->field('createtime,title,details')
            ->order('id desc')
            ->limit($number,$limit)
            ->select();
//        print_r($cparty_article);exit;



        //用户积分
        $cparty_user = Db::name('cparty_user')
            ->where('id='.$this->userid)
            ->value('integral');
        $cparty_user2['user_integral'] = $cparty_user;

        //组织名称
        $cparty_branch = Db::name('cparty_branch')
            ->where('id='.$this->branchid)
            ->value('name');
        $cparty_branch2['branch_name'] = $cparty_branch;


        //轮播图片和名称
        $cparty_artcate = Db::name('cparty_artcate')
            ->where('navnumber!=0')
            ->field('name,ciconimage')
            ->order('id desc')
            ->limit(5)
            ->select();
//

        $arr=array_merge($cparty_article,$cparty_artcate,$cparty_user2,$cparty_branch2);
//        print_r($arr);
        if (!empty($arr)){
            $this->success('请求成功',$arr,1000);
        }else{
            $this->error('暂无数据','',-1);
        }


    }

    //党建动态--所有动态
    public function dynamic_all(){

        //轮播图片和的名称
        $arr = Db::name('cparty_artcate')
//            ->where('navnumber!=0')
            ->field('id,name,ciconimage')
            ->order('id desc')
            ->select();
        if (!empty($arr)){
            $this->success('请求成功',$arr,1000);
        }else{
            $this->error('暂无数据','',-1);
        }
    }

    //党建动态--刷新积分
    public function dynamic_integral(){

        $jifen_step = Config::get('myconfig')['jifen'];
//        print_r(Config::get('myconfig')['jifen']);exit;
        //积分
        $integra = Db::name('cparty_user')
            ->where('id='.$this->userid)
            ->value('integral');
        //刷新积分
        $data=[];
//        $data['integral'] = $integra+5;
        $data['integral'] = $integra+$jifen_step;
        $info = Db::name('cparty_user')
            ->where('id='.$this->userid)
            ->update($data);
        if ($info){
            $this->success('刷新积分成功',$info,1000);
        }else{
            $this->error('刷新积分失败','',-1);
        }
    }

    //党建动态--评论
    public function dynamic_comment(){
        $articleid='';
        if(isset($_REQUEST['articleid'])) {
            $articleid=$_REQUEST['articleid'];
        }

//            $cparty_article=Db::name('cparty_article')
//
//            ->field('a.id,a.title,b.details,b.picallimages,b.id as d')
//            ->alias('a')
//            ->join('cparty_artmessage b','a.id = b.articleid')
//            ->where('a.branchid='.$this->branchid)
//
//            ->select();
//
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 5;
        $number = ($page-1)*$limit;
        if(!empty($articleid)){
            //文章评论 关联查找对应的用户名

            $cparty_artmessage_user=Db::name('cparty_user')

                ->field('a.id,a.realname,b.details,b.picallimages,b.createtime,b.id as d')
                ->alias('a')
                ->join('cparty_artmessage b','a.id = b.userid')
                ->where('b.articleid='.$articleid)
                ->limit($number,$limit)
                ->select();
//            print_r($cparty_artmessage_user);exit;
//
//            $cparty_artmessage = Db::name('cparty_artmessage')
//                ->where('articleid='.$articleid)
////            ->field('createtime,title,details')
//                ->order('id desc')
////                ->limit(5)
//                ->select();


            if (!empty($cparty_artmessage_user)){
                $this->success('请求成功',$cparty_artmessage_user,1000);
            }else{
                $this->error('请求失败1','',-1);
            }
        }else{
            $this->error('请求失败','',-1);
        }

    }


    //党建动态--提交评论
    public function dynamic_subcomment(){
//        return 4;

        $data=[];
        $data['articleid'] =  isset($_REQUEST['articleid']) ? $_REQUEST['articleid'] : '';
        $data['details'] =  isset($_REQUEST['details']) ? $_REQUEST['details'] : '';
        $data['picallimages'] =  isset($_REQUEST['picallimages']) ? $_REQUEST['picallimages'] : '';
        $time=time();
        $data['createtime'] = $time ;
        $data['userid'] =$this->userid;

        //用户积分
        $res =  Db::name('cparty_artmessage')->insert($data);
        if($res>0){
            $this->success('请求成功','插入数据成功',1000);
        }else{
            $this->error('请求失败','',-1);
        }
//        http://localhost/zhdj/smart_party_building__cms/public/api/cpartyinter/dynamic_subcomment?token=123&articleid=2&details=sfdfd&picallimages=ujiu

    }



    //课程管理
    public function course(){

        //轮播图片和名称
        $cparty_educate = Db::name('cparty_educate')
            ->where('navnumber!=0')
            ->field('name as lunboname,ciconimage')
            ->order('id desc')
            ->limit(5)
            ->select();

        //所有课程 关联查找对应的分类名
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 5;
        $number = ($page-1)*$limit;
        $cparty_educate_edulesson=Db::name('cparty_educate')

            ->field('a.id,a.name,b.details,b.title,b.tilpicimage,b.details,b.tilpicimage,b.integral,b.stustatus,b.createtime,b.id as lesid')
            ->alias('a')
            ->join('cparty_edulesson b','a.id = b.cateid')
            ->limit($number,$limit)
            ->select();
        if(!empty($cparty_educate_edulesson)){
            foreach ($cparty_educate_edulesson as $key => $value){
                $qingkuan=$this->course_learn($cparty_educate_edulesson[$key]['lesid']);
                if($qingkuan=='完成'){
                    $cparty_educate_edulesson[$key]['qingkung']='完成';
                }else if($qingkuan=='正在学习'){
                    $cparty_educate_edulesson[$key]['qingkung']='正在学习';
                }else{
                    $cparty_educate_edulesson[$key]['qingkung']='未学习';
                }

            }
        }else{
            $this->error('暂无数据','',-1);
        }




        $arr=array_merge($cparty_educate_edulesson,$cparty_educate);
        if (!empty($arr)){
            $this->success('请求成功',$arr,1000);
//            $this->success('请求成功',$cparty_educate_edulesson,1000);
        }else{
            $this->error('暂无数据','',-1);
        }


    }

    //课程管理--个人学习情况
    public function course_learn($lessonid){
//    public function course_learn(){
        $where =[];
        $where['userid'] =$this->userid;
//        $where['lessonid'] =21;
        $where['lessonid'] =$lessonid;
        $cparty_edulog = Db::name('cparty_edulog')
            ->where($where)
            ->field('lessonid')
//            ->group('lessonid')
            ->select();
//        print_r($cparty_edulog);exit;

        if(!empty($cparty_edulog)){
            foreach ($cparty_edulog as $value){
                //该课程底下的章节总数
                $where1=[];
                $where1['lessonid']=$value['lessonid'];
                $count1 = Db::name('cparty_educhapter')->where($where1)->count();
                $count2 = Db::name('cparty_edulog')->where($where1)->order('createtime asc')->select();
                if (count($count2) == $count1){
                    $flag =0;
                    foreach ($count2 as $value2){
                        if($value2['status']==2){
                            $flag++;
                        }

                    }
                    if($flag==$count1){
                        return '完成';
                    }else{
                        return '正在学习';
                    }
                }else{
                    return '正在学习';
                }
            }

        }else{
            return '未学习';
        }


    }


    //课程管理--搜索
    public function course_search(){
        $key =  isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
//        $where[] = ['id','like',"%".$key."%"];
//        http://localhost/zhdj/smart_party_building__cms/public/api/cpartyinter/course_search?token=123&key=%E4%BA%91%E8%84%89
        //所有课程 关联查找对应的分类名
        $cparty_educate_edulesson=Db::name('cparty_educate')

            ->field('a.id,a.name,b.details,b.title,b.tilpicimage,b.details,b.tilpicimage,b.integral,b.stustatus,b.createtime,b.id as lesid')
            ->alias('a')
            ->join('cparty_edulesson b','a.id = b.cateid')
//            ->where($where)
            ->where('b.title','like','%'.$key.'%')
            ->whereOr('b.details','like','%'.$key.'%')
            ->whereOr('b.createtime','like','%'.$key.'%')
            ->whereOr('b.createtime','like','%'.$key.'%')
            ->whereOr('a.name','like','%'.$key.'%')
            ->select();
//        print_r($cparty_educate_edulesson);exit;
        if(!empty($cparty_educate_edulesson)){
            foreach ($cparty_educate_edulesson as $key => $value){
                $qingkuan=$this->course_learn($cparty_educate_edulesson[$key]['lesid']);
                if($qingkuan=='完成'){
                    $cparty_educate_edulesson[$key]['qingkung']='完成';
                }else if($qingkuan=='正在学习'){
                    $cparty_educate_edulesson[$key]['qingkung']='正在学习';
                }else{
                    $cparty_educate_edulesson[$key]['qingkung']='未学习';
                }

            }
        }else{
            $this->error('暂无数据','',-1);
        }

        $arr=array_merge($cparty_educate_edulesson);
        if (!empty($arr)){
            $this->success('请求成功',$arr,1000);
//            $this->success('请求成功',$cparty_educate_edulesson,1000);
        }else{
            $this->error('暂无数据','',-1);
        }

    }


    //课程管理--详情

    public  function course_details(){
        $lessonid='';
        if(isset($_REQUEST['lessonid'])) {
            $lessonid=$_REQUEST['lessonid'];
        }
//        $data=[];
//        $data['articleid'] =  isset($_REQUEST['articleid']) ? $_REQUEST['articleid'] : '';

        //个人情况
        $person=[];
        $qingkuan=$this->course_learn($lessonid);
        $person['qingkuan']=$qingkuan;
//        print_r($person);exit;
        //统计完成情况
        $sum=[];
        $cparty_edulog = Db::name('cparty_edulog')
//            ->where($where)
            ->field('userid')
            ->group('userid')
            ->select();
        if(!empty($cparty_edulog)){
            $i=0;
            foreach ($cparty_edulog as $key => $value){
                $res=$this->course_sum($lessonid,$value['userid']);
//                $res=$this->course_sum($lessonid,7);

                if($res=='完成'){
                    $i++;
                }

            }
            $sum['sum']=$i;

        }


        if(!empty($lessonid)){
            $cparty_edulesson = Db::name('cparty_edulesson')->where('id='.$lessonid)->select();

            if (!empty($cparty_edulesson)){

                    $cparty_educhapter = Db::name('cparty_educhapter')->where('lessonid='.$lessonid)->select();
                //如果有章节继续查找章节表
                    if (!empty($cparty_educhapter)){
                        $arr=array_merge($cparty_edulesson,$cparty_educhapter,$person,$sum);
                        $this->success('请求成功',$arr,1000);
                    }
                    //该课程下没有章节
                    else{
                        $arr=array_merge($cparty_edulesson,$person,$sum);
                        $this->success('请求成功',$arr,1000);
                    }



            }else{
                $this->error('请求失败','',-1);
            }
        }else{
            $this->error('请求失败','',-1);
        }

    }

    //课程管理--统计课程完成人数
    public function course_sum($lessonid,$userid){
//    public function course_sum(){
//    public function course_learn(){
        $where =[];
//        $where['userid'] =$this->userid;
        $where['userid'] =$userid;
//        $where['userid'] =7;
//        $where['lessonid'] =21;
        $where['lessonid'] =$lessonid;
//        $where['lessonid'] =21;
        $cparty_edulog = Db::name('cparty_edulog')
            ->where($where)
            ->field('lessonid')
//            ->group('lessonid')
            ->select();
//        print_r($cparty_edulog);exit;

        if(!empty($cparty_edulog)){
            foreach ($cparty_edulog as $value){
                //该课程底下的章节总数
                $where1=[];
                $where1['lessonid']=$value['lessonid'];
//
                $count1 = Db::name('cparty_educhapter')->where($where1)->count();
//                $count2 = Db::name('cparty_edulog')->where($where1)->order('createtime asc')->select();
                $count2 = Db::name('cparty_edulog')->where($where)->order('createtime asc')->select();
//                print_r(count($count2));exit;
                if (count($count2) == $count1){
                    $flag =0;
                    foreach ($count2 as $value2){
                        if($value2['status']==2){
                            $flag++;
                        }

                    }
                    if($flag==$count1){
                        return '完成';
                    }else{
                        return '正在学习1';
                    }
                }else{
                    return '正在学习';
                }
            }

        }else{
            return '未学习';
        }


    }


    //课程管理--评论
    public function course_comment(){
        $lessonid='';
        if(isset($_REQUEST['lessonid'])) {
            $lessonid=$_REQUEST['lessonid'];
        }

        if(!empty($lessonid)){
            //党员学习课程 关联查找对应的用户名
            $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
            $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 5;
            $number = ($page-1)*$limit;
            $cparty_edumessage_user=Db::name('cparty_user')

                ->field('a.id,a.realname,b.details,b.picallimage,b.createtime,b.id as edumessageid')
                ->alias('a')
                ->join('cparty_edumessage b','a.id = b.userid')
                ->where('b.lessonid='.$lessonid)
                ->limit($number,$limit)
                ->select();
            if (!empty($cparty_edumessage_user)){
                $this->success('请求成功',$cparty_edumessage_user,1000);
            }else{
                $this->error('请求失败1','',-1);
            }
        }else{
            $this->error('请求失败','',-1);
        }

    }


    //课程管理--提交评论
    public function course_subcomment(){
//        return 4;

        $data=[];
        $data['lessonid'] =  isset($_REQUEST['lessonid']) ? $_REQUEST['lessonid'] : '';
        $data['details'] =  isset($_REQUEST['details']) ? $_REQUEST['details'] : '';
        $data['picallimage'] =  isset($_REQUEST['picallimage']) ? $_REQUEST['picallimage'] : '';
        $time=time();
        $data['createtime'] = $time ;
        $data['userid'] = $this->userid ;

        //用户积分
        $res =  Db::name('cparty_edumessage')->insert($data);
        if($res>0){
            $this->success('请求成功','插入数据成功',1000);
        }else{
            $this->error('请求失败','',-1);
        }
//        http://localhost/zhdj/smart_party_building__cms/public/api/cpartyinter/course_subcomment?token=123&lessonid=2&details=sfdfd&picallimage=ujiu

    }

    //课程管理--更新党员学习课程情况
    public function course_update(){
        $data=[];
        $data['userid'] = $this->userid ;
        $data['lessonid'] =  isset($_REQUEST['lessonid']) ? $_REQUEST['lessonid'] : '';
        $data['chapterid'] =  isset($_REQUEST['chapterid']) ? $_REQUEST['chapterid'] : '';
        $data['stutime'] =  isset($_REQUEST['stutime']) ? $_REQUEST['stutime'] : '';
        $data['status'] =  isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $time=time();
        $data['createtime'] = $time ;


        //有章节
        if(!empty($data['chapterid'])){
            $cparty_edulog = Db::name('cparty_edulog')
                ->where('userid='.$this->userid)
                ->where('lessonid='.$data['lessonid'])
                ->where('chapterid='.$data['chapterid'])
                ->order('id desc')
                ->select();
            if(!empty($cparty_edulog)){
                $info = Db::name('cparty_edulog')
                    ->where('id='.$this->userid)
                    ->update($data);
                if ($info){
                    $this->success('请求成功',$info,1000);
                }else{
                    $this->error('请求失败','',-1);
                }
            }else{
                $res =  Db::name('cparty_edulog')->insert($data);
                if ($res>0){
                    $this->success('请求成功','插入数据成功',1000);
                }else{
                    $this->error('请求失败','',-1);
                }
            }
        }else{
            $cparty_edulog = Db::name('cparty_edulog')
                ->where('userid='.$this->userid)
                ->where('chapterid='.$data['chapterid'])
                ->order('id desc')
                ->select();
            if(!empty($cparty_edulog)){
                $info = Db::name('cparty_edulog')
                    ->where('id='.$this->userid)
                    ->update($data);
                if ($info){
                    $this->success('请求成功',$info,1000);
                }else{
                    $this->error('请求失败','',-1);
                }
            }else{
                $res =  Db::name('cparty_edulog')->insert($data);
                if ($res>0){
                    $this->success('请求成功','插入数据成功',1000);
                }else{
                    $this->error('请求失败','',-1);
                }
            }

        }

    }

    //组织活动
    public function activity(){
//        return 4;

        //文章信息
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 5;
        $number = ($page-1)*$limit;
        $cparty_activity = Db::name('cparty_activity')
            ->where('branchid='.$this->branchid)
//            ->field('createtime,title,details')
            ->order('id desc')
//            ->limit(5)
            ->limit($number,$limit)
            ->select();
        //组织名称
        $cparty_branch = Db::name('cparty_branch')
            ->where('id='.$this->branchid)
            ->value('name');
        $cparty_branch2['branch_name'] = $cparty_branch;


        $arr=array_merge($cparty_activity,$cparty_branch2);
//        print_r($arr);
        if (!empty($arr)){
            $this->success('请求成功',$arr,1000);
        }else{
            $this->error('暂无数据','',-1);
        }


    }

    //组织活动--详情  可能用不到看前端如何交互
    public function activity_details(){
        $activityid =  isset($_REQUEST['activityid']) ? $_REQUEST['activityid'] : '';

        $cparty_activity = Db::name('cparty_activity')
            ->where('id='.$activityid )
//            ->field('createtime,title,details')
            ->order('id desc')
//            ->limit(5)
            ->select();

        //已报名人数
        $count_activity = Db::name('cparty_actenroll')
            ->where('activityid='.$activityid )
            ->count();
//                print_r($count_activity);exit;
        $count_activity2['count'] = $count_activity;
        $arr=array_merge($cparty_activity,$count_activity2);


        if (!empty($cparty_activity)){
            $this->success('请求成功',$arr,1000);
        }else{
            $this->error('暂无数据','',-1);
        }

    }


    //组织活动--提交活动报名
    public function activity_signup(){
        $data=[];
        $data['activityid'] =  isset($_REQUEST['activityid']) ? $_REQUEST['activityid'] : '';
        $data['getval'] =  isset($_REQUEST['getval']) ? $_REQUEST['getval'] : '';
        $data['utype'] =  isset($_REQUEST['utype']) ? $_REQUEST['utype'] : '';
        $time=time();
        $data['createtime'] = $time ;
        $data['userid'] = $this->userid ;

        //用户积分
        $res =  Db::name('cparty_actenroll')->insert($data);
        if($res>0){
            $this->success('请求成功','插入数据成功',1000);
        }else{
            $this->error('请求失败','',-1);
        }
//        http://localhost/zhdj/smart_party_building__cms/public/api/cpartyinter/activity_signup?token=123&activityid=2&getval=10&utype=1&userid=1&time=6

    }

    //组织活动--提交评论
    public function activity_subcomment(){
//        return 4;

        $data=[];
        $data['activityid'] =  isset($_REQUEST['activityid']) ? $_REQUEST['activityid'] : '';
        $data['userid'] = $this->userid ;
        $data['details'] =  isset($_REQUEST['details']) ? $_REQUEST['details'] : '';
        $data['picallfile'] =  isset($_REQUEST['picallfile']) ? $_REQUEST['picallfile'] : '';
        $time=time();
        $data['createtime'] = $time ;



        //用户积分
        $res =  Db::name('cparty_actmessage')->insert($data);
        if($res>0){
            $this->success('请求成功','插入数据成功',1000);
        }else{
            $this->error('请求失败','',-1);
        }
//        http://localhost/zhdj/smart_party_building__cms/public/api/cpartyinter/activity_subcomment?token=123&activityid=2&detailes=fdfd&picallfile=fdfdf12

    }












}
