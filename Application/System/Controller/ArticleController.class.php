<?php
namespace System\Controller;
use Common\Controller\BackController;
class ArticleController extends BackController{

	public function index(){
		$img_db=D('Img');
		$wx_db=D('Wxuser');
		$count=$img_db->count();
		$page=new \Think\Page($count,25);
		$info=$img_db->limit($page->firstRow.','.$page->listRows)->select();
		foreach($info as $key=>$val){
			$where['token']=$val['token'];
			$where['uid']=$val['uid'];
			$res=$wx_db->where($where)->find();
			$info[$key]['wxname']=$res['wxname'];
			
		}
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();
	}
	
	public function del(){
		C('TOKEN_ON',false);
		$id=I('get.id',0,'intval');
		$pid=I('get.pid',0,'intval');
		if(D('Img')->delete($id)){
			$this->success('操作成功',U(MODULE_NAME.'/index',array('pid'=>$pid)));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index',array('pid'=>$pid)));
		}
	}
	
}