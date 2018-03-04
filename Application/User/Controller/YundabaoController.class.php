<?php
namespace User\Controller;
use Common\Controller\UserController;
class YundabaoController extends UserController
{
	public $now_app;
	public $createUrl = "http://app-service.meihua.com/api/create.php";
	public $createAppUrl = "http://app-service.meihua.com/api/createApp.php";
	public $getAppUrl = "http://app-service.meihua.com/api/getApp.php";
	public $downAppUrl = "http://app-service.meihua.com/api/downApp.php";

	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->cfdwdgfds3skgfds3szsd3idsj();
		$this->canUseFunction("Yundabao");
		$domain = str_replace("http://", "", $this->siteUrl);
		$domain = str_replace("https://", "", $domain);
		$this->now_app = M("appdabao")->where(array("token" => $this->token, "domain" => $domain))->find();

		if ($this->now_app == "") {
			$data = array("domain" => $domain, "label" => $this->token, "from" => "1");
			$create_result = $this->yundabaoapi("create", $data);
			if (!$create_result["app_id"] && !$create_result["app_key"]) {
				if (!M("appdabao")->add(array("token" => $this->token, "domain" => $domain, "app_id" => $create_result["app_id"], "app_key" => $create_result["app_key"]))) {
					$this->error("初始化App云打包失败");
				}
			}
			else {
				$this->error($create_result["err_msg"]);
			}

			$this->now_app = M("appdabao")->where(array("token" => $this->token, "domain" => $domain))->find();
		}
	}

	public function add()
	{
		if (IS_POST) {
			$ext_name = strrchr($_POST["icopic"], ".");

			if ($ext_name != ".png") {
				$this->error("请上传png格式的图标");
				exit();
			}

			$ext_name = strrchr($_POST["hellopic"], ".");

			if ($ext_name != ".png") {
				$this->error("请上传png格式的欢迎图");
				exit();
			}

			if (strstr($_POST["weburl"], "{siteUrl}") != "") {
				$_POST["weburl"] = $this->convertLink($_POST["weburl"]);
				$_POST["weburl"] .= "&rget=1&yundabao=1";
			}
			else {
				if ((strstr($_POST["weburl"], "http://") == false) && (strstr($_POST["weburl"], "https://") == false)) {
					$this->error("网址没有http://前缀");
					exit();
				}
			}

			$data = array("name" => $_POST["name"], "intro" => $_POST["intro"], "webUrl" => htmlspecialchars_decode($_POST["weburl"]), "appType" => $_POST["apptype"], "icoPic" => $this->config["site_url"] . $_POST["icopic"], "helloPic" => $this->config["site_url"] . $_POST["hellopic"], "hideTop" => $_POST["hidetop"], "screen" => $_POST["screen"]);
			$data["app_id"] = $this->now_app["app_id"];
			$data["key"] = $this->get_encrypt_key($data, $this->now_app["app_key"]);
			$create_result = $this->yundabaoapi("createApp", $data);

			if (!$create_result["web_app_id"]) {
				$add_data["token"] = $this->token;
				$add_data["web_app_id"] = $create_result["web_app_id"];
				$add_data["addtime"] = time();
				$add_data["name"] = $_POST["name"];

				if ($_POST["intro"] != "") {
					$add_data["intro"] = $_POST["intro"];
				}

				$add_data["weburl"] = $_POST["weburl"];
				$add_data["apptype"] = $_POST["apptype"];
				$add_data["icopic"] = $_POST["icopic"];
				$add_data["hellopic"] = $_POST["hellopic"];
				$add_data["hidetop"] = $_POST["hidetop"];
				$add_data["screen"] = $_POST["screen"];
				$id = M("appdabao_list")->add($add_data);

				if (0 < $id) {
					$this->success("应用创建请求成功", U("User/Yundabao/applist", array("token" => $this->token)));
				}
				else {
					$this->error("应用创建失败，请重试");
				}
			}
			else {
				$this->error("应用创建失败，请重试！错误原因" . $create_result["err_msg"]);
			}
		}
		else {
			$this->display();
		}
	}

	public function QRcode()
	{
		include "./Extend/phpqrcode.php";
		$url = $this->downAppUrl . "?id=" . $_GET["AppId"];
		\QRcode::png($url, false, 1, 11);
	}

	public function download()
	{
		$now_app = M("appdabao_list")->where(array("token" => $this->token, "id" => $_GET["id"]))->find();

		switch ($now_app["status"]) {
		case "0":
			$get_result = $this->yundabaoapi("getApp", array("web_app_id" => $now_app["web_app_id"]));
			if (($get_result["err_code"] == 0) && $get_result["okTime"]) {
				M("appdabao_list")->data(array("id" => $now_app["id"], "status" => "1", "okTime" => $get_result["okTime"]))->save();
				$downloadUrl = $this->downAppUrl . "?id=" . $now_app["web_app_id"];
			}
			else if ($get_result["err_code"] == 1002) {
				M("appdabao_list")->data(array("id" => $now_app["id"], "status" => "2", "err_result" => $get_result["err_msg"]))->save();
				$err_msg = "应用打包失败！返回原因：" . $get_result["err_msg"];
			}
			else if ($get_result["err_code"] == 1001) {
				if ($get_result["err_msg"]["lineUp"]) {
					$err_msg = $get_result["err_msg"]["msg"] . "<br/>前面还有" . $get_result["err_msg"]["lineUp"] . "个应用正在排队打包，大约还需要<span id=\"jishi\">" . $get_result["err_msg"]["waitTime"] . "</span>秒。";
					$jishi = $get_result["err_msg"]["waitTime"];
				}
				else {
					$err_msg = "该应用正在打包中，请稍后再试。应用打包还需要<span id=\"jishi\">20</span>秒";
					$jishi = 20;
				}

				$this->assign("jishi", $jishi);
			}
			else {
				$err_msg = $get_result["err_msg"];
			}

			break;

		case "1":
			$downloadUrl = $this->downAppUrl . "?id=" . $now_app["web_app_id"];
			break;

		case "2":
			$err_msg = "应用打包失败！返回原因：" . $now_app["err_result"];
			break;
		}

		$this->assign("now_app", $now_app);
		$this->assign("downloadUrl", $downloadUrl);
		$this->assign("err_msg", $err_msg);
		$this->display();
	}

	public function applist()
	{
		$where["token"] = $this->token;
		$where_page["token"] = $this->token;

		if (!$_GET["search"]) {
			$search = $_GET["search"];

			if (in_array($search, array("安卓", "ios", "IOS"))) {
				switch ($search) {
				case "安卓":
					$search = 0;
					break;

				case "ios":
				case "IOS":
					$search = 1;
					break;
				}
			}

			$where["apptype|name|web_app_id"] = array("like", "%" . $search . "%");
			$where_page["search"] = $_GET["search"];
		}

		$count = M("appdabao_list")->where($where)->count();
		$page = new \Think\Page($count, 10);

		foreach ($where_page as $key => $val ) {
			$pagethis->parameter .= "$key=" . urlencode($val) . "&";
		}

		$show = $page->show();
		$list = M("appdabao_list")->where($where)->order("addtime desc")->limit($page->firstRow . "," . $page->listRows)->select();
		$this->assign("page", $show);
		$this->assign("applist", $list);
		$this->display();
	}

	protected function yundabaoapi($type, $data)
	{
		switch ($type) {
		case "create":
			return json_decode($this->https_request($this->createUrl, $data), true);
		case "createApp":
			return json_decode($this->https_request($this->createAppUrl, $data), true);
		case "getApp":
			return json_decode($this->https_request($this->getAppUrl, $data), true);
		default:
			return false;
		}
	}

	protected function get_encrypt_key($array, $app_key)
	{
		$new_arr = array();
		ksort($array);

		foreach ($array as $key => $value ) {
			$new_arr[] = $key . "=" . $value;
		}

		$new_arr[] = "app_key=" . $app_key;
		$string = implode("&", $new_arr);
		return md5($string);
	}

	protected function https_request($url, $data)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

		if (!$data) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
}