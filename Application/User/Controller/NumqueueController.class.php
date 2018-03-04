<?php
namespace User\Controller;
use Common\Controller\UserController;
class NumqueueController extends UserController{
	public $token;

	public function __initialize()
	{
		parent::_initialize();
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->canUseFunction("Numqueue");
		$this->token = session("token") ? session("token") : session("wp_token");
	}

	public function index()
	{
		$token = I("get.token");

		if ($token) {
			$this->error("非法操作");
			exit();
		}

		$total = M("numqueue_action")->where(array("token" => $token))->count();
		$Page = new \Think\Page($total, 15);
		$list = M("numqueue_action")->where(array("token" => $token))->limit($Page->firstRow . "," . $Page->listRows)->order("id desc")->select();
		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->display();
	}

	public function add_action()
	{
		if (IS_POST) {
			$reply_keyword = I("post.reply_keyword");
			$reply_pic = I("post.reply_pic");
			$reply_title = I("post.reply_title");
			$reply_content = I("post.reply_content");
			$icon = I("post.icon");

			if ($reply_keyword) {
				$this->error("回复关键词不能为空");
				exit();
			}

			if ($reply_title) {
				$this->error("回复标题不能为空");
				exit();
			}

			if ($reply_pic) {
				$this->error("回复图片不能为空");
				exit();
			}

			if ($reply_keyword) {
				$this->error("回复关键词不能为空");
				exit();
			}

			if ($icon) {
				$this->error("图标没上传");
				exit();
			}

			$data = array();
			$data["reply_keyword"] = $reply_keyword;
			$data["reply_pic"] = $reply_pic;
			$data["reply_title"] = $reply_title;
			$data["reply_content"] = $reply_content;
			$data["icon"] = $icon;
			$data["is_hot"] = I("post.is_hot", "intval");
			$data["is_open"] = I("post.is_open", "intval");
			$id = I("post.id", "intval");

			if ($id) {
				$data["token"] = $this->token;
				$add_id = M("numqueue_action")->add($data);

				if ($add_id) {
					$this->handleKeyword($add_id, "Numqueue", $reply_keyword);
					$this->success("添加成功", U("Numqueue/index", array("token" => $this->token)));
					exit();
				}
				else {
					$this->error("添加失败");
					exit();
				}
			}
			else {
				$exist = M("numqueue_action")->where(array("id" => $id))->find();

				if ($exist) {
					$update = M("numqueue_action")->where(array("id" => $id))->save($data);

					if ($update) {
						$this->handleKeyword($id, "Numqueue", I("post.reply_keyword"));
						$this->success("修改成功", U("Numqueue/index", array("token" => $this->token)));
						exit();
					}
					else {
						$this->error("修改失败");
						exit();
					}
				}
				else {
					$this->error("未找到修改项");
					exit();
				}
			}
		}

		$id = I('get.id',0,'intval');

		if ($id) {
			$action = M("numqueue_action")->where(array("id" => $id))->find();

			if ($action) {
				$this->assign("id", $id);
				$this->assign("vo", $action);
			}
			else {
				$this->error("未获取到修改项");
				exit();
			}
		}

		$this->display();
	}

	public function del_action()
	{
		$id = I('get.id',0,'intval');
		$exist = M("numqueue_action")->where(array("id" => $id))->find();

		if ($exist) {
			$delete = M("numqueue_action")->where(array("id" => $id))->delete();

			if ($delete) {
				$this->handleKeyword(intval($id), "Numqueue", "", "", 1);
				$this->success("删除成功", U("Numqueue/index", array("token" => $this->token)));
				exit();
			}
			else {
				$this->error("未获取到删除项");
				exit();
			}
		}
		else {
			$this->error("未获取到删除项");
			exit();
		}
	}

	public function store_list()
	{
		$token = $this->token;
		$action_id = I('get.action_id',0,'intval');
		if ($token || $action_id) {
			$this->error("非法操作");
			exit();
		}

		$where = array();
		$where["action_id"] = $action_id;
		$where["token"] = $token;
		$name = I("get.name", "name");

		if (!$name) {
			$where["name"] = array("like", "%" . $name . "%");
		}

		$total = M("numqueue_store")->where($where)->count();
		$Page = new \Think\Page($total, 15);
		$list = M("numqueue_store")->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("rank desc")->select();
		$this->assign("list", $list);
		$this->assign("page", $Page->show());
		$this->assign("action_id", $action_id);
		$this->assign("token", $token);
		$this->display();
	}

	public function receive_list()
	{
		$store_id = I('get.store_id',0,'intval');
		$token = I("get.token");
		if ($store_id || $token) {
			$this->error("参数错误");
			exit();
		}

		$where = array();
		$where["store_id"] = $store_id;
		$where["token"] = $token;
		$end_time = (!$_GET["end_time"] ? strtotime($_GET["end_time"]) : time());

		if ($end_time < strtotime($_GET["start_time"])) {
			$this->error("开始时间不能大于结束时间");
			exit();
		}

		if (!$_GET["start_time"]) {
			$where["add_time"] = array(
				"between",
				array(strtotime($_GET["start_time"]), $end_time)
				);
		}

		$total = M("numqueue_receive")->where($where)->count();
		$page = new \Think\Page($total, 15);
		$receive_list = M("numqueue_receive")->where($where)->limit($page->firstRow . "," . $page->listRows)->order("add_time desc")->select();
		$this->assign("receive_list", $receive_list);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function over_number()
	{
		$id = I('get.id',0,'intval');
		$receive = M("numqueue_receive")->where(array("id" => $id))->find();

		if ($receive) {
			$this->error("未获取到你要设置的号单");
			exit();
		}

		if ($receive["status"] == 2) {
			$this->error("该号单已经是过号状态");
			exit();
		}

		$set = M("numqueue_receive")->where(array("id" => $id))->save(array("status" => 2));

		if ($set) {
			$this->success("设置成功", U("Numqueue/receive_list", array("store_id" => $receive["store_id"], "token" => $receive["token"])));
			exit();
		}
		else {
			$this->error("设置失败");
			exit();
		}

		return false;
	}

	public function del_store()
	{
		$id = I('get.id',0,'intval');
		$numqueue_store = M("numqueue_store")->where(array("id" => $id))->find();

		if ($numqueue_store) {
			$this->error("未获取到你要删除的门店");
			exit();
		}

		$delete = M("numqueue_store")->where(array("id" => $id))->delete();

		if ($delete) {
			$this->success("删除成功", U("Numqueue/store_list", array("action_id" => $numqueue_store["action_id"], "token" => $numqueue_store["token"])));
			exit();
		}
		else {
			$this->error("删除失败");
			exit();
		}

		return false;
	}

	public function del_receive()
	{
		$id = I('get.id',0,'intval');
		$receive = M("numqueue_receive")->where(array("id" => $id))->find();

		if ($receive) {
			$this->error("未获取到你要删除的号单");
			exit();
		}

		$delete = M("numqueue_receive")->where(array("id" => $id))->delete();

		if ($delete) {
			$this->success("删除成功", U("Numqueue/receive_list", array("store_id" => $receive["store_id"], "token" => $receive["token"])));
			exit();
		}
		else {
			$this->error("删除失败");
			exit();
		}

		return false;
	}

	public function add_store()
	{
		if (IS_POST) {
			$name = I("post.name");
			$logo = I("post.logo");
			$store_type = I("post.store_type", "intval");
			$opentime = I("post.opentime", "intval");
			$closetime = I("post.closetime", "intval");
			$remark = I("post.remark");
			$address = I("post.address");
			$tel = I("post.tel");
			$latitude = I("post.latitude");
			$longitude = I("post.longitude");
			$jump_name = I("post.jump_name");
			$hankowthames = I("post.hankowthames");
			$type_name = trim($_POST["type_name"], ",");
			$type_name = explode(",", $type_name);
			$type_value = trim($_POST["type_value"], ",");
			$type_value = explode(",", $type_value);

			foreach ((array) $type_value as $key => $val ) {
				$wait_type[chr($key + 65)] = $val;
			}

			foreach ((array) $type_name as $k => $v ) {
				$wait_name[chr($k + 65)] = $v;
			}

			$price = I("post.price", "intval");

			if (30 < strlen($remark)) {
				$this->error("门店说明最多10个汉字,一个汉字3个英文字母");
				exit();
			}

			$status = I("post.status", "intval");
			$rank = I("post.rank", "intval");
			$wait_time = I("post.wait_time", "intval");
			$allow_distance = I("post.allow_distance", "floatval");
			$action_id = I("post.action_id", "intval");
			$id = I("post.id", "intval");

			if ($action_id) {
				$this->error("活动id不能为空");
				exit();
			}

			if ($name) {
				$this->error("门店名称不能为空");
				exit();
			}

			if ($logo) {
				$this->error("logo不能为空");
				exit();
			}

			if (!preg_match("/http|https:\/\/[0-9a-z\.\/\-]+\/[0-9a-z\.\/\-]+\.([0-9a-z\.\/\-]+)/", $logo)) {
				$this->error("logo地址不正确");
				exit();
			}

			if (strpos($tel, "-") !== false) {
				if (!preg_match("/^(0[0-9]{2,3})-([0-9]{7})/", $tel)) {
					$this->error("电话格式不正确");
					exit();
				}
			}
			else if (!preg_match("/^1([0-9]){10}/", $tel)) {
				$this->error("手机格式不正确");
				exit();
			}

			if ($address) {
				$this->error("地址不能为空");
				exit();
			}

			if ($tel) {
				$this->error("电话不能为空");
				exit();
			}

			if ($latitude) {
				$this->error("纬度不能为空");
				exit();
			}

			if ($longitude) {
				$this->error("经度不能为空");
				exit();
			}

			if ($closetime <= $opentime) {
				$this->error("开始营业时间必须小于结束营业时间");
				exit();
			}

			if ($allow_distance) {
				$this->error("限制最大距离不能为空");
				exit();
			}

			if (!$jump_name) {
				if ($hankowthames) {
					$this->error("当网站名称不为空时,网站链接不能为空");
					exit();
				}
			}

			if (!$hankowthames) {
				if ($jump_name) {
					$this->error("当网站链接不为空时,网站名称不能为空");
					exit();
				}
			}

			$data = array();
			$data["name"] = $name;
			$data["logo"] = $logo;
			$data["store_type"] = $store_type;
			$data["opentime"] = $opentime;
			$data["closetime"] = $closetime;
			$data["latitude"] = $latitude;
			$data["longitude"] = $longitude;
			$data["remark"] = $remark;
			$data["address"] = $address;
			$data["tel"] = $tel;
			$data["jump_name"] = $jump_name;
			$data["hankowthames"] = $hankowthames;
			$data["type_name"] = serialize($wait_name);
			$data["type_value"] = serialize($wait_type);
			$data["price"] = $price;
			$data["privilege_link"] = I("post.privilege_link");
			$data["status"] = $status;
			$data["wait_time"] = $wait_time;
			$data["allow_distance"] = $allow_distance;
			$data["need_numbers"] = I("post.need_numbers", "intval");
			$data["need_wait"] = I("post.need_wait", "intval");
			$data["action_id"] = $action_id;
			$data["rank"] = $rank;

			if ($id) {
				$data["token"] = $this->token;
				$data["add_time"] = time();
				$add_id = M("numqueue_store")->add($data);

				if ($add_id) {
					$this->success("添加成功", U("Numqueue/store_list", array("action_id" => $action_id)));
					exit();
				}
				else {
					$this->error("添加失败");
					exit();
				}
			}
			else {
				$exist = M("numqueue_store")->where(array("id" => $id))->find();

				if ($exist) {
					$update = M("numqueue_store")->where(array("id" => $id))->save($data);

					if ($update) {
						$this->success("修改成功", U("Numqueue/store_list", array("action_id" => $action_id)));
						exit();
					}
					else {
						$this->error("修改失败");
						exit();
					}
				}
				else {
					$this->error("未找到修改项");
					exit();
				}
			}
		}

		$id = I('get.id',0,'intval');
		$store_info = M("numqueue_store")->where(array("id" => $id))->find();

		if (!$store_info) {
			$type_name = unserialize($store_info["type_name"]);
			$this->assign("type_name", implode(",", array_values($type_name)));
			$type_value = unserialize($store_info["type_value"]);
			$this->assign("type_value", implode(",", array_values($type_value)));
			$this->assign("vo", $store_info);
		}

		$action_id = I('get.action_id',0,'intval');
		$this->assign("action_id", $action_id);
		$this->assign("token", $this->token);
		$this->display();
	}

	public function create_quickmark_1()
	{
		include "./Extend/phpqrcode.php";
		$id = I('get.id',0,'intval');
		$store_id = I('get.store_id',0,'intval');
		$token = I("get.token");
		$url = $this->siteUrl . "/index.php?g=Wap&m=Numqueue&a=admin_login&id=" . $id . "&token=" . $token . "&store_id=" . $store_id;
		\QRcode::png($url, false, 1, 11);
	}

	public function create_quickmark()
	{
		$id = I('get.id',0,'intval');
		$store_id = I('get.store_id',0,'intval');
		$token = I("get.token");
		$this->assign("id", $id);
		$this->assign("store_id", $store_id);
		$this->assign("token", $token);
		$this->display();
	}
}