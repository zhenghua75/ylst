<?php
namespace User\Controller;
use Common\Controller\UserController;
class MicroBrokerController extends UserController {
    public function _initialize() {
        parent::_initialize();
        $this->canUseFunction('MicroBroker');
        $checkZend=new checkZend();
        $checkFunc=new \Extend\checkFunc();
        $checkFunc->cfdwdgfds3skgfds3szsd3idsj();
    }

    public function index() {
        $where = array('token' => session('token'), 'isdel' => 0);
        $list = M('Broker')->where($where)->order('id DESC')->select();
        $count = M('Broker')->where($where)->count();
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->display();
    }

    //添加一个
    public function mbkadd() {
        if (IS_POST) {
            $_POST['token'] = session('token');
            $_POST['title'] = I("post.title");
            if (empty($_POST['title']))
                $this->error('图文消息标题');
            $_POST['keyword'] = I('post.keyword');
            $_POST['picurl'] = I("post.picurl");
            $_POST['imgreply'] = I("post.imgreply");
            $_POST['bgimg'] = I("post.bgimg");
            $_POST['invitecode'] = I("post.invitecode");
            $_POST['ruledesc'] = I("post.ruledesc");
            $_POST['registration'] = I("post.registration");
			$_POST['rinfo'] = I("post.rinfo");
            $_POST['statdate'] = strtotime(I('post.statdate'));
            $_POST['enddate'] = strtotime(I('post.enddate'));
            $_POST['addtime'] = time();
            $_POST['uptime'] = time();
            if ($_POST['enddate'] < $_POST['statdate']) {
                $this->error('结束时间不能小于开始时间!');
                exit;
            }
            $adds = !empty($_POST['add']['id']) && is_array($_POST['add']['id']) ? $_POST['add'] : $_REQUEST['add'];
            if (empty($adds) || empty($adds['xmname'][0]) || empty($adds['xmimg'][0]) || empty($adds['xmnum'][0])) {
                $this->error('产品项目选项你还没有填写完整');
                exit;
            }
            foreach ($adds as $ke => $value) {
                foreach ($value as $k => $v) {
                    $item_add[$k][$ke] = trim($v);
                }
            }
            unset($_POST['add']);
            $data = M('Broker');
            $t_item = M('Broker_item');

            if ($data->create() != false) {
                if ($id = $data->add()) {
                    foreach ($item_add as $k => $v) {
                        if (!empty($v['xmname']) && !empty($v['xmimg'])) {
                            $data2['bid'] = $id;
                            $data2['xmname'] = $v['xmname'];
                            $data2['xmtype'] = empty($v['xmtype']) ? 1 : $v['xmtype'];
                            $data2['xmnum'] = empty($v['xmnum']) ? 0 : $v['xmnum'];
                            $data2['xmimg'] = $v['xmimg']; //empty($v['xmimg'])
                            $data2['tourl'] = $v['tourl'];
                            $t_item->add($data2);
                        }
                    }

                    $this->handleKeyword($id, 'MicroBroker', I('post.keyword'));
                    $this->success('添加成功', U('MicroBroker/index', array('token' => session('token'))));
                } else {
                    $this->error('服务器繁忙,请稍候再试');
                }
            } else {
                $this->error($data->getError());
            }
        } else {
            $mbkarr = array('imgreply' => $this->staticPath . '/tpl/static/microbroker/images/imgreply-default.jpg', 'picurl' => $this->staticPath . '/tpl/static/microbroker/images/bg-loader-default.jpg', 'bgimg' => $this->staticPath . '/tpl/static/microbroker/images/register_bg.png');
            $this->assign('mbk', $mbkarr);
            $invitecode = $this->get_rand(7);
            $this->assign('invitecode', $invitecode);
            $this->assign('mbkid', 0);
            $this->display();
        }
    }

    //编辑
    public function mbkedit() {
        if (IS_POST) {
            $token = session('token');
            $_POST['title'] = I("post.title");
            if (empty($_POST['title']))
                $this->error('图文消息标题');
            $_POST['keyword'] = I('post.keyword');
            $_POST['picurl'] = I("post.picurl");
            $_POST['imgreply'] = I("post.imgreply");
            $_POST['bgimg'] = I("post.bgimg");
            $_POST['invitecode'] = I("post.invitecode");
            $_POST['ruledesc'] = I("post.ruledesc");
            $_POST['registration'] = I("post.registration");
			$_POST['rinfo'] = I("post.rinfo");
            $_POST['statdate'] = strtotime(I('post.statdate'));
            $_POST['enddate'] = strtotime(I('post.enddate'));
            $_POST['uptime'] = time();
            $bid = I("post.id",0, 'intval');
            if ($_POST['enddate'] < $_POST['statdate']) {
                $this->error('结束时间不能小于开始时间!');
                exit;
            }
            $data = D('Broker');
            $check = NULL;
            $where = array('id' => $bid, 'token' => $token);
            if ($bid > 0) {
                $check = $data->where($where)->find();
            } else {
                exit($this->error('非法操作!'));
            }
            if ($check == NULL)
                exit($this->error('非法操作'));

            $adds = !empty($_POST['add']['id']) && is_array($_POST['add']['id']) ? $_POST['add'] : $_REQUEST['add'];
            if (empty($adds) || empty($adds['xmname'][0]) || empty($adds['xmimg'][0]) || empty($adds['xmnum'][0])) {
                $this->error('投票选项你还没有填写完整');
                exit;
            }
            unset($_POST['id'], $_POST['add']);
            $item_add = array();
            foreach ($adds as $ke => $value) {
                foreach ($value as $kk => $vv) {
                    $item_add[$kk][$ke] = trim($vv);
                }
            }
            $t_item = M('Broker_item');
            foreach ($item_add as $k => $v) {
                if (!empty($v['xmname']) && !empty($v['xmimg'])) {
                    $itemid = intval(trim($v['id']));
                    unset($v['id']);
                    if ($itemid > 0) {
                        $data2['xmname'] = trim($v['xmname']);
                        $data2['xmtype'] = empty($v['xmtype']) ? 1 : $v['xmtype'];
                        $data2['xmnum'] = empty($v['xmnum']) ? 0 : $v['xmnum'];
                        $data2['xmimg'] = $v['xmimg'];
                        $data2['tourl'] = $v['tourl'];
                        $t_item->where(array('id' => $itemid, 'bid' => $bid))->save($data2);
                    } else {
                        $addarr['bid'] = $bid;
                        $addarr['xmname'] = trim($v['xmname']);
                        $addarr['xmtype'] = empty($v['xmtype']) ? 1 : $v['xmtype'];
                        $addarr['xmnum'] = empty($v['xmnum']) ? 0 : $v['xmnum'];
                        $addarr['xmimg'] = $v['xmimg'];
                        $addarr['tourl'] = $v['tourl'];
                        $t_item->add($addarr);
                    }
                }
            }

            if ($data->create()) {
                if ($data->where($where)->save($_POST)) {
                    $this->handleKeyword($bid, 'MicroBroker', I('post.keyword'));
                    $this->success('修改成功!', U('MicroBroker/index', array('token' => session('token'))));
                    exit;
                } else {
                    //$this->error('没有做任何修改！');exit;
                    $this->success('修改成功', U('MicroBroker/index', array('token' => session('token'))));
                    exit;
                }
            } else {
                $this->error($data->getError());
            }
        } else {
            $bid = (int) I('get.id');
            $where = array('id' => $bid, 'token' => session('token'));
            $data = M('Broker');
            $check = $data->where($where)->find();
            if ($check == NULL || !is_array($check))
                $this->error('非法操作');
            if ($check['isdel'] == 1)
                Header("Location:" . U('MicroBroker/index', array('token' => session('token'))));
            $items = M('Broker_item')->where('bid=' . $bid)->order('id ASC')->select();
            $this->assign('items', $items);
            $this->assign('mbk', $check);
            $this->assign('invitecode', $check['invitecode']);
            $this->assign('mbkid', $bid);
            $this->display('mbkadd');
        }
    }

    //得到一个随机字符串
    private function get_rand($length = 8) {
        // 密码字符集，可任意添加你需要的字符
        //$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
        //$chars = '-_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-9876543210ZYXWVUTSRQPONMLKJIHGFEDCBAzyxwvutsrqponmlkjihgfedcba';
        $chars = '01234567899876543210';
        $strs = '';
        for ($i = 0; $i < $length; $i++) {
            // 这里提供两种字符获取方式
            // 第一种是使用 substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组 $chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
            $strs .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $strs;
    }

    public function delitem() {
        $itemid = intval(I('post.id'));
        $bid = intval(I('post.bid'));
        $bkdb = M('Broker');
        $find = array('id' => $bid, 'token' => session('token'));
        $result = $bkdb->where($find)->find();
        if ($result && is_array($result)) {
            M('Broker_item')->where(array('bid' => $bid, 'id' => $itemid))->delete();
            $this->dexit(array('iserror' => 0));
        } else {
            $this->dexit(array('iserror' => 1));
        }
    }

    //删除一个活动，软删除
    public function mbkdel() {
        $id = intval(I('post.id'));
		$db_Broker=M('Broker');
		$tmp=$db_Broker->where(array('id' => $id, 'token' => session('token')))->find();
        M('Broker')->where(array('id' => $id, 'token' => session('token')))->save(array('isdel' => 1));
		/*删除关键词*/
		$this->handleKeyword($id, 'MicroBroker', $tmp['keyword'],0,true);
        $this->dexit(array('error' => 0));
    }

    //核查活动存在
    private function mbkcheck($id) {
        $check = M('Broker')->where(array('id' => $id, 'token' => session('token')))->find();
        if (empty($check) || !is_array($check) || $check['isdel'] == 1)
            Header("Location:" . U('MicroBroker/index', array('token' => session('token'))));
    }

    //某个活动的经纪人所推荐的所有客户管理列表
    public function mbkClients() {
        $bid = I('get.id',0, 'intval');
        $this->mbkcheck($bid);
        $db_client = M('broker_client');
        $db_user = M('Broker_user');
        $where = array('bid' => $bid, 'token' => session('token'));
        $count = $db_client->where($where)->count();
        $Page = new \Think\Page($count, 20);
        $show = $Page->show();
        $joinitem = C('DB_PREFIX') . 'broker_item';
        $joinuser = C('DB_PREFIX') . 'broker_user';
        $db_client->join('as b_c LEFT JOIN ' . $joinitem . ' as b_i on b_c.proid=b_i.id');
        $db_client->join('LEFT JOIN ' . $joinuser . ' as b_u on b_c.tjuid=b_u.id');
        $db_client->where(array('b_c.bid' => $bid, 'b_c.token' => session('token')))->order('b_c.id DESC')->limit($Page->firstRow . ',' . $Page->listRows);
        $clientList = $db_client->field('b_c.*,b_i.xmname,b_i.tourl,b_u.username,b_u.tel')->select();
        if (is_array($clientList)) {
            foreach ($clientList as $k => $v) {
                if ($v['verifyuid'] > 0) {
                    $tmp = $db_user->where(array('id' => $v['verifyuid'], 'bid' => $v['bid']))->find();
                    $clientList[$k]['verifyname'] = $tmp['username'];
                    $clientList[$k]['verifytel'] = $tmp['tel'];
                } else {
                    $clientList[$k]['verifyname'] = '';
                    $clientList[$k]['verifytel'] = '';
                }
            }
        }
        $statusarr = array(0 => '新用户', 1 => '已跟进', 2 => '已到访', 3 => '已认筹', 4 => '已认购', 5 => '已签约', 6 => '已回款', 7 => '完成');
        $this->assign('statusarr', $statusarr);
        $this->assign('clientList', $clientList);
        $this->assign('pageshow', $show);
        $this->assign('bid', $bid);
        $this->display();
    }

    public function Consultant() {
        $bid = I('get.bid',0, 'intval');
        $clienid = I('get.id',0, 'intval');
        if (IS_POST) {
            $clienid = I('post.clienid',0, 'intval');
            $bid = I('post.bid',0, 'intval'));
            $itemid = I('post.itemid',0, 'intval'));
            $token = session('token');
            if ($itemid > 0) {
                M('Broker_client')->where(array('id' => $clienid, 'bid' => $bid, 'token' => $token))->save(array('verifyuid' => $itemid));
            }
            echo "<script type='text/javascript'>parent.location.reload();</script>";
            exit;
        } else {
            $db_user = M('Broker_user');
            $res = $db_user->where(array('bid' => $bid, 'is_verify' => 1, 'token' => session('token')))->field('id,tel,username')->select();
            $this->assign('consultant', $res);
            $this->assign('clienid', $clienid);
            $this->assign('bid', $bid);
            $this->display();
        }
    }

    //添加一个置业顾问
    public function addOneConsultant() {
        $bid = I('get.id',0, 'intval');
        if (IS_POST) {
            $id = I('post.bid',0, 'intval');
            if ($bid == $id) {
                $data = array('bid' => $id, 'token' => session('token'));
                $telREG = '/(^(([0\+]\d{2,3}-)?(0\d{2,3})-)(\d{7,8})(-(\d{3,}))?$)|(^0{0,1}1[3|4|5|6|7|8|9][0-9]{9}$)/';

                $tel = I('post.tel');
                if (!empty($tel)) {
                    if (preg_match($telREG, $tel)) {
                        $data['tel'] = $tel;
                    } else {
                        $this->error('手机号码格式不对');
                        exit;
                    }
                } else {
                    $this->error('手机号码不能为空');
                    exit;
                }

                $username = I('post.username');
                if (empty($username)) {
                    $this->error('产品顾问名称不能为空');
                    exit;
                }
                $data['username'] = htmlspecialchars($username, ENT_QUOTES);
                $password = I('post.password');
				$tmp=strlen($password);
                if (empty($password)) {
                    $this->error('密码不能为空');
                    exit;
                }elseif(!is_numeric($password) || $tmp<6 || $tmp>8){
				    $this->error('密码必须为你6到8位的数字');
                    exit;
				}
				$db_brokeruser = M('Broker_user');
				$tmpTel = $db_brokeruser->where(array('bid' => $id, 'tel' => $data['tel']))->find();
				if(!empty($tmpTel)){
				    $this->error('此手机号码已经被注册过了！');
                    exit;   
				}
                $data['pwd'] = md5($password);
                $company = I('post.company');
                $data['company'] = htmlspecialchars($company, ENT_QUOTES);
                $data['identity'] = 7;
                $data['is_verify'] = 1;
                $data['wecha_id'] = "MBK_" . $id . '_temp' . $data['tel'];
                $data['addtime'] = time();
                $userid = $db_brokeruser->add($data);
                if ($userid > 0) {
                    $this->success('添加成功!', U('MicroBroker/mbkBrokers', array('token' => session('token'), 'id' => $id)));
                } else {
                    $this->error('添加失败');
                    exit;
                }
            } else {
                $this->error('添加失败');
                exit;
            }
        } else {
            $this->assign('id', $bid);
            $this->display();
        }
    }

    //某个活动的经纪人管理列表
    public function mbkBrokers() {
        $bid = (int) I('get.id');
        $this->mbkcheck($bid);
        $jointable = C('DB_PREFIX') . 'broker_translation';
        $db_user = M('Broker_user');
        $where = array('bid' => $bid, 'token' => session('token'));
        $count = $db_user->where($where)->count();
        $Page = new \Think\Page($count, 20);
        $show = $Page->show();
        $db_user->join('as b_u LEFT JOIN ' . $jointable . ' as b_t on b_u.identity=b_t.id');
        $db_user->where(array('b_u.bid' => $bid, 'b_u.token' => session('token')))->order('b_u.id DESC')->limit($Page->firstRow . ',' . $Page->listRows);
        $userList = $db_user->field('b_u.*,b_t.description')->select();
        //echo $db_user->getLastSql();
        $this->assign('id', $bid);
        $this->assign('userList', $userList);
        $this->assign('pageshow', $show);
        $this->display();
    }

    //某个活动的经纪人加入黑名单
    public function mbkBlacklist() {
        $id = intval(I('post.id'));
        $bid = intval(I('post.bid'));
        $st = intval(I('post.st'));
        $token = I('post.token');
        M('Broker_user')->where(array('id' => $id, 'bid' => $bid, 'token' => session('token')))->save(array('status' => $st));
        $this->dexit(array('error' => 0));
    }

    public function mbkDetail() {
        $bid = (int) I('get.bid');
        $id = (int) I('get.id');
        $jointable = C('DB_PREFIX') . 'broker_translation';
        $db_user = M('Broker_user');
        $db_user->join('as b_u LEFT JOIN ' . $jointable . ' as b_t on b_u.identity=b_t.id');
        $thisuser = $db_user->where(array('b_u.id' => $id, 'b_u.bid' => $bid, 'b_u.token' => session('token')))->field('b_u.*,b_t.description')->find();
        $this->assign('thisuser', $thisuser);
        $this->display();
    }

    //操作日志
    public function mbkMoneyOperatLog() {
        $bid = (int) I('get.bid');
        $id = (int) I('get.id');
        $userarr = M('Broker_user')->where(array('id' => $id, 'bid' => $bid, 'token' => session('token')))->find();
        $db_optlog = M('Broker_optionlog');
        $where = array('tjuid' => $id, 'bid' => $bid, 'token' => session('token'));
        $count = $db_optlog->where($where)->count();
        $Page = new \Think\Page($count, 20);
        $show = $Page->show();
        $logarr = $db_optlog->where($where)->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('thisuser', $userarr);
        $this->assign('logarr', $logarr);
        $this->assign('pagshow', $show);
        $this->display();
    }

    //经纪人 用户佣金操作
    public function mbkOperatMoney() {
        $bid = (int) I('get.bid');
        $id = (int) I('get.id');
		$cmmid=I('get.cmmid')? intval(I('get.cmmid')):0;//broker_commission 表id
        $db_user = M('Broker_user');
        if (IS_POST) {
            $uid = intval(I('post.u_id'));
            $bid = intval(I('post.bid'));
			$cmmid = intval(I('post.cmmid'));
            $opttype = intval(I('post.opttype')); //1 充值，2 提取
            $tomoney = I('post.tomoney');
            $totalcash = I('post.totalcash'); //当前可提取金额 额度
            $token = session('token');
            if (is_numeric($tomoney)) {
                if ($opttype == 1) {
                    M('Broker_user')->where(array('id' => $uid, 'bid' => $bid, 'token' => $token))->setInc('totalcash', $tomoney);
					if($cmmid>0){
					  $upwhere=array('id' => $cmmid, 'bid' => $bid, 'tjuid' => $uid);
					  M('Broker_commission')->where($upwhere)->save(array('status' => 1));
					  M('Broker_commission')->where($upwhere)->setInc('money',$tomoney);
					}
                    M('Broker_optionlog')->add(array('token' => $token, 'bid' => $bid, 'tjuid' => $uid, 'logstr' => '佣金操作充值', 'addtime' => time(), 'money' => $tomoney)); //佣金操作日志
                    echo "<script type='text/javascript'>alert('充值成功！');parent.location.reload();</script>";
                    exit;
                } elseif ($opttype == 2) {
                    if ($totalcash >= $tomoney) {
                        M('Broker_user')->where(array('id' => $uid, 'bid' => $bid, 'token' => $token))->setDec('totalcash', $tomoney);

                        M('Broker_user')->where(array('id' => $uid, 'bid' => $bid, 'token' => $token))->setInc('extractcash', $tomoney);
                        M('Broker_optionlog')->add(array('token' => $token, 'bid' => $bid, 'tjuid' => $uid, 'logstr' => '佣金操作提取', 'addtime' => time(), 'money' => $tomoney)); //佣金操作日志
                        echo "<script type='text/javascript'>alert('提取成功！');parent.location.reload();</script>";
                        exit;
                    } else {
                        echo "<script type='text/javascript'>alert('当前可提取的佣金余额不足！');parent.location.reload();</script>";
                        exit;
                    }
                }
            } else {
                echo "<script type='text/javascript'>alert('充值/提取佣金额度必须是数字');parent.location.reload();</script>";
                exit;
            }
        } else {
            $thisuser = $db_user->where(array('id' => $id, 'bid' => $bid, 'token' => session('token')))->find();
            $this->assign('thisuser', $thisuser);
            $this->assign('u_id', $id);
            $this->assign('bid', $bid);
			$this->assign('cmmid', $cmmid);
            $this->display();
        }
    }

    //回款管理列表
    public function mbkConfirm() {
        $bid = (int) I('get.id');
        $this->mbkcheck($bid);
        $token = I('get.token');
        $tmps = array();
        if ($token == session('token')) {
            $db_commission = M('Broker_commission');
            $where = array('bid' => $bid, 'client_status' => 6);
            $count = $db_commission->where($where)->count();
            $Page = new \Think\Page($count, 20);
            $show = $Page->show();
            $jointable = C('DB_PREFIX') . 'broker_item';
            $db_commission->join('as b_c LEFT JOIN ' . $jointable . ' as b_i on b_c.proid=b_i.id');
            $tmps = $db_commission->where('b_c.bid=' . $bid . ' AND b_c.client_status=6')->order('b_c.id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->field('b_c.*,b_i.xmname,b_i.xmtype,b_i.xmnum')->select();
        }
        $this->assign('commdatas', $tmps);
        $this->assign('pageshow', $show);
        $this->display();
    }

    public function mbkSureMoney() {
        $id = intval(I('post.id'));
        $tjuid = intval(I('post.tjuid')); //经纪人broker_user表id
        $bid = intval(I('post.bid'));
        $money = I('post.money');
        $token = I('post.token');
        if (($token == session('token')) && ($money >= 0)) {
            M('Broker_commission')->where(array('id' => $id, 'bid' => $bid, 'tjuid' => $tjuid))->save(array('status' => 1));

            M('Broker_user')->where(array('id' => $tjuid, 'bid' => $bid, 'token' => $token))->setInc('totalcash', $money);

            M('Broker_optionlog')->add(array('token' => $token, 'bid' => $bid, 'tjuid' => $tjuid, 'logstr' => '佣金审核充值', 'addtime' => time(), 'money' => $money)); //佣金操作日志
            $this->dexit(array('error' => 0));
        }
        $this->dexit(array('error' => 1));
    }

    //json格式输出封装函数
    private function dexit($data = '') {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }

}