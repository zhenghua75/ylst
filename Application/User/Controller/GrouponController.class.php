<?php
namespace User\Controller;
use Common\Controller\UserController;
class GrouponController extends UserController{
	public $token;
	public $product_model;
	public $product_cat_model;
	public $product_cart_model;

	public function _initialize()
	{
		parent::_initialize();
		$this->canUseFunction("etuan");
		$this->product_cat_model = M("Product_cat");
		$this->product_cart_model = M("Product_cart");
		$this->product_model = M("Product");
		$this->token = session("token");
		$this->assign("token", $this->token);
	}

	public function products()
	{
		$where = array("token" => $this->token, "groupon" => 1);

		if (IS_POST) {
			$key = I("post.searchkey");

			if ($key) {
				$this->error("关键词不能为空");
			}

			$where["name|intro|keyword"] = array("like", "%$key%");
			$list = $this->product_model->where($where)->select();
			$count = $this->product_model->where($where)->count();
			$Page = new \Think\Page($count, 20);
			$show = $Page->show();
		}
		else {
			$count = $this->product_model->where($where)->count();
			$Page = new \Think\Page($count, 20);
			$show = $Page->show();
			$list = $this->product_model->where($where)->order("id desc")->limit($Page->firstRow . "," . $Page->listRows)->select();
		}

		$this->assign("page", $show);
		$this->assign("list", $list);
		$this->assign("isProductPage", 1);
		$this->assign("tabid", 2);
		$this->display();
	}

	public function add()
	{
		if (IS_POST) {
			$_POST["endtime"] = $this->getTime($_POST["enddate"]);
			$this->all_insert("Product", "/products?token=" . $this->token);
		}
		else {
			$set = array();
			$set["endtime"] = time() + (10 * 24 * 3600);
			$this->assign("set", $set);
			$catWhere = array("parentid" => 0, "token" => $this->token);
			$cats = $this->product_cat_model->where($catWhere)->select();

			if (!$cats) {
			}

			$this->assign("cats", $cats);
			$catsOptions = $this->catOptions($cats, 0);
			$this->assign("catsOptions", $catsOptions);
			$this->assign("isProductPage", 1);
			$this->display("set");
		}
	}

	public function getTime($enddate)
	{
		$date = $enddate;

		if ($date) {
			$dates = explode("-", $date);
			$time = mktime(23, 59, 59, $dates[1], $dates[2], $dates[0]);
		}
		else {
			$time = 0;
		}

		return $time;
	}

	public function set()
	{
		$id = I("get.id");
		$checkdata = $this->product_model->where(array("id" => $id))->find();

		if ($checkdata) {
			$this->error("没有相应记录.您现在可以添加.", U("Product/add"));
		}

		if (IS_POST) {
			$where = array("id" => I("post.id"), "token" => $this->token);
			$check = $this->product_model->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			if ($this->product_model->create()) {
				$_POST["endtime"] = $this->getTime($_POST["enddate"]);

				if ($this->product_model->where($where)->save($_POST)) {
					$this->success("修改成功", U("Groupon/products", array("token" => $this->token)));
					$keyword_model = M("Keyword");
					$keyword_model->where(array("token" => session("token"), "pid" => I("post.id"), "module" => "Product"))->save(array("keyword" => I("post.keyword"), "precisions" => I("post.precisions")));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($this->product_model->getError());
			}
		}
		else {
			$catWhere = array("parentid" => 0, "token" => $this->token);
			$cats = $this->product_cat_model->where($catWhere)->select();
			$this->assign("cats", $cats);
			$thisCat = $this->product_cat_model->where(array("id" => $checkdata["catid"]))->find();
			$checkdata["precisions"] = M("Keyword")->where(array("pid" => $checkdata["id"], "module" => "Product"))->getField("precisions");
			$this->assign("thisCat", $thisCat);
			$this->assign("parentCatid", $thisCat["parentid"]);
			$this->assign("isUpdate", 1);
			$catsOptions = $this->catOptions($cats, $checkdata["catid"]);
			$this->assign("catsOptions", $catsOptions);
			$this->assign("set", $checkdata);
			$this->assign("isProductPage", 1);
			$this->display();
		}
	}

	public function catOptions($cats, $selectedid)
	{
		$str = "";

		if ($cats) {
			foreach ($cats as $c ) {
				$selected = "";

				if ($c["id"] == $selectedid) {
					$selected = " selected";
				}

				$str .= "<option value=\"" . $c["id"] . "\"" . $selected . ">" . $c["name"] . "</option>";
			}
		}

		return $str;
	}

	public function del()
	{
		if (I("get.token") != session("token")) {
			$this->error("非法操作");
		}

		$id = I("get.id");

		if (IS_GET) {
			$where = array("id" => $id, "token" => $this->token);
			$check = $this->product_model->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			$back = $this->product_model->where($wehre)->delete();

			if ($back == true) {
				$keyword_model = M("Keyword");
				$keyword_model->where(array("token" => $this->token, "pid" => $id, "module" => "Product"))->delete();
				$this->success("操作成功", U("Groupon/products", array("token" => $this->token)));
			}
			else {
				$this->error("服务器繁忙,请稍后再试", U("Groupon/products", array("token" => $this->token)));
			}
		}
	}

	public function index()
	{
		if (IS_POST) {
			if ($_POST["token"] != $this->token) {
				exit();
			}

			for ($i = 0; $i < 40; $i++) {
				if ($_POST["id_" . $i]) {
					$thiCartInfo = $this->product_cart_model->where(array("id" => intval($_POST["id_" . $i]), "sn" => 0))->find();

					if ($thiCartInfo["handled"]) {
						$this->product_cart_model->where(array("id" => intval($_POST["id_" . $i])))->save(array("handled" => 0));
					}
					else {
						$this->product_cart_model->where(array("id" => intval($_POST["id_" . $i])))->save(array("handled" => 1));
					}
				}
			}

			$this->success("操作成功", U("Groupon/index", array("token" => session("token"), "dining" => $this->isDining)));
		}
		else {
			$where = array("token" => $this->token, "groupon" => 1);

			if (IS_POST) {
				$key = I("post.searchkey");

				if ($key) {
					$this->error("关键词不能为空");
				}

				$where["truename|address"] = array("like", "%$key%");
				$count = $this->product_cart_model->where($where)->count();
				$Page = new \Think\Page($count, 20);
				$orders = $this->product_cart_model->where($where)->limit($Page->firstRow . "," . $Page->listRows)->select();
				$show = $Page->show();
			}
			else {
				if ($_GET["handled"]) {
					$where["handled"] = intval($_GET["handled"]);
				}

				if ($_GET["code"]) {
					$where["code"] = I("get.code");
				}

				$count = $this->product_cart_model->where($where)->count();
				$Page = new \Think\Page($count, 20);
				$show = $Page->show();
				$orders = $this->product_cart_model->where($where)->order("time DESC")->limit($Page->firstRow . "," . $Page->listRows)->select();
			}

			$unHandledCount = $this->product_cart_model->where(array("token" => $this->token, "handled" => 0, "groupon" => 1))->count();
			$this->assign("unhandledCount", $unHandledCount);
			$this->assign("orders", $orders);
			$this->assign("page", $show);
			$this->assign("tabid", 1);
			$this->display();
		}
	}

	public function config()
	{
		$infotype = "Groupon";
		$this->reply_info_model = M("Reply_info");
		$thisInfo = $this->reply_info_model->where(array("infotype" => $infotype, "token" => $this->token))->find();
		S("grouponConfig" . $this->token, $thisInfo);
		if ($thisInfo && ($thisInfo["token"] != $this->token)) {
			exit();
		}

		if (IS_POST) {
			$row["title"] = I("post.title");
			$row["info"] = I("post.info");
			$row["picurl"] = I("post.picurl");
			$row["apiurl"] = I("post.apiurl");
			$row["token"] = I("post.token");
			$row["infotype"] = I("post.infotype");
			$row["config"] = serialize(array("tplid" => intval($_POST["tplid"])));

			if ($thisInfo) {
				$where = array("infotype" => $thisInfo["infotype"], "token" => $this->token);
				$this->reply_info_model->where($where)->save($row);
				$keyword_model = M("Keyword");
				$this->success("修改成功", U("Groupon/config", $where));
			}
			else {
				$where = array("infotype" => $thisInfo["infotype"], "token" => $this->token);
				$this->reply_info_model->add($row);
				$this->success("设置成功", U("Groupon/config", $where));
			}
		}
		else {
			$config = unserialize($thisInfo["config"]);
			$this->assign("config", $config);
			$this->assign("tplid", $config["tplid"]);
			$this->assign("set", $thisInfo);
			$this->assign("tabid", 3);
			$this->display();
		}
	}

	public function sn()
	{
		$model = M("ProductSn");

		if (IS_POST) {
			if ($_POST["token"] != $this->token) {
				exit();
			}

			foreach ($_POST["id"] as $id ) {
				$model->where(array("token" => $this->_session("token"), "id" => (int) $id))->delete();
			}

			$this->success("操作成功", U("Groupon/sn", array("token" => session("token"), "id" => (int) $_GET["id"])));
		}
		else {
			$id = intval(I("get.id"));
			$data = M("Product")->where(array("token" => $this->token, "id" => $id))->find();

			if ($id) {
				$this->error("ID 不能为空");
			}

			$this->assign("data", $data);
			$recordWhere["token"] = $this->token;
			$recordWhere["pid"] = $id;
			$recordWhere["sendstutas"] = ((int) $_GET["status"] ? 0 : 1);
			$count = $model->where(array("token" => $this->token, "pid" => $id))->count();
			$count2 = $model->where($recordWhere)->count();
			$page = new \Think\Page($count2, 25);
			$this->assign("page", $page->show());
			$models = $model->where($recordWhere)->order("sendtime DESC, id ASC")->limit($page->firstRow . "," . $page->listRows)->select();
			$recordWhere["sendstutas"] = 1;
			$saleCount = $model->where($recordWhere)->count();
			$this->assign("count", $count);
			$this->assign("saleCount", $saleCount);
			$this->assign("models", $models);
			$this->display();
		}
	}

	public function snDelete()
	{
		$db = M("ProductSn");
		$rt = $db->where(array("id" => intval($_GET["id"])))->find();

		if ($this->token != $rt["token"]) {
			exit("no permission");
		}

		$db->where(array("id" => intval($_GET["id"])))->delete();
		$this->success("操作成功");
	}

	public function uploadSNExcel()
	{
		$this->_localUploadSNExcel(M("ProductSn"));
	}

	private function _localUploadSNExcel($db, $pid, $fields)
	{
		if ($db) {
			$db = M("Lottery_record");
		}

		if ($pid) {
			$pid = "lid";
		}

		$upyun = A("User/Upyun");
		$return = $upyun->localUpload(array("xls"));

		if ($return["error"]) {
			$this->error($return["msg"]);
		}
		else {
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding("UTF-8");
			$data->read(str_replace("http://" . $_SERVER["HTTP_HOST"], $_SERVER["DOCUMENT_ROOT"], $return["msg"]));
			chmod(str_replace("http://" . $_SERVER["HTTP_HOST"], $_SERVER["DOCUMENT_ROOT"], $return["msg"]), 511);
			$sheet = $data->sheets[0];
			$rows = $sheet["cells"];

			if ($rows) {
				$i = 0;

				foreach ($rows as $r ) {
					if ($i != 0) {
						$where = array("token" => $this->token, $pid => intval($_POST[$pid]), "sn" => trim($r[$fields["sn"]]));
						$check = $db->where($where)->find();

						if (!$check) {
							foreach ($fields as $key => $field ) {
								if (("sn" == $key) || $r[$field]) {
									continue;
								}

								$where[$key] = $r[$field];
							}

							$db->add($where);
						}
					}

					$i++;
				}
			}

			$this->success("操作完成", U("Groupon/sn", array("token" => session("token"), "id" => (int) $_POST["pid"])));
		}
	}
}
