<?php
namespace User\Controller;
use Common\Controller\UserController;
class CatemenuController extends UserController {
    public $fid;
    public $token;
    public function _initialize() {
        parent::_initialize();
        $this->fid=intval($_GET['fid']);
        $this->assign('fid',$this->fid);
        if ($this->fid){
            $thisCatemenu=M('Catemenu')->find($this->fid);
            $this->assign('thisCatemenu',$thisCatemenu);
        }
    }
    public function index(){
        $db=D('catemenu');
        $where['token']=session('token');
        $where['fid']=intval($_GET['fid']);
        $count=$db->where($where)->count();
        $page=new \Think\Page($count,25);
        $info=$db->where($where)->order('orderss desc')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('countMenu',$count);
        $this->assign('page',$page->show());
        $this->assign('info',$info);
        $this->display();
    }
    
    public function add(){
        $this->display();
    }
    
    public function edit(){
        $id=I('get.id',0,'intval');
        $info=M('Catemenu')->find($id);
        $this->assign('info',$info);
        $this->display();
    }
    
    public function del(){
        $where['id']=I('get.id',0,'intval');
        $where['token']=session('token');
		
					
		S("bottomMenus_".$where['token'],NULL);
		
        if(D(MODULE_NAME)->where($where)->delete()){
            $fidwhere['fid']=intval($where['id']);
            D(MODULE_NAME)->where($fidwhere)->delete();
            $this->success('操作成功',U(MODULE_NAME.'/index',array('fid'=>$_GET['fid'])));
        }else{
            $this->error('操作失败',U(MODULE_NAME.'/index',array('fid'=>$_GET['fid'])));
        }
    }
    public function insert(){
        $token = I('post.token');
		S("bottomMenus_".$token,NULL);
        $name='Catemenu';
        $db=D($name);
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
		$token = session('token');
		S("bottomMenus_".$token,NULL);

        $this->all_save();
    }
    public function styleSet(){
        $db=M('home');
        $RadioGroup1=$db->where(array('token'=>$this->token))->getfield("RadioGroup");
        $this->assign('RadioGroup1',$RadioGroup1);
        $this->assign('radiogroup',$RadioGroup1);
        $this->display();
    }
    public function styleChange(){
        $db=M('home');
        $info=$db->where(array('token'=>$this->token))->find();
        $radiogroup=I('get.radiogroup');
		$token = $this->token;
		S("homeinfo_".$token,NULL);
        $data['radiogroup']=$radiogroup;
        if($info==false){
            $res=$db->add($data);
        }else{
            $data['id']=$info['id'];
            $res=$db->save($data);
        }
    }
    public function colorChange(){
        $db=M('styleset');
        $info=$db->where(array('token'=>$this->token))->find();
        $plugmenucolor=I('get.themestyle');
        $data['plugmenucolor']=$plugmenucolor;
        if($info==false){
            $res=$db->add($data);
        }else{
            $data['id']=$info['id'];
            $res=$db->save($data);
        }
    }
	
	public function chooseMenu()
	{
		$tpid = isset($_GET['tpid']) ? intval($_GET['tpid']) : 0;
		include('./PigCms/Lib/ORG/radiogroup.php');
		$this->assign("info", $bottomMenu[$tpid]);
		$this->assign('menu',$bottomMenu);
		$this->display();
	}
}