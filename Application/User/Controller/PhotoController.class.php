<?php
namespace User\Controller;
use Common\Controller\UserController;
class PhotoController extends UserController{

	public function index(){
		$reply_info_db=M('Reply_info');
		$config=$reply_info_db->where(array('token'=>$this->token,'infotype'=>'album'))->find();
		if ($config){
			$headpic=$config['picurl'];
		}else {
			$headpic='/tpl/Wap/default/common/css/Photo/banner.jpg';
		}
		$this->assign('headpic',$headpic);
		//
		$this->canUseFunction('album');
		//相册列表
		$data=M('Photo');
		$count      = $data->where(array('token'=>$_SESSION['token']))->count();
		$Page       = new \Think\Page($count,12);
		$show       = $Page->show();
		$list = $data->where(array('token'=>$_SESSION['token']))->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $k=>$v){
			$list[$k]['num'] = M('photo_list')->where(array('token'=>$v['token'],'pid'=>$v['id']))->count();
		}
		$this->assign('page',$show);		
		$this->assign('photo',$list);
		$this->display();		
	}
	public function config(){
		$reply_info_db=M('Reply_info');
		$config=$reply_info_db->where(array('token'=>$this->token,'infotype'=>'album'))->find();
		$configArr=array();
		$configArr['title']='相册';
		$configArr['info']='';
		$configArr['picurl']=I('post.picurl');
		$configArr['token']=$this->token;
		$configArr['apiurl']='';
		$configArr['infotype']='album';
		//
		if ($config){
			$reply_info_db->where(array('token'=>$this->token,'infotype'=>'album'))->save($configArr);
		}else {
			$reply_info_db->add($configArr);
		}
		$this->success('操作成功');
	}
	public function edit(){
		if(I('get.token')!=session('token')){$this->error('非法操作');}
		$data=D('Photo');
		if(IS_POST){
			$this->all_save('Photo');
		}else{
			$photo=$data->where(array('token'=>session('token'),'id'=>I('get.id')))->find();
			if($photo==false){
				$this->error('相册不存在');
			}else{
				$this->assign('photo',$photo);
			}
			$this->display();		
		
		}
	}
	public function list_edit(){
		if(I('get.token')!=session('token')){$this->error('非法操作');}
		$check=M('Photo_list')->field('id,pid')->where(array('token'=>$_SESSION['token'],'id'=>I('post.id')))->find();
		if($check==false){$this->error('照片不存在');}
		if(IS_POST){
			$this->all_save('Photo_list','/list_add?id='.$check['pid']);		
		}else{
			$this->error('非法操作');
		}
	}
	public function list_del(){
		if(I('get.token')!=session('token')){$this->error('非法操作');}
		$check=M('Photo_list')->field('id,pid')->where(array('token'=>$_SESSION['token'],'id'=>I('get.id')))->find();
		if($check==false){$this->error('服务器繁忙');}
		if(empty($_POST['edit'])){
			if(M('Photo_list')->where(array('id'=>$check['id']))->delete()){
				M('Photo')->where(array('id'=>$check['pid']))->setDec('num');
				$this->success('操作成功');
			}else{
				$this->error('服务器繁忙,请稍后再试');
			}
		}
	}
	public function list_add(){
		
		$checkdata=M('Photo')->where(array('token'=>$_SESSION['token'],'pid'=>I('get.pid')))->find();
		if($checkdata==false){$this->error('相册不存在');}
		if(IS_POST){
			unset($_POST['s']);
			
			$pid = (int)$_POST['pid'];
			unset($_POST['__hash__']);
			unset($_POST['pid']);
			$photo_list = M('Photo_list');

			
			foreach($_POST as $k=>$v){
				$kArr = explode('_',$k);//1.title;2.2;1.sort;2.2
				$arr[$kArr[1]][$kArr[0]] = $v;
			}
			foreach($arr as $key=>$val){
				if(!array_key_exists('status',$val)){
					$arr[$key]['status'] = '0';
				}
				if($arr[$key]['title'] == '') $arr[$key]['title'] = '12345';
				$arr[$key]['pid'] = $pid;
				$arr[$key]['token'] = $this->token;
				$arr[$key]['create_time'] = time();
				$photo_list->add($arr[$key]);
			}

			M('Photo')->where(array('token'=>session('token'),'id'=>$pid))->setInc('num',count($arr));
			
			$this->success('保存成功');
			
			
		}else{
			$data=M('Photo_list');
			$count      = $data->where(array('token'=>$_SESSION['token'],'pid'=>I('get.pid')))->count();
			$Page       = new \Think\Page($count,120);
			$show       = $Page->show();
			$list = $data->where(array('token'=>$_SESSION['token'],'pid'=>I('get.id')))->order('sort desc')->limit($Page->firstRow.','.$Page->listRows)->select();	
		//upyun多文件上传
		
			$bucket = UNYUN_BUCKET;
			$form_api_secret = UNYUN_FORM_API_SECRET; /// 表单 API 功能的密匙（请访问又拍云管理后台的空间管理页面获取）

			$options = array();
			$options['bucket'] = $bucket; /// 空间名
			$options['expiration'] = time()+600; /// 授权过期时间
			$options['save-key'] = '/'.$this->token.'/{year}/{mon}/{day}/'.time().'_{random}{.suffix}'; /// 文件名生成格式，请参阅 API 文档
			$options['allow-file-type'] = C('up_exts'); /// 控制文件上传的类型，可选
			$options['content-length-range'] = '0,'.intval(C('up_size'))*1024; /// 限制文件大小，可选
			if (intval($_GET['width'])){
				$options['x-gmkerl-type'] = 'fix_width';
				$options['fix_width '] = $_GET['width'];
			}
			$policy = base64_encode(json_encode($options));
			$sign = md5($policy.'&'.$form_api_secret); /// 表单 API 功能的密匙（请访问又拍云管理后台的空间管理页面获取）
			$this->assign('bucket',$bucket);
			$this->assign('sign',$sign);
			$this->assign('policy',$policy);
			
			
			
			$this->assign('page',$show);		
			$this->assign('photo',$list);
			$this->display();	
		
		}
		
	}
	public function add(){
		if(IS_POST){
			$this->all_insert('Photo','/add');			
		}else{
			$this->display();	
		
		}
		
	}
	public function del(){
		if(I('get.token')!=session('token')){$this->error('非法操作');}
		$check=M('Photo')->field('id')->where(array('token'=>$_SESSION['token'],'id'=>I('get.id')))->find();
		if($check==false){$this->error('服务器繁忙');}
		if(empty($_POST['edit'])){
			if(M('Photo')->where(array('id'=>$check['id']))->delete()){
				M('Photo_list')->where(array('pid'=>$check['id']))->delete();
				$this->success('操作成功');
			}else{
				$this->error('服务器繁忙,请稍后再试');
			}
		}
	
	}


}