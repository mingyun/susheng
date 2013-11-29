<?php
/**
 * @name 微信被动接口示意文件
 * 
 * @license 
 * 
 */
date_default_timezone_set('Asia/Shanghai');
include "Wechat.class.php"; 
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors','On');

 
define(FLAG,$_GET['website']);
class WechatTools implements WechatSessionToolInter{

	var $memcache;
	var $db_link = null;
	function __construct(){
		//这里使用memcache存储cookies和token，没有该环境的用户可以自己去实现使用文件或其他方式存取
		$this->memcache = new Memcache();
		$this->memcache->connect("ip", 11211);
		$this->cookie="wechat_cookies_".FLAG;
		$this->token="wechat_token_".FLAG;
	}

	/**
	 * @name 获取Cookies
	 * @see WechatSessionToolInter::getCookies()
	 */
	public function getCookies() {
		return $this->memcache->get($this->cookie);  //使用memcache高速缓存存取cookies
	}

	/** 
	 * @name 获取token
	 * @see WechatSessionToolInter::getToken()
	 */
	public function getToken() {
		return $this->memcache->get($this->token);  //使用memcache高速缓存存取Token
	}

	/**
	 * @name 设置保存Cookies
	 * @param string $Cookies
	 * @see WechatSessionToolInter::setCookies()
	 */
	public function setCookies($Cookies) {
		$this->memcache->set($this->cookie, $Cookies);  //使用memcache高速缓存存取cookies
	}

	/**
	 * @name 设置保存token
	 * @param string $token
	 * @see WechatSessionToolInter::setToken()
	 */
	public function setToken($token) {
		$this->memcache->set($this->token, $token);  //使用memcache高速缓存存取Token
	}


}
//上面类的实例化
$wechatToolObj = new WechatTools();
if(FLAG=='1'){
	$wechatOptions= array(
				'token'=>'',
				'account'=>'email',
				'password'=>'pwd',
				"wechattool"=>$wechatToolObj
	);
}
print_r($wechatOptions);
$wechatObj = new Wechat($wechatOptions);
 
 //echo '<pre>';print_r($wechatObj);
$wechatObj->positiveInit();  //主动响应组件初始化
   $wechatObj->login();
   $wechatObj->keepLive();
    //单条消息发送
	//if ($wechatObj->checkValid()) {echo '开始啦';
	
		//$singleresult = $wechatObj->send("647940102", $msg);//发送内容不能有@
		//print_r($singleresult);//成功返回1 
		$list=$wechatObj->getfriendlist2();
		echo '<pre>';print_r($list);
		//$topmsg =$wechatObj->getMessageAjax2(0, 1, 0);
		//echo '<pre>';print_r($topmsg);
		//$mess=$wechatObj->getMessageAjax(0,1);
		$messes=$wechatObj->getMessage2();echo '<pre>';print_r($messes);
		$user=$wechatObj->getContactInfo(647940102);print_r($user);die;
		$newsArray=array(
      array('title'=>'发送图文','digest'=>'','author'=>'test','image'=>'p.jpg','content'=>'','sourceurl'=>''),
      array('title'=>'发送图文2','digest'=>'','author'=>'test2','image'=>'','content'=>'','sourceurl'=>''),
      );
		//$wechatObj->sendPreNews(647940102, $newsArray);
		$createTime=1377679459;$content='448361';
			foreach($messes as $msg) {
				// 仅仅时间符合
				if($msg['date_time'] == $createTime && $msg['content'] == $content) {
					// 内容+时间都符合
					//if($msg['content'] == $content) { 
						$return_fit=$msg;//echo json_encode($msg);die;
					//}
				// 仅仅是内容符合
				} elseif($msg['date_time'] == $createTime) {
					//echo 'weimao';echo json_encode($msg);die;
					$return_content=$msg;
				} elseif($msg['content'] == $content) {
					$contentMsg[] = $msg;
					//echo json_encode($messes[0]);die;
				}
			}
			if(!empty($contentMsg)) {
				//echo json_encode($contentMsg[0]);die;
			}
			if(!empty($return_fit)){
				$json=$return_fit;
			}elseif(!empty($return_content)) {
				$json=$return_content;
			}elseif(!empty($contentMsg)) {
				$json=$contentMsg[0];
			}
			$wxhao=$wechatObj->getContactInfo($json['fakeid']);
			$json['wxhao']=$wxhao['Username'];
			print_r($wxhao);echo json_encode($json);die;
			//echo json_encode($messes[0]);die;
		
		if(isset($_POST['bang']) && $_POST['bang']=='bang'){
			$createTime=$_POST['CreateTime'];$content=$_POST['keyword'];
			foreach($messes as $msg) {
				if($msg['date_time'] == $createTime && $msg['content'] == $content) {
					// 内容+时间都符合
					$return_fit=$msg;
				// 仅仅是内容符合
				} elseif($msg['date_time'] == $createTime) {
					$return_content=$msg;
				} elseif($msg['content'] == $content) {
					$contentMsg[] = $msg;
				}
			}
			if(!empty($return_fit)){
				$json=$return_fit;
			}elseif(!empty($return_content)) {
				$json=$return_content;
			}elseif(!empty($contentMsg)) {
				$json=$contentMsg[0];
			}
			$wxhao=$wechatObj->getContactInfo($json['fakeid']);
			$json['wxhao']=$wxhao['Username'];
			echo json_encode($json);
			//echo json_encode($topmsg[0]);
		}
	//}





?>
