<?php
namespace User\Controller;
use Common\Controller\UserController;
class AdvanceTplController extends UserController{
    public function _initialize() {
        parent::_initialize();
        //
        $this->canUseFunction('advanceTpl');
        header('Location:/cms/manage/index.php');
    }
}

