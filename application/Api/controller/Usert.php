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


class Usert extends Controller{
    public function test(){
        return 55;
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

//        $data=array(
//
//            "list"=>$checklist
//
//
//        );
//
//        $mydata[]=$data;


        return json(['status' => 'success','msg' => '成功！','stories'=>$list]);

        $this->assign("list", $list);
        $this->assign("gamerid", $gamerId);
        $this->assign("proxy", $proxy);
        $this->assign("starttime", $startime);
        $this->assign("endTime", $endTime);
        return $this->fetch();
    }
    






}