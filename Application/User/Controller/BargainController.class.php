<?php
namespace User\Controller;
use Common\Controller\UserController;
class BargainController extends UserController{
	public $store_id = 0;
	public $is_syn;
	public $SALT;

	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("Bargain");
		$this->m_bargain = M("bargain");
		$this->m_order = M("bargain_order");
		$this->m_kanuser = M("bargain_kanuser");
		$this->m_userinfo = M("userinfo");

		if (!updateSync::getIfWeidian()) {
			$this->Micrstore_URL = C("weidian_domain") ? C("weidian_domain") : "http://v.meihua.com";
			$this->SALT = C("encryption") ? C("encryption") : "pigcms";
		}
		else {
			$this->Micrstore_URL = "http://v.meihua.com";
			$this->SALT = "pigcms";
		}

		$this->store_id = $this->wxuser["routerid"];
		$this->is_syn = session("is_syn");
	}

	public function index()
	{
		$where["token"] = $this->token;
		$where_page["token"] = $this->token;

		if (!$_GET["name"]) {
			$where["name"] = array("like", "%" . $_GET["name"] . "%");
			$where_page["name"] = $_GET["name"];
		}

		import("ORG.Util.Page");
		$count = $this->m_bargain->where($where)->count();
		$page = new \Think\Page($count, 8);

		foreach ($where_page as $key => $val ) {
			$pagethis->parameter .= "$key=" . urlencode($val) . "&";
		}

		$show = $page->show();
		$bargain_list = $this->m_bargain->where($where)->order("addtime desc")->limit($page->firstRow . "," . $page->listRows)->select();

		foreach ($bargain_list as $k => $v ) {
			$where_kanuser["token"] = $this->token;
			$where_kanuser["bargain_id"] = $v["pigcms_id"];
			$count_canyu = $this->m_kanuser->where($where_kanuser)->count();
			$where_order["token"] = $this->token;
			$where_order["paid"] = 1;
			$where_order["bargain_id"] = $v["pigcms_id"];
			$count_pay = $this->m_order->where($where_order)->count();
			$bargain_list[$k]["count_canyu"] = $count_canyu;
			$bargain_list[$k]["count_pay"] = $count_pay;

			if ($v["is_new"] == 2) {
				$bargain_list[$k]["original"] = $bargain_list[$k]["original"] / 100;
				$bargain_list[$k]["minimum"] = $bargain_list[$k]["minimum"] / 100;
			}

			if ($this->is_syn == 1) {
				$post_data = NULL;
				$sort_data = NULL;
				$sign_key = NULL;
				$url = $this->Micrstore_URL . "/api/activity/act_getquantity.php";
				$post_data["product_id"] = $v["product_id"];
				$post_data["sku_id"] = $v["sku_id"];
				$sort_data = $post_data;
				$sort_data["salt"] = $this->SALT;
				ksort($sort_data);
				$sign_key = sha1(http_build_query($sort_data));
				$post_data["sign_key"] = $sign_key;
				$curlResult = $this->curl_post($url, $post_data);
				$return = json_decode($curlResult, true);
				$bargain_list[$k]["inventory"] = $return["num"];
			}
		}

		$this->assign("page", $show);
		$this->assign("bargain_list", $bargain_list);
		$this->display();
	}

	public function add()
	{
		if ($_SESSION["is_syn"] == 1) {
			$isBind = $this->isBindWeixin($this->token);
			$this->assign("wdIsBind", $isBind);
		}

		$this->display();
	}

	public function doadd()
	{
		if (IS_POST) {
			$_POST["token"] = $this->token;
			$_POST["addtime"] = time();

			if ($_POST["is_new"] == 2) {
				$_POST["original"] = floor($_POST["original"] * 100);
				$_POST["minimum"] = floor($_POST["minimum"] * 100);
				$_POST["kan_min"] = floor($_POST["kan_min"] * 100);
				$_POST["kan_max"] = floor($_POST["kan_max"] * 100);
			}

			if ($this->m_bargain->create() != false) {
				if ($id = $this->m_bargain->add()) {
					$this->handleKeyword($id, "Bargain", $_POST["keyword"], 0, 0);

					if ($this->is_syn == 1) {
						$this->apiWdActivity($id);
					}

					$this->success("活动创建成功", U("Bargain/index", array("token" => $this->token)));
				}
				else {
					$this->error("服务器繁忙,请稍候再试");
				}
			}
			else {
				$this->error($this->m_bargain->getError());
			}
		}
		else {
			$this->error("操作失败");
		}
	}

	public function update()
	{
		if ($_SESSION["is_syn"] == 1) {
			$isBind = $this->isBindWeixin($this->token);
			$this->assign("wdIsBind", $isBind);
		}

		$where["token"] = $this->token;
		$where["pigcms_id"] = (int) $_GET["id"];
		$bargain = $this->m_bargain->where($where)->find();

		if ($bargain["is_new"] == 2) {
			$bargain["original"] = $bargain["original"] / 100;
			$bargain["minimum"] = $bargain["minimum"] / 100;
			$bargain["kan_min"] = $bargain["kan_min"] / 100;
			$bargain["kan_max"] = $bargain["kan_max"] / 100;
		}

		$this->assign("bargain", $bargain);
		$where_order["token"] = $this->token;
		$where_order["bargain_id"] = (int) $_GET["id"];
		$count_order = $this->m_order->where($where_order)->count();
		$this->assign("count_order", $count_order);
		$this->display();
	}

	public function doupdate()
	{
		if (IS_POST) {
			$where["pigcms_id"] = $_POST["pigcms_id"];
			$where["token"] = $this->token;

			if ($_POST["is_new"] == 2) {
				$_POST["original"] = floor($_POST["original"] * 100);
				$_POST["minimum"] = floor($_POST["minimum"] * 100);
				$_POST["kan_min"] = floor($_POST["kan_min"] * 100);
				$_POST["kan_max"] = floor($_POST["kan_max"] * 100);
			}

			if ($this->m_bargain->create() != false) {
				$update = $this->m_bargain->where($where)->save();
				$id = $_POST["pigcms_id"];
				$this->handleKeyword($id, "Bargain", $_POST["keyword"], 0, 0);

				if ($this->is_syn == 1) {
					$this->apiWdActivity($id);
				}

				S($_POST["pigcms_id"] . "bargain" . $this->token, NULL);
				$this->success("活动修改成功", U("Bargain/index", array("token" => $this->token)));
			}
			else {
				$this->error($this->m_bargain->getError());
			}
		}
		else {
			$this->error("操作失败");
		}
	}

	public function order()
	{
		$where_order["token"] = $this->token;
		$where_order["paid"] = 1;
		$where_page["token"] = $this->token;

		if ($_GET["id"] != "") {
			$where_order["bargain_id"] = (int) $_GET["id"];
			$where_page["id"] = $_GET["id"];
		}

		if ($_GET["orderid"] != "") {
			if (!strpos($_GET["orderid"], "bargain") === false) {
				$where_order["orderid"] = $_GET["orderid"];
			}
			else {
				$where_order["pigcms_id"] = $_GET["orderid"] - 10000000;
			}

			$where_page["orderid"] = $_GET["orderid"];
		}

		import("ORG.Util.Page");
		$count = $this->m_order->where($where_order)->count();
		$page = new \Think\Page($count, 8);

		foreach ($where_page as $key => $val ) {
			$pagethis->parameter .= "$key=" . urlencode($val) . "&";
		}

		$show = $page->show();
		$order_list = $this->m_order->where($where_order)->order("addtime desc")->limit($page->firstRow . "," . $page->listRows)->select();

		foreach ($order_list as $k => $v ) {
			$where_userinfo["token"] = $this->token;
			$where_userinfo["wecha_id"] = $v["wecha_id"];
			$userinfo = $this->m_userinfo->where($where_userinfo)->find();
			$order_list[$k]["wechaname"] = $userinfo["wechaname"];
			$order_list[$k]["tel"] = $userinfo["tel"];
			$order_list[$k]["address"] = $userinfo["address"];
		}

		$this->assign("page", $show);
		$this->assign("order_list", $order_list);
		$this->display();
	}

	public function kanuser()
	{
		$where_kanuser["token"] = $this->token;
		$where_page["token"] = $this->token;
		$where_kanuser["orderid"] = (int) $_GET["orderid"];
		$where_page["orderid"] = $_GET["orderid"];
		import("ORG.Util.Page");
		$count = $this->m_kanuser->where($where_kanuser)->count();
		$page = new \Think\Page($count, 8);

		foreach ($where_page as $key => $val ) {
			$pagethis->parameter .= "$key=" . urlencode($val) . "&";
		}

		$show = $page->show();
		$kanuser_list = $this->m_kanuser->where($where_kanuser)->order("addtime")->limit($page->firstRow . "," . $page->listRows)->select();

		foreach ($kanuser_list as $k => $v ) {
			$where_userinfo2["token"] = $this->token;
			$where_userinfo2["wecha_id"] = $v["friend"];
			$userinfo2 = $this->m_userinfo->where($where_userinfo2)->find();
			$kanuser_list[$k]["wechaname"] = $userinfo2["wechaname"];
		}

		$this->assign("page", $show);
		$this->assign("kanuser_list", $kanuser_list);
		$where_order["token"] = $this->token;
		$where_order["pigcms_id"] = (int) $_GET["orderid"];
		$order = $this->m_order->where($where_order)->find();
		$where["token"] = $this->token;
		$where["pigcms_id"] = $order["bargain_id"];
		$bargain = $this->m_bargain->where($where)->find();
		$where_userinfo["token"] = $this->token;
		$where_userinfo["wecha_id"] = $order["wecha_id"];
		$userinfo = $this->m_userinfo->where($where_userinfo)->find();
		$this->assign("bargain", $bargain);
		$this->assign("userinfo", $userinfo);
		$this->display();
	}

	public function ajax()
	{
		switch ($_POST["type"]) {
		case "state":
			$where["token"] = $_POST["token"];
			$where["pigcms_id"] = (int) $_POST["id"];
			$state = $this->m_bargain->where($where)->getField("state");

			if ($state == 1) {
				$save["state"] = 0;
				$data["error"] = 0;
			}
			else {
				$save["state"] = 1;
				$data["error"] = 1;
			}

			$update = $this->m_bargain->where($where)->save($save);
			S($_POST["id"] . "bargain" . $this->token, NULL);
			$this->ajaxReturn($data, "JSON");
			break;
		}
	}

	public function operate()
	{
		switch ($_GET["type"]) {
		case "del":
			$where["token"] = $this->token;
			$where["pigcms_id"] = (int) $_GET["id"];
			$del = $this->m_bargain->where($where)->delete();
			S((int) $_GET["id"] . "bargain" . $this->token, NULL);
			$this->success("删除成功", U("Bargain/index", array("token" => $this->token)));
			break;

		case "fahuo":
			$where_order["token"] = $this->token;
			$where_order["pigcms_id"] = (int) $_GET["orderid"];
			$save_order["state"] = 0;
			$save_order["expressnum"] = 0;
			$save_order["expressname"] = 0;
			$update_order = $this->m_order->where($where_order)->save($save_order);
			$this->redirect("Bargain/order", array("token" => $this->token, "id" => $_GET["id"]));
			break;
		}
	}

	public function fahuo()
	{
		$where_order["token"] = $_POST["token"];
		$where_order["pigcms_id"] = (int) $_POST["orderid"];
		$save_order["state"] = 1;
		$save_order["expressnum"] = $_POST["fahuoid"];
		$save_order["expressname"] = $_POST["fahuoname"];
		$update_order = $this->m_order->where($where_order)->save($save_order);
		$order = $this->m_order->where($where_order)->find();
		$orderid = $order["pigcms_id"] + 10000000;
		$model = new \Extend\templateNews();
		$model->sendTempMsg("OPENTM200565259", array("href" => $this->siteUrl . U("Wap/Bargain/myorder", array("token" => $this->token)), "wecha_id" => $order["wecha_id"], "first" => "您的订单" . $orderid . "已发货", "keyword1" => $orderid, "keyword2" => $order["expressname"], "keyword3" => $order["expressnum"], "remark" => date("Y年m月d日H时i分s秒")));
		$data["error"] = 0;
		$this->ajaxReturn($data, "JSON");
	}

	public function wdActOrder()
	{
		$wxuser = M("Wxuser")->where(array("token" => $this->token))->find();
		$user = M("Users")->where(array("id" => $wxuser["uid"]))->find();

		if ($user["is_syn"] == 1) {
			redirect($user["source_domain"] . "/wap/act_redirect.php");
			exit();
		}
		else {
			$this->error("非法访问");
		}
	}

	public function goods()
	{
		$p = ($_GET["p"] ? intval($_GET["p"]) : 1);
		$keyword = ($_GET["keyword"] ? trim($_GET["keyword"]) : "");
		$post_data = array("p" => $p, "token" => $this->token, "keyword" => $keyword);
		$this->assign("keyword", $keyword);
		$sort_data = $post_data;
		$sort_data["salt"] = $this->SALT;
		ksort($sort_data);
		$sign_key = sha1(http_build_query($sort_data));
		$post_data["sign_key"] = $sign_key;
		$url = $this->Micrstore_URL . "/api/activity/act_goods.php";
		$curlResult = $this->curl_post($url, $post_data);
		$return = json_decode($curlResult, true);
		$data = $return["data"];
		$page = new \Think\Page($data["total"], 5);
		$this->assign("data", $return["data"]);
		$this->assign("page", $page);
		$this->display();
	}

	public function qrcode()
	{
		$modelId = ($_GET["modelId"] ? intval($_GET["modelId"]) : 0);
		$bargainRow = M("Bargain")->where(array("pigcms_id" => $modelId))->find();
		$post_data = array("title" => $bargainRow["name"], "info" => $bargainRow["wxinfo"], "image" => $bargainRow["wxpic"], "token" => $bargainRow["token"], "keyword" => $bargainRow["keyword"], "model" => "bargain", "modelId" => $modelId);
		$sort_data = $post_data;
		$sort_data["salt"] = $this->SALT;
		ksort($sort_data);
		$sign_key = sha1(http_build_query($sort_data));
		$post_data["sign_key"] = $sign_key;
		$url = $this->Micrstore_URL . "/api/activity/act_shop_qrcode.php";
		$curlResult = $this->curl_post($url, $post_data);
		$return = json_decode($curlResult, true);
		$data = $return["data"];
		$this->assign("data", $return);
		$this->display();
	}

	public function apiWdActivity($modelId)
	{
		$bargainRow = M("Bargain")->where(array("pigcms_id" => $modelId))->find();
		$post_data = array("title" => $bargainRow["name"], "info" => $bargainRow["wxinfo"], "image" => $bargainRow["wxpic"], "token" => $bargainRow["token"], "keyword" => $bargainRow["keyword"], "model" => "bargain", "modelId" => $modelId);
		$sort_data = $post_data;
		$sort_data["salt"] = $this->SALT;
		ksort($sort_data);
		$sign_key = sha1(http_build_query($sort_data));
		$post_data["sign_key"] = $sign_key;
		$url = $this->Micrstore_URL . "/api/activity/act_qrcode.php";
		$curlResult = $this->curl_post($url, $post_data);
		$return = json_decode($curlResult, true);
		$data = $return["data"];

		if ($return["error_code"] != 0) {
			return false;
		}

		return true;
	}

	public function isBindWeixin($token)
	{
		$post_data = array("token" => $token);
		$sort_data = $post_data;
		$sort_data["salt"] = $this->SALT;
		ksort($sort_data);
		$sign_key = sha1(http_build_query($sort_data));
		$post_data["sign_key"] = $sign_key;
		$url = $this->Micrstore_URL . "/api/activity/act_wxbind.php";
		$curlResult = $this->curl_post($url, $post_data);
		$return = json_decode($curlResult, true);
		$isBind = $return["is_bind"];
		return $isBind;
	}

	private function curl_post($url, $post)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
}
