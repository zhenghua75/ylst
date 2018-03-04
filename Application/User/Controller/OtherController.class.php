<?php
namespace User\Controller;
use Common\Controller\UserController;
class OtherController extends UserController{
	//配置
	public function index(){
		$other=M('Other')->where(array('token'=>session('token')))->find();
		if(IS_POST){
			if($other==false){				
				$this->all_insert('Other','/index');
			}else{
				$_POST['id']=$other['id'];
				$this->all_save('Other','/index');				
			}
		}else{
			$this->assign('other',$other);
			$this->display();
		}
	}
	
}