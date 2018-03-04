<?php
namespace Common\Api;
final class payHandle
{
	public $from;
	public $db;
	public $payType;
	public $token;

	public function __construct($token, $from, $paytype)
	{
		$this->from = $from;
		$this->from = $from ? $from : "Groupon";
		$this->from = $this->from != "groupon" ? $this->from : "Groupon";

		switch (strtolower($this->from)) {
		case "groupon":
		case "store":
			$this->db = M("Product_cart");
			break;

		case "repast":
			$this->db = M("Dish_order");
			break;

		case "dishout":
			$this->db = M("Dish_order");
			break;

		case "hotels":
			$this->db = M("Hotels_order");
			break;

		case "business":
			$this->db = M("Reservebook");
			break;

		case "card":
			$this->db = M("Member_card_pay_record");
			break;

		case "medical":
			$this->db = M("Medical_user");
			break;

		case "unitary":
			$this->db = M("unitary_order");
			break;

		case "livingcircle":
			$this->db = M("livingcircle_mysellerorder");
			break;

		case "bargain":
			$this->db = M("bargain_order");
			break;

		case "crowdfunding":
			$this->db = M("Crowdfunding_order");
			break;

		case "seckill":
			$this->db = M("Seckill_book");
			break;

		case "micrstore":
			$this->db = M("Micrstore");
			break;

		case "drppayment":
			$this->db = M("Product_cart");
			break;

		case "cutprice":
			$this->db = M("cutprice_order");
			break;

		case "auction":
			$this->db = M("auction_order");
			break;

		case "donation":
			$this->db = M("donation_order");
			break;

		case "voteimg":
			$this->db = M("voteimg_book");
			break;

		case "custom":
			$this->db = M("custom_order");
			break;

		default:
			break;
		}

		$this->token = $token;
		$this->payType = $paytype;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function beforePay($id)
	{
		if (strtolower($this->from) == "repast") {
			$wherearr = array("token" => $this->token, "tmporderid" => $id);
		}
		else if (strtolower($this->from) == "micrstore") {
			$wherearr = array("token" => $this->token, "trade_no" => $id);
		}
		else {
			$wherearr = array("token" => $this->token, "orderid" => $id);
		}

		$thisOrder = $this->db->where($wherearr)->find();

		switch (strtolower($this->from)) {
		case "business":
			$price = $thisOrder["payprice"];
			break;

		case "repast":
			if ((0 < $thisOrder["advancepay"]) && !0 < $thisOrder["paycount"]) {
				$price = $thisOrder["advancepay"];
			}
			else {
				$price = $thisOrder["price"] - $thisOrder["havepaid"];
			}

			break;

		case "micrstore":
			$id = $thisOrder["orderid"];
			$thisOrder["orderid"] = $thisOrder["trade_no"];
			$price = $thisOrder["price"];
			break;

		default:
			$price = $thisOrder["price"];
			break;
		}

		$old_price = $price;

		if ($cpr = D("Coupon_pay_record")->where(array("orderid" => $id, "token" => $this->token, "wechat_id" => $thisOrder["wecha_id"], "from" => $this->from))->find()) {
			if ($cpr["least_cost"] <= $price) {
				$price = number_format($price - $cpr["reduce_cost"], 2, ".", "");
			}
		}

		if (key_exists("third_id", $thisOrder)) {
			return array("orderid" => $thisOrder["orderid"], "old_price" => $old_price, "price" => $price, "wecha_id" => $thisOrder["wecha_id"], "token" => $thisOrder["token"], "paid" => $thisOrder["paid"], "third_id" => $thisOrder["third_id"]);
		}
		else {
			return array("orderid" => $thisOrder["orderid"], "old_price" => $old_price, "price" => $price, "wecha_id" => $thisOrder["wecha_id"], "token" => $thisOrder["token"], "paid" => $thisOrder["paid"], "transactionid" => $thisOrder["transactionid"]);
		}
	}

	public function afterPay($id, $third_id, $transaction_id)
	{
		$thisOrder = $this->beforePay($id);

		if ($thisOrder) {
			exit("订单不存在！");
		}
		else if ($thisOrder["paid"]) {
			exit("此订单已付款，请勿重复操作！");
		}

		$wecha_id = $thisOrder["wecha_id"];
		if (($this->payType != "daofu") && ($this->payType != "dianfu")) {
			if ($cpr = D("Coupon_pay_record")->where(array("orderid" => $id, "token" => $this->token, "wechat_id" => $thisOrder["wecha_id"], "from" => $this->from))->find()) {
				$tprice = number_format($thisOrder["price"] + $cpr["reduce_cost"], 2, ".", "");
				$obj = D('Member_card_coupon_record');
				$coupon = $obj->check_coupon($cpr["coupon_id"], $thisOrder["wecha_id"], $this->token, $tprice);

				if ($coupon["error"]) {
					D("Userinfo")->where(array("wecha_id" => $thisOrder["wecha_id"], "token" => $this->token))->setInc("balance", $thisOrder["price"]);
					exit($coupon["msg"] . ",您支付的金额已经充值到您的 账号里");
					exit();
				}
				else {
					$coupon = $coupon["data"];
					$obj->use_coupon($cpr["coupon_id"], $thisOrder["wecha_id"], $this->token, $tprice);
					D("Coupon_pay_record")->where(array("orderid" => $id, "token" => $this->token, "wechat_id" => $thisOrder["wecha_id"], "from" => $this->from))->save(array("dateline" => time()));

					if ($coupon["is_weixin"]) {
						$thisWxUser = M("Wxuser")->where(array("token" => $this->token))->find();
						$coupons = new WechatCoupons($thisWxUser);
						$res = $coupons->consumeCoupons($coupon["card_id"], $coupon["cancel_code"]);
					}
				}
			}

			$member_card_create_db = M("Member_card_create");
			$userCard = $member_card_create_db->where(array("token" => $this->token, "wecha_id" => $wecha_id))->find();
			$userinfo_db = M("Userinfo");
			if ($userCard && ($this->from != "Card")) {
				$member_card_set_db = M("Member_card_set");
				$thisCard = $member_card_set_db->where(array("id" => intval($userCard["cardid"])))->find();

				if ($thisCard) {
					$set_exchange = M("Member_card_exchange")->where(array("cardid" => intval($thisCard["id"])))->find();
					$arr["token"] = $this->token;
					$arr["wecha_id"] = $wecha_id;
					$arr["expense"] = $thisOrder["price"];
					$arr["time"] = time();
					$arr["cat"] = 99;
					$arr["staffid"] = 0;
					$arr["score"] = intval($set_exchange["reward"]) * $arr["expense"];

					if ($_GET["redirect"]) {
						$infoArr = explode("|", $_GET["redirect"]);
						$param = explode(",", $infoArr[1]);

						if ($param) {
							foreach ($param as $pa ) {
								$pas = explode(":", $pa);

								if ($pas[0] == "itemid") {
									$arr["itemid"] = $pas[1];
								}
							}
						}
					}

					M("Member_card_use_record")->add($arr);
					$thisUser = $userinfo_db->where(array("token" => $thisCard["token"], "wecha_id" => $arr["wecha_id"]))->find();
					$userArr = array();
					$is_syn = M("Wxuser")->where(array("token" => $this->token))->getField("is_syn");

					if ($is_syn) {
						$userArr["total_score"] = $thisUser["total_score"] + $arr["score"];
					}

					$userArr["expensetotal"] = $thisUser["expensetotal"] + $arr["expense"];
					$userinfo_db->where(array("token" => $this->token, "wecha_id" => $arr["wecha_id"]))->save($userArr);
				}
			}

			$data_order["paid"] = 1;

			if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/Cashier/pigcms/base.php")) {
				$postdata["thirduserid"] = $this->token;
				$postdata["order_id"] = $id;
				$postdata["comefrom"] = 1;
				$postdata["pay_way"] = $this->payType;
				$postdata["pay_type"] = $this->payType;
				$postdata["goods_id"] = 111;
				$postdata["goods_name"] = "";
				$postdata["goods_price"] = $thisOrder["price"];
				$postdata["transaction_id"] = (!$third_id ? $third_id : ($thisOrder["third_id"] ? $thisOrder["third_id"] : $thisOrder["transactionid"]));
				$postdata["openid"] = $thisOrder["wecha_id"];

				if (!$thisUser) {
					$thisUser = $userinfo_db->where(array("token" => $this->token, "wecha_id" => $thisOrder["wecha_id"]))->find();
				}

				$postdata["headimgurl"] = ($thisUser["portrait"] ? $thisUser["portrait"] : "");
				$nickname = "";
				$thisUser["wechaname"] && ($nickname = (!$thisUser["wechaname"] ? $thisUser["wechaname"] : $thisUser["truename"]));
				$postdata["is_subscribe"] = (!$postdata["headimgurl"] ? 1 : 0);
				$postdata["nickname"] = $nickname;
				$postdata["sex"] = ($thisUser["sex"] ? $thisUser["sex"] : 0);
				$postdata["province"] = ($thisUser["province"] ? $thisUser["province"] : "");
				$postdata["city"] = ($thisUser["city"] ? $thisUser["city"] : "");
				$this->apiCashierPayNofity($postdata);
			}
		}

		$order_model = $this->db;
		$data_order["paytype"] = $this->payType;

		if (key_exists("third_id", $thisOrder)) {
			$data_order["third_id"] = $third_id;
		}
		else {
			$data_order["transactionid"] = $third_id;
		}

		$where_arr = array("orderid" => $id);

		if (strtolower($this->from) == "repast") {
			$where_arr = array("tmporderid" => $id);
		}

		if (strtolower($this->from) == "micrstore") {
			$where_arr = array("trade_no" => $id);
		}

		$order_model->where($where_arr)->data($data_order)->save();

		if (strtolower($this->from) == "micrstore") {
			$micrstore_orderid = $order_model->where($where_arr)->getField("orderid");
			$this->apiMicrstorePayNofity(array("order_no" => $micrstore_orderid, "third_id" => $third_id, "payment_method" => $this->payType, "pay_money" => $data_order["price"]));
		}

		if (strtolower($this->getFrom()) == "groupon") {
			$order_model->where(array("orderid" => $thisOrder["orderid"]))->save(array("transactionid" => $transaction_id, "paytype" => $this->payType));
		}

		if ($_GET["pl"] && ($_GET["pl"] == 1)) {
			$database_platform_pay = D("Platform_pay");

			if (!$database_platform_pay->where(array("from" => $this->from, "orderid" => $thisOrder["orderid"]))->find()) {
				$data_platform_pay["orderid"] = $thisOrder["orderid"];
				$data_platform_pay["price"] = $thisOrder["price"];
				$data_platform_pay["wecha_id"] = $thisOrder["wecha_id"];
				$data_platform_pay["token"] = $thisOrder["token"];
				$data_platform_pay["from"] = $this->from;
				$data_platform_pay["time"] = $_SERVER["REQUEST_TIME"];
				$database_platform_pay->data($data_platform_pay)->add();
			}
		}

		if ($this->payType == "weixin") {
			$plat_type = ($_GET["pl"] ? intval($_GET["pl"]) : 0);
			$database_weixin_bill = D("Weixin_bill");

			if (!$database_weixin_bill->where(array("from" => $this->from, "orderid" => $thisOrder["orderid"]))->find()) {
				if ($plat_type != 1) {
					$payConfig = M("Alipay_config")->where(array("token" => $this->token))->find();
					$payConfigInfo = unserialize($payConfig["info"]);
					$appid = ($payConfigInfo["weixin"]["new_appid"] ? $payConfigInfo["weixin"]["new_appid"] : $payConfigInfo["weixin"]["appid"]);
					$mchid = ($payConfigInfo["weixin"]["mchid"] ? $payConfigInfo["weixin"]["mchid"] : "");
				}
				else {
					$appid = C("appid");
					$mchid = C("platform_weixin_mchid");
				}

				$data_system_pay["orderid"] = $thisOrder["orderid"];
				$data_system_pay["price"] = $thisOrder["price"];
				$data_system_pay["wecha_id"] = $thisOrder["wecha_id"];
				$data_system_pay["token"] = $thisOrder["token"];
				$data_system_pay["from"] = $this->from;
				$data_system_pay["time"] = $_SERVER["REQUEST_TIME"];
				$data_system_pay["third_id"] = $third_id;
				$data_system_pay["plat_type"] = $plat_type;
				$data_system_pay["appid"] = $appid;
				$data_system_pay["mchid"] = $mchid;
				$database_weixin_bill->data($data_system_pay)->add();
			}
		}

		return $thisOrder;
	}

	private function apiMicrstorePayNofity($data)
	{
		if (updateSync::getIfWeidian()) {
			$Micrstore_URL = (C("weidian_domain") ? C("weidian_domain") : "http://v.meihua.com");
			$SALT = (C("encryption") ? C("encryption") : "pigcms");
		}
		else {
			$Micrstore_URL = "http://v.meihua.com";
			$SALT = "pigcms";
		}

		$sort_data = $data;
		$sort_data["salt"] = $SALT;
		ksort($sort_data);
		$sort_data = array_map("callback", $sort_data);
		$sign_key = sha1(http_build_query($sort_data));
		$data["sign_key"] = $sign_key;
		$data["request_time"] = time();
		$url = $Micrstore_URL . "/api/pay_notify.php";
		$return = json_decode($this->curl_post($url, $data), true);
	}

	private function apiCashierPayNofity($data)
	{
		if (!$data) {
			$validate = $data;
			$validate["salt"] = "pigcmso2oCashier";
			sort($validate, SORT_STRING);
			$data["sign"] = sha1(implode($validate));
			unset($validate);
			$postdataStr = base64_encode(json_encode($data));
			$purl = ($_SERVER["HTTP_X_FORWARDED_HOST"] ? $_SERVER["HTTP_X_FORWARDED_HOST"] : ($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : ""));
			$purl = strtolower($purl);
			if ((strpos($purl, "http:") === false) && (strpos($purl, "https:") === false)) {
				$purl = "http://" . $purl;
			}

			$purl = rtrim($purl, "/");
			$returnret = $this->curl_post($purl . "/merchants.php?m=Index&c=auth&a=orderCreat", $postdataStr);
		}
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


?>
