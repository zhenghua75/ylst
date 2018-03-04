<?php
namespace User\Controller;
use Common\Controller\UserController;
class DizwifiController extends UserController{
	public $expiration_time;

	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("Dizwifi");
		$this->dizwifi = new Dizwifi();
		$this->expiration_time = 12 * 3600;
	}

	public function index()
	{
		$total = M("dizwifi_device")->where(array("token" => $this->token))->count();
		$Page = new \Think\Page($total, 10);
		$list = M("dizwifi_device")->where(array("token" => $this->token))->limit($Page->firstRow . "," . $Page->listRows)->order("id desc")->select();
		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("token", $this->token);
		$this->display();
	}

	public function AddDevice()
	{
		if (IS_POST) {
			if (($_POST["shop_id"] == "") || ($_POST["shop_name"] == "")) {
				$this->error("必须选择一个门店");
			}

			if ($_POST["ssid"] == "") {
				$this->error("无线网名称不能为空");
			}

			if ($_POST["password"] == "") {
				$this->error("无线网密码不能为空");
			}

			if ($_POST["bssid"] == "") {
				$this->error("无线网mac地址不能为空");
			}
			else if (!preg_match("/^[A-Fa-f0-9]{2}(:[A-Fa-f0-9]{2}){5}$/", $_POST["bssid"])) {
				$this->error("无线网mac地址格式错误");
			}

			$device = M("dizwifi_device")->where(array("shop_id" => $_POST["shop_id"], "token" => $this->token))->order("add_time asc")->find();

			if (!$device) {
				if (("WX" . $_POST["ssid"]) != $device["ssid"]) {
					$this->error("同一个门店下的设备，无线网名称必须相同");
				}

				if ($_POST["password"] != $device["password"]) {
					$this->error("同一个门店下的设备，无线网密码必须相同");
				}
			}

			$device_count = M("dizwifi_device")->where(array("bssid" => trim($_POST["bssid"]), "token" => $this->token))->count();

			if (0 < $device_count) {
				$this->error("该设备已经被添加");
			}

			$data = array();
			$data["shop_id"] = $_POST["shop_id"];
			$data["shop_name"] = $_POST["shop_name"];
			$data["ssid"] = "WX" . trim($_POST["ssid"]);
			$data["password"] = trim($_POST["password"]);
			$data["bssid"] = strtolower(trim($_POST["bssid"]));
			$adddevice = $this->dizwifi->AddDevice($data["shop_id"], $data["ssid"], $data["password"]);

			if ($adddevice["errcode"] == 0) {
				$data["token"] = $this->token;
				$data["add_time"] = time();
				$add = M("dizwifi_device")->add($data);

				if ($add) {
					$this->success("添加成功", U("Dizwifi/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("添加失败,请重试");
				}
			}
			else {
				$this->error("添加失败");
			}
		}
		else {
			$cache_data = S($this->token . "_shoplist");

			if ($cache_data["status"] == 1) {
				$result = $cache_data["data"];
			}
			else {
				$records = array();
				$list = array();
				$shoplist = $this->dizwifi->ShopList(1, 20);

				if ($shoplist["errcode"] == 0) {
					$records = $shoplist["successmsg"]["records"];
					$totalcount = $shoplist["successmsg"]["totalcount"];

					if (20 < $totalcount) {
						for ($i = 2; $i <= ceil($totalcount / 20); $i++) {
							$list = $this->dizwifi->ShopList($i, 20);

							if ($list["errcode"] == 0) {
								$result = array_merge($records, $list["successmsg"]["records"]);
							}
						}
					}
					else {
						$result = $records;
					}

					$cache_data = array("status" => 1, "data" => $result);
					S($this->token . "_shoplist", $cache_data, $this->expiration_time);
				}
				else {
					$this->error($shoplist["errmsg"]);
				}
			}

			$this->assign("shop_list", $result);
			$this->assign("token", $this->token);
			$this->display();
		}
	}

	public function getcode()
	{
		$id = (int) $_GET["id"];
		$device = M("dizwifi_device")->where(array("id" => $id))->find();

		if (S("shopcode_" . $device["shop_id"]) != "") {
			$this->assign("codeurl", S("shopcode_" . $device["shop_id"]));
			$this->display();
			return true;
		}

		if (!$device) {
			$code = $this->dizwifi->GetQrcode($device["shop_id"], 1);

			if ($code["errcode"] == 0) {
				S("shopcode_" . $device["shop_id"], $code["successmsg"], $this->expiration_time);
				$this->assign("codeurl", $code["successmsg"]);
				$this->display();
			}
			else {
				$this->error($code["errmsg"]);
			}
		}
		else {
			$this->error("操作失败");
		}
	}

	public function DelDevice()
	{
		$id = (int) $_GET["id"];
		$device = M("dizwifi_device")->where(array("id" => $id))->find();

		if (!$device) {
			$DeleteDevice = $this->dizwifi->DeleteDevice($device["bssid"]);

			if ($DeleteDevice["errcode"] == 0) {
				$del = M("dizwifi_device")->where(array("id" => $id))->delete();

				if ($del) {
					$this->success("删除成功", U("Dizwifi/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("删除失败，请稍后再试");
				}
			}
			else {
				$this->error($DeleteDevice["errmsg"]);
			}
		}
		else {
			$this->error("未找到删除项");
		}
	}

	public function sethomepage()
	{
		if (IS_POST) {
			$data = array();
			if (($_POST["template_id"] == 1) && ($_POST["url"] == "")) {
				$this->error("自定义链接不能为空");
			}
			else {
				if (($_POST["template_id"] == 1) && ($_POST["url"] != "")) {
					if ((strpos($_POST["url"], "{siteUrl}") !== false) || (strpos($_POST["url"], "{wechat_id}") !== false)) {
						$url = str_replace(array("{siteUrl}", "{wechat_id}"), array($this->siteUrl, ""), $_POST["url"]);
					}
					else {
						$url = trim($_POST["url"]);
					}
				}
			}

			$data["url"] = $url;
			$data["template_id"] = $_POST["template_id"];
			$data["bar_type"] = $_POST["bar_type"];
			$data["shop_id"] = $_POST["shop_id"];
			$id = (int) $_POST["page_id"];
			$setpage = $this->dizwifi->SetHomgpage($_POST["shop_id"], $_POST["template_id"], $url);
			$setbar = $this->dizwifi->SetBar($_POST["shop_id"], $_POST["bar_type"]);
			if (($setpage["errcode"] == 0) && ($setbar["errcode"] == 0)) {
				if ($id) {
					$set = M("dizwifi_homepage")->where(array("id" => $id))->save($data);
				}
				else {
					$data["token"] = $this->token;
					$set = M("dizwifi_homepage")->add($data);
				}

				if ($set) {
					$this->success("设置成功", U("Dizwifi/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("设置失败");
				}
			}
			else {
				$msg = "";
				$msg .= ($setpage["errmsg"] != "" ? $setpage["errmsg"] : "");
				$msg .= ($setbar["errmsg"] != "" ? $setbar["errmsg"] : "");
				$this->error($msg);
			}
		}

		$id = (int) $_GET["id"];
		$device = M("dizwifi_device")->where(array("id" => $id))->find();

		if (!$device) {
			$set = M("dizwifi_homepage")->where(array("shop_id" => $device["shop_id"]))->find();
			$this->assign("set", $set);
			$this->assign("shop_id", $device["shop_id"]);
			$this->assign("shop_name", $device["shop_name"]);
		}
		else {
			$this->error("操作失败");
		}

		$this->display();
	}

	public function statistics()
	{
		$token = I("get.token");
		$devices = M("dizwifi_device")->where(array("token" => $this->token))->order("id desc")->select();
		$this->assign("devices", $devices);
		$this->assign("current_month", date("n"));
		$this->assign("token", $token);
		$this->display();
	}

	public function statistics_success()
	{
		$shop_id = I('get.shop_id',0,'intval');
		$token = I('get.token');
		$month = I('get.month',0,'intval');
		$charts = array();
		$cache_name = "dizwifi" . substr(md5($token . "_" . $shop_id . "_" . $month), 0, 5);
		$map = array();
		$map["shop_id"] = $shop_id;
		$map["token"] = $token;
		$device_info = M("dizwifi_device")->where($map)->find();
		$begin_date = mktime(0, 0, 0, $month, 1, date("Y"));

		if ($month == date("n")) {
			$end_date = time() - (24 * 3600);
		}
		else {
			$end_date = mktime(0, 0, 0, $month, date("t", $begin_date), date("Y"));
		}

		if ($month < date("n", $device_info["add_time"])) {
			$this->default_charts("charts", date("t", $begin_date));
			exit();
		}

		if (date("n", time()) < $month) {
			$this->default_charts("charts", date("t", $begin_date));
			exit();
		}

		if ($device_info) {
			$statistics_info = S($cache_name);

			if ($statistics_info["status"] == 1) {
				foreach ((array) $statistics_info["data"] as $key => $val ) {
					$charts["xAxis"] .= "\"" . date("d", intval($val["statis_time"] / 1000)) . "日\",";
					$charts["total_user"] .= "\"" . $val["total_user"] . "\",";
					$charts["homepage_uv"] .= "\"" . $val["homepage_uv"] . "\",";
					$charts["new_fans"] .= "\"" . $val["new_fans"] . "\",";
					$charts["total_fans"] .= "\"" . $val["total_fans"] . "\",";
				}
			}
			else {
				$statistics_result = $this->dizwifi->StatisticsList(date("Y-m-d", $begin_date), date("Y-m-d", $end_date), $device_info["shop_id"]);

				if ($statistics_result["errcode"] == 0) {
					foreach ($statistics_result["successmsg"] as $key => $val ) {
						$charts["xAxis"] .= "\"" . date("d", intval($val["statis_time"] / 1000)) . "日\",";
						$charts["total_user"] .= "\"" . $val["total_user"] . "\",";
						$charts["homepage_uv"] .= "\"" . $val["homepage_uv"] . "\",";
						$charts["new_fans"] .= "\"" . $val["new_fans"] . "\",";
						$charts["total_fans"] .= "\"" . $val["total_fans"] . "\",";
					}

					$cache_data = array("status" => 1, "data" => $statistics_result["successmsg"]);
					S($cache_name, $cache_data, $this->expiration_time);
				}
				else {
					$this->default_charts("charts", date("t", $begin_date));
					exit();
				}
			}
		}
		else {
			$this->default_charts("charts", date("t", $begin_date));
			exit();
		}

		$this->assign("charts", $charts);
		$this->display();
	}

	private function default_charts($assign, $times)
	{
		$data = array();

		for ($i = 1; $i <= $times; $i++) {
			$data["xAxis"] .= "\"" . $i . "日\",";
			$data["total_user"] .= "\"0\",";
			$data["homepage_uv"] .= "\"0\",";
			$data["new_fans"] .= "\"0\",";
			$data["total_fans"] .= "\"0\",";
		}

		$this->assign($assign, $data);
		$this->display();
		exit();
	}
}
