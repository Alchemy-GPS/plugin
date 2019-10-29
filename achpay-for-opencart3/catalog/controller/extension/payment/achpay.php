<?php

class ControllerExtensionPaymentachpay extends Controller
{

    private function _redirect($url, $status = 302) {
        header('location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
        exit();
    }

    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/achpay');
        $this->load->language('extension/payment/achpay');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        $data['text_credit_card'] = $this->language->get('text_credit_card');
        $data['text_wait'] = $this->language->get('text_wait');
        
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');
//        获取加密币支付接口列表
		$params  = [
			'merchantId'=>'6e476e074f6546ed8d3b5e8d0579a6b5',
			'appVersion'=>'1.0',
			'sign'=>''
		];
//		获取加密币列表
		$cyrptlist = json_decode($this->achpay_http_post('http://test.alchemy.foundation/gateway/query/merchant/cryptocurrencyList',$params),true)['currencyVoList'];
		$blockchain=[];
		$lightning=[];
		foreach ($cyrptlist as $v){
			if($v['netWorkType']=='blockchain'){
				array_push($blockchain,['cryptocurrencyName'=>$v['cryptocurrencyName'],'cryptocurrendyId'=>$v['cryptocurrendyId']]);
			}else{
				array_push($lightning,['cryptocurrencyName'=>$v['cryptocurrencyName'],'cryptocurrendyId'=>$v['cryptocurrendyId']]);
			}
		}
		$data['blockchains'] = $blockchain;
		$data['lightnings'] = $lightning;
//		var_dump($data);exit;
        return $this->load->view('extension/payment/achpay', $data);
    }

    public function returns(){
		$this->load->model('checkout/order');
		$this->language->load('extension/payment/achpay');

		$order_prefix       = $this->config->get('payment_achpay_order_prefix');

		$out_trade_no = isset($_REQUEST['order_id'])?$_REQUEST['order_id']:0;
		$OrderNo = $order_id = substr($out_trade_no, strlen($order_prefix));
		$success_status = $this->config->get('payment_achpay_order_succeed_status_id');
		$order_info =  $this->model_checkout_order->getOrder($order_id);
//		订单号不存在
		if(!$order_info){
			$data=array();
			$data['text_wait'] = '3秒后跳转支付页面...';
			$data['Your_BillNo'] = $data['text_billno'] = $order_id;
			$data['text_failure_wait']='3秒后跳转支付页面...';
			$data['title']=$data['heading_title'] = '订单信息错误！';
			$data['text_response']  = '订单信息错误！';
			$data['text_failure'] = '订单信息错误！';
			$data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/payment/achcryreturns', $data));
			return;
		}
		$db = DB_PREFIX;
		$result =$this->db->query(
			"select *
	        from `{$db}order`
            where order_id = '{$order_id}'
                 and order_status_id='{$success_status}'
            limit 1;");
//		订单已支付成功
		if($result->num_rows){
			$this->_redirect($this->url->link('checkout/success', '', 'SSL'));
			return;
		}
//		查询加密币支付方式支付结果

		try{
			$data = [
				"orderId"=>$order_prefix.$order_id,
//				"orderId"=>'opencrypt61',
				"merchantId"=>$this->config->get('payment_achpay_appid'),
				"apiVersion"=>"1.0",
				"sign"=>"123123"
			];
			$res = $this->achpay_http_post('http://test.alchemy.foundation/gateway/query/order/status',$data);
//			判断支付接口返回的结果
			if(!$res){
				throw new Exception('支付接口繁忙');
			}
//			判断状态码
			$res = json_decode($res,true);
//			var_dump($res);exit;
			if( $res['returnCode'] != 0000){
				throw new Exception($res['returnMsg']);
			}
			$db = DB_PREFIX;
//			更改订单状态
			$trade_order_id =substr($res['orderId'], strlen($order_prefix));
			$result =$this->db->query(
				"select *
            from `{$db}order`
            where order_id = '{$trade_order_id}'
            and order_status_id='{$success_status}'
            limit 1;");
			if($result->num_rows){
				throw new Exception('该笔订单已支付成功');
				return;
			}
//			检测订单状态
			if($res['orderStatus'] == 'SUCCESS' || $res['orderStatus'] == 'MSUCCESS'){
				$result= $this->db->query(
					"update `{$db}order`
            set order_status_id='{$success_status}'
            where order_id = '{$trade_order_id}';");
				if($result->num_rows){
					$this->_redirect($this->url->link('checkout/success', '', 'SSL'));
					return;
				}
			}

		}catch(Exception $e){
			$data=array();
			$msg =  "提示:{$e->getMessage()}";
			$data['text_wait'] = '3秒后跳转支付页面...';
			$data['Your_BillNo'] = $data['text_billno'] = $order_id;
			$data['text_failure_wait']='暂未接收到支付结果，如已支付，请耐心等待，3秒后跳转支付页面...';
			$data['title']=$data['heading_title'] = $msg;
			$data['text_response']  = $msg;
			$data['text_failure'] = $msg;
			$data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/payment/achreturns', $data));
		}
//        未查询到支付结果
		$data=array();

		$data['text_wait'] = '3秒后跳转支付页面...';
		$data['Your_BillNo'] = $data['text_billno'] = $order_id;
		$data['text_failure_wait']='暂未接收到支付结果，如已支付，请耐心等待，3秒后跳转支付页面...';
		$data['title']=$data['heading_title'] = $msg;
		$data['text_response']  = $msg;
		$data['text_failure'] = $msg;
		$data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/achreturns', $data));
    }

    public function notify(){
		$this->load->model('checkout/order');
		$this->language->load('extension/payment/achpay');
		$order_prefix       = $this->config->get('payment_achpay_order_prefix');
		$data = $_POST;
		foreach ($data as $k=>$v){
			$data[$k] = stripslashes($v);
		}
//        验证签名
		if(!isset($data['sign'])||!isset($data['orderApplyNo'])){
			echo 'failed';exit;
		}
//		$hash =$this->generate_xh_hash($data,$appsecret);
//        if($data['hash']!=$hash){
//            //签名验证失败
//            echo 'failed';exit;
//        }
		//商户订单ID
		$trade_order_id =substr($data['orderApplyNo'], strlen($order_prefix));
		$success_status = $this->config->get('payment_achpay_order_succeed_status_id');
		$db = DB_PREFIX;
//		查询该订单在数据库中是否已经支付成功
		$result =$this->db->query(
			"select *
            from `{$db}order`
            where order_id = '{$trade_order_id}'
            and order_status_id='{$success_status}'
            limit 1;");
		if($result->num_rows){
			echo 'SUCCESS';exit;
		}else{
			$result= $this->db->query(
				"update `{$db}order`
            set order_status_id='{$success_status}'
            where order_id = '{$trade_order_id}';");
			if($result->num_rows){
				echo 'SUCCESS';exit;
			}
		}
		echo 'failed';exit;
    }
    
    public function send()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/achpay');
        $this->language->load('extension/payment/achpay');
        $order_id = $this->session->data['order_id'];
		if(is_null($_POST['type'])){
			$json['error'] = "no checked!";
			$this->response->setOutput(json_encode($json));
		}else{
			$type = $_POST['type'];
		}

        $json=array();
        if(empty($order_id)){
            $json['error'] = '购物车已清空，请重新购物！';
            $this->response->setOutput(json_encode($json));
            $this->response->addHeader('Content-Type: application/json');
            return;
        }
        
        $order = $this->model_checkout_order->getOrder($order_id);
        if(!$order){
            $json['error'] = '购物车已清空，请重新购物！';
            $this->response->setOutput(json_encode($json));
            $this->response->addHeader('Content-Type: application/json');
            return;
        }
        
        $currency = $order['currency_code'];
        $order_prefix = $this->config->get('payment_achpay_order_prefix');
        $amount = round($this->currency->format($order['total'], $currency, $order['currency_value'], FALSE),2);
        
        $products = $this->cart->getProducts();
        if(!$products){
            $json['error'] = '购物车已清空，请重新购物！';
            $this->response->setOutput(json_encode($json));
            $this->response->addHeader('Content-Type: application/json');
            return;
        }
        $title = '';
        foreach ($products as $product) {
            if(!empty($title)){
                $title.='...';
                break;
            }
            $title.=$product['name'];
        }
        $return_url = $this->url->link('extension/payment/achpay/returns', '', 'SSL');
        $return_url .= (strpos($return_url, '?')===false?'?':'&').'order_id='.$order_prefix.$order_id;
//        法币列表
		switch ($order['currency_code']){
			case 'CNY':
				$currencyId=156;
				break;
			case 'USD':
				$currencyId=840;
				break;
			case 'JPY':
				$currencyId=392;
				break;
			case 'HKD':
				$currencyId=344;
				break;
			case 'IDR':
				$currencyId=360;
				break;
			case 'KRW':
				$currencyId=410;
				break;
			case 'PHP':
				$currencyId=608;
				break;
			case 'SGD':
				$currencyId=702;
				break;
			case 'THB':
				$currencyId=764;
				break;
			case 'MYR':
				$currencyId=458;
				break;
		}

		$data=[
			"orderId"=>$order_prefix.$order_id,
			"merchantId"=>$this->config->get('payment_achpay_appid'),
			"cryptoCurrencyId"=>$type,
			"appVersion"=>"1.0",
			"successUrl"=>$return_url,
			"failUrl"=>$this->url->link('checkout/checkout', '', 'SSL'),
			"currencyAmount"=>$amount,
			"currencyId"=>$currencyId
		];
        $data['sign']     = $this->generate_xh_hash($data,$this->config->get('payment_achpay_appsecret'));
		$url              = $this->config->get('payment_achpay_transaction_url');
		$json['success'] =$url.'?'.http_build_query($data);
        $this->response->setOutput(json_encode($json));
        $this->response->addHeader('Content-Type: application/json');
    }
    
    private function generate_xh_hash(array $datas,$hashkey){
        ksort($datas);
        reset($datas);
         
        $pre =array();
        foreach ($datas as $key => $data){
            if(is_null($data)||$data===''){continue;}
            if($key=='hash'){
                continue;
            }
            $pre[$key]=stripslashes($data);
        }
         
        $arg  = '';
        $qty = count($pre);
        $index=0;
         
        foreach ($pre as $key=>$val){
            $arg.="$key=$val";
            if($index++<($qty-1)){
                $arg.="&";
            }
        }
         
        return md5($arg.$hashkey);
    }

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