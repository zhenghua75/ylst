<?php
namespace User\Controller;
use Common\Controller\UserController;
class InviteController extends UserController{
	public function _initialize(){
		parent::_initialize();
		$this->canUseFunction('Invite');
	}

	public function add(){
		$Pig=M('Invite');
		session('yid',null);
		$where['token']=session('token');
		$count=$Pig->where($where)->count();
		$page=new \Think\Page($count,25);
		$list=$Pig->where($where)->limit($page->firstRow.','.$page->listRows)->order('id')->select();
		$this->assign('page',$page->show());
		$this->assign('pig',$list);
		$this->display();
	 }



	public function listdel(){
		$id = I('get.id');
		$Invite = M('Invite');
		$invidel = $Invite->where("id='$id'")->delete();
		$this->handleKeyword(intval($_GET['id']),'Invite','','',1);

		$Enroll = M('Invite_enroll');
		$Enroll->where("yid='$id'")->delete();

		$Meeting = M('Invite_meeting');
		$Meeting->where("yid='$id'")->delete();

		$Partner = M('Invite_partner');
		$Partner->where("yid='$id'")->delete();

		$User = M('Invite_user');
		$User->where("yid='$id'")->delete();

		if ($invidel) {
			$this->success('操作成功');				
		}else{
			$this->error('服务器繁忙或信息没有修改!');
		}
	}


    public function index(){		
     	$token = $data['token'] = $this->token;
    	if (IS_POST) {
    		$Pig = M("Invite");
			$data['keyword'] = I('post.keyword');
			$data['title'] = I('post.title');
			$data['replypic'] = I('post.replypic');
			$data['content'] = I('post.content');
			$data['cover'] = I('post.cover');
			$data['inback'] = I('post.inback');
			$data['photo'] = I('post.photo');
			$data['meetpic'] = I('post.meetpic');
			$data['linkman'] = I('post.linkman');
			$data['site'] = I('post.site');
			foreach ($data as $value){
				   if($value==""){
				    $this->error('带 <font color="red">*</font> 的必须填');
				     }	
				 }
			$data['twopic'] = I('post.twopic');
			$data['warn'] = I('post.warn');
			$data['email'] = I('post.email');
			$data['theme'] = I('post.theme');
			$data['themeurl'] = I('post.themeurl');
			$id = I('get.yid')?I('get.yid'):session('yid');
			if ($Pig->where(array('token'=>"$token",'id'=>"$id"))->select()) {
				$rel=$Pig->where(array('token'=>"$token",'id'=>"$id"))->save($data);
				$this->handleKeyword($id,'Invite',I('post.keyword'));
				if($rel){
					$this->success('操作成功',U('Invite/user',array('token'=>$token,'yid'=>$id)));				
				}else{
					$this->error('服务器繁忙或信息没有修改!');
				}
			}else{



				$rel=$Pig->add($data);

				$this->handleKeyword(intval($rel),'Invite',I('post.keyword'));

				if($rel){
					$Pig = M("Invite");
					$yid = $Pig->order('id desc')->limit(1)->getField('id');
					session('yid',"$id");
					$this->success('操作成功',U('Invite/user',array('token'=>$token,'yid'=>$yid)));
				}else{
					$this->error('服务器繁忙或信息没有修改!');
				}
			}
 		}else{
			$id = I('get.yid')?I('get.yid'):session('yid');
			if ($id) {
				$Pig = M("Invite");
				$Invite = $Pig->where(array('token'=>"$token",'id'=>"$id"))->find();
				$this->assign('Invite',$Invite);
				$this->assign('yid',$id);
			}
			$this->assign('tabid',1);
    		$this->display();
    	}
    }



    public function user(){
    	$token = $data['token'] = $this->token;
    	if (IS_POST) {
    		$Pig = M("Invite_user");
			$id = $data['yid'] = I('get.yid');
			$data['headpic'] = I('post.headpic');
			$data['username'] = I('post.username');
			$data['position'] = I('post.position');
			$data['synopsis'] = I('post.synopsis');
			foreach ($data as $value){
				   if($value==""){
				    $this->error('数据不能为空');
				     }	
				 }
			$rel=$Pig->add($data);
				if($rel){
					$this->success('操作成功');				
				}else{
					$this->error('服务器繁忙或信息没有修改!');
				}
			
    	}else{
			$id = I('get.yid')?I('get.yid'):session('yid');
			if ($id) {
				$Pig = M("Invite_user");
	    		$list = $Pig->where(array('token'=>"$token",'yid'=>"$id"))->select();
				$this->assign('list',$list);
				$this->assign('yid',$id);
			}else{
				if(session('yid') == false){
					$this->error('请先填写配置信息');			
				}
			}
			$this->assign('tabid',2);
    		$this->display();
    	}
    }



    public function userdel(){
    	$id = I('get.id');
    	$Pig = M("Invite_user");
    	$rel = $Pig->where("id='$id'")->delete();
    	if($rel){
			$this->success('操作成功');				
		}else{
			$this->error('服务器繁忙或信息没有修改!');
		}
    }



    public function meeting(){
    	$token = $data['token'] = $this->token;
    	if (IS_POST) {
    		$Pig = M("Invite_meeting"); 
    		$data['yid'] = I('get.yid');
			$data['time'] = strtotime(I('post.time'));
			$data['ytime'] = strtotime(I('post.ytime'));	
			$data['xtime'] = strtotime(I('post.xtime'));
			if ($data['ytime']>$data['xtime']) {
				$this->error('时间先后顺序有问题');
			}
			$data['guest'] = I('post.guest');
			$data['content'] = I('post.content');
			$data['call'] = I('post.call');
			$data['site'] = I('post.site');
			foreach ($this->_post as $value){
				   if($value==""){
				    $this->error('数据不能为空');
				     }
				 }	
			$rel=$Pig->add($data);
				if($rel){
					$this->success('操作成功');				
				}else{
					$this->error('服务器繁忙或信息没有修改!');
				}
    	}else{
    		$id = I('get.yid')?I('get.yid'):session('yid');
			if ($id) {
				$Pig = M("Invite_meeting");
	    		$list = $Pig->where(array('token'=>"$token",'yid'=>"$id"))->select();
				$this->assign('list',$list);
				$this->assign('yid',$id);
			}else{
				if(session('?yid') == false){
					$this->error('请先填写配置信息');			
				}
			}
			$this->assign('tabid',3);
    		$this->display();
    	}
    }



    public function meetdel(){
    	$id = I('get.id');
    	$Pig = M("Invite_meeting");
    	$rel = $Pig->where("id=$id")->delete();
    	if($rel){
			$this->success('操作成功');				
		}else{
			$this->error('服务器繁忙或信息没有修改!');
		}
    }



    public function partner(){
    	$token = $data['token'] = $this->token;
    	if (IS_POST) {
			$Pig = M("Invite_partner");
			$data['partnertype'] = I('post.partnertype');	
			$data['typepic'] = I('post.typepic');	
			$data['remark'] = I('post.remark');	
			$data['company'] = I('post.company');	
			$data['contact'] = I('post.contact');	
			$data['photo'] = I('post.photo');	
			$data['qq'] = I('post.qq');	
			$data['scheme'] = I('post.scheme');
    		$data['yid'] = I('get.yid');
			foreach ($this->_post as $value){
				if($value==""){
				$this->error('数据不能为空');
				}	
			}
			$rel=$Pig->add($data);
				if($rel){
					$this->success('操作成功');
				}else{
					$this->error('服务器繁忙或信息没有修改!');
				}
    	}else{
    		$id = I('get.yid')?I('get.yid'):session('yid');
			if ($id) {
	    		$Pig = M("Invite_partner");
	    		$list = $Pig->where(array('token'=>"$token",'yid'=>"$id"))->select();
				$this->assign('list',$list);
				$this->assign('yid',$id);
			}else{
				if(session('?yid') == false){
					$this->error('请先填写配置信息');			
				}
			}
    		$this->assign('tabid',5);
    		$this->display();
    	}
    }



    public function pardel(){
    	$id = I('get.id');
    	$Pig = M("Invite_partner");
    	$rel = $Pig->where("id='$id'")->delete();
    	if($rel){
			$this->success('操作成功');				
		}else{
			$this->error('服务器繁忙或信息没有修改!');
		}
    }



    public function enroll(){	
    	$id = I('get.yid')?I('get.yid'):session('yid');
    	$token = $this->token;
			if ($id) {
    			$Pig = M("Invite_enroll");
	    		$list = $Pig->where(array('token'=>"$token",'yid'=>"$id"))->select();
				$this->assign('list',$list);
				$this->assign('yid',$id);
			}
		$this->assign('tabid',7);
		$this->display();
    }



    public function enrdel(){
    	$id = I('get.id');
    	$Pig = M("Invite_enroll");
    	$rel = $Pig->where("id=$id")->delete();
    	if($rel){
			$this->success('操作成功');				
		}else{
			$this->error('服务器繁忙或信息没有修改!');
		}
    }
}