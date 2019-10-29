<?php

class Controllerextensionpaymentachpay extends Controller
{

    private $error = array();
 
    private function _redirect($url, $status = 302) {
        header('location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
        exit();
    }
    
    public function index()
    {
        $this->load->language('extension/payment/achpay');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
       
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_achpay', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $url =$this->url->link('extension/payment/achpay', 'user_token=' . $this->session->data['user_token'], 'SSL');
            
            $this->_redirect($url);
        }
        
        $data['success'] = isset($this->session->data['success'])?$this->session->data['success']:null;
        $this->session->data['success']=null;
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        
        $data['entry_achpay_appid'] = $this->language->get('entry_appid');
      
        $data['entry_achpay_appsecret'] = $this->language->get('entry_appsecret');
        $data['entry_achpay_order_prefix']= $this->language->get('entry_order_prefix');
        $data['entry_achpay_transaction_url'] = $this->language->get('entry_transaction_url');
        
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_order_succeed_status'] = $this->language->get('entry_order_succeed_status');
        $data['entry_order_payWait_status_id'] = $this->language->get('entry_order_payWait_status_id');
        $data['entry_order_failed_status'] = $this->language->get('entry_order_failed_status');
        
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');       
        $data['tab_general'] = $this->language->get('tab_general');
//        var_dump($data);exit;
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['error_appid'])) {
            $data['error_appid'] = $this->error['error_appid'];
        } else {
            $data['error_appid'] = '';
        }
        
        if (isset($this->error['error_appsecret'])) {
            $data['error_appsecret'] = $this->error['error_appsecret'];
        } else {
            $data['error_appsecret'] = '';
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' =>false
        );
        
        
       $url_payment =$this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token'], 'SSL');
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $url_payment,
            'separator' => '::'
        );
         $url_payment= $this->url->link('extension/payment/achpay', 'user_token=' . $this->session->data['user_token'], 'SSL');
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' =>$url_payment,
            'separator' => '::'
        );
        
        $data['action'] = $this->url->link('extension/payment/achpay', 'user_token=' . $this->session->data['user_token'], 'SSL');
        
        $data['cancel'] = $this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token'], 'SSL');	
   
      
        if (isset($this->request->post['payment_achpay_appid'])) {
            $data['payment_achpay_appid'] = $this->request->post['payment_achpay_appid'];
        } else {
            $data['payment_achpay_appid'] = $this->config->get('payment_achpay_appid');
        }
       
        if (isset($this->request->post['payment_achpay_appsecret'])) {
            $data['payment_achpay_appsecret'] = $this->request->post['payment_achpay_appsecret'];
        } else {
            $data['payment_achpay_appsecret'] = $this->config->get('payment_achpay_appsecret');
        }
        
        if (isset($this->request->post['payment_achpay_transaction_url'])) {
            $data['payment_achpay_transaction_url'] = $this->request->post['payment_achpay_transaction_url'];
        } else {
            $data['payment_achpay_transaction_url'] = $this->config->get('payment_achpay_transaction_url');
        }
        if(empty($data['payment_achpay_transaction_url'])){
            $data['payment_achpay_transaction_url'] = 'http://test.alchemy.foundation/gateway/trade/plugin/payment';
        }

        if (isset($this->request->post['payment_achpay_order_prefix'])) {
            $data['payment_achpay_order_prefix'] = $this->request->post['payment_achpay_order_prefix'];
        } else if(empty($this->config->get('payment_achpay_order_prefix'))){
			$data['payment_achpay_order_prefix']= 'OPENCRY'.mt_rand(1000,9999);
        }else{
			$data['payment_achpay_order_prefix'] = $this->config->get('payment_achpay_order_prefix');

		}
//		var_dump($data);exit;

		$data['callback'] = HTTP_CATALOG . 'index.php?route=payment/achpay/callback';
       
        if (isset($this->request->post['payment_achpay_order_succeed_status_id'])) {
            $data['payment_achpay_order_succeed_status_id'] = $this->request->post['payment_achpay_order_succeed_status_id'];
        } else {
            $data['payment_achpay_order_succeed_status_id'] = $this->config->get('payment_achpay_order_succeed_status_id');
            if(!$data['payment_achpay_order_succeed_status_id']){
                $data['payment_achpay_order_succeed_status_id']='2';
            }
        }
        
        if (isset($this->request->post['payment_achpay_order_failed_status_id'])) {
            $data['payment_achpay_order_failed_status_id'] = $this->request->post['payment_achpay_order_failed_status_id'];
        } else {
            $data['payment_achpay_order_failed_status_id'] = $this->config->get('payment_achpay_order_failed_status_id');
            if(! $data['payment_achpay_order_failed_status_id']){
                $data['payment_achpay_order_failed_status_id']='10';
            }
        }
        
        $this->load->model('localisation/order_status');
        
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        
        if (isset($this->request->post['payment_achpay_geo_zone_id'])) {
            $data['payment_achpay_geo_zone_id'] = $this->request->post['payment_achpay_geo_zone_id'];
        } else {
            $data['payment_achpay_geo_zone_id'] = $this->config->get('payment_achpay_geo_zone_id');
        }
        
        $this->load->model('localisation/geo_zone');
        
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        
        if (isset($this->request->post['payment_achpay_status'])) {
            $data['payment_achpay_status'] = $this->request->post['payment_achpay_status'];
        } else {
            $data['payment_achpay_status'] = $this->config->get('payment_achpay_status');
        }
        
        if (isset($this->request->post['payment_achpay_sort_order'])) {
            $data['payment_achpay_sort_order'] = $this->request->post['payment_achpay_sort_order'];
        } else {
            $data['payment_achpay_sort_order'] = $this->config->get('payment_achpay_sort_order');
        }
        
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/achpay', $data));
       
    }

    protected function validate()
    {
        if (! $this->user->hasPermission('modify', 'extension/payment/achpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return ! $this->error;
    }
}
?>