<?php
namespace System\Controller;
use Common\Controller\BackController;
class SiteController extends BackController{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化.
		$config_file_list = array('alipay.php','email.php','info.php','platform.php','safe.php','sms.php');
		foreach($config_file_list as $vo){
			$fh = fopen(CONF_PATH.$vo,"rb");
			$fs = fread($fh,filesize(CONF_PATH.$vo));
			fclose($fh);
			$fs = str_replace('\'up_exts\'','\'up_exts_error\'',$fs);
			file_put_contents(CONF_PATH.$vo, $fs, LOCK_EX);
			@unlink(RUNTIME_FILE);
		}				
		$_POST = filterPost($_POST, array('up_password'));
    }
	
	public function index(){
		$where=array('agentid'=>$this->agentid);
		$groups=M('User_group')->where($where)->order('id ASC')->select();
		$this->assign('groups',$groups);
		if(class_exists('updateSync')){
			$result = updateSync::getIfWeidian();
			$this->assign('load_config',$result);
		}
		$this->display();
	}
	public function mysql(){
		
		
		$this->display();
		
	}
	public function mysqlajax(){
		switch($_POST['type']){
			case 'table_name':
				$db_name = C('DB_NAME');
				$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db_name."'";
				$query_sql = M()->query($sql);
				$table_name = array();
				foreach($query_sql as $k=>$v){
					$table_name[$k] = $v['TABLE_NAME'];
				}
				$data['table_name'] = $table_name;
				$data['table_count'] = count($table_name);
				$this->ajaxReturn($data,'JSON');
			break;
			case 'youhuasql':
				$sql_OPTIMIZE = "OPTIMIZE TABLE `".$_POST['table_name']."`";
				$query_sql_OPTIMIZE = M()->query($sql_OPTIMIZE);
				$query_sql_OPTIMIZE[0]['Table'] = str_replace(C('DB_NAME').'.','',$query_sql_OPTIMIZE[0]['Table']);
				$data = $query_sql_OPTIMIZE[0];
				$this->ajaxReturn($data,'JSON');
			break;
			case 'xiufusql':
				$sql_REPAIR = "REPAIR TABLE `".$_POST['table_name']."`";
				$query_sql_REPAIR = M()->query($sql_REPAIR);
				$query_sql_REPAIR[0]['Table'] = str_replace(C('DB_NAME').'.','',$query_sql_REPAIR[0]['Table']);
				$data = $query_sql_REPAIR[0];
				$this->ajaxReturn($data,'JSON');
			break;
		}
	}
	public function appajax(){
		$renzhengfuwuhaodata = S('renzhengfuwuhaodata');
		
		$appid = $_POST['appid'];
		$secret = $_POST['appsecret'];
		
		if($appid != $renzhengfuwuhaodata['appid'] || $secret != $renzhengfuwuhaodata['appsecret']){
			S($appid ,null);
			$apiOauth = new apiOauth();
			$access_token = $apiOauth->update_authorizer_access_token($appid,$_POST);
			if($access_token == false){
				$data['error'] = 2;
			}else{
				$url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$access_token}";
				$yzdata = "{\"action\":\"long2short\",\"long_url\":\"".C('site_url')."\"}";
				$wxdata = json_decode($this->https_request($url,$yzdata), true);
				if($wxdata['errcode'] == 48001){
					$data['error'] = 1;
				}else{
					$data['error'] = 0;
				}
			}
			$renzhengfuwuhaodata['appid'] = $appid;
			$renzhengfuwuhaodata['appsecret'] = $secret;
			S('renzhengfuwuhaodata',$renzhengfuwuhaodata);
		}else{
			$data['error'] = 0;
		}
		$this->ajaxReturn($data,'JSON');
	}
	//https请求（支持GET和POST）
    protected function https_request($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
	public function email(){
		$this->display();
	}	
	public function alipay(){
		$this->display();
	}
	public function safe(){
		$this->display();
	}
	public function upfile(){
		$this->display();
	}
	public function sms(){
		$total=M('Sms_expendrecord')->sum('count');
		$this->assign('total',$total);
		$this->display();
	}
	public function wechat_api(){
		$where = array('agentid' => $this->agentid);
		$site 	= M('weixin_account')->where($where)->find();
		if(IS_POST){
			if($site){
				if(M('Weixin_account')->where($where)->save($_POST)){
					$this->success('操作成功');
				}else{
					$this->success('操作失败');
				}
			}else{
				$_POST['agentid'] = $this->agentid;
				if(M('Weixin_account')->add($_POST)){
					$this->success('操作成功');
				}else{
					$this->success('操作失败');
				}
			}
		}else{
			$this->assign('site',$site);
			$this->display();
		}	
	}

	public function weixinPay(){
		$pay 	= M('WeixinPay')->where(array('system'=>1))->find();
		if(IS_POST){
			if($pay){
				$_POST = array_map('trim', $_POST);
				if(M('WeixinPay')->where(array('system'=>1))->save($_POST)){
					$this->success('操作成功');
				}else{
					$this->success('操作失败');
				}
			}else{
				$_POST['system'] = 1;
				$_POST['create_time'] = time();
				$_POST = array_map('trim', $_POST);
				if(M('WeixinPay')->add($_POST)){
					$this->success('操作成功');
				}else{
					$this->success('操作失败');
				}
			}
		}else{
			$this->assign('pay',$pay);
			$this->display();
		}
	}
	public function insert(){
		$appid = $_POST['appid'];
		$secret = $_POST['secret'];
		if($_POST['up_exts'] != ''){
			$_POST['up_exts'] = str_replace("'",'',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('"','',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('‘','',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('“','',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('，',',',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('’','',$_POST['up_exts']);
			$_POST['up_exts'] = str_replace('”','',$_POST['up_exts']);
			$_POST['up_exts'] = trim($_POST['up_exts']);
			$_POST['up_exts'] = strtolower($_POST['up_exts']);
		}else{
			unset($_POST['up_exts']);
		}
		if (empty($_POST['encryption'])) {
			$_POST['encryption'] = C('encryption');
		}
		if ('clear' == $_POST['encryption']) {
			unset($_POST['encryption']);
		}
		if($appid != '' && $secret != ''){
			$renzhengfuwuhaodata = S('renzhengfuwuhaodata');
			if($appid != $renzhengfuwuhaodata['appid'] || $secret != $renzhengfuwuhaodata['appsecret']){
				$apiOauth = new apiOauth();
				$renzhengfuwuhaodata['appid'] = $appid;
				$renzhengfuwuhaodata['appsecret'] = $secret;
				$access_token = $apiOauth->update_authorizer_access_token($appid,$renzhengfuwuhaodata);
				if($access_token == false){
					$this->error('您填的appid和appsecret不正确');exit;
				}
				$url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$access_token}";
				$yzdata = "{\"action\":\"long2short\",\"long_url\":\"".C('site_url')."\"}";
				$wxdata = json_decode($this->https_request($url,$yzdata), true);
				if($wxdata['errcode'] == 48001){
					$this->error('您填的appid和appsecret并不是认证后的服务号！');
					exit;
				}
			}
		}
		$file=base64_decode(I('post.files'));
		$file_hash = I('post.files_hash');
		unset($_POST['files_hash']);
		if ($file_hash != md5($file.'|validate_pigcms')) {
			$this->error('文件验证失败。');
		}
		if ('info.php' != $file) {
			unset($_POST['encryption']);
		}
		unset($_POST['files']);
		unset($_POST[C('TOKEN_NAME')]);
		if (isset($_POST['countsz'])){
		$_POST['countsz']=base64_encode($_POST['countsz']);
		}
		if($this->update_config($_POST,CONF_PATH.$file)){
			$this->success('操作成功');
		}else{
			$this->success('操作失败');
		}
	}
	public function updatekey(){
		$file='info.php';
		
		
		$config_file=CONF_PATH.$file;
		!is_file($config_file) && $config_file = CONF_PATH . 'web.php';
		if (is_writable($config_file)) {
			$config = require $config_file;
			if ($_GET['key']){
			$config['server_key']=$_GET['key'];
			}
			file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
			@unlink(RUNTIME_FILE);
			exit(1);
		} else {
			exit(-1);
		}
	}
	public function smssendtest(){
		if (strlen($_GET['mp'])!=11){
			$this->error('请输入正确的手机号');
		}
		$this->error(Sms::sendSms('admin','hello,你好',$_GET['mp']));
	}
	private function update_config($config, $config_file = '') {
		!is_file($config_file) && $config_file = CONF_PATH . 'web.php';
		if (is_writable($config_file)) {
			file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
			@unlink(RUNTIME_FILE);
			return true;
		} else {
			return false;
		}
	}
}