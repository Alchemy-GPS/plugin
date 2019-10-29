<?php
/**
 * 定时查单类
 * User: yutianqi
 * Date: 2019/3/4
 * Time: 10:44
 */
class ControllerExtensionPaymentAchContab extends Controller{
	/**
	 * 微信支付宝个人收款定时查单
	 */
	public function wechatandalipay()
	{
		$db = DB_PREFIX;
//		查询当天所有微信支付宝未支付订单
		$result =$this->db->query(
			"SELECT order_id,total FROM `{$db}order` WHERE 
			  to_days(date_added) = to_days(now()) AND 
(payment_code = 'achwechat' OR payment_code = 'achalipay') AND order_status_id = 0 ;");
//		如果不存在未支付订单
		if(!$result->num_rows){
			exit("ok");
		}
//		如果存在未支付订单
		try{
//			获取未支付订单数据
			$arr = $result->rows;
//			获取订单支付成功状态
			$success_status = $this->config->get('payment_achwechat_order_succeed_status_id');
			$appid = $this->config->get('payment_achwechat_appid');
			$order_prefix       = $this->config->get('payment_achwechat_order_prefix');
//			遍历执行查询订单状态
			foreach ($arr as $k=>$v){
				$data = [
					"orderApplyNo"=>$order_prefix.$v['order_id'],
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
					$this->db->query("update `{$db}order` set order_status_id='{$success_status}' where order_id = '{$v['order_id']}';");
					echo '订单号:'.$order_prefix.$v['order_id'].'支付成功';
				}
			}
		}catch(Exception $e){
			exit($e->getMessage());
		}
		exit;

	}

	public function achpay()
	{
		$db = DB_PREFIX;
//		查询当天所有微信支付宝未支付订单
		$result =$this->db->query(
			"SELECT order_id,total FROM `{$db}order` WHERE 
			  to_days(date_added) = to_days(now()) AND 
payment_code = 'achpay' AND order_status_id = 0 ;");
//		如果不存在未支付订单
		if(!$result->num_rows){
			exit("ok");
		}
//		如果存在未支付订单
		try{
//			获取未支付订单数据
			$arr = $result->rows;
//			获取订单支付成功状态
			$appid = $this->config->get('payment_achpay_appid');
			$order_prefix       = $this->config->get('payment_achpay_order_prefix');
			$success_status = $this->config->get('payment_achpay_order_succeed_status_id');
//			遍历执行查询订单状态
			foreach ($arr as $k=>$v){
				$data = [
					"orderId"=>$order_prefix.$v['order_id'],
					"merchantId"=>$appid,
					"apiVersion"=>"1.0"
				];
//				制作签名
				$data['sign'] = '';
				$res = $this->achpay_http_post('http://test.alchemy.foundation/gateway/query/order/status',$data);
				$res = json_decode($res,true);
				if($res['status'] == '404'){
					throw new Exception('支付接口返回404');
					break;
				}
				if($res['orderStatus']=='SUCCESS' || $res['orderStatus']=='MSUCCESS'){
					$this->db->query("update `{$db}order` set order_status_id='{$success_status}' where order_id = '{$v['order_id']}';");
					echo '订单号:'.$order_prefix.$v['order_id'].'支付成功';
				}
			}
		}catch(Exception $e){
			exit($e->getMessage());

		}
		exit;

	}

	/**
	 * post json 方法
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
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch,CURLOPT_URL, $url);
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