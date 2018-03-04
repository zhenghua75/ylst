<?php
namespace User\Controller;
use Common\Controller\UserController;
class TmplsController extends UserController
{
	public function index()
	{
		$db = D("Wxuser");
		$type = I("get.type", "intval");
		$where["token"] = session("token");
		$where["uid"] = session("uid");
		$info = $db->where($where)->find();
		//include "./PigCms/Lib/ORG/index.Tpl.php";
		$tpl = C('ClassifyTpls');
		foreach ($tpl as $k => $v ) {
			$sort[$k] = $v["sort"];
			$tpltypeid[$k] = $v["tpltypeid"];
		}

		array_multisort($sort, SORT_DESC, $tpltypeid, SORT_DESC, $tpl);
		$this->assign("info", $info);
		$this->assign("tpl", $tpl);
		$whe["token"] = session("token");
		$whe["fid"] = intval($_GET["fid"]);

		if ($_GET["cid"]) {
			$whe["fid"] = (int) $_GET["cid"];
		}

		$Classify = D("Classify");
		$count = $Classify->where($whe)->count();
		$page = new \Think\Page($count, 10);
		$classinfo = $Classify->where($whe)->order("sorts desc")->limit($page->firstRow . "," . $page->listRows)->select();
		$this->assign("page", $page->show());
		$this->assign("classinfo", $classinfo);
		$this->assign("count", $count);
		$this->assign("type", $type);
		$this->display();
	}

	public function QRcode()
	{
		include "./Extend/phpqrcode.php";
		$viewUrl = C("site_url") . U("Wap/Index/index", array("token" => $this->token));
		$url = urldecode($viewUrl);
		\QRcode::png($url, false, 0, 8);
	}

	public function add()
	{
		$gets = I("get.style");
		$db = M("Wxuser");
		include "./PigCms/Lib/ORG/index.Tpl.php";

		foreach ($tpl as $k => $v ) {
			if ($gets == $v["tpltypeid"]) {
				$data["tpltypeid"] = $v["tpltypeid"];
				$data["tpltypename"] = $v["tpltypename"];
			}
		}

		$where["token"] = session("token");
		S("homeinfo_" . $where["token"], NULL);
		S("wxuser_" . $where["token"], NULL);
		$data["dynamicTmpls"] = 0;
		$db->where($where)->save($data);
		M("Home")->where(array("token" => session("token")))->save(array("advancetpl" => 0));

		if ($_GET["noajax"]) {
			$this->success("成功！", "/index.php?g=User&m=Tmpls&a=index&token=" . $this->token);
		}
	}

	public function lists()
	{
		$gets = I("get.style");
		$db = M("Wxuser");

		switch ($gets) {
		case 4:
			$data["tpllistid"] = 4;
			$data["tpllistname"] = "ktv_list";
			break;

		case 1:
			$data["tpllistid"] = 1;
			$data["tpllistname"] = "yl_list";
			break;
		}

		$where["token"] = session("token");
		$db->where($where)->save($data);
	}

	public function content()
	{
		$gets = I("get.style");
		$db = M("Wxuser");

		switch ($gets) {
		case 1:
			$data["tplcontentid"] = 1;
			$data["tplcontentname"] = "yl_content";
			break;

		case 3:
			$data["tplcontentid"] = 3;
			$data["tplcontentname"] = "ktv_content";
			break;
		}

		$where["token"] = session("token");
		$db->where($where)->save($data);
	}

	public function background()
	{
		$data["color_id"] = I("get.style");
		$db = M("Wxuser");
		$where["token"] = session("token");
		S("homeinfo_" . $where["token"], NULL);
		S("wxuser_" . $where["token"], NULL);
		$db->where($where)->save($data);
	}

	public function insert()
	{
	}

	public function upsave()
	{
	}
}
