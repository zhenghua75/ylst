<?php
namespace System\Controller;
use Common\Controller\BackController;
class PhpmaController extends BackController{

	public function _initialize() {
		parent::_initialize();
	}

	public function index(){
		$this->assign('flag',$_SESSION['administrator']);
		$this->assign('verify',$_SESSION['verify']);
		$this->assign('str',time());
		$this->display();
	}



}