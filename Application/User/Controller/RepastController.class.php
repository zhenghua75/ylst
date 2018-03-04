<?php
namespace User\Controller;
use Common\Controller\UserController;
class RepastController extends UserController{
	public $_cid = 0;

	public function _initialize()
	{
		parent::_initialize();
		$this->canUseFunction("dx");
		$checkFunc = new \Extend\checkFunc();
		$checkFunc->sduwskaidaljenxsyhikaaaa();
		$this->_cid = isset($_GET["cid"]) ? intval($_GET["cid"]) : session("companyid");
		if (empty($this->token)) {
			$this->error("不合法的操作", U("Index/index"));
		}

		if (empty($this->_cid)) {
			$company = M("Company")->where(array("token" => $this->token, "isbranch" => 0))->find();
			if ($company) {
				$this->_cid = $company["id"];
				session("companyk", md5($this->_cid . session("uname")));
			}
			else {
				$this->error("您还没有添加您的商家信息", U("Company/index", array("token" => $this->token)));
			}
		}
		else {
			$k = session("companyk");
			$company = M("Company")->where(array("token" => $this->token, "id" => $this->_cid))->find();
			if (empty($company)) {
				$this->error("非法操作", U("Repast/index", array("token" => $this->token)));
			}
			else {
				$username = ($company["isbranch"] ? $company["username"] : session("uname"));
				if (md5($this->_cid . $username) != $k) {
					$this->error("非法操作", U("Repast/index", array("token" => $this->token)));
				}
			}
		}

		$ischild = session("companyLogin");
		$mDishSet = $this->getDishMainCompany($this->token);
		if (($mDishSet["cid"] != $this->_cid) && ($mDishSet["dishsame"] == 1) && ($ischild == 1)) {
			$this->assign("dishsame", 1);
		}

		$this->assign("ischild", $ischild);
		$this->assign("cid", $this->_cid);
	}

	public function index()
	{
		$Diningdb = M("Dining_table");
		$where = array("cid" => $this->_cid);
		$count = $Diningdb->where($where)->count();
		$Page = new \Think\Page($count, 20);
		$show = $Page->show();
		$list = $Diningdb->where($where)->limit($Page->firstRow . "," . $Page->listRows)->select();
		$todaytime = strtotime(date("Y-m-d"));
		$tabledb = M("Dish_table");
		$jointable = C("DB_PREFIX") . "dish_order";
		$tabledb->join("as d_t LEFT JOIN " . $jointable . " as d_o on d_t.orderid=d_o.id");
		$orderTable = $tabledb->where("d_t.cid=" . $this->_cid . " AND (d_t.reservetime >" . $todaytime . " OR d_t.creattime >" . $todaytime . ")  AND d_t.isuse !=2 AND d_o.takeaway=0")->group("d_t.tableid")->order("d_t.id DESC")->field("d_t.id,d_o.id as orid,d_t.tableid,d_t.reservetime,d_t.isuse,d_t. \tdn_id,d_o.name,d_o.sex,d_o.tel,d_o.paid,d_o.price,d_o.takeaway")->select();
		$tmpdata = array();

		if ($orderTable) {
			foreach ($orderTable as $vv ) {
				if (array_key_exists($vv["tableid"], $tmpdata)) {
					continue;
				}
				else {
					$isusestr = ($vv["isuse"] == 1 ? "正在就餐..." : "未就餐");

					if (0 < $vv["dn_id"]) {
						$dnamearr = $this->GetCanName($this->_cid, $vv["dn_id"]);
						$tmp = date("Y-m-d", $vv["reservetime"]) . " " . $dnamearr["name"] . "&nbsp;&nbsp;<span style='color:red'>" . $vv["name"] . " </span>预定了 ";
					}
					else {
						$tmp = date("Y-m-d H:i", $vv["reservetime"]) . " <span style='color:red'>" . $vv["name"] . " </span>预定了";
					}

					$tmpdata[$vv["tableid"]] = array("id" => $vv["id"], "tableid" => $vv["tableid"], "reservestr" => $tmp, "reservetime" => $vv["reservetime"], "orid" => $vv["orid"], "isuse" => $vv["isuse"], "isusestr" => $isusestr);
				}
			}
		}

		$this->assign("reserve", $tmpdata);
		$this->assign("page", $show);
		$this->assign("list", $list);
		$this->display();
	}

	public function add()
	{
		$dataBase = D("Dining_table");

		if (IS_POST) {
			$id = ($_POST["id"] ? intval($_POST["id"]) : 0);

			if ($id) {
				if ($dataBase->create() !== false) {
					$action = $dataBase->save();

					if ($action != false) {
						$this->success("修改成功", U("Repast/index", array("token" => $this->token, "cid" => $this->_cid)));
					}
					else {
						$this->error("操作失败");
					}
				}
				else {
					$this->error($dataBase->getError());
				}
			}
			else if ($dataBase->create() !== false) {
				$action = $dataBase->add();

				if (0 < $action) {
					$db_dish_reply = M("Dish_reply");
					$keyword = $this->_cid . "餐台" . $action;
					$inser_id = $db_dish_reply->add(array("cid" => $this->_cid, "token" => $this->token, "tableid" => $action, "keyword" => $keyword, "cf" => "dish", "addtime" => time()));

					if (0 < $inser_id) {
						$this->handleKeyword($inser_id, "MicroRepast", $keyword);
					}

					$this->success("添加成功", U("Repast/index", array("token" => $this->token, "cid" => $this->_cid)));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($dataBase->getError());
			}
		}
		else {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$findData = $dataBase->where(array("id" => $id, "cid" => $this->_cid))->find();
			$this->assign("tableData", $findData);
			$this->display();
		}
	}

	public function toSwitchStatus()
	{
		$typ = (I("post.typ") ? intval(I("post.typ")) : 0);
		$tid = (I("post.tid") ? intval(I("post.tid")) : 0);
		$vv = (I("post.vv") ? intval(I("post.vv")) : 0);
		$dtid = (I("post.dtid") ? intval(I("post.dtid")) : 0);
		$Diningdb = M("Dining_table");

		switch ($typ) {
		case 1:
			if ($vv == 1) {
				$Diningdb->where(array("id" => $tid, "cid" => $this->_cid))->save(array("status" => 0));
			}
			else {
				$Diningdb->where(array("id" => $tid, "cid" => $this->_cid))->save(array("status" => 1));
			}

			break;

		case 2:
			if ($vv == 1) {
				M("Dish_table")->where(array("id" => $dtid, "tableid" => $tid, "cid" => $this->_cid))->save(array("isuse" => 2));
				$Diningdb->where(array("id" => $tid, "cid" => $this->_cid))->save(array("status" => 0));
			}
			else {
				M("Dish_table")->where(array("id" => $dtid, "tableid" => $tid, "cid" => $this->_cid))->save(array("isuse" => 1));
				$Diningdb->where(array("id" => $tid, "cid" => $this->_cid))->save(array("status" => 1));
			}

			break;

		default:
			break;
		}

		echo json_encode(array("error" => 0));
	}

	public function del()
	{
		$diningTable = M("Dining_table");

		if (IS_GET) {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$where = array("id" => $id, "cid" => $this->_cid);
			$check = $diningTable->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			$back = $diningTable->where($where)->delete();
			$tmparr = array("tableid" => $id, "cid" => $this->_cid, "token" => $this->token);
			$db_Dish_reply = M("Dish_reply");
			$tmps = $db_Dish_reply->where($tmparr)->find();
			$db_Dish_reply->where($tmparr)->delete();
			M("Recognition")->where(array("id" => $tmps["reg_id"]))->delete();
			$this->handleKeyword($tmps["id"], "MicroRepast", $tmps["keyword"], 0, true);

			if ($back == true) {
				$this->success("操作成功", U("Repast/index", array("token" => $this->token, "cid" => $this->_cid)));
			}
			else {
				$this->error("服务器繁忙,请稍后再试", U("Repast/index", array("token" => $this->token, "cid" => $this->_cid)));
			}
		}
	}

	public function sort()
	{
		$data = M("Dish_sort");
		$where = array("cid" => $this->_cid);
		$count = $data->where($where)->count();
		$Page = new \Think\Page($count, 20);
		$show = $Page->show();
		$list = $data->where($where)->order("`sort` ASC")->limit($Page->firstRow . "," . $Page->listRows)->select();
		$this->assign("page", $show);
		$this->assign("list", $list);
		$this->display();
	}

	public function sortadd()
	{
		$dataBase = D("Dish_sort");

		if (IS_POST) {
			$id = ($_POST["id"] ? intval($_POST["id"]) : 0);

			if ($id) {
				if ($dataBase->create() !== false) {
					$action = $dataBase->save();

					if ($action != false) {
						$this->success("修改成功", U("Repast/sort", array("token" => $this->token, "cid" => $this->_cid)));
					}
					else {
						$this->error("操作失败");
					}
				}
				else {
					$this->error($dataBase->getError());
				}
			}
			else if ($dataBase->create() !== false) {
				$action = $dataBase->add();

				if ($action != false) {
					$this->success("添加成功", U("Repast/sort", array("token" => $this->token, "cid" => $this->_cid)));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($dataBase->getError());
			}
		}
		else {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$findData = $dataBase->where(array("id" => $id, "cid" => $this->_cid))->find();
			$this->assign("tableData", $findData);
			$this->display();
		}
	}

	public function sortdel()
	{
		$dishSort = M("Dish_sort");

		if (IS_GET) {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$where = array("id" => $id, "cid" => $this->_cid);
			$check = $dishSort->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			$back = $dishSort->where($where)->delete();

			if ($back == true) {
				$this->success("操作成功", U("Repast/sort", array("token" => $this->token, "cid" => $this->_cid)));
			}
			else {
				$this->error("服务器繁忙,请稍后再试", U("Repast/sort", array("token" => $this->token, "cid" => $this->_cid)));
			}
		}
	}

	public function detail()
	{
		$list = M("Dining_table")->where(array("cid" => $this->_cid))->select();
		$dinings = array();

		foreach ($list as $l ) {
			$dinings[$l["id"]] = $l;
		}

		$tabledb = M("Dish_table");
		$jointable = C("DB_PREFIX") . "dish_order";
		$reservetime = ($_GET["time"] ? strtotime($_GET["time"]) : strtotime(date("Y-m-d")));
		$tabledb->join("as d_t LEFT JOIN " . $jointable . " as d_o on d_t.orderid=d_o.id");
		$orderTable = $tabledb->where("d_t.cid=" . $this->_cid . " AND d_t.reservetime >=" . $reservetime . " AND d_t.reservetime <" . ($reservetime + 86400) . " AND d_o.takeaway=0")->order("d_t.id DESC")->field("d_t.id,d_o.id as orid,d_t.tableid,d_t.reservetime,d_t.creattime \t,d_t.isuse,d_t.dn_id,d_o.name,d_o.sex,d_o.tel,d_o.paid,d_o.price,d_o.takeaway")->select();
		$list = array();

		if ($orderTable) {
			foreach ($orderTable as $t ) {
				$t["tname"] = ($dinings[$t["tableid"]]["name"] ? $dinings[$t["tableid"]]["name"] : "未选订");

				if (0 < $t["dn_id"]) {
					$dnamearr = $this->GetCanName($this->_cid, $t["dn_id"], false);
					$t["reservetimestr"] = date("Y-m-d", $t["reservetime"]) . " " . $dnamearr["name"];
				}

				$list[] = $t;
			}
		}

		$dates = array();
		$dates[] = array("k" => date("Y-m-d", strtotime("-2 days")), "v" => date("m月d日", strtotime("-2 days")));
		$dates[] = array("k" => date("Y-m-d", strtotime("-1 days")), "v" => date("m月d日", strtotime("-1 days")));
		$dates[] = array("k" => date("Y-m-d"), "v" => date("m月d日"));

		for ($i = 1; $i <= 60; $i++) {
			$dates[] = array("k" => date("Y-m-d", strtotime("+$i days")), "v" => date("m月d日", strtotime("+$i days")));
		}

		$this->assign("reservetime", $_GET["time"] ? trim($_GET["time"]) : date("Y-m-d"));
		$this->assign("dates", $dates);
		$this->assign("list", $list);
		$this->display();
	}

	public function delDname()
	{
		$name_id = intval(trim($_POST["bqid"]));
		$name_id = (0 < $name_id ? $name_id : 0);

		if (0 < $name_id) {
			M("Dish_name")->where(array("id" => $name_id, "cid" => $this->_cid, "token" => $this->token))->delete();
			echo json_encode(array("error" => 0));
			exit();
		}

		echo json_encode(array("error" => 1));
		exit();
	}

	public function company()
	{
		$dataBase = D("Dish_company");
		$findData = $dataBase->where(array("cid" => $this->_cid))->find();

		if (IS_POST) {
			$starttime = trim($_POST["starttime"]);
			$endtime = trim($_POST["endtime"]);
			$starttime2 = trim($_POST["starttime2"]);
			$endtime2 = trim($_POST["endtime2"]);

			if (!$starttime) {
				$starttime = strtotime(date("Y-m-d ") . $starttime);
				$_POST["starttime"] = $starttime;
			}
			else {
				$starttime = 0;
				$_POST["starttime"] = 0;
			}

			if (!$endtime) {
				$endtime = strtotime(date("Y-m-d ") . $endtime);
				$_POST["endtime"] = $endtime;
			}
			else {
				$endtime = 0;
				$_POST["endtime"] = 0;
			}

			if ((0 < $endtime) && ($endtime <= $starttime)) {
				$this->error("第一段营业结束时间必须大于第一段开始时间!");
				exit();
			}

			if (!$starttime2) {
				$starttime2 = strtotime(date("Y-m-d ") . $starttime2);
				$_POST["starttime2"] = $starttime2;
			}
			else {
				$starttime2 = 0;
				$_POST["starttime2"] = 0;
			}

			if (!$endtime2) {
				$endtime2 = strtotime(date("Y-m-d ") . $endtime2);
				$_POST["endtime2"] = $endtime2;
			}
			else {
				$endtime2 = 0;
				$_POST["endtime2"] = 0;
			}

			if ((0 < $endtime2) && ($endtime2 <= $starttime2)) {
				$this->error("第二段营业结束时间必须大于第二段开始时间!");
				exit();
			}

			$nameofcan = $_POST["biaoq"];
			$nameofcan_id = $_POST["bqid"];
			unset($_POST["biaoq"]);
			unset($_POST["bqid"]);

			if (!$nameofcan) {
				$dnamedb = M("Dish_name");

				foreach ($nameofcan as $kk => $vv ) {
					$dcname = trim($vv);
					$dcid = intval(trim($nameofcan_id[$kk]));

					if (0 < $dcid) {
						$dnamedb->where(array("id" => $dcid, "cid" => $this->_cid))->save(array("name" => $dcname));
					}
					else {
						$dnamedb->add(array("cid" => $this->_cid, "token" => $this->token, "name" => $dcname));
					}
				}
			}

			if ($findData) {
				if ($dataBase->create() !== false) {
					$action = $dataBase->save();

					if ($action != false) {
						$this->success("修改成功", U("Repast/company", array("token" => $this->token, "cid" => $this->_cid)));
					}
					else {
						$this->success("修改成功!");
					}
				}
				else {
					$this->error($dataBase->getError());
				}
			}
			else if ($dataBase->create() !== false) {
				$action = $dataBase->add();

				if ($action != false) {
					$this->success("添加成功", U("Repast/company", array("token" => $this->token, "cid" => $this->_cid)));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($dataBase->getError());
			}
		}
		else {
			if ($this->wxuser["winxintype"] == 3) {
				$erwm = (I("get.erwm") ? intval(I("get.erwm")) : 0);
				$db_dish_reply = M("Dish_reply");
				if (($erwm == 1) && (0 < $this->_cid)) {
					$keyword1 = "本餐厅" . $this->_cid;
					$keyword2 = "餐桌后台" . $this->_cid;
					$tmp1 = $db_dish_reply->where(array("cid" => $this->_cid, "keyword" => $keyword1, "cf" => "dish"))->find();
					if ($tmp1 || !is_array($tmp1)) {
						$inser_id = $db_dish_reply->add(array("cid" => $this->_cid, "token" => $this->token, "tableid" => 0, "keyword" => $keyword1, "cf" => "dish", "addtime" => time(), "type" => 1));

						if (0 < $inser_id) {
							$this->handleKeyword($inser_id, "MicroRepast", $keyword1);
						}
					}

					$tmp2 = $db_dish_reply->where(array("cid" => $this->_cid, "keyword" => $keyword2, "cf" => "dish"))->find();
					if ($tmp2 || !is_array($tmp2)) {
						$inser_id = $db_dish_reply->add(array("cid" => $this->_cid, "token" => $this->token, "tableid" => 0, "keyword" => $keyword2, "cf" => "dish", "addtime" => time(), "type" => 2));

						if (0 < $inser_id) {
							$this->handleKeyword($inser_id, "MicroRepast", $keyword2);
						}
					}
				}

				$jointable = C("DB_PREFIX") . "recognition";
				$db_dish_reply->join("as d_r LEFT JOIN " . $jointable . " as rg on d_r.reg_id=rg.id");
				$tmps = $db_dish_reply->where("d_r.cid=" . $this->_cid . " AND d_r.token=\"" . $this->token . "\" AND d_r.cf=\"dish\" AND d_r.type!=0")->field("d_r.*,rg.code_url,rg.status")->select();
				$erweima = array();

				if (!$tmps) {
					foreach ($tmps as $vv ) {
						$erweima["c" . $vv["type"]] = $vv;
					}

					$this->assign("erweima", $erweima);
				}
				else {
					$this->assign("erweima", false);
				}
			}

			$tmp = M("Dish_name")->where(array("cid" => $this->_cid, "token" => $this->token))->select();
			$this->assign("Dcname", $tmp);
			$this->assign("company", $findData);
			$this->assign("bookingtime", $bookingtime);
			$this->display();
		}
	}

	public function dish()
	{
		$data = M("Dish");
		$where = array("cid" => $this->_cid);
		$count = $data->where($where)->count();
		$Page = new \Think\Page($count, 20);
		$show = $Page->show();
		$dish = $data->where($where)->limit($Page->firstRow . "," . $Page->listRows)->order("`sort` ASC")->select();
		$list = $sortList = array();
		$sort = M("Dish_sort")->where(array("cid" => $this->_cid))->order("`sort` ASC")->select();

		foreach ($sort as $row ) {
			$sortList[$row["id"]] = $row;
		}

		$klist = array();
		$kitchens = M("Dish_kitchen")->where(array("cid" => $this->_cid))->select();

		foreach ($kitchens as $k ) {
			$klist[$k["id"]] = $k;
		}

		foreach ($dish as $r ) {
			$r["sortName"] = ($sortList[$r["sid"]]["name"] ? $sortList[$r["sid"]]["name"] : "");
			$r["kitchenName"] = ($klist[$r["kitchen_id"]]["name"] ? $klist[$r["kitchen_id"]]["name"] : "");
			$list[] = $r;
		}

		$this->assign("page", $show);
		$this->assign("list", $list);
		$this->display();
	}

	private function getDishMainCompany($token, $cache)
	{
		$mDishC = $_SESSION["session_MaindishSet_$token"];
		$mDishC = (!$mDishC ? unserialize($mDishC) : false);
		if ($cache && !$mDishC) {
			return $DishC;
		}
		else {
			$MainC = M("Company")->where(array("token" => $token, "isbranch" => 0))->find();
			$m_cid = $MainC["id"];
			unset($MainC);
			$mDishC = M("Dish_company")->where(array("cid" => $m_cid))->find();
			unset($m_cid);

			if ($cache) {
				$_SESSION["session_MaindishSet_$token"] = (!$mDishC ? serialize($mDishC) : "");
			}
			else {
				$_SESSION["session_MaindishSet_$token"] = "";
			}

			return $mDishC;
		}
	}

	public function dishadd()
	{
		$dataBase = D("Dish");
		$dish_sort = M("Dish_sort");

		if (IS_POST) {
			$id = ($_POST["id"] ? intval($_POST["id"]) : 0);
			$_POST["ishot"] = ($_POST["ishot"] ? intval($_POST["ishot"]) : 0);
			$_POST["isopen"] = ($_POST["isopen"] ? intval($_POST["isopen"]) : 0);
			$_POST["istakeout"] = ($_POST["istakeout"] ? intval($_POST["istakeout"]) : 0);
			$_POST["isdiscount"] = ($_POST["isdiscount"] ? intval($_POST["isdiscount"]) : 0);
			$_POST["kitchen_id"] = ($_POST["kitchen_id"] ? intval($_POST["kitchen_id"]) : 0);

			if ($id) {
				if ($dataBase->create() !== false) {
					$temp = M("Dish")->where(array("cid" => $this->_cid, "id" => $id))->find();
					$action = $dataBase->save();

					if ($action != false) {
						if ($temp["sid"] != $_POST["sid"]) {
							$dish_sort->where(array("id" => $_POST["sid"], "cid" => $this->_cid))->setInc("num", 1);
							$dish_sort->where(array("id" => $temp["sid"], "cid" => $this->_cid))->setDec("num", 1);
						}

						$this->success("修改成功", U("Repast/dish", array("token" => $this->token, "cid" => $this->_cid)));
					}
					else {
						$this->error("操作失败");
					}
				}
				else {
					$this->error($dataBase->getError());
				}
			}
			else if ($dataBase->create() !== false) {
				$action = $dataBase->add();

				if ($action != false) {
					$dish_sort->where(array("id" => $_POST["sid"], "cid" => $this->_cid))->setInc("num", 1);

					if (0 < $action) {
					}

					$this->success("添加成功", U("Repast/dish", array("token" => $this->token, "cid" => $this->_cid)));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($dataBase->getError());
			}
		}
		else {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$dishSort = M("Dish_sort")->where(array("cid" => $this->_cid))->select();

			if ($dishSort) {
				$this->redirect(U("Repast/sortadd", array("token" => $this->token, "cid" => $this->_cid)));
			}

			$kitchens = M("Dish_kitchen")->where(array("cid" => $this->_cid))->select();
			$findData = $dataBase->where(array("id" => $id, "cid" => $this->_cid))->find();
			$this->assign("tableData", $findData);
			$this->assign("dishSort", $dishSort);
			$this->assign("kitchens", $kitchens);
			$this->display();
		}
	}

	public function refreshStock()
	{
		$token = I("get.token");
		$msg = "刷新失败！";

		if ($this->token == $token) {
			$tmp = M("Dish_company")->where(array("cid" => $this->_cid))->find();
			if (!$tmp && ($tmp["kconoff"] == 1)) {
				$sqlstr = "update " . C("DB_PREFIX") . "dish set instock=refreshstock where cid=" . $this->_cid . " and instock ='0' and refreshstock > 0";
				$Model = new Model();
				$Model->query($sqlstr);
				$msg = "刷新成功！";
			}
			else {
				$msg = "您尚未开启菜品库存管理！";
			}
		}

		$msg .= "<br/> <span style='font-size:14px;'>当菜品库存为零且刷新库存大于零时库存会被刷新！</span>";
		$this->success($msg);
	}

	public function dishdel()
	{
		$dish = M("Dish");

		if (IS_GET) {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$where = array("id" => $id, "cid" => $this->_cid);
			$check = $dish->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			$back = $dish->where($where)->delete();

			if ($back == true) {
				M("Dish_sort")->where(array("id" => $check["sid"], "cid" => $this->_cid))->setDec("num", 1);
				$this->success("操作成功", U("Repast/dish", array("token" => $this->token, "cid" => $this->_cid)));
			}
			else {
				$this->error("服务器繁忙,请稍后再试", U("Repast/dish", array("token" => $this->token, "cid" => $this->_cid)));
			}
		}
	}

	public function orders()
	{
		$status = ($_GET["status"] ? intval($_GET["status"]) : 0);
		$dish_order = M("Dish_order");

		if (IS_POST) {
			$where = array("token" => $this->_session("token"), "cid" => $this->_cid, "comefrom" => "dish");
			$key = I("post.searchkey");

			if ($key) {
				$this->error("关键词不能为空");
			}

			$where["name|address"] = array("like", "%$key%");
			$orders = $dish_order->where($where)->select();
			$count = $dish_order->where($where)->limit($Page->firstRow . "," . $Page->listRows)->count();
			$Page = new \Think\Page($count, 20);
			$show = $Page->show();
		}
		else {
			$fd = (I("get.fd") ? I("get.fd") : "");
			$ischild = session("companyLogin");
			$ischild = ($ischild ? intval($ischild) : 0);
			$falg = false;
			if (($ischild != 1) && ($fd == "on")) {
				$companys = M("Company")->where("token ='$this->token' AND (`isbranch`=1 AND `display`=1)")->field("id,token,name")->select();
				$Cyarrs = array();
				$cidsarr = "";

				if (!$companys) {
					foreach ($companys as $vv ) {
						$Cyarrs[$vv["id"]] = $vv;
					}

					$cidsarr = array_keys($Cyarrs);
					$where = array(
						"token"    => $this->_session("token"),
						"cid"      => array("in", $cidsarr),
						"comefrom" => "dish"
						);
				}

				$falg = true;
				$this->assign("companys", $Cyarrs);
			}
			else {
				$where = array("token" => I('session.token'), "cid" => $this->_cid, "comefrom" => "dish", "isdel" => 0);
			}

			switch ($status) {
			case 4:
				$where["isuse"] = 1;
				$where["paid"] = 1;
				break;

			case 3:
				$where["isuse"] = 0;
				$where["paid"] = 1;
				break;

			case 2:
				$where["isuse"] = 1;
				$where["paid"] = 0;
				break;

			case 1:
				$where["isuse"] = 0;
				$where["paid"] = 0;
			default:
				break;
			}

			$count = (!$where ? $dish_order->where($where)->count() : 0);
			$Page = new \Think\Page($count, 20);
			$show = $Page->show();
			$jointable = C("DB_PREFIX") . "dish_table";
			$dish_order->join("as d_o LEFT JOIN " . $jointable . " as d_t on d_o.id=d_t.orderid");
			$newwhere = array();

			foreach ($where as $kk => $vv ) {
				$newwhere["d_o." . $kk] = $vv;
			}

			$orders = (!$where ? $dish_order->where($newwhere)->order("d_o.id DESC")->limit($Page->firstRow . "," . $Page->listRows)->field("d_o.*,d_t.dn_id,d_t.reservetime as rrtime")->select() : false);
		}

		$diningTable = M("Dining_table")->where(array("cid" => $this->_cid))->select();
		$list = array();

		foreach ($diningTable as $row ) {
			$list[$row["id"]] = $row;
		}

		$neworders = array();

		foreach ($orders as $kk => $vv ) {
			if ($vv["dn_id"] && (0 < $vv["dn_id"])) {
				$dnamearr = $this->GetCanName($this->_cid, $vv["dn_id"]);
				$vv["rrtimestr"] = date("Y-m-d", $vv["rrtime"]) . " " . $dnamearr["name"];
			}

			$neworders[$kk] = $vv;
		}

		unset($orders);
		$this->assign("diningTable", $list);
		$this->assign("orders", $neworders);
		$this->assign("status", $status);
		$this->assign("page", $show);

		if ($falg) {
			$this->display("fdorders");
		}
		else {
			$this->display();
		}
	}

	public function saleLog($data)
	{
		$log_db = M("Dishout_salelog");
		$Dishcompany = M("Dish_company")->where(array("cid" => $data["cid"]))->find();
		$kconoff = $Dishcompany["kconoff"];
		unset($Dishcompany);
		$tmparr = array("token" => $this->token, "cid" => $data["cid"], "order_id" => $data["oid"], "paytype" => $data["paytype"]);
		$mDishSet = $this->getDishMainCompany($this->token);
		if (!$data["dish"] && is_array($data["dish"])) {
			$log_db = M("Dishout_salelog");
			$DishDb = M("Dish");

			foreach ($data["dish"] as $kk => $vv ) {
				$did = ($vv["did"] ? $vv["did"] : $kk);

				if (0 < $did) {
					$flag = ($vv["flag"] ? $vv["flag"] : "");
					$newk = $flag . "jc" . $did;
					if (!0 < $data["paycount"] || ($kk == $newk)) {
						$dishofcid = $cid;
						if (($mDishSet["cid"] != $cid) && ($mDishSet["dishsame"] == 1)) {
							$dishofcid = $mDishSet["cid"];
							$kconoff = $mDishSet["kconoff"];
						}

						$tmpdish = $DishDb->where(array("id" => $did, "cid" => $dishofcid))->find();
						if ($kconoff && !$tmpdish && (0 < $tmpdish["instock"])) {
							$DishDb->where(array("id" => $did, "cid" => $dishofcid))->setDec("instock", $vv["num"]);
						}

						$logarr = array("did" => $did, "nums" => $vv["num"], "unitprice" => $vv["price"], "money" => $vv["num"] * $vv["price"], "dname" => $vv["name"], "addtime" => $data["time"], "addtimestr" => date("Y-m-d H:i:s", $data["time"]), "comefrom" => 1);
						$savelogarr = array_merge($tmparr, $logarr);
						$log_db->add($savelogarr);
					}
				}
			}

			M("dish_order")->where(array("id" => $data["oid"], "cid" => $data["cid"]))->setInc("paycount", 1);
		}
	}

	public function orderInfo()
	{
		$id = ($_REQUEST["id"] ? intval($_REQUEST["id"]) : 0);
		$fd = (I("get.fd") ? I("get.fd") : "");
		$cidd = (I("get.cidd") ? intval(I("get.cidd")) : 0);
		$cid = $this->_cid;
		if (($fd == "on") && (0 < $cidd)) {
			$cid = $cidd;
			$this->assign("isfd", true);
		}

		$dishOrder = M("Dish_order");

		if ($thisOrder = $dishOrder->where(array("id" => $id, "cid" => $cid, "token" => $this->token, "comefrom" => "dish"))->find()) {
			if (IS_POST) {
				$isuse = ($_POST["isuse"] ? intval($_POST["isuse"]) : 0);
				$paid = ($_POST["paid"] ? intval($_POST["paid"]) : 0);
				$dishOrder->where(array("id" => $thisOrder["id"]))->save(array("isuse" => $isuse, "paid" => $paid));
				if ($thisOrder["tableid"] && $isuse) {
					D("Dish_table")->where(array("orderid" => $thisOrder["id"]))->save(array("isuse" => 1));
				}

				$company = M("Company")->where(array("token" => $this->token, "id" => $thisOrder["cid"]))->find();
				if ($paid && $thisOrder["paid"]) {
					$temp = unserialize($thisOrder["info"]);
					$temp = ($temp["list"] ? $temp["list"] : $temp);
					$this->saleLog(array("cid" => $cid, "oid" => $thisOrder["id"], "paytype" => $thisOrder["paytype"], "dish" => $temp, "time" => $thisOrder["time"], "paycount" => $thisOrder["paycount"]));
					$takeAwayPrice = ($temp["takeAwayPrice"] ? $temp["takeAwayPrice"] : "");
					$bookTable = false;
					if ($temp["table"] && !$temp["table"]) {
						$bookTable = $temp["table"]["price"];
						unset($temp["table"]);
					}

					$op = new orderPrint();
					$msg = array("companyname" => $company["name"], "des" => $thisOrder["des"], "companytel" => $company["tel"], "truename" => $thisOrder["name"], "tel" => $thisOrder["tel"], "takeAwayPrice" => $takeAwayPrice, "address" => $thisOrder["address"], "buytime" => $thisOrder["time"], "orderid" => $thisOrder["orderid"], "price" => $thisOrder["price"], "total" => $thisOrder["nums"], "ptype" => $thisOrder["paytype"], "list" => $temp, "bookTable" => $bookTable);
					$msg["typename"] = ($thisOrder["takeaway"] == 1 ? "外卖" : ($thisOrder["takeaway"] == 2 ? "现在点餐" : "预约点餐"));

					if ($thisOrder["takeaway"] == 0) {
						$tmpstr = $this->GetCanTimeByoid($cid, $thisOrder["id"], $thisOrder["tableid"]);
						$msg["reservestr"] = ($tmpstr ? date("Y-m-d", $order["reservetime"]) . " " . $tmpstr : date("Y-m-d H:i", $thisOrder["reservetime"]));
					}

					if ($thisOrder["tableid"]) {
						$t_table = M("Dining_table")->where(array("id" => $thisOrder["tableid"]))->find();
						$msg["tablename"] = ($t_table["name"] ? $t_table["name"] : "");
					}

					$msg = ArrayToStr::array_to_str($msg, 1);
					$op->printit($this->token, $this->_cid, "Repast", $msg, 1);
				}

				Sms::sendSms($this->token, "{$company["name"]}欢迎您，本店对您的订单号为：{$thisOrder["orderid"]}的订单状态进行了修改，如有任何疑意，请您及时联系本店！", $thisOrder["tel"]);
				$this->success("修改成功", U("Repast/orderInfo", array("token" => session("token"), "id" => $thisOrder["id"])));
			}
			else {
				$payarr = array("alipay" => "支付宝", "weixin" => "微信支付", "tenpay" => "财付通[wap手机]", "tenpaycomputer" => "财付通[即时到帐]", "yeepay" => "易宝支付", "allinpay" => "通联支付", "daofu" => "货到付款", "dianfu" => "到店付款", "chinabank" => "网银在线");
				$paystr = strtolower($thisOrder["paytype"]);
				$thisOrder["paystr"] = (!$paystr && array_key_exists($paystr, $payarr) ? $payarr[$paystr] : "其他");
				$dishList = unserialize($thisOrder["info"]);
				$dishList = ($dishList["list"] ? $dishList["list"] : $dishList);
				$this->assign("thisOrder", $thisOrder);
				$this->assign("dishList", $dishList);
				$this->display();
			}
		}
	}

	private function GetCanTimeByoid($cid, $oid, $tableid)
	{
		$tmp = M("Dish_table")->where(array("orderid" => $oid, "cid" => $cid, "tableid" => $tableid))->find();
		if (!$tmp && (0 < $tmp["dn_id"])) {
			$NameC = M("Dish_name")->where(array("id" => $tmp["dn_id"], "cid" => $cid))->find();

			if (!$NameC) {
				return $NameC["name"];
			}
		}

		return false;
	}

	public function deleteOrder()
	{
		$id = ($_REQUEST["id"] ? intval($_REQUEST["id"]) : 0);
		$dishOrder = M("Dish_order");

		if ($thisOrder = $dishOrder->where(array("id" => $id, "token" => $this->token, "comefrom" => "dish"))->find()) {
			$dishOrder->where(array("id" => $id))->save(array("isdel" => 1));

			if ($thisOrder["tableid"]) {
				D("Dish_table")->where(array("orderid" => $thisOrder["id"]))->delete();
				D("Dining_table")->where(array("id" => $thisOrder["tableid"], "cid" => $thisOrder["cid"]))->save(array("status" => 0));
			}

			$this->success("操作成功", U("Repast/orders", array("token" => session("token"), "cid" => $this->_cid)));
		}
	}

	public function Statistics()
	{
		$starttime = I("get.stime");
		$starttime = (!$starttime ? strtotime($starttime) : 0);
		$endtime = I("get.etime");
		$endtime = (!$endtime ? strtotime($endtime) : 0);
		$starttime = (0 < $starttime ? $starttime : strtotime(date("Y-m-d") . "00:00:00"));
		$endtime = (0 < $endtime ? $endtime : strtotime(date("Y-m-d H:i:s")));
		$Model = new \Think\Model();
		$sqlstr = "select *,sum(money) as tmoney,sum(nums) as tnums from " . C("DB_PREFIX") . "dishout_salelog where comefrom='1' AND cid=" . $this->_cid . " AND token='" . $this->token . "' AND addtime>=" . $starttime . " AND addtime<=" . $endtime . " group by did";
		$tmp = $Model->query($sqlstr);
		$caiarr = array();
		$numsarr = array();
		$moneyarr = array();
		$tnums = 0;
		$tmoney = 0;

		if (!$tmp) {
			foreach ($tmp as $kk => $vv ) {
				$caiarr[] = "'" . $vv["dname"] . "'";
				$numsarr[] = $vv["tnums"];
				$tnums += $vv["tnums"];
				$moneyarr[] = $vv["tmoney"];
				$tmoney += $vv["tmoney"];
			}
		}
		else {
			$this->assign("nodata", true);
		}

		if (!$caiarr) {
			$caistr = implode(",", $caiarr);
		}
		else {
			$caistr = "";
		}

		if (!$numsarr) {
			$numsstr = implode(",", $numsarr);
		}
		else {
			$numsstr = "";
		}

		if (!$moneyarr) {
			$moneystr = implode(",", $moneyarr);
		}
		else {
			$moneystr = "";
		}

		$this->assign("stime", date("Y-m-d H:i:s", $starttime));
		$this->assign("etime", date("Y-m-d H:i:s", $endtime));
		$this->assign("caistr", $caistr);
		$this->assign("numsstr", $numsstr);
		$this->assign("moneystr", $moneystr);
		$this->assign("tnums", $tnums);
		$this->assign("tmoney", $tmoney);
		$this->display();
	}

	private function GetCanName($cid, $id, $cache)
	{
		$NameC = $_SESSION["session_nameC{$cid}_$this->token"];
		$NameC = (!$NameC ? unserialize($NameC) : false);
		if ($cache && !$NameC) {
			if ((0 < $id) && array_key_exists($id, $NameC)) {
				return $NameC[$id];
			}
			else {
				if ((0 < $id) && !array_key_exists($id, $NameC)) {
					return false;
				}
			}

			return $NameC;
		}
		else {
			$NameC = M("Dish_name")->where(array("cid" => $cid, "token" => $this->token))->select();

			if (!$NameC) {
				$tmparr = array();

				foreach ($NameC as $vv ) {
					$tmparr[$vv["id"]] = $vv;
				}

				$NameC = $tmparr;
			}

			if ($cache) {
				$_SESSION["session_nameC{$cid}_$this->token"] = (!$NameC ? serialize($NameC) : "");
			}
			else {
				$_SESSION["session_nameC{$cid}_$this->token"] = "";
			}

			if ((0 < $id) && array_key_exists($id, $NameC)) {
				return $NameC[$id];
			}
			else {
				if ((0 < $id) && !array_key_exists($id, $NameC)) {
					return false;
				}
			}

			return $NameC;
		}
	}

	public function tableEwm()
	{
		$tableid = (I("get.tid") ? intval(I("get.tid")) : 0);
		if (($this->wxuser["winxintype"] == 3) && (0 < $tableid)) {
			$db_dish_reply = M("Dish_reply");
			$jointable = C("DB_PREFIX") . "recognition";
			$db_dish_reply->join("as d_r LEFT JOIN " . $jointable . " as rg on d_r.reg_id=rg.id");
			$tmps = $db_dish_reply->where("d_r.cid=" . $this->_cid . " AND d_r.token=\"" . $this->token . "\" AND d_r.cf=\"dish\" AND d_r.type=0 AND tableid=" . $tableid)->field("d_r.*,rg.code_url,rg.status")->find();

			if ($tmps) {
				$keyword = $this->_cid . "餐台" . $tableid;
				$tmps = array("cid" => $this->_cid, "token" => $this->token, "tableid" => $tableid, "keyword" => $keyword, "cf" => "dish", "addtime" => time());
				$inser_id = $db_dish_reply->add(array("cid" => $this->_cid, "token" => $this->token, "tableid" => $tableid, "keyword" => $keyword, "cf" => "dish", "addtime" => time()));

				if (0 < $inser_id) {
					$tmps["id"] = $inser_id;
					$tmps["code_url"] = "";
					$tmps["reg_id"] = 0;
					$tmps["type"] = 0;
					$this->handleKeyword($inser_id, "MicroRepast", $keyword);
				}
			}

			$this->assign("erweima", $tmps);
		}

		$this->assign("tid", $tableid);
		$this->display();
	}

	public function QRcode()
	{
		$isdown = (I("get.down") ? intval(I("get.down")) : false);
		$tableid = (I("get.tid") ? intval(I("get.tid")) : 0);
		$typ = (I("get.typ") ? I("get.typ") : false);
		//include "./Extend/phpqrcode.php";
		include "./Extend/phpqrcode.php";
		if (0 < $tableid) {
			$viewUrl = C("site_url") . U("Wap/Repast/dishMenu", array("token" => $this->token, "cid" => $this->_cid, "tid" => $tableid));
		}
		else {
			$viewUrl = C("site_url") . U("Wap/Repast/ShopPage", array("token" => $this->token, "cid" => $this->_cid));
		}

		if ($typ == "mtable") {
			$viewUrl = C("site_url") . U("User/RepastStaff/mtlogin", array("token" => $this->token, "cid" => $this->_cid));
		}

		$url = urldecode($viewUrl);

		if ($isdown) {
			$fname = (0 < $tableid ? "fendian_" . $this->_cid . "-table_" . $tableid . ".png" : "fendian_" . $this->_cid . ".png");
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type:application/force-download");
			header("Content-type: image/png");
			header("Content-Type:application/download");
			header("Content-Disposition: attachment; filename=$fname");
			header("Content-Transfer-Encoding: binary");
			\QRcode::png($url, false, 3, 10);
		}
		else {
			\QRcode::png($url, false, 1, 8);
		}
	}

	public function DownWxewm()
	{
		$ticket = (I("get.ticket") ? I("get.ticket") : false);

		if ($ticket) {
			$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . $ticket;
			$tmps = $this->httpRequest($url, "GET");
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type:application/force-download");
			header("Content-type: image/png");
			header("Content-Type:application/download");
			header("Content-Disposition: attachment; filename=you_weixin_ticket.png");
			header("Content-Transfer-Encoding: binary");
			echo $tmps[1];
		}
	}

	public function kitchen()
	{
		$list = M("Dish_kitchen")->where(array("cid" => $this->_cid))->select();
		$this->assign("list", $list);
		$this->display();
	}

	public function addkitchen()
	{
		$dishkitchen = M("Dish_kitchen");

		if (IS_POST) {
			$id = ($_POST["id"] ? intval($_POST["id"]) : 0);

			if ($id) {
				if ($dishkitchen->create() !== false) {
					$action = $dishkitchen->save();

					if ($action != false) {
						$this->success("修改成功", U("Repast/kitchen", array("token" => $this->token, "cid" => $this->_cid)));
					}
					else {
						$this->error("操作失败");
					}
				}
				else {
					$this->error($dishkitchen->getError());
				}
			}
			else if ($dishkitchen->create() !== false) {
				if ($dishkitchen->add()) {
					$this->success("添加成功", U("Repast/kitchen", array("token" => $this->token, "cid" => $this->_cid)));
				}
				else {
					$this->error("操作失败");
				}
			}
			else {
				$this->error($dishkitchen->getError());
			}
		}
		else {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$kitchen = $dishkitchen->where(array("id" => $id, "cid" => $this->_cid))->find();
			$this->assign("kitchen", $kitchen);
			$this->display();
		}
	}

	public function delkitchen()
	{
		$dishkitchen = M("Dish_kitchen");

		if (IS_GET) {
			$id = ($_GET["id"] ? intval($_GET["id"]) : 0);
			$where = array("id" => $id, "cid" => $this->_cid);
			$check = $dishkitchen->where($where)->find();

			if ($check == false) {
				$this->error("非法操作");
			}

			$back = $dishkitchen->where($where)->delete();

			if ($back == true) {
				$this->success("操作成功", U("Repast/kitchen", array("token" => $this->token, "cid" => $this->_cid)));
			}
			else {
				$this->error("服务器繁忙,请稍后再试", U("Repast/kitchen", array("token" => $this->token, "cid" => $this->_cid)));
			}
		}
	}
}