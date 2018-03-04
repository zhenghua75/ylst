<?php
namespace Common\Behavior;
/**
 * 行为扩展：代理检测
 */
class AgentCheckBehavior {
    public function run(&$params) {
        // 代理访问检测
        //C('isFuwu',0);
        //C('isWechat',0);
        $userAgent=strtolower($_SERVER['HTTP_USER_AGENT']);
		if (strpos($userAgent,'alipayclient')){
			//$this->isFuwu=1;
			define('isFuwu',1);
			//C('isFuwu',1);
		}elseif (strpos($userAgent,'micromessenger')){
			//$this->isWechat=1;
			define('isWechat', 1);
			//C('isWechat',1);
		}

		defined('isFuwu') or define('isFuwu', 0);
		defined('isWechat') or define('isWechat', 0);

		if (C('agent_version')){
			$thisAgent=M('agent')->where(array('siteurl'=>'http://'.$_SERVER['HTTP_HOST']))->find();
			if ($thisAgent){
				$minGroup=M('User_group')->where(array('agentid'=>$thisAgent['id']))->order('id ASC')->find();
				C('minGroupid',$minGroup['id']);
				C('DEFAULT_THEME','agent_'.$thisAgent['id']);
				define('agentid',$thisAgent['id']);
				define('isAgent',1);
			}
		}
		defined('agentid') or define('agentid', 0);
		defined('isAgent') or define('isAgent', 0);
    }
}
