<?php
namespace User\Controller;
use Common\Controller\UserController;
class AdmaController extends UserController{
	public function index(){
		if (C('STATICS_PATH')){
			$staticPath='';
		}else {
			$staticPath='http://s.404.cn';
		}
		$this->assign('staticPath',$staticPath);		
		$this->canUseFunction('adma');
		$data=D('Adma');
		$adma=$data->where(array('token'=>session('token'),'uid'=>session('uid')))->find();
		$this->assign('adma',$adma);

		if(IS_POST){
			$update_wxuser = M("wxuser")->where(array('token'=>$this->token))->save(array('qr'=>$_POST['url']));
			$_POST['uid']=session('uid');
			$_POST['token']=session('token');			
			if($data->create()){
				if($adma==false){
					if($data->add()){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试');
					}
				}else{
					$_POST['id']=$adma['id'];
					if($data->save($_POST)){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试');
					}
				}
			}else{
				
				$this->error($data->getError());
			}
		
		}else{

			if($adma==false){
				$adma=array();
				$adma['url'] = M("wxuser")->where(array('token'=>$this->token))->getField("qr");
				$adma['copyright']='© 2001-2013 某某微信版权所有';
				$adma['info']='微信营销管理平台为个人和企业提供基于微信公众平台的一系列功能，包括智能回复、微信3G网站、互动营销活动，会员管理，在线订单，数据统计等系统功能,带给你全新的微信互动营销体验。';
				$adma['title']=C('site_name');
				$this->assign('adma',$adma);
			}
			$this->display();
		}
	}
	public function edit(){
		if (C('STATICS_PATH')){
			$staticPath='';
		}else {
			$staticPath='http://s.404.cn';
		}
		$this->assign('staticPath',$staticPath);	
		if(I('get.token')!=session('token')){$this->error('非法操作');}
		$data=D('Api');
		if(IS_POST){
			if($data->create()){
				if($data->where(array('token'=>session('token'),'uid'=>session('uid')))->save()!=false){
					$this->success('操作成功');					
				}else{
					$this->error('服务器繁忙，请稍候再试');
				}			
			}else{			
				$this->error($data->getError());
			}		
		}else{
			$this->error('非法操作');		
		
		}
	}

}