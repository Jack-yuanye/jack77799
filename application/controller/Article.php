<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 示例接口
 */
class Article extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
//    protected $noNeedLogin = ['test', 'test1'];
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function test()
    {
        $this->success('返回成功', $this->request->param());
    }

    /**
     * 无需登录的接口
     *
     */

    public function test1()
    {
        if(isset($_GET['id'])){
            if($_GET['id']=='admin'){
                $article = new \app\admin\model\cparty\Article;
                $article=$article->select();
                $this->success('返回成功', ['action' => $article]);
            }else{
                return "参数不正确";
            }

        }else{
            return "请带入参数";
        }

    }
//对应前台评论按钮
    public function artmessage()
    {
        if(isset($_POST['articleid'])){
            $articleid=$_POST['articleid'];
            $userid=$_POST['userid'];
            $details=$_POST['details'];
            $picallimages=$_POST['picallimages'];
            $createtime=$_POST['createtime'];
            $artmessage = new \app\admin\model\cparty\Artmessage;
            $artmessage ->
                data(['articleid'=>$articleid,'userid'=> $userid,'userid'=> $userid,'details'=> $details,'picallimages'=> $picallimages,'createtime'=> $createtime])
                ->insert();


        }


    }
//    public function test1()
//    {
//        $this->success('返回成功', ['action' => 'test1']);
//    }

    /**
     * 需要登录的接口
     *
     */
    public function test2()
    {
        $this->success('返回成功', ['action' => 'test2']);
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }

}
