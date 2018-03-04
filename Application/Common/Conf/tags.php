<?php
return array(
  'app_begin'=>array(
    'Common\Behavior\AgentCheckBehavior',
    'Common\Behavior\FuwuCheckBehavior'
   ),
  'action_begin'=>array(
  	'Common\Behavior\CompanyWebsiteCheckBehavior',
  	'Common\Behavior\MobileWebsiteCheckBehavior'
  	),
);