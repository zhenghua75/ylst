<?php
namespace User\Controller;
use Common\Controller\UserController;
class WechatgroupController extends UserController{
	public $thisWxUser;

	public function _initialize()
	{
		parent::_initialize();
		$where = array("token" => $this->token);
		$this->thisWxUser = M("Wxuser")->where($where)->find();
		if ((!$this->thisWxUser["appid"] || !$this->thisWxUser["appsecret"]) && ($this->thisWxUser["type"] == 0)) {
			$this->error("请先设置AppID和AppSecret再使用本功能，谢谢", "?g=User&m=Index&a=edit&id=" . $this->thisWxUser["id"]);
		}

		if ($this->thisWxUser["winxintype"] != 3) {
		}
	}

	public function index()
	{
		$showStatistics = 1;
		if ($_GET["p"] || $_POST["keyword"]) {
			$showStatistics = 0;
		}

		$this->assign("showStatistics", $showStatistics);
		$group_list_db = M("Wechat_group_list");
		$where = array("token" => $this->token);
		if (IS_POST && strlen(trim($_POST["keyword"]))) {
			$keyword = htmlspecialchars(trim($_POST["keyword"]));
			$where["nickname"] = array("like", "%" . $keyword . "%");
			$list = $group_list_db->where($where)->order("id desc")->select();
		}
		else {
			if ($_GET["wechatgroupid"]) {
				$where["g_id"] = intval($_GET["wechatgroupid"]);
			}

			$count = $group_list_db->where($where)->count();
			$page = new \Think\Page($count, 10);
			$list = $group_list_db->where($where)->limit($page->firstRow . "," . $page->listRows)->order("id desc")->select();
			$this->assign("page", $page->show());
		}

		$wechat_group_db = M("Wechat_group");
		$access_token = $this->_getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/groups/get?access_token=" . $access_token;
		$json = json_decode($this->curlGet($url));
		$wechat_groups = $json->groups;
		$wechat_groups_ids = array();

		if ($wechat_groups) {
			foreach ($wechat_groups as $g ) {
				$thisGroupInDb = $wechat_group_db->where(array("token" => $this->token, "wechatgroupid" => $g->id))->find();
				$arr = array("token" => $this->token, "wechatgroupid" => $g->id, "name" => $g->name, "fanscount" => $g->count);

				if (!$thisGroupInDb) {
					$wechat_group_db->add($arr);
				}
				else {
					$wechat_group_db->where(array("id" => $thisGroupInDb["id"]))->save($arr);
				}

				array_push($wechat_groups_ids, $g->id);
			}
		}

		$wechat_group_db = M("Wechat_group");
		$groups = $wechat_group_db->where(array("token" => $this->token))->order("id ASC")->select();
		$this->assign("groups", $groups);
		$groupsByWechatGroupID = array();

		if ($groups) {
			foreach ($groups as $g ) {
				$groupsByWechatGroupID[$g["wechatgroupid"]] = $g;
			}
		}

		if ($list) {
			$i = 0;

			foreach ($list as $item ) {
				$t = substr($item["headimgurl"], 0, -1);
				$list[$i]["smallheadimgurl"] = $item["headimgurl"];
				$list[$i]["groupName"] = $groupsByWechatGroupID[$item["g_id"]]["name"];
				$i++;
			}
		}

		$this->assign("list", $list);

		if ($showStatistics) {
			$fansCount = $group_list_db->where($where)->count();
			$where["sex"] = 1;
			$maleCount = $group_list_db->where($where)->count();
			$where["sex"] = 2;
			$femaleCount = $group_list_db->where($where)->count();
			$this->assign("fansCount", $fansCount);
			$this->assign("maleCount", $maleCount);
			$this->assign("femaleCount", $femaleCount);
			$unknownSexCount = $fansCount - $maleCount - $femaleCount;
			$this->assign("unknownSexCount", $unknownSexCount);
			$xml = "<chart borderThickness=\"0\" caption=\"粉丝性别比例图\" baseFontColor=\"666666\" baseFont=\"宋体\" baseFontSize=\"14\" bgColor=\"FFFFFF\" bgAlpha=\"0\" showBorder=\"0\" bgAngle=\"360\" pieYScale=\"90\"  pieSliceDepth=\"5\" smartLineColor=\"666666\"><set label=\"男性\" value=\"" . $maleCount . "\"/><set label=\"女性\" value=\"" . $femaleCount . "\"/><set label=\"未知性别\" value=\"" . $unknownSexCount . "\"/></chart>";
			$this->assign("xml", $xml);
		}

		$this->display();
	}

	public function send()
	{
		if (IS_GET) {
			if ($_GET["wxupover"] != "y") {
				if ($_GET["next_openid"] == "") {
					session("fansTextData", NULL);
				}

				$access_token = $this->_getAccessToken();
				$url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=" . $access_token;

				if ($_GET["next_openid"]) {
					$url .= "&next_openid=" . $_GET["next_openid"];
				}

				$json_token = json_decode($this->curlGet($url), true);

				if (0 < $json_token["errcode"]) {
					$this->error("获取粉丝接口调用超过限制，请稍后再来");
					exit();
				}

				$arrayData = $json_token["data"]["openid"];
				$nextOpenID = $json_token["next_openid"];
				$textData = implode(",", $arrayData);
				$textData_session = session("fansTextData");

				if ($textData_session == "") {
					$textData_session = $textData;
				}
				else {
					$textData_session .= "," . $textData;
				}

				session("fansTextData", $textData_session);

				if (count($arrayData) < 10000) {
					$this->success("粉丝列表获取完成，正在写入数据库……", U("User/Wechat_group/send", array("token" => $this->token, "wxupover" => "y")));
				}
				else {
					$this->success("正在获取粉丝列表……", U("User/Wechat_group/send", array("token" => $this->token, "next_openid" => $nextOpenID)));
				}
			}
			else {
				$textData_session = session("fansTextData");
				session("fansTextData", $textData_session);
				$arrayData = explode(",", $textData_session);
				$a = 0;
				$b = 0;
				$n = 0;
				$num = intval($_GET["num"]);

				for ($i = $num; $i < ($num + 20); $i++) {
					$check = M("Wechat_group_list")->field("openid")->where(array("openid" => $arrayData[$i], "token" => $this->token))->find();

					if ($check == false) {
						if ($arrayData[$i] != "") {
							M("Wechat_group_list")->data(array("openid" => $arrayData[$i], "token" => $this->token))->add();
							$a++;
						}
					}
					else {
						$b++;
					}

					if ((count($arrayData) - 1) <= $i) {
						$over = true;
						$upnum = $n + 1;
					}
					else {
						$over = false;
						$n++;
						$upnum = $n;
					}
				}

				$upnum = intval($_GET["num"]) + $upnum;

				if ($over) {
					session("fansTextData", NULL);
					$this->success("更新完成" . count($arrayData) . "条,现在获取粉丝详细信息", "?g=User&m=Wechat_group&a=send_info&token=" . $this->token);
				}
				else {
					$this->success("本次更新" . $a . "条,重复" . ($b = ($b == 1 ? 0 : $b . "条，已更新粉丝" . $upnum . "条")), U("User/Wechat_group/send", array("token" => $this->token, "wxupover" => "y", "num" => $num + 20)));
				}
			}
		}
		else {
			$this->error("非法操作");
		}
	}

	public function send_info()
	{
		if (IS_GET) {
			$refreshAll = ($_GET["all"] ? 1 : 0);
			$access_token = $this->_getAccessToken();

			if ($refreshAll) {
				$fansCount = M("Wechat_group_list")->where(array("token" => session("token")))->count();
				$i = intval($_GET["i"]);
				$step = 20;
				$fans = M("Wechat_group_list")->where(array("token" => session("token")))->order("id DESC")->limit($i, $step)->select();

				if ($fans) {
					foreach ($fans as $data_all ) {
						$url2 = "https://api.weixin.qq.com/cgi-bin/user/info?openid=" . $data_all["openid"] . "&access_token=" . $access_token;
						$classData = json_decode($this->curlGet($url2));
						if ((0 < $classData->errcode) && ($classData->errcode != 40003)) {
							$this->error("获取粉丝接口调用超过限制，请稍后再来");
							exit();
						}

						if ($classData->subscribe == 1) {
							$data["nickname"] = str_replace(array("'", "\\"), array(""), $classData->nickname);
							$data["sex"] = $classData->sex;
							$data["city"] = $classData->city;
							$data["province"] = $classData->province;
							$data["headimgurl"] = $classData->headimgurl;
							$data["subscribe_time"] = $classData->subscribe_time;
							S($this->token . "_" . $data_all["openid"], NULL);
							$data["g_id"] = $classData->groupid;
							M("wechat_group_list")->where(array("id" => $data_all["id"]))->save($data);
							S("fans_" . $this->token . "_" . $data_all["openid"], NULL);
						}
						else {
							M("wechat_group_list")->delete($data_all["id"]);
						}
					}

					$i = $step + $i;
					$this->success("更新中请勿关闭...进度：" . $i . "/" . $fansCount, "?g=User&m=Wechat_group&a=send_info&token=" . $this->token . "&all=1&i=" . $i);
				}
				else {
					$this->success("更新完毕", "?g=User&m=Wechat_group&a=index&token=" . $this->token);
					exit();
				}
			}
			else {
				$dataAll = M("Wechat_group_list")->field("openid,id")->where(array("token" => session("token"), "subscribe_time" => ""))->order("id desc")->limit(20)->select();

				if ($dataAll == false) {
					$this->success("更新完毕", "?g=User&m=Wechat_group&a=index&token=" . $this->token);
					exit();
				}

				$i = 0;

				foreach ($dataAll as $data_all ) {
					$url2 = "https://api.weixin.qq.com/cgi-bin/user/info?openid=" . $data_all["openid"] . "&access_token=" . $access_token;
					$classData = json_decode($this->curlGet($url2));
					if ((0 < $classData->errcode) && ($classData->errcode != 40003)) {
						$this->error("获取粉丝接口调用超过限制，请稍后再来");
						exit();
					}

					if ($classData->subscribe == 1) {
						$data["openid"] = $classData->openid;
						$data["nickname"] = str_replace("'", "", $classData->nickname);
						$data["sex"] = $classData->sex;
						$data["city"] = $classData->city;
						$data["province"] = $classData->province;
						$data["headimgurl"] = $classData->headimgurl;
						$data["subscribe_time"] = $classData->subscribe_time;
						$data["token"] = session("token");
						$data["id"] = $data_all["id"];
						S($this->token . "_" . $data_all["openid"], NULL);
						$data["g_id"] = $classData->groupid;
						M("wechat_group_list")->save($data);
						$i++;
					}
					else {
						M("wechat_group_list")->delete($data_all["id"]);
					}
				}

				$count = M("Wechat_group_list")->field("id")->where(array("token" => session("token"), "subscribe_time" => ""))->count();
				$this->success("还有" . $count . "个粉丝信息没有更新,<br />请耐心等待", U("Wechat_group/send_info", array("token" => $this->token)));
			}
		}
		else {
			$this->error("非法操作");
		}
	}

	public function setGroup()
	{
		if (IS_POST) {
			$wechat_group_list_db = M("wechat_group_list");
			$wechatgroupid = intval(I("post.wechatgroupid"));
			$access_token = $this->_getAccessToken();

			foreach ($_POST as $k => $v ) {
				if (!strpos($k, "id_") === false) {
					$id = intval(str_replace("id_", "", $k));
					$thisFans = $wechat_group_list_db->where(array("id" => $id, "token" => $this->token))->find();
					$url = "https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=" . $access_token;
					$json = json_decode($this->curlGet($url, "post", "{\"openid\":\"" . $thisFans["openid"] . "\",\"to_groupid\":" . $wechatgroupid . "}"));
					$wechat_group_list_db->where(array("id" => $id))->save(array("g_id" => $wechatgroupid));
				}
			}

			$this->success("设置完毕", "?g=User&m=Wechat_group&a=index&token=" . $this->token);
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

	public function showExternalPic()
	{
		$types = array("gif" => "image/gif", "jpeg" => "image/jpeg", "jpg" => "image/jpeg", "jpe" => "image/jpeg", "png" => "image/png");
		$wecha_id = I("get.wecha_id");
		$token = I("get.token");
		$imgData = S($token . "_" . $wecha_id);

		if (!$imgData) {
			$url = $_GET["url"];
			$dir = pathinfo($url);
			$host = $dir["dirname"];
			$refer = "http://www.qq.com/";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_REFERER, $refer);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close($ch);
			$ext = strtolower(substr(strrchr($url, "."), 1, 10));
			$ext = "jpg";
			$type = ($types[$ext] ? $types[$ext] : "image/jpeg");
			S($token . "_" . $wecha_id, $data);
			header("Content-type: " . $type);
			echo $data;
		}
		else {
			$ext = "jpg";
			$type = ($types[$ext] ? $types[$ext] : "image/jpeg");
			header("Content-type: " . $type);
			echo $imgData;
		}
	}

	public function groups()
	{
		$wechat_group_db = M("Wechat_group");
		$groups = $wechat_group_db->where(array("token" => $this->token))->order("id ASC")->select();
		$this->assign("groups", $groups);
		$this->display();
	}

	public function sysGroups()
	{
		$wechat_group_db = M("Wechat_group");
		$access_token = $this->_getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/groups/get?access_token=" . $access_token;
		$json = json_decode($this->curlGet($url));
		$wechat_groups = $json->groups;
		$wechat_groups_ids = array();

		if ($wechat_groups) {
			foreach ($wechat_groups as $g ) {
				$thisGroupInDb = $wechat_group_db->where(array("token" => $this->token, "wechatgroupid" => $g->id))->find();
				$arr = array("token" => $this->token, "wechatgroupid" => $g->id, "name" => $g->name, "fanscount" => $g->count);

				if (!$thisGroupInDb) {
					$wechat_group_db->add($arr);
				}
				else {
					$wechat_group_db->where(array("id" => $thisGroupInDb["id"]))->save($arr);
				}

				array_push($wechat_groups_ids, $g->id);
			}
		}

		$groups = $wechat_group_db->where(array("token" => $this->token))->order("id ASC")->select();

		if ($groups) {
			foreach ($groups as $g ) {
				if (!in_array($g["wechatgroupid"], $wechat_groups_ids)) {
					$wechat_group_db->where(array("id" => $g["id"]))->delete();
				}
			}
		}

		$this->success("操作成功", U("Wechat_group/groups"));
	}

	public function groupSet()
	{
		if ($this->wxuser["winxintype"] != "3") {
			$this->error("只有认证的服务号才可以添加分组！");
			exit();
		}

		$wechat_group_db = M("Wechat_group");
		$thisGroup = $wechat_group_db->where(array("id" => intval($_GET["id"])))->find();
		if ($thisGroup && ($thisGroup["token"] != $this->token)) {
			$this->error("非法操作");
		}

		if (IS_POST) {
			$arr = array();
			$arr["name"] = I("post.name");
			$arr["intro"] = I("post.intro");
			$arr["token"] = $this->token;
			$access_token = $this->_getAccessToken();

			if ($_POST["id"]) {
				$url = "https://api.weixin.qq.com/cgi-bin/groups/update?access_token=" . $access_token;
				$json = json_decode($this->curlGet($url, "post", "{\"group\":{\"id\":" . $thisGroup["wechatgroupid"] . ",\"name\":\"" . $arr["name"] . "\"}}"));
				$wechat_group_db->where(array("id" => intval($_POST["id"])))->save($arr);
			}
			else {
				$url = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=" . $access_token;
				$json = json_decode($this->curlGet($url, "post", "{\"group\":{\"name\":\"" . $arr["name"] . "\"}}"));
				$arr["wechatgroupid"] = $json->group->id;
				$wechat_group_db->add($arr);
			}

			$this->success("操作成功", U("Wechat_group/groups"));
		}
		else {
			$this->assign("thisGroup", $thisGroup);
			$this->display();
		}
	}

	public function groupDelete()
	{
	}

	public function _getAccessToken()
	{
		$apiOauth = new \Common\Api\apiOauth();
		$access_token = $apiOauth->update_authorizer_access_token($this->thisWxUser["appid"]);
		return $access_token;
	}

	public function info()
	{
		$db = M("Userinfo");
		$tip = I("get.tip", "intval");
		$this->assign("tip", $tip);
		if (IS_POST && strlen(trim($_POST["keyword"]))) {
			$tip = I("post.tip", "intval");

			if ($tip == "") {
				$where["wecha_id"] = array("notlike", "z_%");
			}
			else {
				$where["wecha_id"] = array("like", "z_%");
			}

			$type = I("post.type", "intval");
			$keyword = htmlspecialchars(trim($_POST["keyword"]));

			if ($type == 1) {
				$where["wechaname"] = array("like", "%" . $keyword . "%");
			}
			else {
				$where["tel"] = array("like", "%" . $keyword . "%");
			}

			$where["token"] = $this->token;
			$count = $db->where($where)->count();
			$page = new \Think\Page($count, 15);
			$list = $db->where($where)->limit($page->firstRow . "," . $page->listRows)->order("id desc")->select();

			if ($list == "") {
				$this->error("没有找到匹配的粉丝信息");
			}

			$this->assign("type", $type);
			$this->assign("keyword", $keyword);
		}
		else {
			if ($tip == "") {
				$where["wecha_id"] = array("notlike", "z_%");
			}
			else {
				$where["wecha_id"] = array("like", "z_%");
			}

			$where["token"] = $this->token;
			$count = $db->where($where)->count();
			$page = new \Think\Page($count, 15);
			$list = $db->where($where)->limit($page->firstRow . "," . $page->listRows)->order("id desc")->select();
		}

		$groups = M("Wechat_group_list");
		$group = M("Wechat_group");

		foreach ($list as $k => $v ) {
			$id = $groups->where(array("openid" => $v["wecha_id"], "token" => $this->token))->getField("g_id");
			$list[$k]["wechatname"] = $group->where(array("token" => $this->token, "wechatgroupid" => $id))->getField("name");
		}

		$wx = M("Wxuser");
		$fuwu = $wx->where(array("token" => $this->token))->getField("fuwuappid");
		$this->assign("tip", $tip);
		$this->assign("fuwu", $fuwu);
		$this->assign("list", $list);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function setinfo()
	{
		$id = I("get.id", "intval");
		$db = M("Userinfo");
		$list = $db->where(array("id" => $id))->find();
		$html_layout = $this->html_layout($this->token, $list);
		$this->assign("verify", $html_layout["verify"]);
		$this->assign("formData", $html_layout["string"]);
		$this->assign("list", $list);
		$group = M("Wechat_group");
		$gro = $group->where(array("token" => $this->token))->select();
		$groups = M("Wechat_group_list");
		$gid = $groups->where(array("token" => $this->token, "openid" => $list["wecha_id"]))->getField("g_id");

		if ($_POST) {
			$attact = ($_POST["data"] ? $_POST["data"] : array());
			unset($attact[0]);

			foreach ($attact as $index => $row ) {
				switch ($row["item_name"]) {
				case "wechaname":
					$data["wechaname"] = $row["val"];
					break;

				case "tel":
					$data["tel"] = $row["val"];
					break;

				case "portrait":
					$data["portrait"] = $row["val"];
					break;

				case "truename":
					$data["truename"] = $row["val"];
					break;

				case "qq":
					$data["qq"] = $row["val"];
					break;

				case "paypass":
					$data["paypass"] = md5($row["val"]);
					break;

				case "sex":
					if ($row["val"] == "男") {
						$data["sex"] = 1;
					}
					else if ($row["val"] == "女") {
						$data["sex"] = 2;
					}
					else if ($row["val"] == "其他") {
						$data["sex"] = 3;
					}

					break;

				case "bornyear":
					$data["bornyear"] = $row["val"];
					break;

				case "bornmonth":
					$data["bornmonth"] = $row["val"];
					break;

				case "bornday":
					$data["bornday"] = $row["val"];
					break;

				case "address":
					$data["address"] = $row["val"];
					break;

				case "origin":
					$data["origin"] = $row["val"];
					break;

				default:
					$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : "");
				}
			}

			$info = $db->where(array("id" => $id))->save($data);
			$this->save_attach($id, $attact);
			S("fans_" . $this->token . "_" . $list["wecha_id"], NULL);
			exit(json_encode(array("error" => false, "msg" => "ok")));

			if ($info) {
				$this->success("修改成功", U("Wechat_group/info"));
				exit();
			}
			else {
				$this->error("修改失败");
			}
		}

		$this->assign("gid", $gid);
		$this->assign("gro", $gro);
		$this->display();
	}

	private function save_attach($uid, $data)
	{
		$db = D("Userinfo_attach");
		$attachs = $db->where(array("uid" => $uid))->select();
		$list = array();

		foreach ($attachs as $a ) {
			$list[$a["field_id"]] = $a;
		}

		foreach ($data as $d ) {
			if ($d["id"] || !is_numeric($d["id"])) {
				continue;
			}

			if ($d["type"] == "password") {
				$d["val"] = md5($d["val"]);
			}

			if ($list[$d["id"]]) {
				$db->where(array("uid" => $uid, "field_id" => $d["id"]))->save(array("uid" => $uid, "field_id" => $d["id"], "field_value" => $d["val"]));
				unset($list[$d["id"]]);
			}
			else {
				$db->add(array("uid" => $uid, "field_id" => $d["id"], "field_value" => $d["val"]));
			}
		}

		if ($list) {
			$ids = array();

			foreach ($list as $l ) {
				$ids[] = $l["field_id"];
			}

			$db->where(array(
	"uid"      => $uid,
	"field_id" => array("in", $ids)
	))->delete();
		}
	}

	private function html_layout($token, $userinfo)
	{
		$fields = M("Member_card_custom_field")->where(array("token" => $token))->order("sort DESC")->select();

		if ($fields) {
			$conf = M("Member_card_custom")->where(array("token" => $token))->find();

			if ($conf) {
				$conf = array("wechaname" => 1, "is_wechaname" => 1, "tel" => 1, "is_tel" => 0, "portrait" => 1, "is_portrait" => 0, "truename" => 1, "is_truename" => 0, "qq" => 1, "is_qq" => 0, "paypass" => 1, "is_paypass" => 1, "sex" => 1, "is_sex" => 0, "bornyear" => 1, "is_bornyear" => 0, "bornmonth" => 1, "is_bornmonth" => 0, "bornday" => 1, "is_bornday" => 0, "address" => 1, "is_address" => 0, "origin" => 1, "is_origin" => 0);
			}

			$fields[] = array("field_id" => "m1", "item_name" => "wechaname", "field_name" => "微信昵称", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["wechaname"], "is_empty" => $conf["is_wechaname"]);
			$fields[] = array("field_id" => "m2", "item_name" => "tel", "field_name" => "手机号", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["tel"], "is_empty" => $conf["is_tel"]);
			$fields[] = array("field_id" => "m3", "item_name" => "portrait", "field_name" => "头像", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["portrait"], "is_empty" => $conf["is_portrait"]);
			$fields[] = array("field_id" => "m4", "item_name" => "truename", "field_name" => "真实姓名", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["truename"], "is_empty" => $conf["is_truename"]);
			$fields[] = array("field_id" => "m5", "item_name" => "qq", "field_name" => "QQ号码", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["qq"], "is_empty" => $conf["is_qq"]);
			$fields[] = array("field_id" => "m7", "item_name" => "sex", "field_name" => "性别", "field_option" => "男|女|其他", "field_type" => "select", "field_match" => "", "is_show" => $conf["sex"], "is_empty" => $conf["is_sex"]);
			$fields[] = array("field_id" => "m8", "item_name" => "bornyear", "field_name" => "出生年", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornyear"], "is_empty" => $conf["is_bornyear"]);
			$fields[] = array("field_id" => "m9", "item_name" => "bornmonth", "field_name" => "出生月", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornmonth"], "is_empty" => $conf["is_bornmonth"]);
			$fields[] = array("field_id" => "m10", "item_name" => "bornday", "field_name" => "出生日", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornday"], "is_empty" => $conf["is_bornday"]);
			$fields[] = array("field_id" => "m10", "item_name" => "address", "field_name" => "地址", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["address"], "is_empty" => $conf["is_address"]);
			$fields[] = array("field_id" => "m12", "item_name" => "origin", "field_name" => "来源渠道", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["origin"], "is_empty" => $conf["is_origin"]);
		}
		else {
			if ($conf = M("Member_card_custom")->where(array("token" => $token))->find()) {
				$old_fields[] = array("field_id" => "m1", "item_name" => "wechaname", "field_name" => "微信昵称", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["wechaname"], "is_empty" => $conf["is_wechaname"]);
				$old_fields[] = array("field_id" => "m2", "item_name" => "tel", "field_name" => "手机号", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["tel"], "is_empty" => $conf["is_tel"]);
				$old_fields[] = array("field_id" => "m3", "item_name" => "portrait", "field_name" => "头像", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["portrait"], "is_empty" => $conf["is_portrait"]);
				$old_fields[] = array("field_id" => "m4", "item_name" => "truename", "field_name" => "真实姓名", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["truename"], "is_empty" => $conf["is_truename"]);
				$old_fields[] = array("field_id" => "m5", "item_name" => "qq", "field_name" => "QQ号码", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["qq"], "is_empty" => $conf["is_qq"]);
				$old_fields[] = array("field_id" => "m7", "item_name" => "sex", "field_name" => "性别", "field_option" => "男|女|其他", "field_type" => "select", "field_match" => "", "is_show" => $conf["sex"], "is_empty" => $conf["is_sex"]);
				$old_fields[] = array("field_id" => "m8", "item_name" => "bornyear", "field_name" => "出生年", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornyear"], "is_empty" => $conf["is_bornyear"]);
				$old_fields[] = array("field_id" => "m9", "item_name" => "bornmonth", "field_name" => "出生月", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornmonth"], "is_empty" => $conf["is_bornmonth"]);
				$old_fields[] = array("field_id" => "m10", "item_name" => "bornday", "field_name" => "出生日", "field_option" => "", "field_type" => "text", "field_match" => "^[\u4e00-\u9fa5\a-zA-Z0-9]+\$", "is_show" => $conf["bornday"], "is_empty" => $conf["is_bornday"]);
			}

			$old_fields && ($fields = array_merge($old_fields, $fields));
		}

		$list = array();

		if ($userinfo) {
			$db = D("Userinfo_attach");
			$attachs = $db->where(array("uid" => $userinfo["id"]))->select();

			foreach ($attachs as $a ) {
				$list[$a["field_id"]] = $a;
			}
		}

		$str = "";
		$arr = array();

		foreach ($fields as $key => $value ) {
			if ($value["is_show"] && !in_array($value["item_name"], array("wechaname", "sex", "tel", "portrait"))) {
				continue;
			}

			switch ($value["item_name"]) {
			case "wechaname":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["wechaname"] ? $userinfo["wechaname"] : ""));
				break;

			case "tel":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["tel"] ? $userinfo["tel"] : ""));
				break;

			case "portrait":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["portrait"] ? $userinfo["portrait"] : ""));
				break;

			case "truename":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["truename"] ? $userinfo["truename"] : ""));
				break;

			case "qq":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["qq"] ? $userinfo["qq"] : ""));
				break;

			case "sex":
				$t = array(1 => "男", 0 => "女", 1 => "其他");
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($t[$userinfo["sex"]] ? $t[$userinfo["sex"]] : ""));
				break;

			case "bornyear":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["bornyear"] ? $userinfo["bornyear"] : ""));
				break;

			case "bornmonth":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["bornmonth"] ? $userinfo["bornmonth"] : ""));
				break;

			case "bornday":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["bornday"] ? $userinfo["bornday"] : ""));
				break;

			case "address":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["address"] ? $userinfo["address"] : ""));
				break;

			case "origin":
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : ($userinfo["origin"] ? $userinfo["origin"] : ""));
				break;

			default:
				$v = ($list[$value["field_id"]]["field_value"] ? $list[$value["field_id"]]["field_value"] : "");
			}

			if ($value["item_name"] == "portrait") {
				$str .= "<tr>";
				$str .= "<th valign=\"top\"><label for=\"info\">头像：</label></th>";
				$str .= "<td>";
				$str .= "<input class=\"px\"  name=\"portrait\" value=\"" . $v . "\" id=\"portrait\" style=\"width:363px;\"  />";
				$str .= "<script src=\"" . $this->staticPath . "__PUBLIC__/web/js/upyun.js\"></script><a href=\"###\" onclick=\"upyunPicUpload('portrait',0,0,'" . $token . "')\" class=\"a_upload\">上传</a> <a href=\"###\" onclick=\"viewImg('portrait')\">预览</a>";
				$str .= "</td>";
				$str .= "</tr>";
			}
			else {
				$str .= "<tr><th valign=\"top\"><label>" . $value["field_name"] . "<input type=\"hidden\" id=\"field_" . $value["field_id"] . "\" name=\"field_" . $value["field_id"] . "\" value=\"" . $value["field_id"] . "\"></label></th>";
				$str .= $this->_getInput($value, $v);
				$str .= "</tr>";
			}

			$arr[] = array("id" => $value["field_id"], "name" => $value["field_name"], "type" => $value["field_type"], "item_name" => $value["item_name"], "match" => $value["field_match"], "is_empty" => $value["is_empty"]);
		}

		return array("string" => $str, "verify" => $arr);
	}

	private function _getInput($value, $v)
	{
		$input = "";

		switch ($value["field_type"]) {
		case "text":
			$input .= "<td><input type=\"text\" class=\"px\" id=\"field_id_" . $value["field_id"] . "\" name=\"field_id_" . $value["field_id"] . "\" value=\"" . $v . "\"></td>";
			break;

		case "textarea":
			$input .= "<td><textarea name=\"field_id_" . $value["field_id"] . "\" id=\"field_id_" . $value["field_id"] . "\" rows=\"4\" cols=\"25\">" . $v . "</textarea></td>";
			break;

		case "checkbox":
			$option = explode("|", $value["field_option"]);
			$v_arr = explode("|", $v);
			$input .= "<td height=\"39\">";

			for ($i = 0; $i < count($option); $i++) {
				if ($v_arr && in_array($option[$i], $v_arr)) {
					$checked = "checked=true";
				}
				else {
					$checked = "";
				}

				$input .= "<label><input type=\"checkbox\" name=\"field_id_" . $value["field_id"] . "[]\" class=\"field_id_" . $value["field_id"] . "\" value=\"" . $option[$i] . "\" " . $checked . " />" . $option[$i] . "</label>　";
			}

			$input .= "</td>";
			break;

		case "radio":
			$option = explode("|", $value["field_option"]);
			$input .= "<td height=\"39\">";

			for ($i = 0; $i < count($option); $i++) {
				if ($v) {
					$checked = ($v == $option[$i] ? "checked=true" : "");
				}
				else {
					$checked = ($i == 0 ? "checked=true" : "");
				}

				$input .= "<label><input type=\"radio\" name=\"field_id_" . $value["field_id"] . "\" class=\"field_id_" . $value["field_id"] . "\" value=\"" . $option[$i] . "\" " . $checked . " />" . $option[$i] . "</label>　";
			}

			$input .= "</td>";
			break;

		case "select":
			$input .= "<td><select name=\"field_id_" . $value["field_id"] . "\" id=\"field_id_" . $value["field_id"] . "\" class=\"dropdown-select\"><option value=\"\">请选择..</option>";
			$op_arr = explode("|", $value["field_option"]);
			$num = count($op_arr);

			if (0 < $num) {
				for ($i = 0; $i < $num; $i++) {
					if ($v && ($v == $op_arr[$i])) {
						$input .= "<option value=\"" . $op_arr[$i] . "\" selected>" . $op_arr[$i] . "</option>";
					}
					else {
						$input .= "<option value=\"" . $op_arr[$i] . "\">" . $op_arr[$i] . "</option>";
					}
				}
			}

			$input .= "</select></td>";
			break;

		case "date":
			$v = ($v ? $v : date("Y-m-d"));
			$input .= "<td><input type=\"text\" class=\"px\" name=\"field_id_" . $value["field_id"] . "\" id=\"field_id_" . $value["field_id"] . "\" value=\"" . $v . "\" onClick=\"WdatePicker()\"/></td>";
		}

		return $input;
	}

	public function exportmember()
	{
		$page = ($_GET["page"] ? intval($_GET["page"]) : 1);
		$id = I("get.id", "intval");
		$token = $this->token;
		$tip = intval($_GET["tip"]);

		if ($tip == "") {
			$where["wecha_id"] = array("notlike", "z_%");
		}
		else {
			$where["wecha_id"] = array("like", "z_%");
		}

		$where["token"] = $token;
		$createInfo = M("Userinfo")->field("id, truename, wechaname, tel, total_score, sex, bornyear, bornmonth, bornday, portrait, qq, getcardtime, expensetotal, balance, wecha_id, address")->where($where)->order("id DESC")->select();
		$result = $uids = array();

		foreach ($createInfo as $row ) {
			$result[$row["id"]] = $row;
			$uids[] = $row["id"];
		}

		$column_name = array("O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
		$column_field = array("A" => "number", "B" => "truename", "C" => "tel", "D" => "total_score", "E" => "sex", "F" => "bornyear", "G" => "bornmonth", "H" => "bornday", "I" => "portrait", "G" => "qq", "K" => "getcardtime", "L" => "expensetotal", "M" => "balance", "N" => "wecha_id", "O" => "address");
		import("@.ORG.phpexcel.PHPExcel");
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		$objProps->setCreator("会员信息");
		$objProps->setTitle("会员信息");
		$objProps->setSubject("会员信息");
		$objProps->setDescription("会员信息");
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setCellValue("A1", "会员编号");
		$objActSheet->setCellValue("B1", "姓名");
		$objActSheet->setCellValue("C1", "手机号");
		$objActSheet->setCellValue("D1", "积分");
		$objActSheet->setCellValue("E1", "性别 (男； 女； 其他)");
		$objActSheet->setCellValue("F1", "出生年");
		$objActSheet->setCellValue("G1", "出生月");
		$objActSheet->setCellValue("H1", "出生日");
		$objActSheet->setCellValue("I1", "头像地址");
		$objActSheet->setCellValue("J1", "QQ号");
		$objActSheet->setCellValue("K1", "领卡时间");
		$objActSheet->setCellValue("L1", "消费总额(单位：元)");
		$objActSheet->setCellValue("M1", "余额 (单位：元)");
		$objActSheet->setCellValue("N1", "微信OpenID");
		$objActSheet->setCellValue("O1", "会员地址");
		$fields = M("Member_card_custom_field")->field("field_id, field_name, item_name")->where(array("token" => $token, "is_show" => "1"))->select();
		$tarray = $column_field;
		$id_field = array();
		$index_id = array();

		foreach ($fields as $field ) {
			$id_field[$field["field_id"]] = $field["item_name"];

			if ($field["item_name"]) {
				$index_id[$field["item_name"]] = $field["field_id"];
			}

			if ($field["item_name"] && !in_array($field["item_name"], $tarray)) {
				if ($column_name) {
					break;
				}

				$letter = array_shift($column_name);
				$objActSheet->setCellValueExplicit($letter . "1", $field["field_name"]);
				$column_field[$letter] = $field["field_id"];
			}
		}

		$list = array();
		$attachs = M("Userinfo_attach")->where(array(
	"uid" => array("in", $uids)
	))->select();

		foreach ($attachs as $use ) {
			$list[$use["uid"]][$use["field_id"]] = $use["field_value"];
		}

		$key_id = array_flip($column_field);
		$index = 2;

		foreach ($result as $uid => $res ) {
			$attachs = $list[$uid];
			$objActSheet->setCellValueExplicit("A" . $index, $uid);
			if ($res["truename"] && $index_id["truename"]) {
				$res["truename"] = $attachs[$index_id["truename"]];
			}

			$objActSheet->setCellValueExplicit("B" . $index, $res["truename"]);
			if ($res["tel"] && $index_id["tel"]) {
				$res["tel"] = $attachs[$index_id["tel"]];
			}

			$objActSheet->setCellValueExplicit("C" . $index, $res["tel"] . " ");
			$objActSheet->setCellValueExplicit("D" . $index, $res["total_score"]);
			if ($res["sex"] && $index_id["sex"]) {
				$res["sex"] = $attachs[$index_id["sex"]];
			}

			$res["sex"] = ($res["sex"] == 1 ? "男" : ($res["sex"] == 2 ? "女" : "其他"));
			$objActSheet->setCellValueExplicit("E" . $index, $res["sex"]);
			if ($res["bornyear"] && $index_id["bornyear"]) {
				$res["bornyear"] = $attachs[$index_id["bornyear"]];
			}

			$objActSheet->setCellValue("F" . $index, $res["bornyear"]);
			if ($res["bornmonth"] && $index_id["bornmonth"]) {
				$res["bornmonth"] = $attachs[$index_id["bornmonth"]];
			}

			$objActSheet->setCellValueExplicit("G" . $index, $res["bornmonth"]);
			if ($res["bornday"] && $index_id["bornday"]) {
				$res["bornday"] = $attachs[$index_id["bornday"]];
			}

			$objActSheet->setCellValueExplicit("H" . $index, $res["bornday"]);
			if ($res["portrait"] && $index_id["portrait"]) {
				$res["portrait"] = $attachs[$index_id["portrait"]];
			}

			$objActSheet->setCellValueExplicit("I" . $index, $res["portrait"]);
			if ($res["qq"] && $index_id["qq"]) {
				$res["qq"] = $attachs[$index_id["qq"]];
			}

			$objActSheet->setCellValueExplicit("J" . $index, $res["qq"]);
			$res["getcardtime"] = ($res["getcardtime"] ? date("Y-m-d", $res["getcardtime"]) : "暂无");
			$objActSheet->setCellValueExplicit("K" . $index, $res["getcardtime"]);
			$objActSheet->setCellValueExplicit("L" . $index, $res["expensetotal"]);
			$objActSheet->setCellValueExplicit("M" . $index, $res["balance"]);
			$objActSheet->setCellValueExplicit("N" . $index, $res["wecha_id"]);
			$objActSheet->setCellValueExplicit("O" . $index, $res["address"]);

			foreach ($attachs as $fid => $fvalue ) {
				if ($id_field[$fid]) {
					continue;
				}

				if ($key_id[$fid]) {
					$objActSheet->setCellValueExplicit($key_id[$fid] . $index, $fvalue . " ");
				}
			}

			$index++;
		}

		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header("Content-Disposition:attachment;filename=\"member_info.xls\"");
		header("Content-Transfer-Encoding:binary");
		$objWriter->save("php://output");
		exit();
	}
}