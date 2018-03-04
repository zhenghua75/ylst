<?php 
namespace User\Controller;
use Common\Controller\UserController;
class RedpacketController extends UserController{
	public function _initialize() {
		parent::_initialize();
		$this->canUseFunction('Red_packet');
	}
	
	public function index(){
		$searchkey	= I('post.searchkey');
		$where 		= array('token'=>$this->token);
		if(!empty($searchkey)){
			$where['title|keyword'] = array('like','%'.$searchkey.'%');
		}
		
		$count	= M('Red_packet')->where($where)->count();
		$Page   = new \Think\Page($count,15);
		$list 	= M('Red_packet')->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $key=>$value){
			$log 	= M('Red_packet_log')->Distinct(true)->field('wecha_id')->where(array('token'=>$this->token,'packet_id'=>$value['id']))->select();
			$list[$key]['pcount']	= count($log);
		}
		$this->assign('list',$list);
		$this->assign('page',$Page->show());

		$this->display();
	}
	
	public function set(){
		$prize_db 		= M('Red_packet');
		$keyword_db		= M('Keyword');
		$where  		= array('token'=>$this->token,'id'=>I('get.id',0,'intval'));
		$packet_info 	= $prize_db->where($where)->find();
		

		if(IS_POST){
			if($prize_db->create()){
				$_POST['start_time'] 	= strtotime($_POST['start_time']);
				$_POST['end_time'] 		= strtotime($_POST['end_time']);
				//添加
				if(empty($packet_info)){
					$_POST['token'] 		= $this->token;
					$id = $prize_db->add($_POST);

					$this->handleKeyword($id,'Packet',I('post.keyword'));
					$this->success('添加成功',U('Redpacket/index',array('token'=>$this->token)));
					//修改
				}else{
					$swhere = array('token'=>$this->token,'id'=>I('post.id','intval'));
					$offset = $prize_db->where($swhere)->save($_POST);//更新设置表
					

					$this->handleKeyword(I('post.id','intval'),'Packet',I('post.keyword'));
					$this->success('修改成功',U('Redpacket/index',array('token'=>$this->token)));
				}
			}else{
		
				$this->error($prize_db->getError());
			}
		}else{
		
			$this->assign('set',$packet_info);
			$this->display();
		}

	}
	
	public function del(){
		$id 	= I('get.id','intval');
		$where 	= array('token'=>$this->token,'id'=>$id);
		
		if(M('Red_packet')->where($where)->delete()){
			M('Red_packet_prize')->where(array('token'=>$this->token,'packet_id'=>$id))->delete();
			M('Red_packet_log')->where(array('token'=>$this->token,'packet_id'=>$id))->delete();
			M('Red_packet_reward')->where(array('token'=>$this->token,'packet_id'=>$id))->delete();
			M('Keyword')->where(array('token'=>$this->token,'pid'=>$id,'module'=>'Packet'))->delete();
			$this->success('删除成功',U('Redpacket/index',array('token'=>$this->token)));
		}
		
	}
	
	
/*******（废）*******/

	
	public function prize_list(){
		$packet_id 	= I('get.id',0,'intval');
		$type		= I('post.type');
		$searchkey	= I('post.searchkey');
		$where 		= array('token'=>$this->token,'packet_id'=>$packet_id);
		if(!empty($searchkey)){
			$where['name'] = array('like','%'.$searchkey.'%');
		}
		if(!empty($type)){
			$where['type'] = $type;
		}
		$count	= M('Red_packet_prize')->where($where)->count();
		$Page   = new \Think\Page($count,15);
		$list 	= M('Red_packet_prize')->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		
		$this->assign('list',$list);
		$this->assign('page',$Page->show());
		
		
		$this->assign('packet_id',$packet_id);
		$this->display();
	}
	
	public function add_prize(){
		$prize_db 		= D('Red_packet_prize');
		$packet_id 		= I('get.packet_id',0,'intval');
		$id 			= I('get.id',0,'intval');
		$where  		= array('token'=>$this->token,'packet_id'=>$packet_id,'id'=>$id);
		$prize_info 	= $prize_db->where($where)->find();

		if(IS_POST){
			if($prize_db->create()){
				//添加
				if(empty($prize_info)){
					$_POST['token'] 		= $this->token;
					$_POST['packet_id']	 	= $packet_id;
					$id = $prize_db->add($_POST);
					
					$this->success('添加成功',U('Redpacket/prize_list',array('token'=>$this->token,'id'=>$packet_id)));
					//修改
				}else{
					$swhere = array('token'=>$this->token,'id'=>I('post.id','intval'));
					$offset = $prize_db->where($swhere)->save($_POST);//更新设置表

					$this->success('修改成功',U('Redpacket/prize_list',array('token'=>$this->token,'id'=>$packet_id)));
				}
			}else{
		
				$this->error($prize_db->getError());
			}
		}else{
			$this->assign('packet_id',$packet_id);
			$this->assign('info',$prize_info);
			$this->display();
		}
	}
	
	public function prize_del(){
		$packet_id 	= I('get.packet_id',0,'intval');
		$id 		= I('get.id',0,'intval');
		$where	 	= array('token'=>$this->token,'packet_id'=>$packet_id,'id'=>$id);	
		if(M('Red_packet_prize')->where($where)->delete()){
			$this->success('删除成功',U('Redpacket/prize_list',array('token'=>$this->token,'id'=>$packet_id)));
		}
	}
	
/*********/
	public function prize_log(){
		$packet_id 	= I('get.id',0,'intval');
		$prize_id 	= I('get.prize_id',0,'intval');
		$log_id 	= I('get.log_id');
		$where	 	= array('token'=>$this->token,'packet_id'=>$packet_id);
		$searchkey	= I('post.searchkey');
		$is_reward	= I('post.is_reward',0,'intval');
			
		if(!empty($searchkey)){
			$searchkey 	= M('Userinfo')->where(array('truename|wechaname'=>$searchkey))->getField('wecha_id');
			$where['wecha_id'] = $searchkey;
		}

		if(!empty($is_reward)){
			$where['is_reward'] = $is_reward;
		}
		
		if(!empty($prize_id)){
			$where['prize_id'] 	= $prize_id;
		}
	
		if(!empty($log_id)){
			$where['id'] 	= array('in',$log_id);
		}
		
		$count	= M('Red_packet_log')->where($where)->count();
		$Page   = new \Think\Page($count,20);
		$list 	= M('Red_packet_log')->where($where)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();

		foreach ($list as $key=>$value){
			$list[$key]['wxname'] 		= M('Userinfo')->where(array('wecha_id'=>$value['wecha_id']))->getField('wechaname');
		}
		
		$this->assign('list',$list);
		$this->assign('packet_id',$packet_id);
		$this->assign('page',$Page->show());
		$this->display();
	}
	

	public function log_del(){
		$packet_id 	= I('get.packet_id',0,'intval');
		$id 		= I('get.id',0,'intval');
		$where	 	= array('token'=>$this->token,'packet_id'=>$packet_id,'id'=>$id);	
		if(M('Red_packet_log')->where($where)->delete()){
			$this->success('删除成功',U('Redpacket/prize_log',array('token'=>$this->token,'id'=>$packet_id)));
		}
	}

	public function show_forms(){
		$id 	= I('get.id',0,'intval');
		$packet_id 	= I('get.packet_id',0,'intval');

		$where	= array('token'=>$this->token,'id'=>$id,'packet_id'=>$packet_id);
		$info 	= M('Red_packet_exchange')->where($where)->find();
		
		$info['wxname'] 	= M('Userinfo')->where(array('wecha_id'=>$info['wecha_id']))->getField('wechaname');
		
		$this->assign('info',$info);
		$this->display();
	}
	
	public function is_ok(){
		$id 	= I('get.id',0,'intval');
		$packet_id 	= I('get.packet_id',0,'intval');
		$where 		= array('token'=>$this->token,'id'=>$id,'packet_id'=>$packet_id);

		$result 	= array();
			
		M('Red_packet_exchange')->where($where)->save(array('status'=>'1'));
		
		$result['err'] 	= 0;
		$result['info'] = '操作成功！';

		echo json_encode($result);
	}
	
	public function exchange(){
		$packet_id 	= I('get.id',0,'intval');
		$where	 	= array('token'=>$this->token,'packet_id'=>$packet_id);
		
		$type 		= I('post.type',0,'intval');
		$status 	= I('post.status');
		$searchkey 	= I('post.searchkey');
		
		if(!empty($type)){
			$where['type'] 	= $type;
		}
		if($status != ''){
			$where['status'] = intval($status);
		}
		if(!empty($searchkey)){
			$searchkey 	= M('Userinfo')->where(array('truename|wechaname'=>$searchkey))->getField('wecha_id');
			$where['wecha_id'] = $searchkey;
		}

		$count	= M('red_packet_exchange')->where($where)->count();
		$Page   = new \Think\Page($count,20);
		$list 	= M('red_packet_exchange')->where($where)->order('status asc,time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		
		foreach ($list as $key=>$value){
			$list[$key]['wxname'] 		= M('Userinfo')->where(array('wecha_id'=>$value['wecha_id']))->getField('wechaname');
		}
		
		$this->assign('list',$list);
		$this->assign('packet_id',$packet_id);
		$this->assign('page',$Page->show());
		$this->display();
	}	
	
	public function change_del(){
		$packet_id 	= I('get.packet_id',0,'intval');
		$id 		= I('get.id',0,'intval');
		$where	 	= array('token'=>$this->token,'packet_id'=>$packet_id,'id'=>$id);	
		if(M('red_packet_exchange')->where($where)->delete()){
			$this->success('删除成功',U('Redpacket/exchange',array('token'=>$this->token,'id'=>$packet_id)));
		}
	}
}