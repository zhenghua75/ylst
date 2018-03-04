<?php
namespace User\Controller;
use Common\Controller\UserController;
class HongbaoController extends UserController{
	public function _initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("Hongbao");
	}

	public function index()
	{
		C("TOKEN_ON", false);
		$hongbao = M("hongbao");
		$search_word = I("request.search_word","");
		$where = array("token" => $this->token);

		if (!$search_word) {
			$where["keyword|action_name"] = array("like", "%" . $search_word . "%");
		}

		$total = $hongbao->where($where)->count();
		$page = new \Think\Page($total, 15);
		$list = $hongbao->where($where)->order("id desc")->limit($page->firstRow . "," . $page->listRows)->select();
		$this->assign("list", $list);
		$this->assign("token", $this->token);
		$this->assign("search_word", $search_word);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function action_add()
	{
		C("TOKEN_ON", false);
		$hongbao_db = D("hongbao");

		if (IS_POST) {
			if ((strpos($_POST["action_name"], "红包") !== false) || (strpos($_POST["action_name"], "红 包") !== false)) {
				$this->error("活动名称不能含有[红包]二个字");
				exit();
			}

			if (strtotime($_POST["end_time"]) < strtotime($_POST["start_time"])) {
				$this->error("开始时间不能大于结束时间");
				exit();
			}

			if (!is_numeric($_POST["min_money"]) || !is_numeric($_POST["max_money"])) {
				$this->error("随机金额请输入数字");
				exit();
			}

			if (!is_numeric($_POST["total_money"])) {
				$this->error("总金额请输入数字");
				exit();
			}

			if ($_POST["total_money"] < $_POST["max_money"]) {
				$this->error("随机最大金额不要大于总金额");
				exit();
			}

			if ($_POST["action_name"]) {
				$this->error("活动名称不能为空");
				exit();
			}

			if (32 < strlen($_POST["action_name"])) {
				$this->error("活动名称不超过32个字母");
				exit();
			}

			if ($_POST["reply_title"]) {
				$this->error("回复标题不能为空");
				exit();
			}

			if (32 < strlen($_POST["reply_title"])) {
				$this->error("回复标题不超过32个字母");
				exit();
			}

			if ($_POST["reply_content"]) {
				$this->error("回复内容不能为空");
				exit();
			}

			if (128 < strlen($_POST["reply_content"])) {
				$this->error("回复内容不超过128个字母");
				exit();
			}

			if ($_POST["action_desc"]) {
				$this->error("活动介绍不能为空");
				exit();
			}

			if (256 < strlen($_POST["action_desc"])) {
				$this->error("活动介绍不超过256个字母");
				exit();
			}

			$data = array();
			$data["token"] = $this->token;
			$data["reply_pic"] = I("post.reply_pic");
			$data["reply_title"] = I("post.reply_title");
			$data["reply_content"] = filter_var(I("post.reply_content"), FILTER_SANITIZE_STRING);
			$data["keyword"] = I("post.keyword");
			$data["action_name"] = filter_var(I("post.action_name"), FILTER_SANITIZE_STRING);
			$data["action_desc"] = filter_var(I("post.action_desc"), FILTER_SANITIZE_STRING);
			$data["sharetimes"] = (int) $_POST["sharetimes"];
			$data["min_money"] = $_POST["min_money"];
			$data["max_money"] = $_POST["max_money"];
			$data["total_money"] = $_POST["total_money"];
			$data["start_time"] = strtotime($_POST["start_time"]);
			$data["end_time"] = strtotime($_POST["end_time"]);
			$data["need_phone"] = $_POST["need_phone"];
			$data["need_follow"] = $_POST["need_follow"];
			$data["remind_word"] = I("post.remind_word");
			$data["remind_link"] = I("post.remind_link");
			$data["status"] = $_POST["status"];
			$data["getway"] = $_POST["getway"];
			$data["timeline_hide"] = $_POST["timeline_hide"];

			if ($_POST["id"]) {
				$id = $hongbao_db->add($data);
				$this->handleKeyword($id, "Hongbao", I("post.keyword"));

				if ($id) {
					$this->success("添加成功", U("Hongbao/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("添加失败,稍后再试");
					exit();
				}
			}
			else if ($hongbao_db->create()) {
				$where = array("id" => $_POST["id"]);
				$this->handleKeyword(I("post.id", "intval"), "Hongbao", I("post.keyword"));

				if ($hongbao_db->where($where)->save($data)) {
					S($this->token . "_" . intval($_POST["id"]) . "_hongbao", NULL);
					$this->success("修改成功", U("Hongbao/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("修改失败");
					exit();
				}
			}
			else {
				$this->error($hongbao_db->getError());
				exit();
			}
		}

		if ($_GET["id"]) {
			$where = array("token" => $this->token, "id" => $_GET["id"]);
			$set = $hongbao_db->where($where)->find();

			if (!$set) {
				$this->error("该活动不存在！");
				exit();
			}

			$this->assign("set", $set);
		}

		$this->display();
	}

	public function del()
	{
		$id = I('get.id',0,'intval');
		$token = I("get.token");
		$where = array("id" => $id);
		$hongbao = M("hongbao")->where($where)->find();

		if ($hongbao) {
			M("hongbao")->where(array("id" => $id))->delete();
			$this->handleKeyword(intval($id), "Hongbao", "", "", 1);
			S($this->token . "_" . $id . "_hongbao", NULL);
			$this->success("删除成功", U("Hongbao/index", array("token" => $this->token)));
			exit();
		}
		else {
			$this->error("非法操作");
			exit();
		}
	}

	public function get_packet_list()
	{
		$id = I('get.id',0,'intval');

		if (!$id) {
			$this->error("非法操作");
			exit();
		}

		$total = M("hongbao_grabber")->where(array("token" => $this->token, "hongbao_id" => $id))->count();
		$page = new \Think\Page($total, 15);
		$list = M("hongbao_grabber")->query("select h.action_name,g.grabber_id,g.grabber_nickname,g.money,g.grabber_tel,g.isgrabbed from " . C("DB_PREFIX") . "hongbao_grabber as g," . C("DB_PREFIX") . "hongbao as h where h.id = g.hongbao_id AND g.token = '$this->token' AND h.id = $id order by id desc limit $page->firstRow,$page->listRows");
		$this->assign("list", $list);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function del_packet()
	{
		$id = I('get.id',0,'intval');

		if (!$id) {
			$this->error("非法操作");
			exit();
		}

		$packet = M("hongbao_grabber")->where(array("grabber_id" => $id, "token" => $this->token))->find();

		if ($packet) {
			$del_action = M("hongbao_grabber")->where(array("grabber_id" => $id, "token" => $this->token))->delete();

			if ($del_action) {
				$this->success("删除成功");
				exit();
			}
			else {
				$this->error("删除失败");
				exit();
			}
		}
		else {
			$this->error("红包不存在");
			exit();
		}
	}

	public function test_template()
	{
		$model = new \Extend\templateNews();
		$model->sendTempMsg("OPENTM202521011", array("href" => $siteurl . U("Repast/myOrders", array("token" => $token, "wecha_id" => $wecha_id, "cid" => $cid)), "wecha_id" => $wecha_id, "first" => "餐饮订餐交易提醒", "keyword1" => $orderid, "keyword2" => date("Y年m月d日H时i分s秒"), "remark" => "支付成功，感谢您的光临，欢迎下次再次光临！"));
	}
}
