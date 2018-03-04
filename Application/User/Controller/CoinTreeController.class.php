<?php
namespace User\Controller;
use Common\Controller\UserController;
class CoinTreeController extends UserController{
	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("CoinTree");
	}

	public function index()
	{
		$where = array("token" => $this->token);
		$search_word = I("post.search_word");

		if (!$search_word) {
			$where["action_name|keyword"] = array("like", "%" . $search_word . "%");
		}

		$total = M("cointree")->where($where)->count();
		$Page = new \Think\Page($total, 10);
		$list = M("cointree")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("id desc")->select();
		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("search_word", $search_word);
		$this->display();
	}

	public function add_action()
	{
		if (IS_POST) {
			$data = array();
			$data["action_name"] = I("post.action_name");
			$data["keyword"] = I("post.keyword");
			$data["reply_title"] = I("post.reply_title");
			$data["reply_content"] = I("post.reply_content");
			$data["reply_pic"] = I("post.reply_pic");
			$data["action_desc"] = I("post.action_desc");
			$data["remind_word"] = I("post.remind_word");
			$data["remind_link"] = I("post.remind_link");
			$data["starttime"] = strtotime(I("post.starttime"));
			$data["endtime"] = strtotime(I("post.endtime"));
			$data["totaltimes"] = I("post.totaltimes", "intval");
			$data["everydaytimes"] = I("post.everydaytimes", "intval");
			$data["join_number"] = I("post.join_number", "intval");
			$data["usedup_conins"] = I("post.usedup_conins", "intval");
			$data["gain_conins"] = I("post.gain_conins", "intval");
			$data["timespan"] = I("post.timespan", "intval");
			$data["record_nums"] = I("post.record_nums", "intval");
			$data["is_limitwin"] = I("post.is_limitwin", "intval");
			$data["is_follow"] = I("post.is_follow", "intval");
			$data["is_register"] = I("post.is_register", "intval");
			$data["is_amount"] = I("post.is_amount", "intval");
			$data["custom_sharetitle"] = I("post.custom_sharetitle");
			$data["custom_sharedsc"] = I("post.custom_sharedsc");
			$data["follow_msg"] = I("post.follow_msg");
			$data["follow_btn_msg"] = I("post.follow_btn_msg");
			$data["custom_follow_url"] = I("post.custom_follow_url");
			$data["register_msg"] = I("post.register_msg");
			$data["sms_verify"] = I("post.sms_verify", "intval");
			$data["status"] = I("post.status", "intval");

			if ($this->token == "") {
				$this->error("参数错误");
			}

			if ($data["action_name"] == "") {
				$this->error("活动名称不能为空");
			}

			if ($data["keyword"] == "") {
				$this->error("回复的关键词不能为空");
			}

			if ($data["reply_title"] == "") {
				$this->error("回复的标题不能为空");
			}

			if ($data["reply_content"] == "") {
				$this->error("回复的内容不能为空");
			}

			if ($data["reply_pic"] == "") {
				$this->error("回复图片不能为空");
			}

			if ($data["remind_word"] == "") {
				$this->error("活动提示语不能为空");
			}

			if ($data["remind_link"] == "") {
				$this->error("提示语跳转链接不能为空");
			}

			if ((int) $data["totaltimes"] <= 0) {
				$this->error("总摇奖次数请输入大于0的整数");
			}

			if ((int) $data["everydaytimes"] < 0) {
				$this->error("每天摇奖次数请输入整数");
			}

			if ((int) $data["totaltimes"] < (int) $data["everydaytimes"]) {
				$this->error("每人每天的摇奖次数小于总摇奖次数");
			}

			if ((int) $data["join_number"] <= 0) {
				$this->error("预计总参加人数请输入大于0的整数");
			}

			if ((int) $data["usedup_conins"] <= 0) {
				$this->error("每摇奖一次消耗的金币数请输入大于0的整数");
			}

			if ((int) $data["gain_conins"] <= 0) {
				$this->error("帮助分享一次增加的金币数请输入大于0的整数");
			}

			if ($data["endtime"] < $data["starttime"]) {
				$this->error("开始时间不能大于结束时间");
			}

			if (strpos($data["reply_pic"], "http") === false) {
				$data["reply_pic"] = $this->siteUrl . $data["reply_pic"];
			}

			if (2400 < strlen($data["action_desc"])) {
				$this->error("活动简介不超过800字");
			}

			$prize = array();
			$prize["first_nums"] = I("post.first_nums", "intval");
			$prize["first_img"] = I("post.first_img");
			$prize["first_prize"] = I("post.first_prize");

			if ((int) $prize["first_nums"] <= 0) {
				$this->error("一等奖数量请输入大于0的整数");
			}

			if ($prize["first_img"] == "") {
				$this->error("一等奖展示图片不能为空");
			}

			if ($prize["first_prize"] == "") {
				$this->error("一等奖奖品说明不能为空");
			}

			if ((0 < I("post.second_nums", "intval")) && (I("post.second_img") != "") && (I("post.second_prize") != "")) {
				$prize["second_nums"] = I("post.second_nums", "intval");
				$prize["second_img"] = I("post.second_img");
				$prize["second_prize"] = I("post.second_prize");
			}
			else {
				$prize["second_nums"] = "";
				$prize["second_img"] = "";
				$prize["second_prize"] = "";
			}

			if ((0 < I("post.third_nums", "intval")) && (I("post.third_img") != "") && (I("post.third_prize") != "")) {
				$prize["third_nums"] = I("post.third_nums", "intval");
				$prize["third_img"] = I("post.third_img");
				$prize["third_prize"] = I("post.third_prize");
			}
			else {
				$prize["third_nums"] = "";
				$prize["third_img"] = "";
				$prize["third_prize"] = "";
			}

			if ((0 < I("post.fourth_nums", "intval")) && (I("post.fourth_img") != "") && (I("post.fourth_prize") != "")) {
				$prize["fourth_nums"] = I("post.fourth_nums", "intval");
				$prize["fourth_img"] = I("post.fourth_img");
				$prize["fourth_prize"] = I("post.fourth_prize");
			}
			else {
				$prize["fourth_nums"] = "";
				$prize["fourth_img"] = "";
				$prize["fourth_prize"] = "";
			}

			if ((0 < I("post.fifth_nums", "intval")) && (I("post.fifth_img") != "") && (I("post.fifth_prize") != "")) {
				$prize["fifth_nums"] = I("post.fifth_nums", "intval");
				$prize["fifth_img"] = I("post.fifth_img");
				$prize["fifth_prize"] = I("post.fifth_prize");
			}
			else {
				$prize["fifth_nums"] = "";
				$prize["fifth_img"] = "";
				$prize["fifth_prize"] = "";
			}

			if ((0 < I("post.sixth_nums", "intval")) && (I("post.sixth_img") != "") && (I("post.sixth_prize") != "")) {
				$prize["sixth_nums"] = I("post.sixth_nums", "intval");
				$prize["sixth_img"] = I("post.sixth_img");
				$prize["sixth_prize"] = I("post.sixth_prize");
			}
			else {
				$prize["sixth_nums"] = "";
				$prize["sixth_img"] = "";
				$prize["sixth_prize"] = "";
			}

			$id = I("post.id", "intval");

			if (!$id) {
				$data["token"] = $this->token;
				$action_id = M("cointree")->add($data);
				$prize["cid"] = $action_id;
				$add_prize = M("cointree_prize")->add($prize);
				if ($action_id && $add_prize) {
					$this->handleKeyword($action_id, "CoinTree", I("post.keyword"));
					$this->success("添加摇钱树活动成功", U("CoinTree/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("添加摇钱树活动失败");
					exit();
				}
			}
			else {
				$stat = true;
				$update_action = M("cointree")->where(array("id" => $id))->save($data);

				if ($update_action === false) {
					$stat = false;
				}

				$update_prize = M("cointree_prize")->where(array("cid" => $id))->save($prize);

				if ($update_prize === false) {
					$stat = false;
				}

				if ($stat) {
					$this->handleKeyword($id, "CoinTree", I("post.keyword"));
					S($this->token . "_" . $id . "_cointree", NULL);
					$this->success("修改摇钱树活动成功", U("CoinTree/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("修改摇钱树活动失败");
					exit();
				}
			}
		}

		if ($_GET["id"] != "") {
			$action_info = M("cointree")->where(array("id" => $_GET["id"]))->find();
			$prize_info = M("cointree_prize")->where(array("cid" => $_GET["id"]))->find();
			if (!$action_info && !$prize_info) {
				$this->assign("set", $action_info);
				$this->assign("vo", $prize_info);
			}
		}

		$this->assign("token", $this->token);
		$this->display();
	}

	public function prizerecord()
	{
		C("TOKEN_ON", false);
		$token = I("request.token","");
		$action_id = I("request.id",0, "intval");
		$where = array("token" => $token, "cid" => $action_id);
		$search_word = I("request.search_word","");

		if (!$search_word) {
			$where["serialnumber|wecha_name"] = array("like", "%" . $search_word . "%");
		}

		$total = M("cointree_record")->where($where)->count();
		$Page = new \Think\Page($total, 10);
		$list = M("cointree_record")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("prize asc")->select();
		$this->assign("list", $list);
		$this->assign("token", $token);
		$this->assign("id", $action_id);
		$this->assign("page", $Page->show());
		$this->assign("search_word", $search_word);
		$this->display();
	}

	public function editprizerecord()
	{
		if (IS_POST) {
			$sendstutas = I("post.sendstutas", "intval");
			$id = I("post.id", "intval");

			if ($sendstutas == 1) {
				$data["sendstutas"] = 1;
				$data["sendtime"] = time();
			}
			else {
				$data["sendstutas"] = 0;
				$data["sendtime"] = "";
			}

			$exists = M("cointree_record")->where(array("id" => $id))->find();

			if (!$exists) {
				$update = M("cointree_record")->where(array("id" => $id))->save($data);

				if ($update) {
					$this->success("编辑成功", U("CoinTree/prizerecord", array("token" => $exists["token"], "id" => $exists["cid"])));
					exit();
				}
				else {
					$this->error("编辑失败");
					exit();
				}
			}
			else {
				$this->error("不存在该编辑项");
				exit();
			}
		}

		if ($_GET["id"]) {
			$info = M("cointree_record")->where(array("id" => $_GET["id"]))->find();

			if (!$info) {
				$this->assign("set", $info);
			}
		}

		$this->display();
	}

	public function delprizerecord()
	{
		$id = (int) $_GET["id"];
		$exists = M("cointree_record")->where(array("id" => $id))->find();

		if (!$exists) {
			$del = M("cointree_record")->where(array("id" => $id))->delete();

			if ($del) {
				$this->success("删除成功", U("CoinTree/prizerecord", array("token" => $this->token, "id" => $exists["cid"])));
				exit();
			}
		}
		else {
			$this->error("不存在该删除项");
			exit();
		}
	}

	public function del_action()
	{
		$id = (int) $_GET["id"];
		$exists = M("cointree")->where(array("id" => $id))->find();

		if (!$exists) {
			$del = M("cointree")->where(array("id" => $id))->delete();

			if ($del) {
				$this->handleKeyword($id, "CoinTree", "", "", 1);
				M("cointree_prize")->where(array("cid" => $id))->delete();
				S($this->token . "_" . $id . "_cointree", NULL);
				$this->success("删除成功", U("CoinTree/index", array("token" => $this->token)));
				exit();
			}
		}
		else {
			$this->error("不存在该删除项");
			exit();
		}
	}
}
