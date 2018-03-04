<?php
namespace Common\Api;
class subscribe
{
	public $token;
	public $wecha_id;
	public $siteurl;
	public $thisWxUser = array();

	public function __construct($token, $wecha_id, $data, $siteurl)
	{
		$this->token = $token;
		$this->wecha_id = $wecha_id;
		$this->siteurl = $siteurl;
		$this->thisWxUser = M("Wxuser")->field("appid,appsecret,winxintype")->where(array("token" => $token))->find();
		$this->action = A("Home/Weixin");
	}

	public function sub()
	{
		if ($this->thisWxUser["appid"] && ($this->thisWxUser["winxintype"] == 3)) {
			$apiOauth = new apiOauth();
			$access_token = $apiOauth->update_authorizer_access_token($this->thisWxUser["appid"]);
			$url = "https://api.weixin.qq.com/cgi-bin/user/info?openid=" . $this->wecha_id . "&access_token=" . $access_token;
			$classData = json_decode($this->curlGet($url));
			if ($classData->subscribe && ($classData->subscribe == 1)) {
				$datainfo["wechaname"] = str_replace(array("'", "\\"), array(""), $classData->nickname);
				$datainfo["sex"] = $classData->sex;
				$datainfo["portrait"] = $classData->headimgurl;
				$datainfo["token"] = $this->token;
				$datainfo["wecha_id"] = $this->wecha_id;
				$datainfo["city"] = $classData->city;
				$datainfo["province"] = $classData->province;
				$datainfo["tel"] = "";
				$datainfo["birthday"] = "";
				$datainfo["address"] = "";
				$datainfo["info"] = "";
				$datainfo["sign_score"] = 0;
				$datainfo["expend_score"] = 0;
				$datainfo["continuous"] = 0;
				$datainfo["add_expend"] = 0;
				$datainfo["add_expend_time"] = 0;
				$datainfo["live_time"] = 0;
				$datainfo["getcardtime"] = 0;
			}
		}
		else {
			$datainfo["wechaname"] = "";
			$datainfo["sex"] = "";
			$datainfo["portrait"] = "";
			$datainfo["tel"] = "";
			$datainfo["birthday"] = "";
			$datainfo["address"] = "";
			$datainfo["info"] = "";
			$datainfo["sign_score"] = 0;
			$datainfo["expend_score"] = 0;
			$datainfo["continuous"] = 0;
			$datainfo["add_expend"] = 0;
			$datainfo["add_expend_time"] = 0;
			$datainfo["live_time"] = 0;
			$datainfo["getcardtime"] = 0;
			$datainfo["token"] = $this->token;
			$datainfo["wecha_id"] = $this->wecha_id;
		}

		if (!M("Userinfo")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->getField("id")) {
			$uid = D("Userinfo")->add($datainfo);

			if ($uid) {
				return false;
			}

			if ($cardSet = D("Member_card_set")->where(array("token" => $this->token, "sub_give" => 1))->find()) {
				if ($card = M("Member_card_create")->field("id, number")->where("token='" . $this->token . "' and cardid=" . intval($cardSet["id"]) . " and wecha_id = ''")->order("id ASC")->find()) {
					$is_card = M("Member_card_create")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->find();

					if ($is_card) {
						M("Member_card_create")->where(array("id" => $card["id"]))->save(array("wecha_id" => $this->wecha_id));
						$now = time();

						if (M("Userinfo")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->save(array("getcardtime" => $now))) {
							$gwhere = array(
								"token"   => $this->token,
								"cardid"  => $cardSet["id"],
								"is_open" => "1",
								"start"   => array("lt", $now),
								"end"     => array("gt", $now)
								);
							$gifts = M("Member_card_gifts")->where($gwhere)->select();

							foreach ($gifts as $key => $value ) {
								if ($value["type"] == "1") {
									$arr = array();
									$arr["itemid"] = 0;
									$arr["token"] = $this->token;
									$arr["wecha_id"] = $this->wecha_id;
									$arr["expense"] = 0;
									$arr["time"] = $now;
									$arr["cat"] = 3;
									$arr["staffid"] = 0;
									$arr["notes"] = "开卡赠送积分";
									$arr["score"] = $value["item_value"];
									M("Member_card_use_record")->add($arr);
									M("Userinfo")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->setInc("total_score", $arr["score"]);
								}
								else {
									$cinfo = M("Member_card_coupon")->where(array("token" => $this->token, "id" => $value["item_value"]))->find();

									if ($cinfo["is_weixin"] == 0) {
										$data["token"] = $this->token;
										$data["wecha_id"] = $this->wecha_id;
										$data["coupon_id"] = $value["item_value"];
										$data["is_use"] = "0";
										$data["cardid"] = $cardSet["id"];
										$data["add_time"] = $now;

										if ($cinfo["type"] == 1) {
											$data["coupon_attr"] = serialize(array("coupon_name" => $cinfo["title"]));
										}
										else if ($cinfo["type"] == 2) {
											$data["coupon_attr"] = serialize(array("coupon_name" => $cinfo["title"], "gift_name" => $cinfo["gift_name"], "integral" => $cinfo["integral"]));
										}
										else {
											$data["coupon_attr"] = serialize(array("coupon_name" => $cinfo["title"], "least_cost" => $cinfo["least_cost"], "reduce_cost" => $cinfo["reduce_cost"]));
										}

										if ($value["item_attr"] == 1) {
											$data["coupon_type"] = "1";
										}
										else if ($value["item_attr"] == 2) {
											$data["coupon_type"] = "3";
										}
										else {
											$data["coupon_type"] = "2";
										}

										$data["cancel_code"] = $this->_create_code(12);
										M("Member_card_coupon_record")->add($data);
									}
								}
							}
						}
					}
					else {
						M("Member_card_create")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->delete();
						M("Member_card_create")->where(array("id" => $card["id"]))->save(array("wecha_id" => $this->wecha_id));
					}
				}
			}
		}

		M("Userinfo")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->setField("issub", "1");
		return $this->advanceReply($datainfo["wechaname"], $access_token);
	}

	public function _create_code($length, $type)
	{
		$array = array("number" => "0123456789", "string" => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "mixed" => "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
		$string = $array[$type];
		$count = strlen($string) - 1;
		$rand = "";

		for ($i = 0; $i < $length; $i++) {
			$rand .= $string[mt_rand(0, $count)];
		}

		return $rand;
	}

	public function unsub()
	{
		M("Userinfo")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id))->save(array("issub" => "-1", "wallopen" => "0"));
		$this->action->api("requestdata", "unfollownum");
		echo "";
		return array("", "text");
	}

	public function advanceReply($wechaname, $access_token)
	{
		$reply_list = M("subscribe_reply")->where(array("token" => $this->token))->select();

		if (!$reply_list) {
			foreach ($reply_list as $key => $value ) {
				list($vs_hour, $vs_minute) = explode(":", $value["start_time"]);
				list($ve_hour, $ve_minute) = explode(":", $value["end_time"]);
				$vs_timestamp = mktime($vs_hour, $vs_minute, 0, date("m"), date("d"), date("y"));
				$ve_timestamp = mktime($ve_hour, $ve_minute, 0, date("m"), date("d"), date("y"));
				if (($vs_timestamp < $_SERVER["REQUEST_TIME"]) && ($_SERVER["REQUEST_TIME"] < $ve_timestamp)) {
					switch ($value["reply_type"]) {
					case 1:
						$img = M("img")->where(array("id" => $value["relevance_id"]))->find();

						if (!$img) {
							$url = $this->siteurl . "/index.php?g=Wap&m=Index&a=Replycontent&id=" . $value["relevance_id"] . "&token=" . $this->token;
							$return = array(
								array(
									array($img["title"], $img["text"], $img["pic"], $url)
									),
								"news"
								);
						}
						else {
							$return = false;
						}

						break;

					case 2:
						$wechaname = ($wechaname != "" ? $wechaname : "用户");

						if (strpos($value["title"], "{{微信昵称}}") !== false) {
							$value["title"] = str_replace("{{微信昵称}}", $wechaname, $value["title"]);
						}

						return array(html_entity_decode($value["title"]), "text");
						break;

					case 3:
						$info = M("subscribe_times")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id, "rid" => $value["id"]))->field("times,last_rtime")->find();
						$times = $info["times"];
						$this->clear_subscribe_times(array("isrepeat" => $value["isrepeat"], "token" => $this->token, "wecha_id" => $this->wecha_id, "rid" => $value["id"], "last_rtime" => $info["last_rtime"]));
						if ((($value["isrepeat"] == 1) && ((int) $times < $value["times"])) || (($value["isrepeat"] == 2) && ($times == ""))) {
							$card = M("member_card_coupon")->where(array("id" => $value["relevance_id"]))->find();

							if (!$card) {
								if ($times == "") {
									$data = array();
									$data["wecha_id"] = $this->wecha_id;
									$data["times"] = 1;
									$data["token"] = $this->token;
									$data["rid"] = $value["id"];
									$data["last_rtime"] = time();
									$add = M("subscribe_times")->add($data);

									if ($add) {
										$return = $this->sendCard($card["card_id"], $access_token);

										if (!$return) {
											$return = false;
										}
									}
								}
								else {
									$update = M("subscribe_times")->where(array("wecha_id" => $this->wecha_id, "token" => $this->token, "rid" => $value["id"]))->save(array(
	"times"      => array("exp", "times+1"),
	"last_rtime" => time()
	));

									if ($update) {
										$return = $this->sendCard($card["card_id"], $access_token);

										if (!$return) {
											$return = false;
										}
									}
								}
							}
							else {
								$return = false;
							}
						}
						else {
							$return = false;
						}

						break;

					case 4:
					case 6:
						$info = M("subscribe_times")->where(array("token" => $this->token, "wecha_id" => $this->wecha_id, "rid" => $value["id"]))->field("times,last_rtime")->find();
						$times = $info["times"];
						$this->clear_subscribe_times(array("isrepeat" => $value["isrepeat"], "token" => $this->token, "wecha_id" => $this->wecha_id, "rid" => $value["id"], "last_rtime" => $info["last_rtime"]));
						if ((($value["isrepeat"] == 1) && ((int) $times < $value["times"])) || (($value["isrepeat"] == 2) && ($times == ""))) {
							$hongbao = M("directhongbao")->where(array("id" => $value["relevance_id"]))->find();

							if (!$hongbao) {
								if ($times == "") {
									$return = $this->send_hongbao($wechaname, $hongbao);

									if (!$return) {
										$return = false;
									}
									else {
										$data = array();
										$data["wecha_id"] = $this->wecha_id;
										$data["times"] = 1;
										$data["token"] = $this->token;
										$data["rid"] = $value["id"];
										$data["last_rtime"] = time();
										$add = M("subscribe_times")->add($data);
									}
								}
								else {
									$return = $this->send_hongbao($wechaname, $hongbao);

									if (!$return) {
										$return = false;
									}
									else {
										$update = M("subscribe_times")->where(array("wecha_id" => $this->wecha_id, "token" => $this->token, "rid" => $value["id"]))->save(array(
	"times"      => array("exp", "times+1"),
	"last_rtime" => time()
	));
									}
								}
							}
							else {
								$return = false;
							}
						}
						else {
							$return = false;
						}

						break;

					case 5:
						$value["relevance_id"] = trim($value["relevance_id"], ",");
						$map["id"] = array("exp", " IN (" . $value["relevance_id"] . ") ");
						$img_list = M("img")->where($map)->field("id,title,text,pic,url")->order("field(id," . $value["relevance_id"] . ")")->select();
						$items = array();

						foreach ($img_list as $k => $v ) {
							if ($v["url"] != "") {
								if ((strpos($v["url"], "{siteUrl}") !== false) || (strpos($v["url"], "{wechat_id}") !== false)) {
									$url = str_replace(array("{siteUrl}", "{wechat_id}"), array($this->siteurl, ""), $v["url"]);
									$v["url"] = htmlspecialchars_decode($url);
								}
							}
							else {
								$v["url"] = $this->siteurl . "/index.php?g=Wap&m=Index&a=Replycontent&id=" . $v["id"] . "&token=" . $this->token;
							}

							$i = 0;

							foreach ($v as $kk => $val ) {
								if ($kk != "id") {
									$items[$k][$i] = $val;
									$i++;
								}
							}
						}

						$return = array($items, "news");
						break;

					case 7:
						$img = M("img")->where(array("id" => $value["relevance_id"]))->find();

						if (!$img) {
							if ($img["url"] != "") {
								if ((strpos($img["url"], "{siteUrl}") !== false) || (strpos($img["url"], "{wechat_id}") !== false)) {
									$url = str_replace(array("{siteUrl}", "{wechat_id}"), array($this->siteurl, ""), $img["url"]);
									$img["url"] = htmlspecialchars_decode($url);
								}

								$return = array(
									array(
										array($img["title"], $img["text"], $img["pic"], $img["url"])
										),
									"news"
									);
							}
							else {
								$return = false;
							}
						}
						else {
							$return = false;
						}

						break;

					default:
						$return = false;
						break;
					}
				}
				else {
					continue;
				}

				break;
			}

			return $return;
		}
		else {
			return false;
		}
	}

	private function send_hongbao($wechaname, $params)
	{
		$wechaname = ($wechaname != "" ? $wechaname : "匿名用户");

		if ($this->wecha_id == "") {
			return false;
		}

		$config = array();
		$config["send_name"] = $params["send_name"];
		$config["wishing"] = $params["wishing"];
		$config["act_name"] = $params["act_name"];
		$config["remark"] = $params["remark"];
		$config["token"] = $this->token;
		$config["openid"] = $this->wecha_id;
		$config["money"] = $this->rand_money($params["min_money"], $params["max_money"]);

		if ($params["total_money"] < $config["money"]) {
			return false;
		}

		if ($params["hb_type"] == 1) {
			$config["nick_name"] = $params["send_name"];
			$hb = new Hongbao($config);
			$res = json_decode($hb->send(), true);
		}
		else if ($params["hb_type"] == 2) {
			$config["total_num"] = $params["group_nums"];
			$hb = new Hongbao($config);
			$res = json_decode($hb->FissionSend(), true);
		}
		else {
			return false;
		}

		if ($res["status"] == "SUCCESS") {
			M("directhongbao")->where(array("id" => $params["id"]))->save(array(
	"total_money" => array("exp", "total_money-" . $config["money"])
	));
			$record = array();
			$record["hid"] = $params["id"];
			$record["mch_billno"] = $res["mch_billno"];
			$record["fans_id"] = $this->wecha_id;
			$record["fans_nickname"] = $wechaname;
			$record["money"] = $config["money"];
			$record["hb_type"] = $params["hb_type"];
			$record["token"] = $config["token"];
			M("directhongbao_record")->add($record);
			return true;
		}
		else {
			return false;
		}
	}

	public function sendCard($card_id, $access_token)
	{
		$msgtype = "wxcard";
		$postData = "{\"touser\":\"" . $this->wecha_id . "\",\"msgtype\":\"" . $msgtype . "\",\"" . $msgtype . "\":{\"card_id\":\"" . $card_id . "\"}}";
		$extraUrl = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
		$result_json = $this->curlGet($extraUrl, "POST", $postData);
		$result_array = json_decode($result_json, true);

		if ($result_array["errcode"] == 0) {
			return true;
		}
		else {
			return false;
		}
	}

	public function curlGet($url, $method, $data)
	{
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$temp = curl_exec($ch);
		return $temp;
	}

	private function rand_money($min, $max)
	{
		$rand = $min + ((mt_rand() / mt_getrandmax()) * ($max - $min));
		return round($rand, 2);
	}

	private function clear_subscribe_times($cache_parameter)
	{
		$token = $cache_parameter["token"];
		$wecha_id = $cache_parameter["wecha_id"];
		$isrepeat = $cache_parameter["isrepeat"];
		$rid = $cache_parameter["rid"];
		$last_rtime = $cache_parameter["last_rtime"];

		if ($isrepeat == 2) {
			return false;
		}

		$where = "token = '$token' and wecha_id = '$wecha_id' and rid = $rid";
		$today_time = strtotime(date("Y-m-d 00:00:00", $_SERVER["REQUEST_TIME"]));

		if ($last_rtime < $today_time) {
			M("subscribe_times")->where($where)->save(array("times" => 0));
		}
	}
}


?>
