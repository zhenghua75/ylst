<?php
namespace Common\Api;
final class Sms
{
	public $topdomain;
	public $key;
	public $smsapi_url;

	public function __construct()
	{
	}

	public function checkmobile($mobilephone)
	{
		$mobilephone = trim($mobilephone);

		if (preg_match("/^13[0-9]{1}[0-9]{8}$|15[01236789]{1}[0-9]{8}$|18[01236789]{1}[0-9]{8}$/", $mobilephone)) {
			return $mobilephone;
		}
		else {
			return false;
		}
	}

	public function sendSms($token, $content, $mobile, $send_time, $charset, $id_code)
	{
		if (C("emergent_mode")) {
			return "404";
		}

		if ((C("sms_key") != "") && (C("sms_key") != "key")) {
			$companyid = 0;

			if (!strpos($token, "_") === false) {
				$sarr = explode("_", $token);
				$token = $sarr[0];
				$companyid = intval($sarr[1]);
			}

			if (!$mobile) {
				$companyWhere = array();
				$companyWhere["token"] = $token;

				if ($companyid) {
					$companyWhere["id"] = $companyid;
				}

				$company = M("Company")->where($companyWhere)->find();
				$mobile = $company["mp"];
			}

			$thisWxUser = M("Wxuser")->where(array("token" => Sms::_safe_replace($token)))->find();
			$thisUser = M("Users")->where(array("id" => $thisWxUser["uid"]))->find();

			if ($token == "admin") {
				$thisUser = array("id" => 0);
				$thisWxUser = array("uid" => 0, "token" => $this->token);
			}

			if ((intval($thisUser["smscount"]) < 1) && ($token != "admin")) {
				return "已用完或者未购买短信包";
				exit();
			}
			else {
				if (is_array($mobile)) {
					$mobile = implode(",", $mobile);
				}

				$content = Sms::_safe_replace($content);
				$data = array("topdomain" => C("server_topdomain"), "key" => trim(C("sms_key")), "token" => $token, "content" => $content, "mobile" => $mobile, "sign" => trim(C("sms_sign")));
				$post = "";

				foreach ($data as $k => $v ) {
					$post .= $k . "=" . $v . "&";
				}

				$smsapi_senturl = getUpdateServer() . "oa/admin.php?m=sms&c=sms&a=send";
				$return = Sms::_post($smsapi_senturl, 0, $post);
				$arr = explode("#", $return);
				$this->statuscode = $arr[0];

				if ($mobile) {
					$row = array("uid" => $thisUser["id"], "token" => $thisWxUser["token"], "time" => time(), "mp" => $mobile, "text" => $content, "status" => $this->statuscode, "price" => C("sms_price"));
					M("Sms_record")->add($row);
					if ((intval($this->statuscode) == 0) && ($token != "admin")) {
						M("Users")->where(array("id" => $thisWxUser["uid"]))->setDec("smscount");
					}
				}

				return $return;
			}
		}
	}

	private function _post($url, $limit, $post, $cookie, $ip, $timeout, $block)
	{
		$return = "";
		$url = str_replace("&amp;", "&", $url);
		$matches = parse_url($url);
		$host = $matches["host"];
		$path = ($matches["path"] ? $matches["path"] . ($matches["query"] ? "?" . $matches["query"] : "") : "/");
		$port = (!$matches["port"] ? $matches["port"] : 80);
		$siteurl = Sms::_get_url();

		if ($post) {
			$out = "POST $path HTTP/1.1\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Referer: " . $siteurl . "\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "User-Agent: {$_SERVER["HTTP_USER_AGENT"]}\r\n";
			$out .= "Host: {$host}\r\n";
			$out .= "Content-Length: " . strlen($post) . "\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cache-Control: no-cache\r\n";
			$out .= "Cookie: {$cookie}\r\n\r\n";
			$out .= $post;
		}
		else {
			$out = "GET $path HTTP/1.1\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Referer: " . $siteurl . "\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: {$_SERVER["HTTP_USER_AGENT"]}\r\n";
			$out .= "Host: {$host}\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cookie: {$cookie}\r\n\r\n";
		}

		$fp = @fsockopen($ip ? $ip : $host, $port, $errno, $errstr, $timeout);

		if (!$fp) {
			return "";
		}

		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		@fwrite($fp, $out);
		$status = stream_get_meta_data($fp);

		if ($status["timed_out"]) {
			return "";
		}

		while (!feof($fp)) {
			if (($header = @fgets($fp)) && (($header == "\r\n") || ($header == "\n"))) {
				break;
			}
		}

		$stop = false;
		while (!feof($fp) && !$stop) {
			$data = fread($fp, ($limit == 0) || (8192 < $limit) ? 8192 : $limit);
			$return .= $data;

			if ($limit) {
				$limit -= strlen($data);
				$stop = $limit <= 0;
			}
		}

		@fclose($fp);
		$return_arr = explode("\n", $return);

		if ($return_arr[1]) {
			$return = trim($return_arr[1]);
		}

		unset($return_arr);
		return $return;
	}

	private function _get_url()
	{
		$sys_protocal = ($_SERVER["SERVER_PORT"] && ($_SERVER["SERVER_PORT"] == "443") ? "https://" : "http://");
		$php_self = ($_SERVER["PHP_SELF"] ? Sms::_safe_replace($_SERVER["PHP_SELF"]) : Sms::_safe_replace($_SERVER["SCRIPT_NAME"]));
		$path_info = ($_SERVER["PATH_INFO"] ? Sms::_safe_replace($_SERVER["PATH_INFO"]) : "");
		$relate_url = ($_SERVER["REQUEST_URI"] ? Sms::_safe_replace($_SERVER["REQUEST_URI"]) : $php_self . ($_SERVER["QUERY_STRING"] ? "?" . Sms::_safe_replace($_SERVER["QUERY_STRING"]) : $path_info));
		return $sys_protocal . ($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : "") . $relate_url;
	}

	private function _safe_replace($string)
	{
		$string = str_replace("%20", "", $string);
		$string = str_replace("%27", "", $string);
		$string = str_replace("%2527", "", $string);
		$string = str_replace("*", "", $string);
		$string = str_replace("\"", "&quot;", $string);
		$string = str_replace("'", "", $string);
		$string = str_replace("\"", "", $string);
		$string = str_replace(";", "", $string);
		$string = str_replace("<", "&lt;", $string);
		$string = str_replace(">", "&gt;", $string);
		$string = str_replace("{", "", $string);
		$string = str_replace("}", "", $string);
		$string = str_replace("\\", "", $string);
		return $string;
	}
}


?>
