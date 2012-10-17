<?php 
	$uninstall_reason=array(
		'我觉得这个插件不能满足我的需求，我想同步到的微博服务它并不支持',
		'这个插件有bug，经常同步失败',
		'插件的界面太不美观了',
		'插件的定制性太差',
		'我发现了一个比这个插件更好的同步插件'
	);
	if($_POST['uninstall_unsend'] || $_POST['uninstall_send']){
		delete_option('smc_weibo_options');
		delete_option('smc_allowed_weibo');
		delete_option('smc_weibo_appkey_opt');
		delete_option('smc_global_option');
		$SMC=wp_cache_get('global_option','smc');
		if(empty($SMC))$SMC=array();
		$weiboarray=array_keys($SMC);
		foreach($weiboarray as $weibo){
			if(get_option('smc_'.$weibo.'_timeline_cache'))delete_option('smc_'.$weibo.'_timeline_cache');
			if(get_option('smc_'.$weibo.'_custom_appkey'))delete_option('smc_'.$weibo.'_custom_appkey');
		}
	}
	if($_POST['reset_option']){
		delete_option('smc_weibo_options');
		$info='插件已经被重置，欢迎您继续使用本插件。';
	}
	if($_POST['uninstall_send']){
		$reason_data='';$count=0;
		foreach($uninstall_reason as $reason){
			if($_POST['reason'.$count++]){
				$reason_data.="* $reason\r\n";
			}
		}
		if($_POST['other_reason']){
			$reason_data.="\r\n其他原因：\r\n".$_POST['other_reason']."\r\n";
		}
		if($reason_data){
			$blogname=get_option('blogname');$user=wp_get_current_user();
			$plugin_author_email=str_replace('#','@',PLUGIN_AUTHOR_EMAIL);
			$subject="新的Social Medias Connect卸载报告 - $blogname";
			$body="来自 $blogname 的卸载报告：\r\n\r\n";
			$body.=$reason_data;
			$body.="\r\n";
			$body.="------------------------------------------\r\n";
			$body.="网站名称: $blogname\r\n";
			$body.="网站地址: ".get_option('home')."\r\n";
			$body.="站长昵称: ".$user->user_nicename."\r\n";
			$body.="站长邮箱: ".get_option('admin_email')."\r\n";
			$headers = 'From: '.$user->user_nicename.' <'.get_option('admin_email').'>' . "\r\n" . 'Reply-To: ' . get_option('admin_email');
			wp_mail($plugin_author_email, $subject, $body, $headers);
		}
	}
	if($_POST['uninstall_unsend'] || $_POST['uninstall_send']){
		delete_option('smc_send_me_email');
		delete_option('weibo_access_token');
		$plugin=str_replace('uninstall-menu','social-medias-connect',plugin_basename (__FILE__));
		$deactivate_url=admin_url('plugins.php').'?action=deactivate&paged=1&plugin='.$plugin;
		$deactivate_url=str_replace('&amp;','&',wp_nonce_url($deactivate_url, 'deactivate-plugin_'.$plugin));
		echo '<script type="text/javascript">window.location.href="'.$deactivate_url.'";</script>';
	}
?>
<div class="wrap" style="-webkit-text-size-adjust:none;">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>卸载社交媒体连接插件</h2>
	<?php if($info){
		echo '<div class="updated"><p><b>'.$info.'</b></p></div>';
	} ?>
	<h4>如果你觉得本插件不适合你，想要卸载它，请务必阅读完下面的内容后再进行卸载操作。</h4>
	<ol>
		<li>卸载插件将会删除删除插件的所有设置项。</li>
		<li>卸载插件后使用微博连接注册的用户并不会被删除。</li>
		<li>如果您愿意，欢迎花点时间告知我您卸载插件的原因：<br/>
			<form action="" method="post">
			<?php $count=0;foreach($uninstall_reason as $reason): ?>
				<p><input type="checkbox" value="<?php echo $reason; ?>" name="reason<?php echo $count++; ?>"/> <label><?php echo $reason; ?></label></p>
			<?php endforeach; ?>
				<p>
					<strong>其它原因补充:</strong> <br/><br/>
					<textarea name="other_reason" style="width:500px;font-size:12px;" rows="5"></textarea>
				</p>
				注意: 如果你选择了发送报告，上面你所选择和填写的信息将会被发送给插件作者，用来协助作者继续改进完善此插件。
				<br/>
				<h4>请选择下面的按钮进行操作：</h4>
				<p class="submit">
				<a href="<?php echo smc_menu_page_url('social-medias-connect/function.php',false); ?>&smc_request=getglobaloption"><input type="button" name="reset_appkey" class="button-primary" value="获取插件配置" /></a> 如果插件出现异常，请点此初始化<br/><br/>
				<input type="submit" name="reset_option" class="button-primary" value="重置插件设置" /> 仅仅重置插件设置，插件并不会停用<br/><br/>
				<input type="submit" style="color:yellow;" name="uninstall_send" class="button-primary" value="卸载并发送报告" /> <span style="color:red">建议并希望您能向我发送卸载报告，以帮助我更好的改进本插件。</span><br/><br/>
				<input type="submit" name="uninstall_unsend" class="button-primary" value="卸载不发送报告" /> 删除插件设置，停用插件，不发送报告<br/><br/>
				<?php wp_nonce_field('wptm-delete');?>
				</p>
			</form>
		</li>
	</ol>
</div>