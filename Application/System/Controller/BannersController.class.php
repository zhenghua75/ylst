<?php
namespace System\Controller;
use Common\Controller\BackController;
class BannersController extends BackController{
	public function index(){
		$db=D('Banners');
		$where='';
		S('banners',null);
		if (!C('agent_version')){
			$case=$db->where('status=1')->limit(32)->select();
		}else {
			$case=$db->where('status=1 AND agentid=0')->limit(32)->select();
			$where=array('agentid'=>0);
		}
		S('banners',$banners);
		$count=$db->where($where)->count();
		$page=new \Think\Page($count,25);
		$info=$db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id DESC')->select();
		$this->assign('info',$info);
		$this->assign('page',$page->show());
		$pid=I('get.pid',0,'intval');
		$this->assign('pid',$pid);
		$this->display();
	}
	
	public function edit(){
		$id=I('get.id',0,'intval');
		$pid=I('get.pid',0,'intval');
		$info=D('Banners')->find($id);
		$this->assign('info',$info);
		$this->assign('pid',$pid);
		$this->display('add');
	}
	
	public function add(){
		$pid=I('get.pid',0,'intval');
		$this->assign('pid',$pid);
		$this->display();
	}
	
	public function insert(){
		$db=D('Banners');
		$pid=I('post.pid',0,'intval');
		if($db->create()){
			if($db->add()){
				$this->success('操作成功',U('Banners/index',array('pid'=>$pid,'level'=>3)));
			}else{
				$this->error('操作失败',U('Banners/index',array('pid'=>$pid,'level'=>3)));
			}
		}else{
			$this->error('操作失败',U('Banners/index',array('pid'=>$pid,'level'=>3)));
		}
	}
	
	public function upsave(){
		$db= D('Banners');
		$pid=I('post.pid',0,'intval');
		if($db->create()){
			if($db->save()){
				$this->success('操作成功',U('Banners/index',array('pid'=>$pid,'level'=>3)));
			}else{
				$this->error('操作失败',U('Banners/index',array('pid'=>$pid,'level'=>3)));
			}
		}else{
			$this->error('操作失败',U('Banners/index',array('pid'=>$pid,'level'=>3)));
		}
	}
	
	public function del(){
		$id=I('get.id',0,'intval');
		$pid=I('get.pid',0,'intval');
		$db=D('Banners');
		if($db->delete($id)){
			$this->success('操作成功',U(MODULE_NAME.'/index',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index',array('pid'=>$pid,'level'=>3)));
		}
	}
}