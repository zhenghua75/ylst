<?php
namespace User\Controller;
use Common\Controller\UserController;
class AddressBookController extends UserController{
	public $oper_f;
	private $randstr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	private $randstrps = "abcdefghijklmnopqrstuvwxyz0123456789";

	public function _initialize(){
		parent::_initialize();
		$this->canUseFunction('AddressBook');
	}

	public function index(){
		$token = $this->token;
		$userextinfo_db = M("User_extinfo");
    	$userextinfos = $userextinfo_db->where(array('token'=>$token))->select();
    	$userextinfo_db = M("Userinfo");
    	foreach($userextinfos as $rex){
    		$usertinfos = $userextinfo_db->where(array('token'=>$token,'id'=>$rex['uid']))->find();
    		$userinfo[$rex['uid']]=$usertinfos;
    	}

		$this->assign('userextinfo',$userextinfos);
		$this->assign('userinfo',$userinfo);
		$this->display();
	}

	public function member(){
		$token = $this->token;
		$isuser = 0;
		$uidget=$_GET['uid'];
		if(IS_POST){
			$uidpost = isset($_POST['uid']) ? htmlspecialchars($_POST['uid']) : '';
			$truename = isset($_POST['truename']) ? htmlspecialchars($_POST['truename']) : '';
			$username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
			$status = isset($_POST['status']) ? htmlspecialchars($_POST['status']) : '';
			$portrait = isset($_POST['portrait']) ? htmlspecialchars($_POST['portrait']) : '';
			$sex = isset($_POST['sex']) ? htmlspecialchars($_POST['sex']) : '';
			$tel = isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : '';
			$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
			$busiintro = isset($_POST['busiintro']) ? htmlspecialchars($_POST['busiintro']) : '';
			$skillexp = isset($_POST['skillexp']) ? htmlspecialchars($_POST['skillexp']) : '';
			$industry = isset($_POST['industry']) ? htmlspecialchars($_POST['industry']) : '';
			$compname = isset($_POST['compname']) ? htmlspecialchars($_POST['compname']) : '';
			$compintro = isset($_POST['compintro']) ? htmlspecialchars($_POST['compintro']) : '';
			$focusindus = isset($_POST['focusindus']) ? htmlspecialchars($_POST['focusindus']) : '';
			$selfintro = isset($_POST['selfintro']) ? htmlspecialchars($_POST['selfintro']) : '';
			$socialduty = isset($_POST['socialduty']) ? htmlspecialchars($_POST['socialduty']) : '';
			$hobby = isset($_POST['hobby']) ? htmlspecialchars($_POST['hobby']) : '';
			$city = isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '';
			$learnexp = isset($_POST['learnexp']) ? htmlspecialchars($_POST['learnexp']) : '';
			$referrer = isset($_POST['referrer']) ? htmlspecialchars($_POST['referrer']) : '';
			$referrertel = isset($_POST['referrertel']) ? htmlspecialchars($_POST['referrertel']) : '';
			
			if (empty($truename)) {
				$this->error("姓名不能为空!");
			}
			if (empty($username)) {
				$this->error("登录帐号不能为空!");
			}

			if(!$uidpost){
				$userInfo = M('Userinfo')->where(array('username' => $username,'token'=>$token))->find();
				foreach($userInfo as $rin){
					$userInfoex = M('User_extinfo')->where(array('uid'=>$rin['id'],'token'=>$token))->find();
					if($userInfoex['truename'] = $truename){
						$isuser=1;
						$this->error('该会员姓名或帐号已经注册过');
					}
				}
				if(!$isuser){
					$passwd=$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)};
	                $uid = D("Userinfo")->add(array('token'=>$token, 'truename' => $truename, 'password' => md5($passwd), 'tel' => $tel, 'sex' => $sex,
	                	'username'=>$username,'portrait'=>$portrait));
	                $twid = $this->randstr{rand(0, 51)} . $this->randstr{rand(0, 51)} . $this->randstr{rand(0, 51)} . $uid;
	                D('Userinfo')->where(array('id' => $uid))->save(array('twid' => $twid,'wecha_id' => $twid));
	                D("User_extinfo")->add(array('uid' => $uid, 'token' => $token, 'status' => $status, 'truename' => $truename, 'sex' => $sex, 'tel' => $tel, 
	                	'email' => $email, 'busiintro' => $busiintro,'selfintro' => $selfintro, 'socialduty' => $socialduty, 'hobby'=> $hobby,
	                	'city' => $city, 'learnexp' => $learnexp, 'skillexp' => $skillexp, 'industry' => $industry, 'compname' => $compname, 
	                	'compintro' => $compintro, 'focusindus' => $focusindus, 'regtime'=>time(),'referrer'=>$referrer,'referrertel'=>$referrertel));
	                $this->assign('waitSecond',60);
		            $this->success('会员资料添加成功，随机密码为：'.$passwd, U('AddressBook/member', array('token' => $this->token)));
				}
			}else{
				$userInfo = M('Userinfo')->where(array('username' => $username,'token'=>$token))->find();
				if($userInfo){
	                D('Userinfo')->where(array('id' => $uidpost))->save(array('truename' => $truename,'tel' => $tel, 'sex' => $sex,'portrait'=>$portrait));
	                D("User_extinfo")->where(array('uid' => $uidpost))->save(array('status' =>$status, 'truename' => $truename, 'sex' => $sex, 'tel' => $tel, 
	                	'email' => $email, 'busiintro' => $busiintro,'selfintro' => $selfintro, 'socialduty' => $socialduty, 'hobby'=> $hobby,
	                	'city' => $city, 'learnexp' => $learnexp, 'skillexp' => $skillexp, 'industry' => $industry, 'compname' => $compname, 
	                	'compintro' => $compintro, 'focusindus' => $focusindus,'referrer'=>$referrer,'referrertel'=>$referrertel));
		            $this->success('会员资料修改成功', U('AddressBook/index', array('token' => $this->token)));
				}else{
					$this->success('会员资料修改失败', U('AddressBook/index', array('token' => $this->token)));
				}
			}

		}else{
			if($uidget){
				$userInfo = M('Userinfo')->where(array('id' => $uidget,'token'=>$token))->find();
				$userInfoex = M('User_extinfo')->where(array('uid'=>$uidget,'token'=>$token))->find();
				$this->assign('userInfoex',$userInfoex);
				$this->assign('userInfo',$userInfo);
				$this->assign('uid',$uidget);
			}
			$indus = include('./PigCms/Lib/ORG/Industry.php');
			$induscat = $indus['allcat'];
			$this->assign('induscat',$induscat);
			$this->display();
		}
	}

	public function passwordreset(){
		$token = $this->token;
		$isuser = 0;
		$uidget=$_GET['uid'];

		if($uidget){
			$passwd=$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)}.$this->randstrps{rand(0, 35)};
			$userInfo = M('Userinfo')->where(array('id' => $uidget,'token'=>$token))->find();
			if($userInfo){
            	D('Userinfo')->where(array('id' => $uidget))->save(array('password'=>md5($passwd)));
            	$this->success('密码重置成功，新随机密码为：'.$passwd, U('AddressBook/index', array('token' => $this->token)),false,60);
            }else{
				$this->success('密码重置失败', U('AddressBook/index', array('token' => $this->token)));
			}
		}else{
			$this->success('密码重置失败', U('AddressBook/index', array('token' => $this->token)));
		}
	}
}