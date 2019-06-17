<?php
/**
 * 获取玩家游戏数据
 */

namespace app\command;

use app\admin\model\Playergame;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;

class GetUsergame extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('getUsergame')->setDefinition([                           //Option 和 Argument都可以有多个, Argument的读取和定义的顺序有关.请注意
            new Option('option', 'o', Option::VALUE_OPTIONAL, "命令option选项"),
            //使用方式  php think hello  --option test 或 -o test
            new Argument('test',Argument::OPTIONAL,"test参数"),
            //使用方式    php think hello  test1 (将输入的第一个参数test1赋值给定义的第一个Argument参数test)
            //...
        ])->setDescription('获取第三方数据，存入数据库');
        // 设置参数

    }

    protected function execute(Input $input, Output $output)
    {
//        $planlogModel = new Planlog();
//        $today        = date('Ymd');
//        $planId       = $planlogModel->add(
//            [
//                'plan'       => 'getUsergame',
//                'day'        => $today,
//                'status'     => 0,
//                'inserttime' => date('Y-m-d H:i:s')
//            ]
//        );
        set_time_limit(0);
        save_log('apidata/getUsergame', "start ");
        //获取代理列表
        $today      = date('Ymd');
        $pgame = new Playergame();
        $proxyList  = Db::name("proxy")->where("islock","0")->select();
        $proxyList  = array_column($proxyList, 'code');
        $insertTime = date('YmdHis');
        foreach ($proxyList as $proxy) {
            //循环获取游戏数据
            $info = $pgame->getUsergame($proxy);
            if ($info->code != 0) {
                $output->writeln('proxy' . $proxy . ',code:' . $info->code . ',msg:' . $info->message);
                continue;
            }
            if (!$info->data) {
                //save_log('apidata/getUsergame', "proxyId:{$proxy},handlemsg:nodata");
                $output->writeln('code:' . $info->code . ',msg:' . $info->message . 'data:nodata');
                continue;
            }

            $insertThirdData      = $insertData = [];
            $addNum  = $updateNum = 0;


            Db::startTrans();

            try {
                foreach ($info->data as $data) {
                    $row = Db::name("thirdplayergame")->where(['userid' =>$data->userid, 'roomname' => $data->roomname ])->find();
                    //$row = $thirdplayergameModel->getRow(['userid' => $data->userid, 'roomname' => $data->roomname]);
                    if (!$row) {
                        $addNum++;
                        Db::name("thirdplayergame")->insertGetId([
                            'userid'      => $data->userid,
                            'addtime'     => $data->addtime,
                            'changemoney' => $data->changemoney,
                            'nickname'    => $data->nickname,
                            'roomname'    => $data->roomname,
                            'inserttime'  => $insertTime,
                            'updatetime'  => $insertTime
                        ]);
                        Db::name("playergame")->insertGetId([
                            'userid'      => $data->userid,
                            'addtime'     => $data->addtime,
                            'changemoney' => $data->changemoney / 1000,
                            'nickname'    => $data->nickname,
                            'roomname'    => $data->roomname,
                            'inserttime'  => $insertTime,
                            'proxy_id'    => $proxy,
                            'updatetime'  => $insertTime
                        ]);

                    } else {
                        if ($row['changemoney'] != $data->changemoney) {
                            $updateNum++;
                            Db::name("thirdplayergame")->where('id',$row['id'])->update(['changemoney' => $data->changemoney, 'updatetime' => $insertTime]);
                            Db::name("playergame")->where(['userid' => $data->userid,'roomname' => $data->roomname])->update(['changemoney' => $data->changemoney / 1000,'updatetime'  => $insertTime]);
                        }
                    }
                }
                Db::commit();
                //save_log('apidata/getUsergame', "proxyid:".$proxy.",recordnum:" . count($info->data) . ",addnum:" . $addNum ."updatenum:".$updateNum. "handlemsg:insertsuccess");
            } catch(\Exception $e) {
                Db::rollback();
                //save_log('apidata/getUsergame', "proxyid:".$proxy."handlemsg:" . $e->getMessage());
                $output->writeln('code:500,msg:' . $e->getMessage() . 'data:insertfail');
            }
        }
        //$planlogModel->updateById($planId, ['updatetime' => date('Y-m-d H:i:s'), 'status' => 1]);
        save_log('apidata/getUsergame', "end ");
    }
}