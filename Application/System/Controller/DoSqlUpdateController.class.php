<?php
namespace System\Controller;
use Common\Controller\BackController;
class DoSqlUpdateController extends BackController{
	public function index(){
		$this->redirect('System/System/doSqlUpdate');
	}
}