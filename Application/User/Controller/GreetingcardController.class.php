<?php
namespace User\Controller;
use Common\Controller\UserController;
class GreetingcardController extends UserController{
	//喜帖配置
	public function index(){
		$Greetingcard=M('Greetingcard');
		$where['token']=session('token');
		$count=$Greetingcard->where($where)->count();
		$page=new \Think\Page($count,25);
		$list=$Greetingcard->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('Greetingcard',$list);
		$this->display();
	}
	public function add(){
		if(IS_POST){
			$this->all_insert('Greetingcard','/index');
		}else{
			$photo=M('Photo')->where(array('token'=>session('token')))->select();
			$this->assign('photo',$photo);
			$this->display();
		}
	}
	public function edit(){
		$Greetingcard=M('Greetingcard')->where(array('token'=>session('token'),'id'=>I('get.id',0,'intval')))->find();
		if(IS_POST){
			$_POST['id']=$Greetingcard['id'];
			$this->all_save('Greetingcard','/index');	
		}else{
			
			$this->assign('Greetingcard',$Greetingcard);
			$this->display('add');
		}
	
	}
	public function del(){
		$where['id']=I('get.id',0,'intval');
		$where['token']=session('token');
		if(D('Greetingcard')->where($where)->delete()){
			$this->success('操作成功',U(MODULE_NAME.'/index'));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index'));
		}
	}
}