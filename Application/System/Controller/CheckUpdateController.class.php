<?php
namespace System\Controller;
use Common\Controller\BackController;
class CheckUpdateController extends BackController{
	public function index(){
		$this->redirect('System/System/checkUpdate');
	}
}