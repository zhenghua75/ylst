<?php
namespace Common\Behavior;
/**
 * 行为扩展：代理检测
 */
class MobileWebsiteCheckBehavior {
    public function run(&$params) {
    	$nomsite=isset($_SESSION[$_SERVER['HTTP_HOST'].'nomsite']) ? $_SESSION[$_SERVER['HTTP_HOST'].'nomsite'] : false;
    	$own_domain=I('get.owndomain');
    	$owndomain=$own_domain && !empty($own_domain) ? $own_domain : false;
    	define('owndomain',$owndomain);
    	$rget=intval(I('get.rget'));
    	define('rget',$rget>0 ? $rget : 0);
        if(!$owndomain && (MODULE_NAME!="User") && !$nomsite){
		   $this->check_mobile_website();
		}
    }

	private function check_mobile_website() {
        /*$mb_token = $this->_get('token', 'trim');
        if ($mb_token && !preg_match("/^[0-9a-zA-Z]{3,42}$/", $mb_token)) {
            exit('error token');
        }*/
        $db_mobilesite = M('Mobilesite');
		$tmp =isset($_SESSION[$_SERVER['HTTP_HOST']]) ? $_SESSION[$_SERVER['HTTP_HOST']] : null;
		if(!empty($tmp)){
		  $tmp =unserialize($tmp);
		}else{
          $tmp = $db_mobilesite->where(array('owndomain' => $_SERVER['HTTP_HOST']))->find();
		}
		$bid = I('get.bid') ? intval(I('get.bid')) : 0;//$this->_get('bid', 'trim')
        if (is_array($tmp) && !empty($tmp) && !empty($tmp['token'])) {
			    $_SESSION[$tmp['owndomain']]=serialize($tmp);
				$_SESSION[$_SERVER['HTTP_HOST'].'nomsite']=0;
				$siteUrl=preg_replace('/https?:\/\//','',C('site_url'));
				$siteUrl=trim($siteUrl);
				$siteUrl=rtrim($siteUrl,'/');/****解决人家 换域名后带来的问题*****/
				$admindomain=trim($tmp['admindomain']);
				if($siteUrl!=$admindomain){
				   $admindomain=$siteUrl;
				}
			    if($_SERVER["QUERY_STRING"]=="" || !strpos($_SERVER["REQUEST_URI"],"g=Wap")|| !strpos($_SERVER["REQUEST_URI"],"token=".$tmp['token'])){
				  $request_url='http://' . $admindomain."/index.php?g=Wap&m=Index&a=index&token=".$tmp['token']. "&rget=3&owndomain=" . $tmp['owndomain'];
				}else{
                  $request_url = 'http://' . $admindomain . $_SERVER['REQUEST_URI'] . "&rget=3&owndomain=" . $tmp['owndomain'];
				}
				if(isset($_COOKIE['qmjjr_loginuserid'.$bid])) $request_url=$request_url."&loginuserid=".$_COOKIE['qmjjr_loginuserid'.$bid];
                if (IS_POST) {
                    $responsearr = $this->httpRequest($request_url, REQUEST_METHOD, $_POST);
                } else {
                    $responsearr = $this->httpRequest($request_url, REQUEST_METHOD, null);
                }
				
                $tmpcontent = $responsearr['1'];
				$httpcode=intval(trim($responsearr['0']));
				if(in_array($httpcode,array(301,302))){
					header('Location:'. $responsearr['2']['url']);
					exit();
				}
                /* * ajax请求时 json封装带过来的数据 是否需要解析* */
                /** 格式为{"analyze":1,"error":0,"msg":"opt_cookie","data":{"ckkey":"bfdhdfhdf","ckv":2,"expire":3600}}这样的json**/
                /* * analyze为数字：指明是否需要解析 大于0的值时需要解析 0不需要解析，请不要写成布尔值*** */
                /* * error为数字：指明一个状态**msg为字符串：指明操作**data为数据库：指明要操作的数据* */
                $jsonREG = '/^\{[\"\']analyze[\"\']\:\d\,[\"\']error[\"\']\:\d\,[\"\']msg[\"\']\:(.*)data[\"\']\:(.*)\}\}$/i';
                if (preg_match($jsonREG, $tmpcontent, $matches)) {
                    $jsonstr = $matches[0];
                    $jsonarr = !empty($jsonstr) ? json_decode($jsonstr, TRUE) : false;
                    if ($jsonarr && is_array($jsonarr)) {
                        $is_analyze = isset($jsonarr["analyze"]) ? intval($jsonarr["analyze"]) : 0;
                        if ($is_analyze > 0) {
                            $tmpcontent = $jsonarr["error"];

                            switch ($jsonarr["msg"]) {
                                case "opt_cookie":
                                    $tmpdata = $jsonarr["data"];
                                    $expire = intval($tmpdata["expire"]);
                                    $expire = $expire > 0 ? time() + $expire : 0;
                                    setcookie('qmjjr_loginuserid'.$bid, $tmpdata["ckv"], $expire, "/", $_SERVER["HTTP_HOST"]);
                                    break;
                                default:

                                    break;
                            }
                        }
                    }
                }
                $tmpcontent=str_replace($admindomain,$_SERVER['HTTP_HOST'],$tmpcontent);
               
                $_SESSION['otherSource']=1;
               echo $tmpcontent;
                if (!IS_AJAX && !empty($tmp['tjscript'])) {
                    $tjscript = base64_decode($tmp['tjscript']);
                    $tjscript = urldecode(str_replace('jshtmltag', 'script', $tjscript));
                    echo $tjscript;
                }
                exit();
        }else{
		   $_SESSION[$_SERVER['HTTP_HOST'].'nomsite']=1;
		}
    }
}