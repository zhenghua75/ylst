<?php
namespace User\Controller;
class LotteryController extends LotteryBaseController{
	public function _initialize() {
		parent::_initialize();
	}
	public function cheat(){
		parent::cheat();
		$this->display();
	}
	public function index(){
		parent::index(1);
		$this->display();
	
	}
	public function sn(){
		$type=isset($_GET['type'])?intval($_GET['type']):1;
		parent::sn($type);
		$this->display();
	}
	public function add(){
		parent::add(1);
	}
	
	public function edit(){
		parent::edit(1);
	}
}