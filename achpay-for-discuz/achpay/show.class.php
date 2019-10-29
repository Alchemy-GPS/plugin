<?php
/**
 * 页面嵌入
 */
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_achpay{
	function global_cpnav_extra1(){
		global $_G;
	    $ps = $_G['cache']['plugin']['achpay'];
		if($ps['indexpoint']==1){
			$msg = "<a href='home.php?mod=spacecp&ac=plugin&op=credit&id=achpay:home'>".$ps['fronttext']."</a>";
		    return $msg;
		}
	}
	function global_cpnav_extra2(){
		global $_G;
	    $ps = $_G['cache']['plugin']['achpay'];
		if($ps['indexpoint']==2){
			$msg = "<a href='home.php?mod=spacecp&ac=plugin&op=credit&id=achpay:home'>".$ps['fronttext']."</a>";
		    return $msg;
		}
	}
	function global_usernav_extra3(){
		global $_G;
	    $ps = $_G['cache']['plugin']['achpay'];
		if($ps['indexpoint']==3){
			$msg = "<a href='home.php?mod=spacecp&ac=plugin&op=credit&id=achpay:home'>".$ps['fronttext']."</a>";
		    return $msg;
		}
	}
	function global_footerlink(){
		global $_G;
	    $ps = $_G['cache']['plugin']['achpay'];
		if($ps['indexpoint']==4){
			$msg = "<a href='home.php?mod=spacecp&ac=plugin&op=credit&id=achpay:home'>".$ps['fronttext']."</a>";
		    return $msg;
		}
	}	
}


?>