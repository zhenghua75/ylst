<?php
namespace System\Controller;
use Common\Controller\BackController;
class UsergroupController extends BackController{

		public function index(){
			$map = array();
			if (C('agent_version')){
				$map['agentid']=array('lt',1);
			}
			$UserDB = D('User_group');
			$count = $UserDB->where($map)->count();

			$Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数
			// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
			$nowPage = isset($_GET['p'])?$_GET['p']:1;
			$show       = $Page->show();// 分页显示输出
			$list = $UserDB->where($map)->order('id ASC')->page($nowPage.','.C('PAGE_NUM'))->select();
			if ($list){
				$i=1;
				foreach ($list as $item){
					$UserDB->where(array('id'=>$item['id']))->save(array('taxisid'=>$i));
					$i++;
				}
			}
			$this->assign('list',$list);
			$this->assign('page',$show);// 赋值分页输出
			$this->display();
		}
		public function add(){


			if(IS_POST){
				$_POST['func'] = join(',',$_REQUEST['func']);
				$this->all_insert();
			}else{
				$where = array( 'status' => 1 );
				if(!ALI_FUWU_GROUP){
					$where['funname']  = array('neq','Fuwu');
				}
				$func = M('Function') -> where($where) -> field('funname,name,id') -> select();
				$this->assign('func',$func);
				$info['access_count'] = 0;
				$this->assign('info',$info);
				$this->display();
			}
		}
		public function edit(){
			if(IS_POST){
				$_POST['func'] = join(',',$_REQUEST['func']);
				$access_count = M('UserGroup')->where(array('id'=>(int) $_POST['id']))->getField('access_count');
				$this->all_save();
				if ($_POST['access_count'] != $access_count) {
					$users = M('Users')->field('id')->where(array('gid' => $_POST['id']))->select();
					if ($users) {
						foreach ($users as $user) {
							S('checkVipTime_'.$user['id'], null);
						}
					}
				}
			}else{
				$where = array( 'status' => 1 );
				if(!ALI_FUWU_GROUP){
					$where['funname']  = array('neq','Fuwu');
				}
				$func = M('Function') -> where($where) -> field('funname,name,id') -> select();
				$this->assign('func',$func);
				$id = I('get.id',0,'intval');
				if(!$id)$this->error('参数错误!');
				$info = D('User_group')->getGroup(array('id'=>$id));
				$this->assign('s','编辑');
				$this->assign('info',$info);
				$this->display('add');
			}
		}
		public function del(){
			$id=I('get.id',0,'intval');
			if($id==0)$this->error('非法操作');
			$info = D('User_group')->delete($id);
			if($info){
				$this->success('操作成功');
			}else{
				$this->error('操作失败');
			}
		}


	}