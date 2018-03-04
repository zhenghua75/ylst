<?php
namespace System\Controller;
use Common\Controller\BackController;
class IndexController extends BackController{
	public function index(){
		
	}
	
	public function rollback(){
		$updateRecord=M('System_info')->order('lastsqlupdate DESC')->find();
		$time = $_GET['time']*60*60*24;
		$lastsqlupdate_time = $updateRecord['lastsqlupdate'] - $time;
		$version_time = $updateRecord['version'] - $time;
		//回滚程序
		M('System_info')->where('lastsqlupdate>0')->save(array('version'=>$version_time));
		M('System_info')->where(array('lastsqlupdate'=>$updateRecord['lastsqlupdate']))->save(array('lastsqlupdate'=>$lastsqlupdate_time));
		$this->success('您可以重新进行升级了',U('System/System/main'));
	}
	
	public function del_userinfo($token, $wecha_id) {
	    
	    $count = M('Userinfo')->where(array('token'=>$token, 'wecha_id'=>$wecha_id))->count();
	    if (1 < $count) {
	    	if (M('Userinfo')->where(array('token'=>$token, 'wecha_id'=>$wecha_id))->delete()) {
	    	    $this->error('删除成功');
	    	} else {
	    	    $this->error('删除失败');
	    	}
	    } else {
	        $this->error('不存在重复记录');
	    }
	}
}