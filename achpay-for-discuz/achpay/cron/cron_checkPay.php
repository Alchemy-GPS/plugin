<?php

//cronname:AchpayCheckStatus
//week:-1
//day:-1
//hour:-1
//minute:0,5,10,15,20,25,30,35,40,45,50,55

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
//引入函数
require_once(DISCUZ_ROOT . './source/plugin/achpay/require/function.php');
//所有设置的数据
loadcache('plugin');
$config = $_G['cache']['plugin']['achpay'];

//1.查询所有数据库中未支付的订单
$persons = DB::fetch_all("SELECT orderno,logid FROM %t WHERE status<>1 and paymethod=%s", array('achpay_log','person'));
//echo '<pre>';
//var_dump($persons);exit;
//echo '</pre>';
//个人收款主动查询
if(!empty($persons)){
    foreach ($persons as $k=>$v){
//准备所需数据
        $data = [
            "orderApplyNo"=>$v['orderno'],
            "merchantId"=>$config['person']
        ];
        $data['sign'] = achpay_get_sign($data,DISCUZ_ROOT . 'source/plugin/achpay/keys/pkey.txt');
//    发送post请求
        $res=json_decode(achpay_http_post('http://13.250.21.97:9190/personalCollection/third/order/query',$data));
//    如果返回结果状态为1
//    var_dump($data);exit;
        if($res->data->status==1){
//修改订单为已完成
            $logid = DB::result_first("SELECT logid FROM %t WHERE orderno=%s AND status<>1", array('achpay_log', trim($res->data->orderApplyNo)));
            achpay_finish($logid);
        }
    }
}

$cyrpts = DB::fetch_all("SELECT orderno,logid FROM %t WHERE status<>1 and paymethod=%s", array('achpay_log','cyrpt'));
//var_dump($cyrpts);exit;


if(!empty($cyrpts)){
    foreach ($cyrpts as $k=>$v){
//准备所需数据
        $checkdata= array(
            "orderId"=>$v['orderno'],
            "merchantId"=>$config['cyrpt'],
            "apiVersion"=>"1.0"
        );
        $checkdata['sign'] = achpay_get_sign($checkdata,DISCUZ_ROOT . 'source/plugin/achpay/keys/pkey.txt');
//            var_dump($checkdata);exit;

//    发送post请求
        $cyrptres=json_decode(achpay_http_post('http://test.alchemy.foundation/gateway/query/order/status',$checkdata));
//        var_dump($cyrptres);exit;
        if($cyrptres->orderStatus=='SUCCESS'||$res->orderStatus=='MSUCCESS'){
            $logid = DB::result_first("SELECT logid FROM %t WHERE orderno=%s AND status<>1", array('achpay_log', trim($v['orderno'])));

//如果订单号存在并且金额相同
            if ($logid) {
                achpay_finish($logid);
            }
        }

    }
}


?>