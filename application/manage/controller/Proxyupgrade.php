<?php
namespace app\manage\controller;

use app\manage\controller\Base;
use think\Db;
use think\model;
use think\Paginator;

class Proxyupgrade extends Base{

    public function _initialize() {
        $roleid = session("RoleId");
        if($roleid!=1){
            $this->error('您没有权限','index/index');
        }
    }
    public function index(){
//        $proxy_id = session("proxy_id");
//        //查询满足要求的总的记录数
//        $list= Db::name("template")->order('id desc ')->paginate(3);
//        $this->assign('list',$list);
//        //赋值分页输出
//        //$this->assign("status",$status);
        return $this->fetch();
    }

    public function getIndex()
    {
//        $page  = intval(input('page')) > 0 ? intval(input('page')) : 1;
//        $limit = intval(input('limit')) > 0 ? intval(input('limit')) : 10;
        $proxy_id     = session("proxy_id");
        $list = Db::table('proxyupgrade')->select();
        //$count = Db::table('proxyupgrade')->count();
        return json(['code' => 0, 'data' => $list]);
    }

    public function edit()
    {
        $id = intval(input('id'));
        $profit = intval(input('profit'));
        $percent = intval(input('percent'));

        if ($id<=0 || $profit<=0 || $percent <=0) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }
        if (!Db::table('proxyupgrade')->where('id', $id)->find()) {
            return json(['code' => 2, 'msg' => '记录不存在']);
        }

        $update = Db::table('proxyupgrade')->where('id', $id)->update(['profit' => $profit, 'percent' => $percent]);
        if (!$update) {
            return json(['code' => 3, 'msg' => '更新失败']);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }
    public function add()
    {
        $profit = intval(input('add_profit'));
        $percent = intval(input('add_percent'));

        if ($profit<=0 || $percent <=0) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }

        if (Db::table('proxyupgrade')->where('percent', $percent)->count()) {
            return json(['code' => 2, 'msg' => '不可重复添加相同比例的数据']);
        }

        $update = Db::table('proxyupgrade')->insertGetId(['profit' => $profit, 'percent' => $percent]);
        if (!$update) {
            return json(['code' => 3, 'msg' => '新增失败']);
        }
        return json(['code' => 0, 'msg' => '新增成功']);
    }


    public function delete()
    {
        $id = intval(input('id'));
        if ($id<=0) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }
        $update = Db::table('proxyupgrade')->delete($id);
        if (!$update) {
            return json(['code' => 2, 'msg' => '删除失败']);
        }
        return json(['code' => 0, 'msg' => '删除成功']);
    }


    //总代理，一级代理比例设置
    public function rateinfo()
    {
        $rateData = Db::table('proxypercent')->find(1);
        $this->assign('rateData', $rateData);
        return $this->fetch();
    }

    public function setrate()
    {
        $general = intval(input('rate1'));
        $level1 = intval(input('rate2'));
        $lowest = intval(input('rate3'));

        if ($general<=0 || $level1<=0 || $lowest<=0) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }

        $update = Db::table('proxypercent')->where('id',1)->update(['general_rate' => $general, 'level1_rate'=>$level1,'lowest_rate' => $lowest]);
        if (!$update) {
            return json(['code' => 2, 'msg' => '更新失败']);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    //比率升级开关设置
    public function getOpen()
    {
        $open = Db::table('openupgrade')->find(1);
        $this->assign('open', $open['open']);
        return $this->fetch();
    }


    public function setOpen()
    {
        $open = intval(input('open'));
        Db::table('openupgrade')->where('id', 1)->update(['open' => $open]);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    //直属玩家充值返还比率设置
    public function getBack()
    {
        $rateData = Db::table('proxypercent')->find(1);
        $this->assign('rateData', $rateData);
        $this->assign('open', $rateData['open_back']);

        return $this->fetch();
    }

    public function setBack()
    {
        $back = intval(input('back'));

        if ($back<=0 || $back >=100) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }

        $update = Db::table('proxypercent')->where('id',1)->update(['back_rate' => $back]);
        if (!$update) {
            return json(['code' => 2, 'msg' => '更新失败']);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    //开启关闭直属玩家充值返还比率
    public function setOpenBack()
    {
        $back = intval(input('open'));

        if (!in_array($back, [0,1])) {
            return json(['code' => 1, 'msg' => '输入数据有误']);
        }

        $update = Db::table('proxypercent')->where('id',1)->update(['open_back' => $back]);
        if (!$update) {
            return json(['code' => 2, 'msg' => '更新失败']);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }

}