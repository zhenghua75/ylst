<?php
namespace User\Controller;
use Common\Controller\UserController;
class StaffIndexController extends UserController{
	public $first_func;
	public $token;

	protected function _initialize()
	{
		parent::_initialize();
		$first_func = I("get.first_func");
		$this->first_func = $first_func;
		$this->token = $this->token;
		$this->canUseFunction($first_func);
	}

	public function index()
	{
		$trans = include "./PigCms/Lib/ORG/FuncToModel.php";
		$first_func = ($trans[$this->first_func] ? ucfirst($trans[$this->first_func]) : ucfirst($this->first_func));

		if ($first_func == "Home") {
			$function = "set";
		}

		header("Location:/index.php?g=User&m=" . $first_func . "&a=" . $function . "&token=" . $this->token);
	}
}