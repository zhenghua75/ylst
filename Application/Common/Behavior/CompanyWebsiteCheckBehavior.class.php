<?php
namespace Common\Behavior;
/**
 * 行为扩展：代理检测
 */
class CompanyWebsiteCheckBehavior {
    public function run(&$params) {
        if(MODULE_NAME == 'Home' && CONTROLLER_NAME == 'Index' && ACTION_NAME == 'index'){
			$this->check_company_website();
		}
    }

    private function check_company_website(){
		//如果当前网址和平台网址一样，则不查询。
		if (C('agent_version')){
			$agent=M('Agent')->where(array('siteurl'=>'http://'.$_SERVER['HTTP_HOST']))->find();
		}
		if (!$agent&&C('site_url')){
			$site_domain = parse_url(C('site_url'));
			$now_host = $_SERVER['HTTP_HOST'];
			if($site_domain['host'] != $now_host){
				$now_website = S('now_website'.$now_host);
				if(empty($now_website)){
					$group_list = explode(',',C('APP_GROUP_LIST'));
					if(in_array('Web',$group_list)){
						$database_pc_site = D('Pc_site');
						$condition_pc_site['site'] = $now_host;
						$now_website = $database_pc_site->field(true)->where($condition_pc_site)->find();
					}
				}
				if(!empty($now_website)){
					$_SESSION['now_website'] = $now_website;
					R('Web/Web_index/index');
					exit;
				}
			}
		}
	}
}
