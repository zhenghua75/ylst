<?php
namespace System\Controller;
use Common\Controller\BackController;
class RecordsController extends BackController{
	public function index(){
		$records=M('indent');
		//$db=M('Users');
		$count=$records->count();
		$page=new \Think\Page($count,25);
		$show= $page->show();
		$info=$records->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
		if ($info){
			$i=0;
			foreach ($info as $item){
				if (!$info[$i]['uname']){
					$thisUser=M('Users')->where(array('id'=>$item['uid']))->find();
					$info[$i]['uname']=$thisUser['username'];
				}
				$i++;
			}
		}
		$this->assign('page',$show);
		$this->assign('info',$info);
		$this->display();
	}
	public function send(){
		$money=I('get.price',0,'intval');
		$data['id']=I('get.uid',0,'intval');
		if($money!=false&&$data['id']!=false){
			$thisR=M('Indent')->where(array('id'=>I('get.iid',0,'intval')))->find();
			if ($thisR['status']!=2){
				$status=M('Indent')->where(array('id'=>I('get.iid',0,'intval')))->setField('status',2);
				if($status!=false){
					$this->success('充值成功');
				}else{
					$this->error('充值失败');
				}
			}else {
				$this->error('已经入金过了');
			}
		}else{
			$this->error('非法操作');
		}
	}
}