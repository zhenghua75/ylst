<?php
namespace User\Controller;
use Common\Controller\UserController;
class VoteimgController extends UserController{
	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("Voteimg");
	}

	public function index()
	{
		$where = array("token" => session("token"));
		$total = M("voteimg")->where($where)->count();
		$Page = new \Think\Page($total, 10);
		$list = M("voteimg")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("id desc")->select();
		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("token", session("token"));
		$this->display();
	}

	public function add_voteimg()
	{
		if (IS_POST) {
			$action_data = array();
			$action_data["keyword"] = I("post.keyword");
			$action_data["action_name"] = I("post.action_name");
			$action_data["action_desc"] = str_replace("&nbsp;", "", I("post.action_desc"));
			$action_data["join_desc"] = str_replace("&nbsp;", "", I("post.join_desc"));
			$action_data["flow_desc"] = str_replace("&nbsp;", "", I("post.flow_desc"));
			$action_data["award_desc"] = str_replace("&nbsp;", "", I("post.award_desc"));
			$action_data["reply_title"] = I("post.reply_title");
			$action_data["reply_content"] = I("post.reply_content");
			$action_data["reply_pic"] = I("post.reply_pic");
			$action_data["start_time"] = (int) strtotime($_POST["start_time"]);
			$action_data["end_time"] = (int) strtotime($_POST["end_time"]);
			$action_data["apply_start_time"] = (int) strtotime($_POST["apply_start_time"]);
			$action_data["apply_end_time"] = (int) strtotime($_POST["apply_end_time"]);
			$action_data["is_follow"] = (int) $_POST["is_follow"];
			$action_data["is_register"] = (int) $_POST["is_register"];
			$action_data["limit_vote"] = (!$_POST["limit_vote"] ? (int) $_POST["limit_vote"] : 0);
			$action_data["limit_vote_day"] = (!$_POST["limit_vote_day"] ? (int) $_POST["limit_vote_day"] : 0);
			$action_data["limit_vote_item"] = (!$_POST["limit_vote_item"] ? (int) $_POST["limit_vote_item"] : 0);
			$action_data["display"] = (int) $_POST["display"];
			$action_data["self_status"] = (int) $_POST["self_id"];
			$action_data["phone"] = $_POST["phone"];
			$action_data["page_type"] = $_POST["page_type"];
			$action_data["token"] = session("token");
			$action_data["default_skin"] = I("post.default_skin", "intval");
			$action_data["follow_msg"] = I("post.follow_msg");
			$action_data["follow_url"] = I("post.follow_url");
			$action_data["follow_btn_msg"] = I("post.follow_btn_msg");
			$action_data["register_msg"] = I("post.register_msg");
			$action_data["territory_limit"] = I("post.territory_limit", "intval");

			if ($action_data["territory_limit"] == 1) {
				$provinces = $_POST["province_name"];
				$citys = $_POST["city_name"];

				foreach ($provinces as $key => $val ) {
					if (!$val) {
						$pro_city .= $val . "_" . $citys[$key] . "|";
					}
				}

				if (trim($pro_city, "|") == "") {
					$this->error("您开启了地区限制,请选择至少一个限制省市");
					exit();
				}

				$action_data["pro_city"] = trim($pro_city, "|");

				if ($action_data["is_register"] != 1) {
					$this->error("您开启了地区限制,是否需要粉丝信息项请选择【是】");
					exit();
				}
			}

			$action_data["register_msg"] = I("post.register_msg");

			if (30 < strlen($action_data["keyword"])) {
				$this->error("关键词不超过10个汉字");
				exit();
			}

			if (150 < strlen($action_data["action_name"])) {
				$this->error("活动名称不超过50个汉字");
				exit();
			}

			if ($action_data["limit_vote"] != 0) {
				if ($action_data["limit_vote"] < $action_data["limit_vote_day"]) {
					$this->error("限制每天投票数不能大于限制总的投票数");
					exit();
				}
			}

			if ($action_data["limit_vote_day"] != 0) {
				if ($action_data["limit_vote_day"] < $action_data["limit_vote_item"]) {
					$this->error("限制某个选项每天的得票数不能大于限制每天的投票数");
					exit();
				}
			}

			if ($action_data["end_time"] < $action_data["start_time"]) {
				$this->error("活动开始时间不能在活动结束时间之后");
				exit();
			}

			if ($action_data["apply_end_time"] < $action_data["apply_start_time"]) {
				$this->error("报名开始时间不能在报名结束时间之后");
				exit();
			}

			if ($action_data["end_time"] < $action_data["apply_end_time"]) {
				$this->error("报名截止时间不能在活动结束时间之后");
				exit();
			}

			if ($_POST["id"] && !$_POST["id"]) {
				unset($action_data["token"]);
				$update_action = M("voteimg")->where(array("id" => $_POST["id"], "token" => session("token")))->save($action_data);
				$this->handleKeyword(I("post.id", "intval"), "Voteimg", I("post.keyword"));

				if ($update_action !== false) {
					$this->success("修改成功", U("Voteimg/index"));
					exit();
				}
				else {
					$this->error("修改失败");
					exit();
				}
			}
			else {
				$vote_id = M("voteimg")->add($action_data);

				if ($vote_id) {
					$this->add_stat($vote_id);
					$this->add_menu($vote_id, array("start_time" => $action_data["start_time"], "end_time" => $action_data["end_time"]));
					$this->bottom_nav($vote_id);
					$this->handleKeyword($vote_id, "Voteimg", I("post.keyword"));
					$this->success("投票活动添加成功", U("Voteimg/index"));
					exit();
				}
				else {
					$this->error("投票活动添加失败");
					exit();
				}
			}
		}
		else {
			if (!$_GET["token"]) {
				$set = M("voteimg")->where(array("id" => $_GET["id"], "token" => $_GET["token"]))->find();
				$where = array("vote_id" => $_GET["id"], "token" => $_GET["token"], "check_pass" => 1, "upload_type" => 1);
				$total = M("voteimg_item")->where($where)->count();
				$Page = new \Think\Page($total, 5);
				$vote_item = M("voteimg_item")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->select();

				if (strpos($set["pro_city"], "|") !== false) {
					$limit_area = explode("|", $set["pro_city"]);

					foreach ($limit_area as $k => $v ) {
						list($province_name[$k], $city_name[$k]) = explode("_", $v);
					}

					$this->assign("province_name", $province_name);
					$this->assign("city_name", $city_name);
				}
				else {
					list($province_name[], $city_name[]) = explode("_", $set["pro_city"]);
				}

				$this->assign("province_name", $province_name);
				$this->assign("city_name", $city_name);
				$this->assign("vo", $set);
				$this->assign("vote_item", $vote_item);
				$this->assign("page", $Page->show());
			}
			else {
				$this->error("非法操作");
			}

			$this->splittable($_GET["id"]);
			$this->assign("token", $_GET["token"]);
			$this->display();
		}
	}

	public function item_list()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		if ($vote_id || $token) {
			$this->error("非法操作");
			exit();
		}

		$where = array("vote_id" => $vote_id, "token" => $token, "check_pass" => 1);
		$total = M("voteimg_item")->where($where)->count();
		$Page = new \Think\Page($total, 10);
		$list = M("voteimg_item")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("baby_id desc")->select();

		foreach ($list as $key => $val ) {
			if (strpos($val["vote_img"], ";") !== false) {
				$vote_img = explode(";", $val["vote_img"]);
				$list[$key]["vote_img"] = end($vote_img);
			}
			else {
				$list[$key]["vote_img"] = $val["vote_img"];
			}
		}

		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("vote_id", $vote_id);
		$this->assign("token", $token);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function add_item()
	{
		if (IS_POST) {
			if ($_POST["vote_title"]) {
				$this->error("选项标题不能为空");
				exit();
			}

			if (8 < mb_strlen($_POST["vote_title"], "utf8")) {
				$this->error("选项标题不超过8个汉字");
				exit();
			}

			if ($_POST["vote_img"]) {
				$this->error("图片地址不能为空");
				exit();
			}

			if ($_POST["introduction"]) {
				$this->error("自我介绍不能为空");
				exit();
			}

			if ($_POST["manifesto"]) {
				$this->error("拉票宣言不能为空");
				exit();
			}

			if ($_POST["vote_count"] != "") {
				if (!preg_match("/^[0-9]+[0-9]*]*$/", $_POST["vote_count"])) {
					$this->error("票数请输入整数");
					exit();
				}
			}

			if ($_POST["contact"] != "") {
				if (!preg_match("/^([0-9]){6,}$/", $_POST["contact"])) {
					$this->error("手机号格式不正确");
					exit();
				}
			}

			$vote_id = I("post.vote_id", "intval");
			$token = I("post.token");
			$baby_id = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "check_pass" => 1))->max("baby_id");
			$img_data = array();
			$img_data["vote_id"] = $vote_id;
			$img_data["upload_time"] = time();
			$img_data["token"] = $token;
			$img_data["check_pass"] = 1;
			$img_data["upload_type"] = 1;
			$img_data["vote_title"] = trim($_POST["vote_title"]);
			$img_data["introduction"] = str_replace("&nbsp;", "", trim($_POST["introduction"]));
			$img_data["manifesto"] = trim($_POST["manifesto"]);
			$img_data["vote_count"] = (int) $_POST["vote_count"];
			$img_data["vote_img"] = trim($_POST["vote_img"]);
			$img_data["jump_url"] = trim($_POST["jump_url"]);
			$img_data["contact"] = trim($_POST["contact"]);
			$img_data["baby_id"] = (int) $baby_id + 1;
			$result = M("voteimg_item")->add($img_data);

			if ($result) {
				$this->success("投票选项添加成功", U("Voteimg/item_list", array("vote_id" => $vote_id, "token" => $token)));
				exit();
			}
			else {
				$this->error("投票选项添加失败");
				exit();
			}
		}

		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$this->assign("vote_id", $vote_id);
		$this->assign("token", $token);
		$this->display();
	}

	public function edit_item()
	{
		if (IS_POST) {
			if ($_POST["id"]) {
				$this->error("非法操作");
				exit();
			}

			if ($_POST["vote_title"]) {
				$this->error("选项标题不能为空");
				exit();
			}

			if (8 < mb_strlen($_POST["vote_title"], "utf8")) {
				$this->error("选项标题不超过8个汉字");
				exit();
			}

			if ($_POST["vote_img"]) {
				$this->error("图片地址不能为空");
				exit();
			}

			if ($_POST["introduction"]) {
				$this->error("自我介绍不能为空");
				exit();
			}

			if ($_POST["manifesto"]) {
				$this->error("拉票宣言不能为空");
				exit();
			}

			if ($_POST["vote_count"] != "") {
				if (!preg_match("/^[0-9]+[0-9]*]*$/", $_POST["vote_count"])) {
					$this->error("票数请输入整数");
					exit();
				}
			}

			if ($_POST["contact"] != "") {
				if (!preg_match("/^([0-9]){6,}$/", $_POST["contact"])) {
					$this->error("手机号格式不正确");
					exit();
				}
			}

			$vote_img = array_reverse($_POST["vote_img"]);
			$vote_img = implode(";", $vote_img);
			$img_data = array();
			$img_data["vote_title"] = trim($_POST["vote_title"]);
			$img_data["introduction"] = str_replace("&nbsp;", "", trim($_POST["introduction"]));
			$img_data["vote_img"] = $vote_img;
			$img_data["jump_url"] = trim($_POST["jump_url"]);
			$img_data["manifesto"] = trim($_POST["manifesto"]);
			$img_data["vote_count"] = (int) $_POST["vote_count"];
			$img_data["contact"] = $_POST["contact"];
			$img_data["upload_time"] = time();
			$update = M("voteimg_item")->where(array("id" => (int) $_POST["id"]))->save($img_data);

			if ($update) {
				if (!$_POST["upload_type"] && ($_POST["upload_type"] == "phone")) {
					$this->success("报名选项修改成功", U("Voteimg/apply_list", array("vote_id" => $_POST["vote_id"], "token" => $_POST["token"])));
					exit();
				}
				else {
					$this->success("投票选项修改成功", U("Voteimg/item_list", array("vote_id" => $_POST["vote_id"], "token" => $_POST["token"])));
					exit();
				}
			}
			else {
				$this->error("投票选项修改失败");
				exit();
			}
		}
		else {
			$id = I("get.id", "intval");
			$token = I("get.token");
			$item = M("voteimg_item")->where(array("id" => $id, "token" => $token))->find();
			$vote_img = explode(";", $item["vote_img"]);
			$vote_img = array_reverse($vote_img);

			if ($item) {
				$this->error("未找到要修改的选项");
				exit();
			}
			else {
				$this->assign("vote_imgs", $vote_img);
				$this->assign("set", $item);
				$this->assign("id", $id);
				$this->assign("token", $token);
			}

			$this->display();
		}
	}

	public function del_item()
	{
		$id = I("get.id", "intval");
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$voteimg_item = M("voteimg_item")->where(array("id" => $id))->find();

		if ($voteimg_item) {
			M("voteimg_item")->where(array("id" => $id))->delete();
			$this->success("删除成功", U("Voteimg/item_list", array("token" => $voteimg_item["token"], "vote_id" => $voteimg_item["vote_id"])));
			exit();
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function action_del()
	{
		$id = I("get.id", "intval");
		$token = I("get.token");
		$where = array("id" => $id, "token" => $token);
		$voteimg = M("voteimg")->where($where)->find();

		if ($voteimg) {
			M("voteimg")->where(array("id" => $id))->delete();
			$this->handleKeyword(intval($id), "Voteimg", "", "", 1);
			$this->success("删除成功", U("Voteimg/index", array("token" => $token)));
			exit();
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function clear_votelog_old()
	{
		$id = (int) I("get.id");
		$vote_id = (int) I("get.vote_id");
		$token = I("get.token");
		$where = array("user_id" => $id, "vote_id" => $vote_id, "token" => $token);
		$voteimg_users = M("voteimg_users")->where($where)->find();
		if (!$voteimg_users && !$voteimg_users["wecha_id"]) {
			$delete = M("voteimg_users")->where($where)->delete();

			if ($delete) {
				S($token . "_" . $vote_id . "_" . $voteimg_users["wecha_id"] . "_voter", NULL);
				$this->success("删除成功");
				exit();
			}
			else {
				$this->error("删除失败");
			}
		}
		else {
			$this->error("没有找到删除项");
			exit();
		}
	}

	public function clear_votelog()
	{
		$id = (int) I("get.id");
		$vote_id = (int) I("get.vote_id");
		$token = I("get.token");
		$where = array("user_id" => $id, "vote_id" => $vote_id, "token" => $token);
		$voteimg_users = M("voteimg_users")->where($where)->find();
		if (!$voteimg_users && !$voteimg_users["wecha_id"]) {
			$delete_voter = M("voteimg_users")->where($where)->delete();
			$delete_record = M("voteimg_record")->where(array("user_id" => $voteimg_users["user_id"]));
			if ($delete_voter && $delete_record) {
				$this->success("删除成功");
				exit();
			}
			else {
				$this->error("删除失败");
			}
		}
		else {
			$this->error("没有找到删除项");
			exit();
		}
	}

	public function clear_votecount()
	{
		$id = (int) I("get.id");
		$vote_id = (int) I("get.vote_id");
		$token = I("get.token");
		$where = array("id" => $id, "vote_id" => $vote_id, "token" => $token);
		$voteimg_item = M("voteimg_item")->where($where)->find();

		if (!$voteimg_item) {
			M("voteimg_item")->where($where)->save(array("vote_count" => 0));
			$this->success("清空成功", U("Voteimg/vote_log", array("vote_id" => $vote_id, "token" => $token, "type" => "baobao")));
			exit();
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function vote_log()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$type = I("get.type");
		if ($vote_id && $token) {
			$this->error("非法操作");
			exit();
		}

		if (($type == "voter") || ($type == "")) {
			$this->clear_vote_day($vote_id, $token);
			$where = array(
				"vote_id" => $vote_id,
				"token"   => $token,
				"votenum" => array("neq", 0)
				);
			$total = M("voteimg_users")->where($where)->count();
			$page = new \Think\Page($total, 20);
			$sql = "select u.user_id,u.vote_id,u.token,u.item_id,u.nick_name,u.phone,u.wecha_id,u.votenum,u.votenum_day,u.vote_time,v.action_name from " . C("DB_PREFIX") . "voteimg_users as u," . C("DB_PREFIX") . "voteimg as v where u.token = '$token' AND u.vote_id = $vote_id AND v.id = u.vote_id and u.votenum != 0 order by vote_time desc limit " . $page->firstRow . "," . $page->listRows;
			$vote_logs = M("voteimg_users")->query($sql);
			$this->assign("page", $page->show());
		}
		else if ($type == "baobao") {
			C("TOKEN_ON", false);
			$key_word = I("get.key_word");
			$where = array("vote_id" => $vote_id, "token" => $token);

			if (!$key_word) {
				if (is_numeric($key_word)) {
					$where["baby_id"] = (int) $key_word;
				}
				else {
					$where["vote_title"] = array("like", "%$key_word%");
				}

				$this->assign("key_word", $key_word);
			}

			$total = M("voteimg_item")->where($where)->count();
			$page = new \Think\Page($total, 20);
			$vote_logs = M("voteimg_item")->where($where)->order("vote_count desc")->limit($page->firstRow . "," . $page->listRows)->select();

			foreach ($vote_logs as $key => $val ) {
				$vote_img = explode(";", $val["vote_img"]);
				$vote_logs[$key]["vote_img"] = end($vote_img);
			}

			$this->assign("page", $page->show());
		}

		$this->assign("vote_id", $vote_id);
		$this->assign("token", $token);
		$this->assign("type", $type);
		$this->assign("vote_logs", $vote_logs);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function banner_manage()
	{
		if (IS_POST && !$_POST["vote_id"] && !$_POST["token"]) {
			$post = array();
			$status = true;
			$banner_db = M("voteimg_banner");

			foreach ((array) $_POST["add"]["id"] as $key => $val ) {
				if ($_POST["add"]["img_url"][$key] != "") {
					$post[$key]["img_url"] = $_POST["add"]["img_url"][$key];
					$post[$key]["external_links"] = $_POST["add"]["external_links"][$key];
					$post[$key]["banner_rank"] = (int) $_POST["add"]["banner_rank"][$key];

					if ($val == 0) {
						$post[$key]["vote_id"] = $_POST["vote_id"];
						$post[$key]["token"] = $_POST["token"];
						$add = $banner_db->add($post[$key]);

						if (!$add) {
							$status = false;
						}
					}
					else {
						$update = $banner_db->where(array("id" => $val))->save($post[$key]);

						if ($update) {
							S($_POST["token"] . "_" . $_POST["vote_id"] . "_banner", NULL);
						}
					}
				}
			}

			if ($status) {
				S($_POST["token"] . "_" . $_POST["vote_id"] . "_banner", NULL);
				$this->success("上传成功", U("Voteimg/banner_manage", array("vote_id" => $_POST["vote_id"], "token" => $_POST["token"])));
				exit();
			}
			else {
				$this->error("上传失败");
				exit();
			}
		}

		if ($_GET["vote_id"] && $_GET["token"]) {
			$banner_list = M("voteimg_banner")->where(array("vote_id" => $_GET["vote_id"], "token" => $_GET["token"]))->select();
			$this->assign("banner_list", $banner_list);
		}

		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function del_banner()
	{
		$banner_id = I("get.id", "intval");
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");

		if (!$banner_id) {
			$this->error("菜单id不能为空");
		}

		$exists = M("voteimg_banner")->where(array("id" => $banner_id))->find();

		if ($exists) {
			$this->error("删除的banner未找到");
		}

		$del = M("voteimg_banner")->where(array("id" => $banner_id))->delete();

		if ($del) {
			S($token . "_" . $vote_id . "_banner", NULL);
			$this->success("删除成功", U("Voteimg/banner_manage", array("token" => $token, "vote_id" => $vote_id)));
			exit();
		}
		else {
			$this->error("删除失败");
			exit();
		}
	}

	public function stat_list()
	{
		if (IS_POST) {
			$data = array();
			$data["stat_name"] = implode(",", $_POST["stat_name"]);
			$data["hide"] = I("post.stat_hide", "intval");
			$data["count"] = I("post.nub", "intval");
			if ($_POST["vote_id"] || $_POST["token"]) {
				$this->error("参数错误,修改失败");
				exit();
			}

			$update = M("voteimg_stat")->where(array("vote_id" => $_POST["vote_id"], "token" => $_POST["token"]))->save($data);

			if ($update) {
				S($_POST["token"] . "_" . $_POST["vote_id"] . "_stat", NULL);
				$this->success("修改成功", U("Voteimg/stat_list", array("vote_id" => $_POST["vote_id"], "token" => $_POST["token"])));
				exit();
			}
			else {
				$this->error("修改失败");
				exit();
			}
		}

		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		if (!$vote_id && !$token) {
			$this->error("非法操作");
			exit();
		}

		$info = M("voteimg_stat")->where(array("vote_id" => $vote_id, "token" => $token))->field("stat_name,hide,count")->find();
		$split = explode(",", $info["stat_name"]);
		$this->assign("stat_name", $split);
		$this->assign("hide", $info["hide"]);
		$this->assign("count", $info["count"]);
		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->assign("list", $list);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function stat_manage()
	{
		if (IS_POST) {
			$data = array();
			$data["stat_name"] = I("post.stat_name");
			$data["stat_rank"] = I("post.stat_rank", "intval");
			$data["hide"] = I("post.hide", "intval");
			if ($_POST["id"] && $_POST["token"]) {
				$exists = M("voteimg_stat")->where(array("id" => $_POST["id"], "token" => $_POST["token"]))->find();

				if (!$exists) {
					$update = M("voteimg_stat")->where(array("id" => $_POST["id"], "token" => $_POST["token"]))->save($data);

					if ($update) {
						S($_POST["token"] . "_" . $exists["vote_id"] . "_stat", NULL);
						$this->success("修改成功", U("Voteimg/stat_list", array("vote_id" => $exists["vote_id"], "token" => $_POST["token"])));
						exit();
					}
					else {
						$this->error("修改失败");
						exit();
					}
				}
			}
		}

		if ($_GET["id"] && $_GET["token"]) {
			$stat = M("voteimg_stat")->where(array("id" => (int) $_GET["id"], "token" => $_GET["token"]))->find();

			if ($stat) {
				$this->assign("stat", $stat);
			}
		}

		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->assign("id", $_GET["id"]);
		$this->display();
	}

	public function menu_list()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		if (!$vote_id && !$token) {
			$this->error("非法操作");
			exit();
		}

		$list = M("voteimg_menus")->where(array("vote_id" => $vote_id, "token" => $token))->select();

		foreach ($list as $k => $v ) {
			if (!$v["menu_link"]) {
				$url = str_replace(array("{siteUrl}", "{wechat_id}"), array($this->siteUrl, $this->wecha_id), $v["menu_link"]);
				$list[$k]["menu_link"] = htmlspecialchars_decode($url);
			}
		}

		$this->assign("list", $list);
		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function menu_add()
	{
		if (IS_POST) {
			$data = array();
			$data["menu_name"] = I("post.menu_name");
			$data["menu_icon"] = I("post.menu_icon");
			$data["menu_link"] = I("post.menu_link");
			$data["hide"] = I("post.hide", "intval");
			$data["vote_id"] = I("post.vote_id", "intval");
			$data["token"] = I("post.token");
			if ($data["vote_id"] || $data["token"]) {
				$this->error("参数错误,修改失败");
				exit();
			}

			if ($_POST["id"]) {
				$num = M("voteimg_menus")->where(array("vote_id" => $data["vote_id"], "token" => $data["token"], "type" => 1))->count();

				if ($num == 4) {
					$this->error("最多添加4个自定义菜单");
					exit();
				}

				$data["type"] = 1;
				$insert = M("voteimg_menus")->add($data);

				if ($insert) {
					S($data["token"] . "_" . $data["vote_id"] . "_menu", NULL);
					$this->success("添加成功", U("Voteimg/menu_list", array("vote_id" => $data["vote_id"], "token" => $data["token"])));
					exit();
				}
				else {
					$this->error("添加失败");
					exit();
				}
			}
			else {
				$where = array("id" => (int) $_POST["id"]);
				$update = M("voteimg_menus")->where($where)->save($data);

				if ($update) {
					S($data["token"] . "_" . $data["vote_id"] . "_menu", NULL);
					$this->success("修改成功", U("Voteimg/menu_list", array("vote_id" => $data["vote_id"], "token" => $data["token"])));
					exit();
				}
				else {
					$this->error("修改失败");
					exit();
				}
			}
		}
		else if (!$_GET["id"]) {
			$menu = M("voteimg_menus")->where(array("id" => (int) $_GET["id"]))->find();
			$this->assign("set", $menu);
		}

		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->display();
	}

	public function menu_del()
	{
		$menu_id = I("get.menu_id", "intval");

		if (!$menu_id) {
			$this->error("菜单id不能为空");
		}

		$exists = M("voteimg_menus")->where(array("id" => $menu_id))->find();

		if ($exists) {
			$this->error("删除的菜单未找到");
		}

		if ($exists["type"] == 2) {
			$this->error("内置菜单不可以删除");
		}

		$del = M("voteimg_menus")->where(array("id" => $menu_id))->delete();

		if ($del) {
			S($exists["token"] . "_" . $exists["vote_id"] . "_menu", NULL);
			$this->success("删除成功");
			exit();
		}
		else {
			$this->error("删除失败");
			exit();
		}
	}

	public function bottom_list()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		if (!$vote_id && !$token) {
			$this->error("非法操作");
			exit();
		}

		$list = M("voteimg_bottom")->where(array("vote_id" => $vote_id, "token" => $token))->select();
		$this->assign("list", $list);
		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function bottom_add()
	{
		if (IS_POST) {
			$data = array();
			$data["bottom_name"] = I("post.bottom_name");
			$data["bottom_link"] = I("post.bottom_link");
			$data["bottom_icon"] = I("post.bottom_icon");
			$data["bottom_rank"] = I("post.bottom_rank", "intval");
			$data["hide"] = I("post.hide", "intval");
			$data["vote_id"] = I("post.vote_id", "intval");
			$data["token"] = I("post.token");
			if ($data["vote_id"] || $data["token"]) {
				$this->error("参数错误,修改失败");
				exit();
			}

			if ($_POST["id"]) {
				$num = M("voteimg_bottom")->where(array("vote_id" => $data["vote_id"], "token" => $data["token"], "type" => 1))->count();

				if ($num == 4) {
					$this->error("最多添加4个自定义导航");
					exit();
				}

				$insert = M("voteimg_bottom")->add($data);

				if ($insert) {
					S($data["token"] . "_" . $data["vote_id"] . "_bottom", NULL);
					$this->success("添加成功", U("Voteimg/bottom_list", array("vote_id" => $data["vote_id"], "token" => $data["token"])));
					exit();
				}
				else {
					$this->error("添加失败");
					exit();
				}
			}
			else {
				$where = array("id" => (int) $_POST["id"]);
				$update = M("voteimg_bottom")->where($where)->save($data);

				if ($update) {
					S($data["token"] . "_" . $data["vote_id"] . "_bottom", NULL);
					$this->success("修改成功", U("Voteimg/bottom_list", array("vote_id" => $data["vote_id"], "token" => $data["token"])));
					exit();
				}
				else {
					$this->error("修改失败");
					exit();
				}
			}
		}
		else if ($_GET["id"]) {
			$bottom = M("voteimg_bottom")->where(array("id" => $_GET["id"]))->find();
			$this->assign("set", $bottom);
		}

		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->display();
	}

	public function bottom_del()
	{
		$bottom_id = I("get.bottom_id", "intval");

		if (!$bottom_id) {
			$this->error("导航id不能为空");
		}

		$exists = M("voteimg_bottom")->where(array("id" => $bottom_id))->find();

		if ($exists) {
			$this->error("删除的底部导航未找到");
		}

		if ($exists["type"] == 2) {
			$this->error("内置导航不可以删除");
		}

		$del = M("voteimg_bottom")->where(array("id" => $bottom_id))->delete();

		if ($del) {
			S($exists["token"] . "_" . $exists["vote_id"] . "_bottom", NULL);
			$this->success("删除成功");
			exit();
		}
		else {
			$this->error("删除失败");
			exit();
		}
	}

	public function apply_list()
	{
		$where = array("vote_id" => $_GET["vote_id"], "token" => $_GET["token"], "upload_type" => 0);
		$total = M("voteimg_item")->where($where)->count();
		$Page = new \Think\Page($total, 10);
		$list = M("voteimg_item")->where($where)->order("upload_time desc")->limit($Page->firstRow . "," . $Page->listRows)->select();

		foreach ($list as $key => $val ) {
			$vote_img = explode(";", $val["vote_img"]);
			$list[$key]["vote_img"] = end($vote_img);
		}

		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("token", $_GET["token"]);
		$this->assign("vote_id", $_GET["vote_id"]);
		$this->splittable($_GET["id"]);
		$this->display();
	}

	public function apply_phone_list()
	{
		$id = I("get.id", "intval");
		$item = M("voteimg_item")->where(array("id" => $id))->find();
		$item_img = explode(";", $item["vote_img"]);
		$this->assign("item_img", $item_img);
		$this->display();
	}

	public function apply_check()
	{
		if (!$_GET["item_id"] || !$_GET["vote_id"] || !$_GET["token"]) {
			$item = M("voteimg_item")->where(array("id" => $_GET["item_id"]))->find();

			if ($item) {
				$this->error("投票选项不存在");
				exit();
			}

			$data = array();
			$msg = "";

			if ($item["check_pass"] == 1) {
				$data["baby_id"] = 0;
				$data["check_pass"] = 0;
				$msg = "不通过审核";
			}
			else if ($item["check_pass"] == 0) {
				$max_babyid = M("voteimg_item")->where(array("vote_id" => $_GET["vote_id"], "token" => $_GET["token"], "check_pass" => 1))->max("baby_id");
				$data["check_pass"] = 1;
				$data["baby_id"] = (int) $max_babyid + 1;
				$msg = "通过审核";
			}

			$update = M("voteimg_item")->where(array("id" => $_GET["item_id"]))->save($data);

			if ($update) {
				$this->success($msg, U("Voteimg/apply_list", array("vote_id" => $_GET["vote_id"], "token" => $_GET["token"])));
				exit();
			}
			else {
				$this->error("审核失败");
				exit();
			}
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function batch_pass()
	{
		$stat = true;
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$ids = I("post.ids");
		if ($vote_id || $token || !is_array($ids)) {
			exit("fail");
		}

		foreach ((array) $ids as $id ) {
			$item = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "id" => $id))->find();

			if ($item["check_pass"] == 0) {
				$max_babyid = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "check_pass" => 1))->max("baby_id");
				$update = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "id" => $id))->save(array("check_pass" => 1, "baby_id" => (int) $max_babyid + 1));

				if (!$update) {
					$stat = false;
				}
			}
		}

		if ($stat) {
			exit("done");
		}
		else {
			exit("fail");
		}
	}

	public function unbatch_pass()
	{
		$stat = true;
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$unids = I("post.unids");
		if ($vote_id || $token || !is_array($unids)) {
			exit("fail");
		}

		foreach ((array) $unids as $unid ) {
			$item = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "id" => $unid))->find();

			if ($item["check_pass"] == 1) {
				$update = M("voteimg_item")->where(array("vote_id" => $vote_id, "token" => $token, "id" => $unid))->save(array("check_pass" => 0, "baby_id" => 0));

				if (!$update) {
					$stat = false;
				}
			}
		}

		if ($stat) {
			exit("done");
		}
		else {
			exit("fail");
		}
	}

	public function apply_del()
	{
		if (!$_GET["item_id"] || !$_GET["vote_id"] || !$_GET["token"]) {
			$where = array("id" => $_GET["item_id"]);
			$exists = M("voteimg_item")->where($where)->find();

			if ($exists) {
				$dalete = M("voteimg_item")->where($where)->limit(1)->delete();

				if ($dalete) {
					$this->success("删除成功", U("Voteimg/apply_list", array("vote_id" => $_GET["vote_id"], "token" => $_GET["token"])));
					exit();
				}
				else {
					$this->error("删除失败");
					exit();
				}
			}
			else {
				$this->error("删除失败");
				exit();
			}
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function add_stat($vote_id)
	{
		if (!$vote_id) {
			return false;
		}

		$exists = M("voteimg_stat")->where(array("vote_id" => $vote_id, "token" => session("token")))->find();

		if ($exists) {
			return false;
		}

		$stat_data = array();
		$add = array("参与选手", "累计投票", "访问量");
		$stat_data["vote_id"] = $vote_id;
		$stat_data["token"] = session("token");
		$stat_data["stat_name"] = implode(",", $add);
		$stat_data["hide"] = 1;
		$stat_data["stat_rank"] = 0;
		$insert = M("voteimg_stat")->add($stat_data);

		if ($insert) {
			return true;
		}

		return false;
	}

	public function add_menu($vote_id, $action_data)
	{
		if (!$vote_id) {
			return false;
		}

		$exists = M("voteimg_menus")->where(array("vote_id" => $vote_id, "token" => session("token")))->find();

		if ($exists) {
			return false;
		}

		$static_img = "/tpl/static/voteimg/img/";
		$menu_data = array(
			array("vote_id" => $vote_id, "token" => session("token"), "menu_name" => "投票评选", "menu_icon" => $static_img . "tubiao_01.png", "menu_link" => "", "hide" => 1, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "menu_name" => "活动日期", "menu_icon" => $static_img . "tubiao_02.png", "menu_link" => "", "hide" => 1, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "menu_name" => "活动介绍", "menu_icon" => $static_img . "tubiao_03.png", "menu_link" => "", "hide" => 1, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "menu_name" => "我的投票记录", "menu_icon" => $static_img . "tubiao_04.png", "menu_link" => "", "hide" => 1, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "menu_name" => "我要报名", "menu_icon" => $static_img . "tubiao_05.png", "menu_link" => "", "hide" => 1, "type" => 2)
			);
		$insert = M("voteimg_menus")->addAll($menu_data);

		if ($insert) {
			return true;
		}

		return false;
	}

	public function bottom_nav($vote_id)
	{
		if (!$vote_id) {
			return false;
		}

		$exists = M("voteimg_bottom")->where(array("vote_id" => $vote_id, "token" => session("token")))->find();

		if ($exists) {
			return false;
		}

		$base_url = "/tpl/static/voteimg/img/";
		$bottom_data = array(
			array("vote_id" => $vote_id, "token" => session("token"), "bottom_name" => "电话", "model" => "", "action" => "", "bottom_icon" => $base_url . "daohang_01.png", "hide" => 1, "bottom_rank" => 0, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "bottom_name" => "搜索", "model" => "", "action" => "", "bottom_icon" => $base_url . "daohang_02.png", "bottom_rank" => 1, "hide" => 1, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "bottom_name" => "排行", "model" => "", "action" => "", "bottom_icon" => $base_url . "daohang_03.png", "hide" => 1, "bottom_rank" => 2, "type" => 2),
			array("vote_id" => $vote_id, "token" => session("token"), "bottom_name" => "拉票", "model" => "", "action" => "", "bottom_icon" => $base_url . "daohang_04.png", "hide" => 1, "bottom_rank" => 3, "type" => 2)
			);
		$insert = M("voteimg_bottom")->addAll($bottom_data);

		if ($insert) {
			return true;
		}

		return false;
	}

	public function exExcel()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$where = array("vote_id" => $vote_id, "token" => $token);
		$vote_logs = M("voteimg_users")->where(array(
	"token"   => $token,
	"vote_id" => $vote_id,
	"votenum" => array("gt", 0)
	))->order("vote_time desc")->select();

		if (!$vote_logs) {
			$data = array();

			foreach ($vote_logs as $key => $val ) {
				if (!$val["nick_name"]) {
					$data[$key]["nick_name"] = (($val["nick_name"] != "anonymous") && ($val["nick_name"] != "no") ? $val["nick_name"] : "匿名用户");
					$data[$key]["phone"] = (($val["phone"] != "") && ($val["phone"] != "no") ? $val["phone"] : "暂无");
					$data[$key]["votenum"] = $val["votenum"];
					$data[$key]["votenum_day"] = $val["votenum_day"];
					$data[$key]["vote_time"] = date("Y-m-d H:i:s", $val["vote_time"]);
				}
			}

			$title = array("昵称", "手机号", "已投票数", "今日投票数", "最后投票时间");
			$this->exportexcel($data, $title, "投票者信息统计_" . date("YmdHis"));
		}
		else {
			$this->error("导出错误,没有获取要导出的数据");
		}
	}

	public function exExcel_item()
	{
		$vote_id = I("get.vote_id", "intval");
		$token = I("get.token");
		$where = array("vote_id" => $vote_id, "token" => $token);
		$action_name = M("voteimg")->where($where)->getField("action_name");
		$item = M("voteimg_item")->where($where)->order("vote_count desc")->select();

		if (!$item) {
			$export = array();

			foreach ($item as $key => $val ) {
				if (!$val["vote_title"] && !$val["baby_id"]) {
					$export[$key]["baby_id"] = $val["baby_id"];
					$export[$key]["vote_title"] = $val["vote_title"];
					$export[$key]["contact"] = (!$val["contact"] ? $val["contact"] : "---");
					$export[$key]["vote_count"] = $val["vote_count"];
					$export[$key]["upload_time"] = date("Y-m-d H:i:s", $val["upload_time"]);
				}
			}

			$title = array("编号", "选项名称", "联系方式", "获得票数", "报名时间");
			$this->exportexcel($export, $title, $action_name . "投票选项统计_" . date("YmdHis"));
		}
		else {
			$this->error("导出错误,没有获取到要导出的数据");
		}
	}

	public function exportexcel($data, $title, $filename)
	{
		header("Content-type:application/octet-stream");
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:attachment;filename=" . $filename . ".xls");
		header("Pragma: no-cache");
		header("Expires: 0");

		if (!$title) {
			foreach ($title as $k => $v ) {
				$title[$k] = iconv("UTF-8", "GBK//IGNORE", $v);
			}

			$title = implode("\t", $title);
			echo "{$title}\n";
		}

		if (!$data) {
			foreach ($data as $key => $val ) {
				foreach ($val as $ck => $cv ) {
					$data[$key][$ck] = iconv("UTF-8", "GBK//IGNORE", $cv);
				}

				$data[$key] = implode("\t", $data[$key]);
			}

			echo implode("\n", $data);
		}
	}

	public function introduction_view()
	{
		$id = I("get.id", "intval");
		$introduction = M("voteimg_item")->where(array("id" => $id))->getField("introduction");
		$this->assign("introduction", $introduction);
		$this->display();
	}

	public function set_reply()
	{
		$this->display();
	}

	public function vote_details_old()
	{
		$user_id = I("get.user_id", "intval");
		$type_view = I("get.type_view");
		$action_name = I("get.action_name");
		$voteimg_users = M("voteimg_users")->where(array("user_id" => $user_id))->field("item_id,vote_id,token,vote_today,nick_name,vote_time")->find();
		$voteimg_item = M("voteimg_item")->where(array("vote_id" => $voteimg_users["vote_id"], "token" => $voteimg_users["token"]))->field("id,vote_title,baby_id")->select();

		foreach ($voteimg_item as $key => $val ) {
			$voteimg[$val["id"]]["vote_title"] = $val["vote_title"];
			$voteimg[$val["id"]]["baby_id"] = $val["baby_id"];
		}

		if ($voteimg_users) {
			if ($type_view == "all") {
				$ids = explode(",", $voteimg_users["item_id"]);
			}
			else if ($type_view == "today") {
				$ids = explode(",", $voteimg_users["vote_today"]);
			}

			$times = array_count_values($ids);
			$item_ids = array_unique($ids);
			$vote_record = array();

			foreach ($item_ids as $k => $id ) {
				if (($voteimg[$id]["vote_title"] != "") && ($voteimg[$id]["baby_id"] != "")) {
					$vote_record[$k]["vote_title"] = $voteimg[$id]["vote_title"];
					$vote_record[$k]["baby_id"] = $voteimg[$id]["baby_id"];
					$vote_record[$k]["my_vote_count"] = $times[$id];
					$vote_record[$k]["action_name"] = $action_name;
				}
			}
		}
		else {
			$vote_record = array();
		}

		foreach ($vote_record as $k => $v ) {
			$my_vote_count[$k] = $v["my_vote_count"];
		}

		array_multisort($my_vote_count, SORT_DESC, $vote_record);
		$this->assign("nick_name", $voteimg_users["nick_name"]);
		$this->assign("vote_record", $vote_record);
		$this->display();
	}

	public function vote_details()
	{
		C("TOKEN_ON", false);
		$user_id = I("request.user_id", "intval");
		$type_view = I("request.type_view");
		$action_name = I("request.action_name");
		$nick_name = I("request.nick_name");
		$searchword = I("request.searchword");
		$token = $this->token;
		$where = array();
		$where[C("DB_PREFIX") . "voteimg_record.user_id"] = $user_id;
		$where[C("DB_PREFIX") . "voteimg_record.token"] = $token;

		if ($searchword != "") {
			if (is_numeric($searchword)) {
				$where[C("DB_PREFIX") . "voteimg_item.baby_id"] = (int) $searchword;
			}
			else {
				$where[C("DB_PREFIX") . "voteimg_item.vote_title"] = array("like", "%$searchword%");
			}
		}

		if ($type_view == "all") {
			$total = M("voteimg_record")->where($where)->join(C("DB_PREFIX") . "voteimg_item on " . C("DB_PREFIX") . "voteimg_item.id = " . C("DB_PREFIX") . "voteimg_record.item_id")->field("baby_id,vote_title,vote_time")->order("vote_time desc")->limit($page->firstRow . "," . $page->listRows)->count();
			$page = new \Think\Page($total, 10);
			$record = M("voteimg_record")->where($where)->join(C("DB_PREFIX") . "voteimg_item on " . C("DB_PREFIX") . "voteimg_item.id = " . C("DB_PREFIX") . "voteimg_record.item_id")->field("baby_id,vote_title,vote_time")->order("vote_time desc")->limit($page->firstRow . "," . $page->listRows)->select();
			$this->assign("page", $page->show());
		}
		else if ($type_view == "today") {
			$today_time = strtotime(date("Y-m-d 00:00:00", $_SERVER["REQUEST_TIME"]));
			$evening_time = strtotime(date("Y-m-d 23:59:59", $_SERVER["REQUEST_TIME"]));
			$where["vote_time"] = array("elt", $evening_time);
			$where["vote_time"] = array("egt", $today_time);
			$total = M("voteimg_record")->where($where)->join(C("DB_PREFIX") . "voteimg_item on " . C("DB_PREFIX") . "voteimg_item.id = " . C("DB_PREFIX") . "voteimg_record.item_id")->field("baby_id,vote_title,vote_time")->order("vote_time desc")->limit($page->firstRow . "," . $page->listRows)->count();
			$page = new \Think\Page($total, 10);
			$record = M("voteimg_record")->where($where)->join(C("DB_PREFIX") . "voteimg_item on " . C("DB_PREFIX") . "voteimg_item.id = " . C("DB_PREFIX") . "voteimg_record.item_id")->field("baby_id,vote_title,vote_time")->order("vote_time desc")->limit($page->firstRow . "," . $page->listRows)->select();
			$this->assign("page", $page->show());
		}

		$this->assign("nick_name", $nick_name);
		$this->assign("action_name", $action_name);
		$this->assign("vote_record", $record);
		$this->assign("type_view", $type_view);
		$this->assign("user_id", $user_id);
		$this->assign("firstRow", $page->firstRow);
		$this->assign("searchword", $searchword);
		$this->display();
	}

	private function clear_vote_day($vote_id, $token)
	{
		$today_time = strtotime(date("Y-m-d 00:00:00", $_SERVER["REQUEST_TIME"]));
		$evening_time = strtotime(date("Y-m-d 23:59:59", $_SERVER["REQUEST_TIME"]));
		$cache_time = $evening_time - $_SERVER["REQUEST_TIME"];
		$where = "vote_id = $vote_id and token = '$token' and vote_time < '$today_time'";

		if (M("voteimg_users")->where($where)->find()) {
			M("voteimg_users")->where($where)->save(array("votenum_day" => 0, "vote_today" => 0));
		}
	}

	public function DelProCity()
	{
		if (IS_AJAX) {
			$action_id = (int) $_GET["action_id"];
			$id = (int) $_GET["id"];
			if ($action_id || $id) {
				exit("fail");
			}

			$voteimg = M("voteimg")->where(array("id" => $action_id))->find();
			$pro_city = $voteimg["pro_city"];

			if (strpos($pro_city, "|") !== false) {
				$explode = explode("|", $pro_city);
				unset($explode[$id - 1]);
				$pro_city = implode("|", $explode);

				if (trim($pro_city, "|") == "") {
					exit("删除失败,您开启了地区限制,至少要有一个限制的省市");
				}

				$update = M("voteimg")->where(array("id" => $action_id))->save(array("pro_city" => trim($pro_city, "|")));

				if ($update) {
					exit("done");
				}
				else {
					exit("fail");
				}
			}
			else if ($voteimg["territory_limit"] == 1) {
				exit("删除失败,您开启了地区限制,至少要有一个限制的省市");
			}
		}

		exit("fail");
	}

	public function splittable($vote_id)
	{
		if ($vote_id == "") {
			return false;
		}

		$vote_id = (int) $vote_id;
		$voteimg = M("voteimg")->where(array("id" => $vote_id))->field("split_number,ifsplit")->find();

		if ($voteimg["ifsplit"] == 1) {
			return false;
		}

		$number = (int) $voteimg["split_number"];
		$pointtime = strtotime("2015-11-20 16:10:00");
		$where = array(
			"vote_id" => $vote_id,
			0         => array(
				"votenum"   => array("gt", 0),
				"vote_time" => array("lt", $pointtime)
				)
			);
		$total = M("voteimg_users")->where($where)->count();

		if ($total <= 0) {
			return false;
		}

		if (1500 < $total) {
			$times = ceil($total / 1500);

			if ($number == $times) {
				M("voteimg")->where(array("id" => $vote_id))->save(array("ifsplit" => 1));
				return false;
			}

			$items = M("voteimg_users")->where($where)->order("vote_time desc")->limit($number * 1500, 1500)->select();
			$i = 0;
			$j = 0;
			$add = array();
			$addArray = array();

			foreach ($items as $key => $value ) {
				$time = 0;

				if ($value["item_id"] != "") {
					if (strpos($value["item_id"], ",") !== false) {
						$ids = explode(",", trim($value["item_id"], ","));

						foreach ($ids as $k => $v ) {
							$time = $time + 10;
							$addArray[$i]["vote_id"] = $value["vote_id"];
							$addArray[$i]["user_id"] = $value["user_id"];
							$addArray[$i]["item_id"] = $v;
							$addArray[$i]["vote_time"] = $value["vote_time"] + $time;
							$addArray[$i]["token"] = $value["token"];
							$addArray[$i]["vote_type"] = 1;
							$i++;
						}
					}
					else {
						$time = $time + 10;
						$add[$j]["vote_id"] = $value["vote_id"];
						$add[$j]["user_id"] = $value["user_id"];
						$add[$j]["item_id"] = $value["item_id"];
						$add[$j]["vote_time"] = $value["vote_time"] + $time;
						$add[$j]["token"] = $value["token"];
						$add[$j]["vote_type"] = 1;
						$j++;
					}
				}
			}

			M("voteimg_record")->addAll($addArray);
			M("voteimg_record")->addAll($add);
			unset($addArray);
			unset($add);
			M("voteimg")->where(array("id" => $vote_id))->setInc("split_number");
			$this->success("由于您的数据量过大需对数据重新整合,请勿关闭浏览器耐心等待1-2分钟", U("Voteimg/add_voteimg", array("id" => $vote_id)));
			exit();
		}
		else {
			$items = M("voteimg_users")->where($where)->order("vote_time desc")->select();
			$i = 0;
			$j = 0;
			$add = array();
			$addArray = array();

			foreach ($items as $key => $value ) {
				$time = 0;

				if ($value["item_id"] != "") {
					if (strpos($value["item_id"], ",") !== false) {
						$ids = explode(",", trim($value["item_id"], ","));

						foreach ($ids as $k => $v ) {
							$time = $time + 10;
							$addArray[$i]["vote_id"] = $value["vote_id"];
							$addArray[$i]["user_id"] = $value["user_id"];
							$addArray[$i]["item_id"] = $v;
							$addArray[$i]["vote_time"] = $value["vote_time"] + $time;
							$addArray[$i]["token"] = $value["token"];
							$addArray[$i]["vote_type"] = 1;
							$i++;
						}
					}
					else {
						$time = $time + 10;
						$add[$j]["vote_id"] = $value["vote_id"];
						$add[$j]["user_id"] = $value["user_id"];
						$add[$j]["item_id"] = $value["item_id"];
						$add[$j]["vote_time"] = $value["vote_time"] + $time;
						$add[$j]["token"] = $value["token"];
						$add[$j]["vote_type"] = 1;
						$j++;
					}
				}
			}

			M("voteimg_record")->addAll($addArray);
			M("voteimg_record")->addAll($add);
			M("voteimg")->where(array("id" => $vote_id))->save(array("ifsplit" => 1));
		}
	}
}