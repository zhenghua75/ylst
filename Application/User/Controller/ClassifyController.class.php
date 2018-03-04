<?php
namespace User\Controller;
use Common\Controller\UserController;
class ClassifyController extends UserController{
	public $fid;
	public function _initialize() {
		parent::_initialize();
		$this->fid=intval($_GET['fid']);
		$this->assign('fid',$this->fid);
		if ($this->fid){
			$thisClassify=M('Classify')->find($this->fid);
			$this->assign('thisClassify',$thisClassify);
		}
	}
	public function index(){
		$db=D('Classify');
		$zid=$db->where(array('id'=>I('get.fid'),'token'=>$this->token))->getField('fid');
		$where['token']=session('token');
		$where['fid']=intval($_GET['fid']);
		$count=$db->where($where)->count();
		$page=new \Think\Page($count,25);
		$info=$db->where($where)->order('sorts desc')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->assign('zid',$zid);
		$this->display();
	}

	public function add(){
		//include('./PigCms/Lib/ORG/index.Tpl.php');
		//include('./PigCms/Lib/ORG/cont.Tpl.php');

		$tpl = C('ClassifyTpls');
		$contTpl =C('ContentTpls');

		$this->assign('tpl',$tpl);
		$this->assign('contTpl',$contTpl);

		$group_list = explode(',',C('APP_GROUP_LIST'));
		if(in_array('Web',$group_list) !== false){
			$this->assign('has_website',true);
		}
			
		$this->display();
	}

	public function edit(){
		$id=I('get.id',0,'intval');
		$info=M('Classify')->find($id);
		// include('./PigCms/Lib/ORG/index.Tpl.php');
		// include('./PigCms/Lib/ORG/cont.Tpl.php');
		$tpl = C('ClassifyTpls');
		$contTpl =C('ContentTpls');
		
		foreach($tpl as $k=>$v){
			if($v['tpltypeid'] == $info['tpid']){
				$info['tplview'] = $v['tplview'];
			}
		}
		foreach($contTpl as $key=>$val){
			if($val['tpltypeid'] == $info['conttpid']){
				$info['tplview2'] = $val['tplview'];
			}
		}
		$this->assign('contTpl',$contTpl);
		$this->assign('tpl',$tpl);
		$this->assign('info',$info);
		$this->display();
	}
	
	public function del(){
		$where['id']=I('get.id',0,'intval');
		$where['uid']=session('uid');
		if(D(MODULE_NAME)->where($where)->delete()){
			$fidwhere['fid']=intval($where['id']);
			D(MODULE_NAME)->where($fidwhere)->delete();
			$this->success('操作成功',U(MODULE_NAME.'/index',array('fid'=>$_GET['fid'])));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index',array('fid'=>$_GET['fid'])));
		}
	}
	//
	public function insert(){
	    $name='Classify';
		$db=D($name);
		$fid = I('post.fid',0,'intval');
		// 处理url 2015-05-22
		if ($this->dwzQuery(array('tinyurl' => $_POST['url']))) {
			$this->error('禁止使用短网址');
		}
		$_POST['url'] = $this->replaceUrl($_POST['url'], array('query'=>array('wecha_id'=>'{wechat_id}')));
		$_POST['info'] = str_replace('&quot;','',$_POST['info']);
		if($fid != ''){
			$f = $db->field('path')->where("id = $fid")->find();
			$_POST['path'] = $f['path'].'-'.$fid;
				
		}
		if($_POST['pc_show']){
			$database_pc_news_category = D('Pc_news_category');
			$data_pc_news_category['cat_name'] = $_POST['name'];
			$data_pc_news_category['token'] = session('token');
			$_POST['pc_cat_id'] = $database_pc_news_category->data($data_pc_news_category)->add();
		}
		if($db->create()===false){
			$this->error($db->getError());
		}else{
			
			$id=$db->add();
			if($id){
				$this->success('操作成功',U(MODULE_NAME.'/index',array('fid'=>$_POST['fid'])));
			}else{
				$this->error('操作失败',U(MODULE_NAME.'/index',array('fid'=>$_POST['fid'])));
			}
		}
	}
	public function upsave(){
		// 处理url 2015-05-22
		if ($this->dwzQuery(array('tinyurl' => $_POST['url']))) {
			$this->error('禁止使用短网址');
		}
		$_POST['url'] = $this->replaceUrl($_POST['url'], array('query'=>array('wecha_id'=>'{wechat_id}')));
		$_POST['info'] = str_replace('&quot;','',$_POST['info']);
		$fid = I('post.fid','intval');
		if($_POST['pc_show']){
			$_POST['pc_cat_id'] = 0;
		}
		if($fid == ''){
			$this->all_save();
		}else{
			$this->all_save('','/index?fid='.$fid);
		}
	}
	
	
	public function chooseTpl(){
	
		// include('./PigCms/Lib/ORG/index.Tpl.php');
		// include('./PigCms/Lib/ORG/cont.Tpl.php');
		$tpl = C('ClassifyTpls');

		$contTpl =C('ContentTpls');
		$tpl = array_reverse($tpl);

		$filter = I('get.filter','');
		if($filter!=='' && $filter !== 'all' && $filter != 'mix'){
			foreach ($tpl as $kk => $vv){
				if(strpos($vv['attr'],$filter)){
					$filterTpl[$kk] = $vv;
				}
			}
			$tpl = $filterTpl;
		}		
		$contTpl = array_reverse($contTpl);
		$tpid = I('get.tpid',0,'intval');

		foreach($tpl as $k=>$v){
			$sort[$k] = $v['sort'];
			$tpltypeid[$k] = $v['tpltypeid'];
			
			if($v['tpltypeid'] == $tpid){
				$info['tplview'] = $v['tplview'];
				$info['tpl_user'] = $v['user'];
			}
		}
		foreach($contTpl as $key=>$val){
			if($val['tpltypeid'] == $tpid){
				$info['tplview2'] = $val['tplview'];
				$info['cont_user'] = $val['user'];
			}
		}
		$this->assign('info',$info);
		$this->assign('contTpl',$contTpl);
		$this->assign('tpl',$tpl);

		$this->display();
	}
	
	public function changeClassifyTpl(){
	
		$tid = I('post.tid',0,'intval');
		$cid = I('post.cid',0,'intval');
		M('Classify')->where(array('token'=>$this->token,'id'=>$cid))->setField('tpid',$tid);
		echo 200;
	}
	
	public function changeClassifyContTpl(){
	
		$tid = I('post.tid',0,'intval');
		$cid = I('post.cid',0,'intval');
		M('Classify')->where(array('token'=>$this->token,'id'=>$cid))->setField('conttpid',$tid);
		echo 200;
	
	}
	public function flash(){
		$tip=I('get.tip',0,'intval');
		$id=I('get.id',0,'intval');
		$fid=I('get.fid',0,'intval');
		if(empty($fid)){
			$fid=0;
		}
		$token=$this->token;

		$fl=M('Classify')->where(array('token'=>$this->token,'id'=>$id,'fid'=>$fid))->find();
		$db=D('Flash');

		$where['uid']=session('uid');
		$where['token']=session('token');
		$where['tip']=$tip;
		$where['did']=$id;
		$where['fid']=$fid;

		$count=$db->where($where)->count();
		$page=new \Think\Page($count,25);
		$info=$db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id DESC')->select();
		$this->assign('page',$page->show());
		$this->assign('fl',$fl);
		$this->assign('info',$info);
		$this->assign('id',$id);
		$this->assign('fid',$fid);
		$this->assign('tip',$tip);
		$this->display();
	}

	public function addflash(){
		$tip=I('get.tip',0,'intval');
		$id=I('get.id',0,'intval');
		$fid=I('get.fid',0,'intval');
		$token=$this->token;
		$fl=M('Classify')->where(array('token'=>$this->token,'id'=>$id))->getField('name');
		$this->assign('fl',$fl);
		$this->assign('tip',$tip);
		$this->assign('id',$id);
		$this->assign('fid',$fid);
		$this->display();
	}

	public function inserts(){
		$fid = I('get.fid',0,'intval');
		if($fid == null){
			$fid = 0;
		}
		$flash=D('Flash');
		$arr=array();
		$arr['token']=$this->token;
		$arr['img']=I('post.img');
		if (I('post.url')){
		$arr['url']=I('post.url');
		}
		$arr['info']=I('post.info');
		$arr['tip']=I('get.tip',0,'intval');
		$arr['did']=I('get.id',0,'intval');
		$arr['fid']=$fid;
		if(empty($_POST['img'])){
			$this->error('请先添加图片');
		}
		if($flash->add($arr)){
			$this->success('操作成功',U(MODULE_NAME.'/flash',array('tip'=>I('get.tip','intval'),'id'=>I('get.id'),'fid'=>I('get.fid'))));
		}else{
			$this->error('操作失败');
		}
		
	}

	public function editflash(){
		$tip=I('get.tip',0,'intval');
		$where['id']=I('get.id',0,'intval');
		$where['uid']=session('uid');
		$res=D('Flash')->where($where)->find();
		$this->assign('info',$res);

		$this->assign('tip',$tip);
		$this->assign('id',I('get.id','intval'));
		$this->display();
	}

	public function delflash(){
		$where['id']=I('get.id',0,'intval');
		$where['token']=$this->token;
		if(D('Flash')->where($where)->delete()){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}

	public function updeit(){
		$flash=D('Flash');
		$id=I('get.id',0,'intval');
		$tip=I('get.tip',0,'intval');
		$list=$flash->where(array('id'=>$id))->find();
		$arr=array();
		$arr['img']=I('post.img');
		$arr['url']=I('post.url');
		$arr['info']=I('post.info');
		$data=$flash->where(array('id'=>$id))->save($arr);
		if($data){
			$this->success('操作成功',U(MODULE_NAME.'/flash',array('tip'=>$tip,'id'=>$list['did'],'fid'=>$list['fid'])));
		}else{
			$this->error('操作失败');
		}
		
	}
	
	public function essay(){
		$token=$this->token;
		$classid=I('get.id',0,'intval');
		$name=M('Classify')->where(array('id'=>$classid,'token'=>$token))->getField('name');
		$essay=M('Img')->where(array('classid'=>$classid,'token'=>$token))->order('usort DESC')->select();
		$this->assign('info',$essay);
		$this->assign('name',$name);
		$this->display();
	}
	
	public function editUsort(){
		$token = I('post.token');
		unset($_POST['__hash__']);
		foreach($_POST as $k=>$v){
			$k = str_replace('usort','',$k);
			$data[$k]=$v;
			M('Img')->where(array('token'=>$token,'id'=>$k))->setField('usort',$v);
		}
		
		$this->success('保存成功');
	}
}