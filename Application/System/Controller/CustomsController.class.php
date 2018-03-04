<?php
namespace System\Controller;
use Common\Controller\BackController;
class CustomsController extends BackController{
	public function index(){
		$db = M('Customs');
		$fc = $db->where(array('agentid'=>'0','type'=>'1'))->find();
		$about = $db->where(array('agentid'=>'0','type'=>'2'))->find();
		$price = $db->where(array('agentid'=>'0','type'=>'3'))->find();
		$common = $db->where(array('agentid'=>'0','type'=>'4'))->find();
		$login = $db->where(array('agentid'=>'0','type'=>'5'))->find();
		$help = $db->where(array('agentid'=>'0','type'=>'6'))->find();
		$this->assign('fc',$fc);
		$this->assign('about',$about);
		$this->assign('price',$price);
		$this->assign('common',$common);
		$this->assign('login',$login);
		$this->assign('help',$help);
		$this->display();
	}
	public function edit(){
		$id=I('get.id',0,'intval');
		$this->assign('id',$id);
		$db=M('Customs');
		if(IS_POST){
			$cid=I('post.id');
			$data['name']=I('post.name');
			$data['url']=I('post.url');
			$data['open']=I('post.open',0,'intval');
			$data['dspl']=I('post.dspl',0,'intval');
			$data['type']=I('post.id',0,'intval');
			$cc=$db->where(array('agentid'=>'0','type'=>$cid))->find();
			S('zdydh',null);
			if($cc == ''){
				$tj=$db->add($data);
			}else{
				$tj=$db->where(array('agentid'=>'0','type'=>$cid))->save($data);
			}
			if($tj){
				$this->success('设置成功',U('Customs/index'));
				exit;
			}else{
				$this->error('设置失败');
			}
		}
		switch($id){
			case 1:
				$names='功能介绍';	
			break;
			case 2:
				$names='关于我们';
			break;
			case 3:
				$names='资费说明';
			break;
			case 4:
				$names='产品案例';
			break;
			case 5:
				$names='管理中心';
			break;
			case 6:
				$names='帮助中心';
			break;
		}
		$list=$db->where(array('agentid'=>'0','type'=>$id))->find();
		$this->assign('names',$names);
		$this->assign('list',$list);
		$this->display();
	}
}