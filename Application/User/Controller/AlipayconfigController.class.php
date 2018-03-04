<?php
namespace User\Controller;
use Common\Controller\UserController;
class AlipayconfigController extends UserController{
	public $pay_config_db;
	public function _initialize() {
		parent::_initialize();
		$this->pay_config_db=M('Alipay_config');
		if (!$this->token){
			exit();
		}
	}
	public function index(){
		//找出支付的配置文件
		$where['token'] = $this->token;
		$config = $this->pay_config_db->where($where)->find();
		if(IS_POST){
			$data_Alipayconfig['token'] = $this->token;
			$data_Alipayconfig['name'] = strval(trim($_POST['alipay']['name']));
			$data_Alipayconfig['pid'] = strval(trim($_POST['alipay']['pid']));
			$data_Alipayconfig['key'] = strval(trim($_POST['alipay']['key']));
			$data_Alipayconfig['partnerkey'] = strval(trim($_POST['tenpayComputer']['partnerkey']));
			$data_Alipayconfig['appsecret'] = strval(trim($_POST['weixin']['appsecret']));
			$data_Alipayconfig['appid'] = strval(trim($_POST['weixin']['appid']));
			$data_Alipayconfig['partnerid'] = strval(trim($_POST['tenpayComputer']['partnerid']));
			$data_Alipayconfig['mchid'] = strval(trim($_POST['weixin']['mchid']));
			$data_Alipayconfig['open'] = strval(trim($_POST['is_open']));
			
			
			

			unset($_POST[C('TOKEN_NAME')],$_POST['token']);
			//为了前台查询快速不用多次分析配置的值，将前台的值序列化了。
			$data_Alipayconfig['info'] = serialize($_POST); 	//因TP在系统变量中已经自动处理了表单中不安全的因素，故而不进行任何处理。
			if($config){
				$this->pay_config_db->where($where)->data($data_Alipayconfig)->save();
			}else{
				$this->pay_config_db->where($where)->data($data_Alipayconfig)->add();
			}
			
			$this->success('设置成功',U('Alipayconfig/index',$where));
		}else{
			if($config){
				$config = unserialize($config['info']);
				$this->assign('config',$config);
			}
			$this->display();
		}
	}
}