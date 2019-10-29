<?php
/*
 * Plugin Name: alchemy-payments-for-woocommerce
 * Plugin URI: http://www.achpay.org
 * Description: 支持微信、支付宝个人收款码收款和加密货币收款
 * Author: ALCHEMY
 * Version: 1.0.1
 * Author URI:  http://www.achpay.org
 * WC tested up to: 3.3.1
 */

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
if (! defined ( 'Alchemy_Payment' )) {define ( 'Alchemy_Payment', 'Alchemy_Payment' );} else {return;}
define ( 'Alchemy_Payment_VERSION', '1.0.1');
define ( 'Alchemy_Payment_ID', 'alchemy-payment-wc');
define ( 'Alchemy_Payment_FILE', __FILE__);
define ( 'Alchemy_Payment_DIR', rtrim ( plugin_dir_path ( Alchemy_Payment_FILE ), '/' ) );
define ( 'Alchemy_Payment_URL', rtrim ( plugin_dir_url ( Alchemy_Payment_FILE ), '/' ) );
load_plugin_textdomain( Alchemy_Payment, false,dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );

add_filter ( 'plugin_action_links_'.plugin_basename( Alchemy_Payment_FILE ),'Alchemy_payment_plugin_action_links',10,1 );
function Alchemy_payment_plugin_action_links($links) {
    return array_merge ( array (
        'settings' => '<a href="' . admin_url ( 'admin.php?page=wc-settings&tab=checkout&section='.Alchemy_Payment_ID ) . '">'.__('设置',Alchemy_Payment).'</a>'
    ), $links );
}

// 加载插件时，初始化自定义类
add_action('plugins_loaded','init_my_gateway_class');
function init_my_gateway_class()
{
    if(!class_exists('WC_Payment_Gateway')){
        return;
    }
    require_once Alchemy_Payment_DIR.'/class-alchemy-wc-payment-gateway.php';
    global $Alchemy_Payment_WC_Payment_Gateway;
    $Alchemy_Payment_WC_Payment_Gateway= new Alchemy_Payment_WC_Payment_Gateway();
    add_action('init', array($Alchemy_Payment_WC_Payment_Gateway,'notify'),10);
    add_action( 'woocommerce_receipt_'.$Alchemy_Payment_WC_Payment_Gateway->id, array($Alchemy_Payment_WC_Payment_Gateway, 'receipt_page'));
//    引入定时查询类
	require_once Alchemy_Payment_DIR.'/class-alchemy-wc-payment-contab.php';
	global $Alchemy_Payment_WC_Payment_Contab;
	$Alchemy_Payment_WC_Payment_Contab= new Alchemy_Payment_WC_Payment_Contab();
//	微信支付宝定时查询
	add_action('init', array($Alchemy_Payment_WC_Payment_Contab,'person'),10);
	add_action('init', array($Alchemy_Payment_WC_Payment_Contab,'crypt'),10);

}







