<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Class Alchemy_Payment_WC_Payment_Contab
 */
class Alchemy_Payment_WC_Payment_Contab{
	/**
	 * 个人收款定时查单
	 * url http://域名/?contab_type=crypt
	 */
	public function person()
	{
		if($_GET['contab_type']=='person'){
			global $wpdb;
			global $Alchemy_Payment_WC_Payment_Gateway;
			$res = $wpdb->get_results("select ID from ".$wpdb->prefix."posts where to_days(post_date) = to_days(now()) AND post_status='wc-pending'");
			if(empty($res)){
				exit('ok');
			}
			try{
//			获取未支付订单数据
				$arr = $res;
//			获取订单支付成功状态
				$appid = $Alchemy_Payment_WC_Payment_Gateway->get_option('appid');
				$order_prefix       = 'WORDPRESS';
//			遍历执行查询订单状态
				foreach ($arr as $k=>$v){
					$data = [
						"orderApplyNo"=>$order_prefix.$v->ID,
						"merchantId"=>$appid
					];
//				制作签名
					$data['sign'] = '';
					$res = $this->achpay_http_post('http://13.250.21.97:9190/personalCollection/third/order/query',$data);

					$res = json_decode($res,true);
					if($res['status'] == '404'){
						throw new Exception('支付接口返回404');
					}
					if($res['data']['status']==1 && $res['data']['amount'] == $v['total']){
						$order= wc_get_order($v);
//                修改订单为已完成
						$order->payment_complete();
					}
					exit('ok');
				}
			}catch(Exception $e){
				exit($e->getMessage());
			}
		}

		return;
	}

	/**
	 * 加密币收款定时查单
	 * url http://域名/?contab_type=person
	 */
	public function crypt()
	{
		if($_GET['contab_type']=='crypt'){
			global $wpdb;
			global $Alchemy_Payment_WC_Payment_Gateway;
			$res = $wpdb->get_results("select ID from ".$wpdb->prefix."posts where to_days(post_date) = to_days(now()) AND post_status='wc-pending'");
			try{
//			获取未支付订单数据
				$arr = $res;
//			获取订单支付成功状态
				$appid = $Alchemy_Payment_WC_Payment_Gateway->get_option('merchantId');;
				$order_prefix       = 'WORDPRESS';
//			遍历执行查询订单状态
				foreach ($arr as $k=>$v){
					$data = [
						"orderId"=>$order_prefix.$v->ID,
						"merchantId"=>$appid,
						"apiVersion"=>"1.0"
					];
//				制作签名
					$data['sign'] = '';
					$res = $this->achpay_http_post('http://test.alchemy.foundation/gateway/query/order/status',$data);
					$res = json_decode($res,true);
					if($res['returnCode']=='1005'){
						continue;
					}
					if($res['status'] == '404'){
						throw new Exception('支付接口返回404');
						break;
					}
					if($res['orderStatus']=='SUCCESS' || $res['orderStatus']=='MSUCCESS'){
						$order= wc_get_order($v);
//                修改订单为已完成
						$order->payment_complete();
						echo $v->ID."支付成功\n";
					}
				}
				exit('ok');
			}catch(Exception $e){
				exit($e->getMessage());

			}
		}

		return;
	}

	/**
	 * http post请求方法
	 * @param $url
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	private function achpay_http_post($url,$data){
		if(!function_exists('curl_init')){
			throw new Exception('php未安装curl组件',500);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 0.5);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_REFERER,get_option('siteurl'));
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8'
			)
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
}