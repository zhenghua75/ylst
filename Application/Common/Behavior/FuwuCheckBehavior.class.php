<?php
namespace Common\Behavior;
/**
 * 行为扩展：代理检测
 */
class FuwuCheckBehavior {
    public function run(&$params) {
        define('ALI_FUWU_GROUP',$this->check_fuwu_exist());
    }
    private function check_fuwu_exist()
    {
    	$group_list = explode(',',C('APP_GROUP_LIST'));
    	
    	return in_array('Fuwu',$group_list);
    }
}
