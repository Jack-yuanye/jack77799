<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/27
 * Time: 17:15
 */



namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\validate;

class Test extends Controller {

    public function test()
    {

        Db::name("proxy")->where("code",1)->update(["balance" =>Db::raw("balance+".'1000')]);
        $money = Db::name("proxy")->where("code", 1)->field('balance,historyin')->find();
        //金额log
        Db::name('moneylog')->insert([
            'type' => 0,
            'detail' => 2,
            'tax' => 0,
            'money' => 1000,
            'leftmoney' => $money['balance'],
            'historyin' => $money['historyin'],
            'proxy_id'  => 1,
            'createtime' => date("Y-m-d H:i:s"),
            'createday' => date("Ymd")
        ]);
        die;
        $data['order_number'] = 'GZ2322435445';
        var_dump(substr($data['order_number'], 0, 2));
        die;
        $a = file_get_contents("http://dey6kxqf.game2019.net/?d=Game&c=GamePayMent&a=updateOrderStatus&roleid=1000&status=SUCCESS&orderId=test123&thirdorderid=77777&amount=1000");
        var_dump($a);
        die;

//        $company = new \app\manage\model\Company();
//        $ret     = $company->getPlayer('WZ0001620');
//        var_dump($ret);
//        die;
//        $res = file_get_contents("http://wx.sacomg.com/api.php?proxyid=%60%B1%8A%60%60%60%60%60b");
//
//        var_dump($res);
//        $data = getTax('WZ0000032', 10000);
//        var_dump($data);
//        die;
        $a= 999999999999999;
        var_dump(sprintf('%.2f',$a));
        die;
    }
    public function index(){


        $proxy = Db::name("proxy")->select();

        foreach ($proxy as $key=>$p){
            $qrcode = '/public/upload/Qrcode/' . $p["code"] . '.png';
            $code =$p["code"];
            $arrTemplate = Db::name('template')->select();
            foreach($arrTemplate as $k=>$template){
                if (!empty($template)) {


                    $tmp = Db::name("user_template")->where(array("proxy_id"=>$code,"template_code"=>$template["template_code"]))->find();

                    if(empty($tmp)) {
                        $cfgname = $p['username'] . $template['template_code'];
                        $tagetimg = "." . $qrcode;// str_replace("/public/","./",$qrcode);
                        $bigimg = "." . $template["template_image"];//str_replace("/public/","./",$template["template_image"]);

                        $qrcode_url = "./public/upload/Qrcode/proxy_" . $code . $template['template_code'] . '.png';
                        $ret = combinePic($bigimg, $tagetimg, $template["x"], $template["y"], $qrcode_url);

                        $qrcode2 =  '/public/upload/Qrcode/proxy_' . $code . $template['template_code'] . '.png';

                        $template = array("proxy_id" => $code, "config_name" => $cfgname, "template_code" => $template['template_code'],
                            "template_name" => $template['template_name'], "qrcode" => $qrcode2, "image_url" => $qrcode2,
                            "down_url" => $template['distribute_url'] . urlencode(compile($code)), "descript" => '');
                        $status = Db::name("user_template")->insert($template);
                        echo  $code."生成ok<br/>";
                    }
                    else{

                        echo  $code."已经存在<br/>";
                    }

                    //echo  $code."生成ok<br/>";

                } else {
                    //return $this->msg(0, "模板不存在", null);
                    echo  $code."template不存在<br/>";
                }
            }



        }

//        $qrcode = '/public/upload/Qrcode/' . $proxy_code . '.png';
//


    }

}