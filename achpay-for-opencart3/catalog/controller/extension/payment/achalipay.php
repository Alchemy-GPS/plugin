<?php

class ControllerExtensionPaymentachalipay extends Controller
{

    private function _redirect($url, $status = 302) {
        header('location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
        exit();
    }
    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/achalipay');
        $this->load->language('extension/payment/achalipay');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        $data['text_credit_card'] = $this->language->get('text_credit_card');
        $data['text_wait'] = $this->language->get('text_wait');
        
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');
        
        return $this->load->view('extension/payment/achalipay', $data);
    }

    public function returns(){
		$this->load->model('checkout/order');
		$this->language->load('extension/payment/achalipay');

		$order_prefix       = $this->config->get('payment_achalipay_order_prefix');

		$out_trade_no = isset($_REQUEST['order_id'])?$_REQUEST['order_id']:0;
		$OrderNo = $order_id = substr($out_trade_no, strlen($order_prefix));
		$success_status = $this->config->get('payment_achalipay_order_succeed_status_id');
		$order_info =  $this->model_checkout_order->getOrder($order_id);
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

			$this->response->setOutput($this->load->view('extension/payment/achreturns', $data));
			return;
		}
		$db = DB_PREFIX;
		$result =$this->db->query(
			"select *
	        from `{$db}order`
            where order_id = '{$order_id}'
                 and order_status_id='{$success_status}'
            limit 1;");

		if($result->num_rows){
			$this->_redirect($this->url->link('checkout/success', '', 'SSL'));
			return;
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
		$this->language->load('extension/payment/achalipay');
		$order_prefix       = $this->config->get('payment_achalipay_order_prefix');
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
		$success_status = $this->config->get('payment_achalipay_order_succeed_status_id');
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
        $this->load->model('extension/payment/achalipay');
        $this->language->load('extension/payment/achalipay');
        $order_id = $this->session->data['order_id'];
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
        $order_prefix = $this->config->get('payment_achalipay_order_prefix');
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
        
        $return_url = $this->url->link('extension/payment/achalipay/returns', '', 'SSL');
        $return_url .= (strpos($return_url, '?')===false?'?':'&').'order_id='.$order_prefix.$order_id;

		$data = array(
			'orderApplyNo'=>$order_prefix.$order_id,
			'merchantId'=>$this->config->get('payment_achalipay_appid'),
			'amount'=>$amount,
			'notifyUrl'=>$this->url->link('extension/payment/achalipay/notify', '', 'SSL'),
			'successUrl'=>$return_url,
			'paymentChannel'=>'alipay'
		);
//		var_dump($this->config->get('payment_achalipay_appid'));exit;
        $data['sign']     = $this->generate_xh_hash($data,$this->config->get('payment_achalipay_appsecret'));
//		var_dump($data);exit;
		$url              = $this->config->get('payment_achalipay_transaction_url');
		$json['success'] =$url.'?'.http_build_query($data);
        $this->response->setOutput(json_encode($json));
        $this->response->addHeader('Content-Type: application/json');
    }
    
    private function http_post($url,$data){
        if(!function_exists('curl_init')){
            throw new Exception('php未安装curl组件',500);
        }
    
        $protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
        $siteurl= $protocol.$_SERVER['HTTP_HOST'];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_REFERER,$siteurl);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error=curl_error($ch);
        curl_close($ch);
        if($httpStatusCode!=200){
            throw new Exception("invalid httpstatus:{$httpStatusCode} ,response:$response,detail_error:".$error,$httpStatusCode);
        }
         
        return $response;
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
    
}