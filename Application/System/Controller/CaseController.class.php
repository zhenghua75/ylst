<?php
namespace System\Controller;
use Common\Controller\BackController;
class CaseController extends BackController{
	public function index(){
		$db=D('Case');
		$where='';
		S('case',null);
		if (!C('agent_version')){
			$case=$db->where('status=1')->limit(32)->select();
		}else {
			$case=$db->where('status=1 AND agentid=0')->limit(32)->select();
			$where=array('agentid'=>0);
		}
		
		S('case',$case);
		$count=$db->where($where)->count();
		$page=new \Think\Page($count,25);
		$info=$db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id DESC')->select();
		$this->assign('info',$info);
		$this->assign('page',$page->show());
		$pid=I('get.pid',0,'intval');
		$this->assign('pid',$pid);
		$this->display();
	}
	public function add(){
		$db=M('Caseclass');
		$data=$db->where(array('agentid'=>'0'))->find();
		if($data == ''){
			$this->error('请先添加分类');
		}
		$pid=I('get.pid',0,'intval');
		$where='';
		$list=$db->where($where)->select();
		$this->assign('list',$list);
		$this->assign('pid',$pid);
		$this->display();
	}
	
	public function edit(){
		$id=I('get.id',0,'intval');
		$pid=I('get.pid',0,'intval');
		$info=D('Case')->find($id);
		$this->assign('info',$info);

		$db=M('Caseclass');
		$where='';
		$list=$db->where($where)->select();
		$this->assign('pid',$pid);
		$this->assign('list',$list);
		$this->display('add');
	}
	
	public function del(){
		$db=D('Case');
		$pid=I('get.pid',0,'intval');
		$id=I('get.id',0,'intval');
		if($db->delete($id)){
			$this->success('操作成功',U(MODULE_NAME.'/index',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index',array('pid'=>$pid,'level'=>3)));
		}
	}
	
	public function insert(){
		$thumb['width']='48';
		$thumb['height']='48';
		$db=D('Case');
		$id=I('post.class');
		$pid=I('post.pid',0,'intval');
		$dbs=M('Caseclass');
		$list=$dbs->where(array('id'=>$id,'agentid'=>'0'))->find();
		if($list == ''){
			$this->error('请选择分类');
		}
		$data['classid']=$list['name'];
		$data['name']=I('post.name');
		$data['class']=I('post.class');
		$data['timg']=I('post.timg');
		$data['img']=I('post.img');
		$data['url']=I('post.url');
		$data['status']=I('post.status');

		if($db->add($data)){
			$this->success('操作成功',U('Case/index',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U('Case/index',array('pid'=>$pid,'level'=>3)));
		}
	}
	
	public function upsave(){
		$db=D('Case');
		$id=I('post.class');
		$pid=I('post.pid',0,'intval');
		$cid=I('post.id');

		$dbs=M('Caseclass');
		$list=$dbs->where(array('id'=>$id))->find();
		$data['classid']=$list['name'];
		$data['name']=I('post.name');
		$data['class']=I('post.class');
		$data['timg']=I('post.timg');
		$data['img']=I('post.img');
		$data['url']=I('post.url');
		$data['status']=I('post.status');
		$slet = $db->where(array('id'=>$cid))->save($data);
		if($slet){
			$this->success('操作成功',U('Case/index',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U('Case/index',array('pid'=>$pid,'level'=>3)));
		}
	}
	
	//案例分类
	public function addclass(){
		$id=I('get.pid');
		$this->assign('id',$id);
		$this->display();
	}

	public function adds(){
		$db=M('Caseclass');
		$id=I('post.pid');
		$name=I('post.name');
		$data=$db->where(array('name'=>$name))->find();
		if($data != ''){
			$this->error('已有此分类',U('Case/addclass'));
		}
		$list=$db->add($_POST);
		if($list){
			$this->success('操作成功',U('Case/indexs',array('pid'=>$id,'level'=>3)));
		}else{
			$this->error('操作失败',U('Case/addclass',array('pid'=>$id,'level'=>3)));
		}
	}

	public function upsaves(){
		$db=M('Caseclass');
		$id=I('post.id',0,'intval');
		$pid=I('post.pid',0,'intval');
		$list=$db->where(array('id'=>$id))->save($_POST);
		if($list){
			$dbs=D('Case');
			$data['classid'] = I('post.name');
			$dbs->where(array('class'=>$id))->save($data);
			$this->success('操作成功',U('Case/indexs',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U('Case/edits',array('pid'=>$pid,'id'=>$id)));
		}	
	}

	public function indexs(){
		$pid=I('get.pid',0,'intval');
		$db=M('Caseclass');
		$where='';
		$list=$db->where($where)->select();
		$this->assign('list',$list);
		$this->assign('pid',$pid);
		$this->display();
	}

	public function edits(){
		$id=I('get.pid',0,'intval');
		$pid=I('get.id',0,'intval');
		$info=D('Caseclass')->find($pid);
		$this->assign('info',$info);
		$this->assign('id',$id);
		$this->display('addclass');
	}
	
	public function dels(){
		$db=D('Caseclass');
		$pid=I('get.pid',0,'intval');
		$id=I('get.id',0,'intval');
		if($db->delete($id)){
			$dbs=D('Case');
			$data['classid'] = '';
			$data['class'] ='0';
			$dbs->where(array('class'=>$id))->save($data);
			$this->success('操作成功',U(MODULE_NAME.'/indexs',array('pid'=>$pid,'level'=>3)));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/indexs',array('pid'=>$pid,'level'=>3)));
		}
	}
}