<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
class Alchemy_Payment_WC_Payment_Gateway extends WC_Payment_Gateway {

    private $instructions;

	/**
     * 初始化
	 * Alchemy_Payment_WC_Payment_Gateway constructor.
	 */
	public function __construct()
	{
		$this->id = Alchemy_Payment_ID;
		$this->icon = Alchemy_Payment_URL . '/images/logo/alchemy.png';
		$this->has_fields = false;

		$this->method_title = __('Alchemy Payment', Alchemy_Payment);
		$this->order_button_text = __('立即支付', 'wechatpay');
		$this->method_description = __('Helps to add Wechat payment gateway that supports the features including QR code payment, OA native payment, exchange rate.', Alchemy_Payment);

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled = $this->get_option('enabled');
		$this->instructions = $this->get_option('instructions');

		$this->init_form_fields();
		$this->init_settings();
		$this->pkey = file_get_contents(rtrim(plugin_dir_path(__FILE__), '/') . '/pkey.txt');
		add_filter('woocommerce_payment_gateways', array($this, 'woocommerce_add_gateway'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
	}

	/**
	 * 处理支付接口通知
	 */
	public function notify(){
	    global $Alchemy_Payment_WC_Payment_Gateway;
	    $data = $_POST;
        //        判断是否存在签名
	    if(!isset($data['sign'])){
	        return;
	    }
	    $sign=$data['sign'];
	    unset($data['sign']);

//        if(!$this->personalSign($data,$sign)){
//            echo 'sign error';
//            exit;
//        }
//        判断是否存在订单id
        if(!isset($data['orderApplyNo'])){
            return;
        }
        $data['orderApplyNo']=str_replace('WORDPRESS','',$data['orderApplyNo']);
        $order = wc_get_order($data['orderApplyNo']);
	    try{
	        if(!$order){
	            throw new Exception('Unknow OrderApplyNo('.$data['orderApplyNo'].')');
	        }
//	        如果订单id和订单金额符合数据库
	        if($data['amount']!==$order->get_data()['total']){
                throw new Exception('The payment amount is incorrect('.$data['amount'].')');
			}
//	        变更订单状态
	        if(!$order->is_paid()){
	            $order->payment_complete(isset($data['transacton_id'])?$data['transacton_id']:'');
	        }
	    }catch(Exception $e){
	        //looger
	        $logger = new WC_Logger();
	        $logger->add( 'Alchemy_Payment', $e->getMessage() );
	        $params = array(
	            'appid'=>$Alchemy_Payment_WC_Payment_Gateway->get_option('appid'),
	            'errcode'=>$e->getCode(),
	            'errmsg'=>$e->getMessage()
	        );
	        print json_encode($params);
	        exit;
	    }
//	    返回成功状态
	    echo 'SUCCESS';
	    exit;
	}
    /*
     * 实现wooc网关接口
     */
	public function woocommerce_add_gateway($methods) {
	    $methods [] = $this;
	    return $methods;
	}

	/**
     * 支付流程
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment($order_id) {
        $order            = wc_get_order ( $order_id );
		$paymentChannel=$_POST['payType'];
		if(!$order||(method_exists($order, 'is_paid')?$order->is_paid():in_array($order->get_status(),  array( 'processing', 'completed' )))){
		    return array (
		        'result' => 'success',
		        'redirect' => $this->get_return_url($order)
		    );
		}
		$siteurl = rtrim(home_url(),'/');
		$posi =strripos($siteurl, '/');
		//若是二级目录域名，需要以“/”结尾，否则会出现403跳转
		if($posi!==false&&$posi>7){
		    $siteurl.='/';
		}
//        如果是加密币支付方式
        if(is_numeric($paymentChannel)){
			switch ($order->get_currency()){
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
                "orderId"=>'WORDPRESS'.$order_id,
                "merchantId"=>$this->get_option('merchantId'),
                "cryptoCurrencyId"=>$_POST['payType'],
                "appVersion"=>"1.0",
                "successUrl"=>$this->get_return_url($order).'&type='.$paymentChannel,
                "failUrl"=>Alchemy_Payment_URL,
                "currencyAmount"=>$order->get_total(),
                "currencyId"=>$currencyId
            ];
            $data['sign']=$this->get_hash($data);
			return array(
				'result'  => 'success',
				'redirect' => rtrim($this->get_option('crytranasction_url')).'?'.http_build_query($data)
			);
        }else{
			$data = [
				'orderApplyNo'=>'WORDPRESS'.$order_id,
				'merchantId'=>$this->get_option('appid'),
				'amount'=>$order->get_total(),
				'currencyId'=>$this->get_option('baseype'),
				'notifyUrl'=>$siteurl,
				'successUrl'=>$this->get_return_url($order).'&type='.$paymentChannel,
				'paymentChannel'=>$paymentChannel
			];
			$data['sign']=$this->get_hash($data);
            return array(
                'result'  => 'success',
                'redirect'=> rtrim($this->get_option('tranasction_url')).'?'.http_build_query($data)
            );
        }

	}

	/**
     * 支付成功回调
	 * @param $order_id
	 */
	public function thankyou_page($order_id) {
//	    查询该订单是否已经支付成功
        $type = $_GET['type'];
		$order = wc_get_order($order_id);
		if(is_numeric($type)){
		    try{
				$data = [
					"orderId"=>"WORDPRESS".$order_id,
					"merchantId"=>$this->get_option('merchantId'),
					"apiVersion"=>"1.0"
				];
				$res = $this->http_post('http://test.alchemy.foundation/gateway/query/order/status',$data);
				$res = json_decode($res,true);
				if($res['status'] == '404'){
					throw new Exception('支付接口返回404');
				}
				if($res['orderStatus']=='SUCCESS' || $res['orderStatus']=='MSUCCESS'){
					if(!$order->is_paid()){
						$order->payment_complete();
					}
				}
            }catch(Exception $e){
				echo $e->getMessage();
			}

        }
        else{
	        try{
                $data = [
                    "orderApplyNo"=>"WORDPRESS".$order_id,
                    "merchantId"=>$this->get_option('appid'),
                ];
                $res = json_decode($this->http_post('http://13.250.21.97:9190/personalCollection/third/order/query',$data),true);
				if($res['returnCode'] !== '0000'){
				    throw new Exception($res['returnMsg']);
                }
                if($res['data']['status']==1){
					if($res['data']['amount']!==$order->get_data()['total']){
						throw new Exception('The payment amount is incorrect('.$data['amount'].')');
					}
//	        变更订单状态
					if(!$order->is_paid()){
					    $order->payment_complete();
					}
                }
            }catch(Exception $e){
                echo $e->getMessage();
            }
        }
	    if ( $this->instructions ) {
	        echo wpautop( wptexturize( $this->instructions ) );
	    }

	}

	/**
     * 发送邮件
	 * @param $order
	 * @param $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	    $method = method_exists($order ,'get_payment_method')?$order->get_payment_method():$order->payment_method;
	    if ( $this->instructions && ! $sent_to_admin && $this->id ===$method) {
	        echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	    }
	}

	/**
	 * 初始化设置阶段
	 */
	function init_form_fields() {
		$this->form_fields = array (
				'enabled' => array (
						'title'       => __('Alchemy支付',Alchemy_Payment),
						'type'        => 'checkbox',
						'label'       => __('启用/禁用',Alchemy_Payment),
						'default'     => 'no',
						'section'     => 'default'
				),
                'cyrpt' => array (
                            'title'       => __('加密币支付',Alchemy_Payment),
                            'type'        => 'checkbox',
                            'label'       => __('启用/禁用',Alchemy_Payment),
                            'default'     => 'no',
                            'section'     => 'default'
                    ),
                'person' => array (
                            'title'       => __('个人收款码支付',Alchemy_Payment),
                            'type'        => 'checkbox',
                            'label'       => __('启用/禁用',Alchemy_Payment),
                            'default'     => 'no',
                            'section'     => 'default'
                    ),
                'appid' => array(
                    'title'       => __( '个人商户ID', Alchemy_Payment ),
                    'type'        => 'text',
                    'css'         => 'width:400px',
                    'section'     => 'default',
                    'description'=>''
                ),
                'perkey' => array (
                    'title'       => __('个人收款秘钥',Alchemy_Payment),
                    'type'        => 'textarea',
                    'desc_tip'    => true,
                    'css'         => 'width:400px',
                    'section'     => 'default'
                ),
                'merchantId' => array(
                    'title'       => __( '加密币商户ID', Alchemy_Payment ),
                    'type'        => 'text',
                    'css'         => 'width:400px',
                    'default'=>'6e476e074f6546ed8d3b5e8d0579a6b5',
                    'section'     => 'default',
                    'description'=>''
                ),
                'crykey' => array (
                    'title'       => __('加密币收款秘钥',Alchemy_Payment),
                    'type'        => 'textarea',
                    'desc_tip'    => true,
                    'css'         => 'width:400px',
                    'section'     => 'default'
                ),
                'tranasction_url' => array(
                    'title'       => __( '个人收款支付网关', Alchemy_Payment ),
                    'type'        => 'text',
                    'css'         => 'width:400px',
                    'default'=>'http://13.250.21.97:9190/personalCollection/third/order/pay/page',
                    'section'     => 'default',
                    'description'=>''
                ),
                'crytranasction_url' => array(
                    'title'       => __( '加密币支付网关', Alchemy_Payment ),
                    'type'        => 'text',
                    'css'         => 'width:400px',
                    'default'=>'http://test.alchemy.foundation/gateway/trade/cryptolist',
                    'section'     => 'default',
                    'description'=>''
                ),
				'title' => array (
						'title'       => __('支付方式标题',Alchemy_Payment),
						'type'        => 'text',
						'default'     =>  __('Alchemy Payment',Alchemy_Payment),
						'desc_tip'    => true,
						'css'         => 'width:400px',
						'section'     => 'default'
				),
				'description' => array (
						'title'       => __('描述',Alchemy_Payment),
						'type'        => 'textarea',
						'default'     => __('Support WeChat payment, alipay payment',Alchemy_Payment),
						'desc_tip'    => true,
						'css'         => 'width:400px',
						'section'     => 'default'
				),
				'instructions' => array(
    					'title'       => __( 'Instructions', Alchemy_Payment ),
    					'type'        => 'textarea',
    					'css'         => 'width:400px',
    					'description' => __( 'Instructions that will be added to the thank you page.', Alchemy_Payment ),
    					'default'     => '',
    					'section'     => 'default'
				)

		);
	}

	/**
     * 设置支付页面标题等信息
	 * @param $order
	 * @param int $limit
	 * @return mixed|void
	 */
	public function get_order_title($order, $limit = 98) {
	    $order_id = method_exists($order, 'get_id')? $order->get_id():$order->id;
		$title ="#{$order_id}";

		$order_items = $order->get_items();
		if($order_items){
		    $qty = count($order_items);
		    foreach ($order_items as $item_id =>$item){
		        $title.="|{$item['name']}";
		        break;
		    }
		    if($qty>1){
		        $title.='...';
		    }
		}

		$title = mb_strimwidth($title, 0, $limit,'utf-8');
		return apply_filters('xh-payment-get-order-title', $title,$order);
	}

	/**
     * 定义支付选择页面
	 * @throws Exception
	 */
    public function payment_fields(){
//		如果存在描述文本则显示
        if ( $description = $this->get_description() ) {
            echo wptexturize( $description ) ;
        }
        ?>
        <script src="https://cdn.bootcss.com/jquery/2.2.3/jquery.min.js"></script>
        <script src="<?php echo Alchemy_Payment_URL ?>/js/modernizr.js"></script>

        <link rel="stylesheet" href="<?php echo Alchemy_Payment_URL ?>/css/style.css">
        <link rel="stylesheet" href="<?php echo Alchemy_Payment_URL ?>/css/reset.css">
        <script type="text/javascript">
            // 防止用户重复下单
            $(function(){
                pushHistory();
                window.addEventListener("popstate", function(e) {
                    // alert("我监听到了浏览器的返回按钮事件啦");//根据自己的需求实现自己的功能
                    window.location.href='/cart/';
                }, false);
                function pushHistory() {
                    var state = {
                        title: "title",
                        url: "/cart/"
                    };
                    window.history.pushState(state, "title", "/cart/");
                }
            });
        </script>
        <div class="accordion">
            <dl>
                <?php if($this->get_option('person')=='yes'){ ?>


                <dt>
                    <a href="#accordion1" aria-expanded="false" aria-controls="accordion1" class="accordion-title accordionTitle js-accordionTrigger" id="wap">alipay&wechat pay</a>
                </dt>
                <dd class="accordion-content accordionItem is-collapsed" id="accordion1" aria-hidden="false" style="margin-bottom: 2px!important;">
                    <div id="custom_input">
                        <p class="form-row form-row-wide">
                            <label>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="payType" value="alipay" checked />Alipay
                            </label>
                            <label>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="payType" value="wechat" />Wechat Pay
                            </label>
                        </p>
                    </div>
                </dd>
                <?php } ?>
        <?php
        //        判断是否开启加密币支付
        if($this->get_option ( 'cyrpt' )=='yes'){
            $data = [
                "merchantId"=>$this->get_option('merchantId'),
                "appVersion"=>"1.0"
            ];
            $data['sign']=$this->get_hash($data);
            $resd = json_decode($this->http_post('http://test.alchemy.foundation/gateway/query/merchant/cryptocurrencyList',$data));
//            如果存在货币列表
            if(isset($resd->currencyVoList)){
                $res=$resd->currencyVoList;
                $blockchain=[];
                $lightning=[];
                foreach ($res as $v){
                    if($v->netWorkType=='blockchain'){
                        array_push($blockchain,['cryptocurrencyName'=>$v->cryptocurrencyName,'cryptocurrendyId'=>$v->cryptocurrendyId]);
                    }else{
                        array_push($lightning,['cryptocurrencyName'=>$v->cryptocurrencyName,'cryptocurrendyId'=>$v->cryptocurrendyId]);
                    }
                }
                ?>
                <dt>
                    <a href="#accordion2" aria-expanded="false" aria-controls="accordion2" class="accordion-title accordionTitle js-accordionTrigger">
                        blockchain pay</a>
                </dt>
                <dd class="accordion-content accordionItem is-collapsed" id="accordion2" aria-hidden="true" style="margin-bottom: 2px!important;">
                    <div id="custom_input">
                        <p class="form-row form-row-wide">
                            <?php
                            foreach ($blockchain as $v){
                                echo '
                                            <label>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="payType" value="'.$v['cryptocurrendyId'].'" />'.ucwords($v['cryptocurrencyName']).'
                                            </label>';
                            }
                            ?>
                        </p>
                    </div>
                </dd>
                <dt>
                    <a href="#accordion3" aria-expanded="false" aria-controls="accordion3" class="accordion-title accordionTitle js-accordionTrigger">
                        lightning pay
                    </a>
                </dt>
                <dd class="accordion-content accordionItem is-collapsed" id="accordion3" aria-hidden="true" style="margin-bottom: 2px!important;">
                    <p class="form-row form-row-wide">
                        <?php
                        foreach ($lightning as $v){
                            echo '
                                            <label>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="payType" value="'.$v['cryptocurrendyId'].'" />'.ucwords($v['cryptocurrencyName']).'
                                            </label>';
                        }
                        ?>
                    </p>
                </dd>


                <?php
            }


        }
        ?>
            </dl>
        </div>
        <script type="text/javascript">
            //uses classList, setAttribute, and querySelectorAll
            //if you want this to work in IE8/9 youll need to polyfill these
            (function(){
                var d = document,
                    accordionToggles = d.querySelectorAll('.js-accordionTrigger'),
                    setAria,
                    setAccordionAria,
                    switchAccordion,
                    touchSupported = ('ontouchstart' in window),
                    pointerSupported = ('pointerdown' in window);

                skipClickDelay = function(e){
                    e.preventDefault();
                    e.target.click();
                }

                setAriaAttr = function(el, ariaType, newProperty){
                    el.setAttribute(ariaType, newProperty);
                };
                setAccordionAria = function(el1, el2, expanded){
                    switch(expanded) {
                        case "true":
                            setAriaAttr(el1, 'aria-expanded', 'true');
                            setAriaAttr(el2, 'aria-hidden', 'false');
                            break;
                        case "false":
                            setAriaAttr(el1, 'aria-expanded', 'false');
                            setAriaAttr(el2, 'aria-hidden', 'true');
                            break;
                        default:
                            break;
                    }
                };
//function
                switchAccordion = function(e) {
                    console.log("triggered");
                    e.preventDefault();
                    var thisAnswer = e.target.parentNode.nextElementSibling;
                    var thisQuestion = e.target;
                    if(thisAnswer.classList.contains('is-collapsed')) {
                        setAccordionAria(thisQuestion, thisAnswer, 'true');
                    } else {
                        setAccordionAria(thisQuestion, thisAnswer, 'false');
                    }
                    thisQuestion.classList.toggle('is-collapsed');
                    thisQuestion.classList.toggle('is-expanded');
                    thisAnswer.classList.toggle('is-collapsed');
                    thisAnswer.classList.toggle('is-expanded');

                    thisAnswer.classList.toggle('animateIn');
                };
                for (var i=0,len=accordionToggles.length; i<len; i++) {
                    if(touchSupported) {
                        accordionToggles[i].addEventListener('touchstart', skipClickDelay, false);
                    }
                    if(pointerSupported){
                        accordionToggles[i].addEventListener('pointerdown', skipClickDelay, false);
                    }
                    accordionToggles[i].addEventListener('click', switchAccordion, false);
                }
            })();
        </script>
        <?php
    }

	/**
	 * http post请求方法
	 * @param $url
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	private function http_post($url,$data){
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

	/**
	 * 创建签名
	 * @param array $datas
	 * @return string|void
	 */
	public function get_hash(array $datas){
		if(empty($datas)){
			return;
		}
//	    对数组进行a-z排序
		ksort($datas);
//        排序后的字符串
		$str='';
		foreach ($datas as $v){
			$str.=$v;
		}
		$encrypted = '';
		$privKeyId = openssl_pkey_get_private($this->pkey);
		openssl_sign($str, $encrypted, $privKeyId);
		openssl_free_key($privKeyId);
//        return $str;
		return base64_encode($encrypted);

	}

	/**
	 * 加密币验签
	 * @param $res
	 * @return bool
	 */
	private function veritySign($res){
//        个人收款
		function object_to_array($obj) {
			$obj = (array)$obj;
			foreach ($obj as $k => $v) {
				if (gettype($v) == 'resource') {
					return;
				}
				if (gettype($v) == 'object' || gettype($v) == 'array') {
					$obj[$k] = (array)object_to_array($v);
				}
			}

			return $obj;
		}
		$arr=object_to_array($res);
//        var_dump($res);exit;
		$cyrsign=$arr['sign'];
		unset($arr['sign']);
		$newArr=[];
		foreach ($arr as $k=>$v){
			if(is_array($v)){
				$newArr[$k]=json_encode($v);
			}else{
				$newArr[$k]=$v;
			}
		}
		ksort($newArr);
//        var_dump($newArr);exit;
		$str='';
		foreach ($newArr as $v){
			$str .= $v;
		}

		$pubKeyFile = file_get_contents(rtrim ( plugin_dir_path ( __FILE__ ), '/' ).'/cryptPubKey.txt');
		$publicKey = openssl_get_publickey($pubKeyFile);
		$sign=base64_decode($cyrsign);
		$result = openssl_verify($str,$sign,$publicKey);
		openssl_free_key($publicKey);
//        var_dump($result);exit;
		return (bool)$result;
	}

	/**
	 * 个人收款验签
	 * @param $data
	 * @param $cyrsign
	 * @return bool
	 */
	private function personalSign($data,$cyrsign){
		ksort($data);
		$str='';
		foreach ($data as $v){
			$str .= $v;
		}
		$pubKeyFile = file_get_contents(rtrim ( plugin_dir_path ( __FILE__ ), '/' ).'/perPubKey.txt');
		$publicKey = openssl_get_publickey($pubKeyFile);
		$sign=base64_decode($cyrsign);
		$result = openssl_verify($str,$sign,$publicKey);
		openssl_free_key($publicKey);
//        var_dump($result);exit;
		return (bool)$result;
	}

}

