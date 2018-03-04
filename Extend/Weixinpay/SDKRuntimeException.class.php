<?php
namespace Extend\Weixinpay;
class  SDKRuntimeException extends \Think\Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}

}

?>