<?php 
delete_option('smc_weibo_options');
delete_option('smc_allowed_weibo');
delete_option('smc_weibo_appkey_opt');
delete_option('smc_global_option');
delete_option('smc_send_me_email');
delete_option('weibo_access_token');
$weiboarray=array('qqweibo','sinaweibo','sohuweibo','163weibo','twitter','renren','kaixin','facebook','fanfou');
foreach($weiboarray as $weibo){
	if(get_option('smc_'.$weibo.'_timeline_cache'))delete_option('smc_'.$weibo.'_timeline_cache');
	if(get_option('smc_'.$weibo.'_custom_appkey'))delete_option('smc_'.$weibo.'_custom_appkey');
}
?>