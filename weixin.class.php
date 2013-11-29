<?php
// error_reporting(0);
/**
 * 微信公共平台整合库
 * @author Ligboy (ligboy@gmail.com)
 * @license 本库的很多思路来自于网上的其他热心人士的贡献，大家任意使用，我本人放弃所有权利，如果您心情好，给我留个署名也行。
 *https://github.com/ligboy/Wechat-php  http://www.cnblogs.com/ligboy/archive/2013/04/30/3051939.html

 {
  "FakeId"  : "2846246900",
  "NickName"  : "蔣美华",
  "ReMarkName": "",
  "Username"  : "jiangmeihua744838",
  "Signature" : "活出风采",
  "Country"   : "中国",
  "Province"  : "北京",
  "City"      : "丰台",
  "Sex"       : "1",
  "GroupID"   : "0",
  "Groups"  : [
                      {
        "GroupId": "0",
                  "GroupName": "未分组"
              }
                ,      {
        "GroupId": "1",
                  "GroupName": "黑名单"
              }
                ,      {
        "GroupId": "2",
                  "GroupName": "星标组"
              }
              ]
}

 */

interface WechatSessionToolInter {
	/**
	 * @name 获取token
	 * 
	 */
	function getToken();
	
	/**
	 * @name 设置保存token
	 * @param string $token
	 */
	function setToken($token);

	/**
	 * @name 获取Cookies
	 *
	 */
	function getCookies();

	/**
	 * @name 设置保存Cookies
	 * @param string $Cookies
	 */
	function setCookies($Cookies);
}


class Wechat {
	/* 配置参数  */
	/**
	 *
	 * @var array
	 * @example array('token'=>'微信接口密钥','account'=>'微信公共平台账号','password'=>'微信公共平台密码','webtoken'=>"微信公共平台网页url的token");
	 */
	private $wechatOptions=array('token'=>'','account'=>'','password'=>'',"wechattool"=>'');	//
	private $cookiefilepath = ""; //以文件形式保存cookie的保存目录，肯定是可写的
	public $webtoken = '';  
	private $webtokenStoragefile = "";  //微信公共平台的token存储文件，就是公共平后台网页的token
	public $debug =  false;  //调试开关
	public $protocol = "https";  //使用协议类型 http or  https

	/* 静态常量 */
	const MSGTYPE_TEXT = 'text';
	const MSGTYPE_IMAGE = 'image';
	const MSGTYPE_LOCATION = 'location';
	const MSGTYPE_LINK = 'link';
	const MSGTYPE_EVENT = 'event';
	const MSGTYPE_MUSIC = 'music';
	const MSGTYPE_NEWS = 'news';
	const MSGTYPE_VOICE = 'voice';
	const MSGTYPE_VIDEO = 'video';

	/* 私有参数 */
	private $_msg;
	private $_funcflag = false;
	public $_receive;
	private $_logcallback;
	private $_token;
	private $_getRevRunOnce = 0;
	private $_cookies;
	private $_wechatcallbackFuns = null;
	private $_curlHttpObject = null;
	/**
	 * @var boolean 自动附带发送openid开关
	 */
	private $_autosendopenid = false;


	/**
	 * @var boolean 被动响应关联动作开关
	 */
	private $_passiveAssociationSwitch = false;
	/**
	 * 
	 */
	/**
	 * @var boolean 被动响应关联动作开关
	 */
	private $_passiveAscGetDetailSwitch = false;
	/**
	 * 初始化工作
	 * @param array $option  array('token'=>'微信接口密钥','account'=>'微信公共平台账号','password'=>'微信公共平台密码');
	 */
	function __construct($option=array())
	{
		if (!empty($option))
		{
			$this->wechatOptions = array_merge($this->wechatOptions, $option);
		}
	}
	/**
	 * @name 主动动作初始化
	 * @return Wechat
	 */
	function positiveInit()
	{
		if (!is_object($this->_wechatcallbackFuns)) {
			if ($this->wechatOptions['wechattool']) {
				$this->setWechatToolFun($this->wechatOptions['wechattool']);
			}
			$this->setWechatToolFun($this->wechatOptions['wechattool']);
		}
		$this->_cookies = $this->getCookies();
		$this->webtoken = (string)$this->getToken();
		return $this;
	}
	private function curlInit($type=null, $option=null) {
		if (!isset($this->_curlHttpObject)) {
			$this->_curlHttpObject = new CurlHttp();
		}
		if ("single"==$type) {
			$this->_curlHttpObject->singleInit($option);
		}
		elseif ("roll"==$type){
			$this->_curlHttpObject->rollInit($option);
		}
		return $this->_curlHttpObject;
	}

	

	private function log($log){
		if ($this->debug && function_exists($this->_logcallback)) {
			if (is_array($log)) $log = print_r($log,true);
			return call_user_func($this->_logcallback,$log);
		}
	}






	/**
	 * 登录微信公共平台，获取并保存cookie、webtoken到指定文件
	 * @return mixed 成功则返回true，失败则返回失败
	 */
	public function login(){
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
		$postfields["username"] = $this->wechatOptions['account'];
		$postfields["pwd"] = md5($this->wechatOptions['password']);
		$postfields["f"] = "json";
		$postfieldss = "username=".urlencode($this->wechatOptions['account'])."&pwd=".urlencode(md5($this->wechatOptions['password']))."&f=json";

		$this->curlInit("single");
		$response = $this->_curlHttpObject->post($url, $postfields, $this->protocol."://mp.weixin.qq.com/cgi-bin/login", $this->_cookies);
		$result = json_decode($response, true);
		if ($result['ErrCode']=="65201"||$result['ErrCode']=="65202"||$result['ErrCode']=="0")
		{
			preg_match('/&token=([\d]+)/i', $result['ErrMsg'],$match);
			$this->webtoken = $match[1];
			$this->setToken($this->webtoken);
			$this->setCookies($this->_curlHttpObject->getCookies());
			return true;
		}
		else
		{
// 			return false;
			return $result['ErrCode'];
		}
	}

	/**
	 * @name 执行关联动作
	 */
	private function doAssociationAction()
	{
		//var_dump($this->_passiveAssociationSwitch && Wechat::MSGTYPE_EVENT!=$this->getRevType() &&	is_object($this->_wechatcallbackFuns) && method_exists($this->_wechatcallbackFuns, "getAscStatusByOpenid") && method_exists($this->_wechatcallbackFuns, "setAssociation") && !$this->_wechatcallbackFuns->getAscStatusByOpenid($this->getRevFrom()));
		if ($this->_passiveAssociationSwitch && Wechat::MSGTYPE_EVENT!=$this->getRevType() &&	is_object($this->_wechatcallbackFuns) && method_exists($this->_wechatcallbackFuns, "getAscStatusByOpenid") && method_exists($this->_wechatcallbackFuns, "setAssociation") && !$this->_wechatcallbackFuns->getAscStatusByOpenid($this->getRevFrom()))
		{
			//$messageList = $this->getMessage();
			$messageList = $this->getMessageAjax(0, 40, 0, 99999999+intval(mt_rand(0, 99999)));
			if ($messageList)
			{
				$count = 0;
				$fakeid = "";
				foreach ($messageList as $value)
				{
					if ($value["dateTime"]==$this->getRevCtime())
					{
						$count += 1;
						$fakeid = $value["fakeId"];
					}
				}
				if (1==$count && $fakeid!="")
				{
					$detailInfo = NULL;
					if ($this->_passiveAscGetDetailSwitch)
					{
						$detailInfo = $this->getContactInfo($fakeid);
					}
					$this->_wechatcallbackFuns->setAssociation((string)$this->getRevFrom(), $fakeid, $detailInfo);
				}
			}
		}
	}
	
	/**
	 * 验证登录是否在线
	 * @return boolean 
	 */
	public function checkValid()
	{
		$postfields = array();
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/getregions?id=1054&t=ajax-getregions&lang=zh_CN&token=".$this->webtoken;
		//判断cookie是否为空，为空的话自动执行登录
		if ($this->_cookies||$this->_cookies = $this->getCookies())
		{
			$this->curlInit("single");
			$response = $this->_curlHttpObject->get($url, $this->protocol."://mp.weixin.qq.com/cgi-bin/", $this->_cookies);
			$result = json_decode($response,1);
			if(isset($result['num']))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * keepLive心跳包保持，在线状态，推荐通过cron每15分钟调用一下
	 * @return boolean
	 */
	public function keepLive()
	{
		if (!$this->checkValid()) {
			return (true===$this->login());
		}
		return 1;
	}

	/**
	 * 主动单条发消息
	 * @param  string $id      用户的fakeid
	 * @param  string $content 发送的内容
	 * @return integer 返回发送结果：成功返回:1,登录问题返回:-1,其他原因返回:0
	 *///https://mp.weixin.qq.com/cgi-bin/singlemsgpage?msgid=&source=&count=20&t=wxm-singlechat&fromfakeid=647940102&token=684658566&lang=zh_CN
	/*public function send($fakeid,$content)
	{
		//判断cookie是否为空，为空的话自动执行登录
		if (file_exists($this->cookiefilepath)||true===$this->login())
		{
			$postfields = array();
			$postfields['tofakeid'] = $fakeid;
			$postfields['type'] = 1;
			$postfields['error']= "false";
			$postfields['token']= $this->webtoken;
			$postfields['content'] = $content;
			$postfields['ajax'] = 1;
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
			$this->curlInit("single");
			$referer = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlemsgpage?msgid=&source=&count=20&t=wxm-singlechat&fromfakeid={$fakeid}&token=".$this->webtoken."&lang=zh_CN";
			$response = $this->_curlHttpObject->post($url, $postfields, $referer, $this->_cookies);
			echo '<pre>';print_r($response);
			$tmp = json_decode($response,true);
			//判断发送结果的逻辑部分
			if ('ok'==$tmp["msg"]) {
				return 1;
			}
			elseif ($tmp['ret']=="-2000")
			{
				return -1;
			}
			else
			{
				return 0;
			}
		}
		else  //登录失败返回false
		{
			return 0;
		}
	}*/
	//refer mp.weixin.qq.com/cgi-bin/singlemsgpage?msgid=&source=&count=20&t=wxm-singlechat&fromfakeid=647940102&token=980099461&lang=zh_CN
	public function send($fakeid,$content)
	{
		//判断cookie是否为空，为空的话自动执行登录
		if (file_exists($this->cookiefilepath)||true===$this->login())
		{
			$postfields = array();
			$postfields['tofakeid'] = $fakeid;
			$postfields['type'] = 1;
			$postfields['mask']= "false";
			$postfields['token']= $this->webtoken;
			$postfields['content'] = $content;
			$postfields['imgcode'] = "";
			$postfields['t'] = "ajax-response";
			//$postfields['quickreplyid'] = "ajax-response";
			$postfields['ajax'] = 1;
			//$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
			$this->curlInit("single");
			//2013/8/28$referer = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlemsgpage?msgid=&source=&count=20&t=wxm-singlechat&fromfakeid={$fakeid}&token=".$this->webtoken."&lang=zh_CN";
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&lang=zh_CN";
			$referer = $this->protocol."://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token=".$this->webtoken."&lang=zh_CN";
			$response = $this->_curlHttpObject->post($url, $postfields, $referer, $this->_cookies);
			//echo '<pre>';print_r($response);die;
			$tmp = json_decode($response,true);
			//判断发送结果的逻辑部分
			if ('ok'==$tmp["msg"]) {
				return 1;
			}
			elseif ($tmp['ret']=="-2000")
			{
				return -1;
			}
			else
			{
				return 0;
			}
		}
		else  //登录失败返回false
		{
			return 0;
		}
	}

	/**
	 * 主动群发相同消息，目前暂支持文本方式
	 * @param  array $fakeidGroup     接受微信fakeid集合数组
	 * @param  string $content 群发消息内容
	 * @return mixed  返回一个记录发送结果的数组列表
	 * 这里需要注意请求耗时问题，目前采用curl并发性请求.
	 */
	public function batSend($fakeidGroup,$content)
	{
		$queueSendArray = array();
		foreach ($fakeidGroup as $key =>$value)
		{
			$queueSendArray[] = array(
					'fakeid' => $value,
					'content' => $content
			);
		}
		return $this->doQueueSend($queueSendArray);

	}
	
	/**
	 * 主动发送队列消息，目前暂支持文本方式
	 * @param array 发送队列数组  array(array('fakeid'='','content'))
	 * @param integer $queueCount 并发数量,默认10
	 * @return mixed  返回一个记录发送结果的数组列表
	 * 这里需要注意请求耗时问题，目前采用curl并发性请求.
	 */
	public function queueSend($queueSendArray,$queueCount=10)
	{
		return $this->doQueueSend($queueSendArray,$queueCount);
	}
	
	/**
	 * 执行主动发送队列，默认并发队列数是10
	 * @param array $queueSendArray 发送队列数组  array(array('fakeid'='','content'))
	 * @param integer $queueCount 并发数量,默认10
	 * @return array  返回一个记录发送结果的数组列表
	 **/
	 private function doQueueSend($queueSendArray, $queueCount=10)
	 {
		$requestArray = array();
		foreach ($queueSendArray as $key =>$value)
		{
			$postfields = array();
			$postfields['tofakeid'] = $value['fakeid'];
			$postfields['type'] = 1;
			$postfields['error']= "false";
			$postfields['token']= $this->webtoken;
			$postfields['content'] = $value['content'];
			$postfields['ajax'] = 1;
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
			$requestArray[] = array('url'=>$url,'method'=>'post','postfields'=>$postfields,'referer'=>$this->protocol."://mp.weixin.qq.com/",'cookies'=>$this->_cookies);
		}
		function callback($result, $key){
			$tmp = json_decode($result,true);
			//判断发送结果的逻辑部分
			if ('ok'==$tmp["msg"]) {
				return 1;
			}
			elseif ($tmp['ret']=="-2000")
			{
				return -1;
			}
			else
			{
				return 0;
			}
		};
	 	$this->curlInit("roll");
		$this->_curlHttpObject->setRollLimitCount($queueCount);
		$response = $this->_curlHttpObject->setCallback("callback")->rollRequest($requestArray);
		return $response;
	}


	/**
	 * 获取用户的信息
	 * @param  string $fakeid 用户的fakeid
	 * @return mixed 如果成功获取返回数据数组，登录问题返回false，其他未知问题返回true，
	 */
	public function getContactInfo($fakeid)
	{ 
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&f=json&random=&lang=zh_CN&fakeid=".$fakeid;
		$this->curlInit("single");
		$postfields = array("token"=>$this->webtoken, "ajax"=>1,'f'=>'json');
		$response = $this->_curlHttpObject->post($url, $postfields, $this->protocol."://mp.weixin.qq.com/", $this->_cookies);
		//echo '<pre>';print_r($response);die;
		$result = json_decode($response,1);
		if($result['contact_info']['fake_id']){
			return $result['contact_info'];
		}
		elseif ($result['ret'])
		{
			return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 获取消息所附文件
	 * @param  string $msgid 消息的id
	 * @return array 如果成功获取返回下载的文件的基本信息
	 */
	public function getDownloadFile($msgid, $filepath = null)
	{
		if ($this->_cookies||true===$this->login())
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/downloadfile?token=".$this->webtoken."&msgid=$msgid&source=";
			$ch = curl_init();
			$tmpfile = $filepath?$filepath:tempnam(sys_get_temp_dir(), 'WechatFileTemp');
			$fp = @fopen($tmpfile,"w");
			if ($fp) {
				curl_setopt($ch, CURLOPT_URL, $url);
				$options = array(
						CURLOPT_RETURNTRANSFER => true,         // return web page
						CURLOPT_HEADER         => false,
						CURLOPT_FOLLOWLOCATION => true,         // follow redirects
						CURLOPT_ENCODING       => "",           // handle all encodings
						CURLOPT_USERAGENT      => "",     // who am i
						CURLOPT_AUTOREFERER    => true,         // set referer on redirect
						CURLOPT_CONNECTTIMEOUT => 10,          // timeout on connect
						CURLOPT_TIMEOUT        => 10,          // timeout on response
						CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
						CURLOPT_POST            => false,            // i am sending post data
						CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
						CURLOPT_SSL_VERIFYPEER => false,        //
// 						CURLOPT_FILE => $fp, //目标文件保存路径
// 						CURLOPT_RETURNTRANSFER => 1
				);
				curl_setopt_array($ch, $options);
				$reqCookiesString = "";
				if(is_array($this->_cookies)){
					foreach ($this->_cookies as $key => $val){
						$reqCookiesString .=  $key."=".$val."; ";
					}
					curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
				}
				$content = curl_exec($ch);
				$info = (curl_getinfo($ch));
				curl_close($ch);
				fwrite($fp, $content);
				fclose($fp);
				$result = array();
				echo filesize($tmpfile);
				if ($content && file_exists($tmpfile) && filesize($tmpfile)>0 && $info["content_type"]!="text/html") {
					
					echo "XXX";
					$result["filename"] = $tmpfile;
					$result["filesize"] = filesize($tmpfile);
					$result['filetype'] = $info["content_type"];
					return $result;
				}
			}
		}
		return false;
	}
	
	/**
	 * @name 获取公共消息列表（html）
	 * @param number $day 
	 * @param number $count 数量限制
	 * @param number $page 页数
	 * @return array|boolean
	 
https://mp.weixin.qq.com/cgi-bin/getmessage?t=wxm-message&token=326774484&lang=zh_CN&count=50
	 */
	public function getMessage($day=0, $count=100, $page=1)
	{
		if ($this->_cookies||true===$this->login())
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/getmessage?t=wxm-message&token=".$this->webtoken."&lang=zh_CN&count=100";
			$this->curlInit("single");
			$result = $this->_curlHttpObject->get($url, $this->protocol."://mp.weixin.qq.com/cgi-bin/", $this->_cookies);
			if (preg_match('%<script type="json" id="json-msgList">([\s\S]*?)</script>%', $result, $match)) {
				$tmp = json_decode($match[1], true);
				return $tmp;
			}
			else
			{
				return false;
			}
			
		}
	}	
	//https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token=684658566&lang=zh_CN  
	public function getMessage2($day=0, $count=100, $page=1)
	{
		if ($this->_cookies||true===$this->login())
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/message?t=message/list&token=".$this->webtoken."&lang=zh_CN&count={$count}";//&day={$day}
			$this->curlInit("single");
			$result = $this->_curlHttpObject->get($url, $this->protocol."://mp.weixin.qq.com/cgi-bin/", $this->_cookies);
			//echo '<pre>';print_r($result);
			if (preg_match('%msg_item\"\:([\s\S]*?)}\)%', $result, $match)) {
				$tmp = json_decode($match[1], true);
				return $tmp;
			}
			else
			{
				return false;
			}
			
		}
	}	
	
	/**
	 * @name 获取与指定用户的对话信息列表
	 * @param string $fakeid 要获取指定用户消息的fakeid（必选）
	 * @param number $lastmsgid 最早消息的id
	 * @param number $createtime 最早消息的时间戳
	 * @param string $lastmsgfromfakeid 消息最后来源
	 */
	public function getSingleMessage($fakeid, $lastmsgid=1, $createtime=0, $lastmsgfromfakeid=null)
	{
		if (!empty($this->_cookies))
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlemsgpage?t=ajax-single-getnewmsg";
			$this->curlInit("single");
			$postfield = array();
			$postfield['createtime']=$createtime;
			$postfield['fromfakeid']=$fakeid;
			$postfield['opcode']=1;
			$postfield['lastmsgfromfakeid']=$lastmsgfromfakeid;
			$postfield['lastmsgid']=$lastmsgid;
			$postfield['token']=$this->webtoken;
			$postfield['ajax']=1;
			$result = $this->_curlHttpObject->post($url, $postfield, $this->protocol."://mp.weixin.qq.com/",$this->_cookies);
			if ($result)
			{
				return json_decode($result, true);
			}
		}
		return false;
	}
	
	/**
	 * @name 获取公共消息时间线列表
	 * @param number $day 获取几日内的消息参数（0:当天;1:昨天;2:前天;3:最近5天.默认0）
	 * @param number $count 获取消息数量限制.默认100
	 * @param number $offset 获取消息开始位置,差不多是偏移分页的样子.默认是0
	 * @param number $msgid 最后消息的id 默认为9999999999(意味着全部消息的意思)
	 * @param boolean $timeline 这个参数决定了上面的$day是否有效，设置成false,直接按时间线排列的全部消息
	 * @return mixed|boolean
	 
	 */
	public function getMessageAjax($day=0, $count=100, $offset=1, $msgid=999999999, $timeline=1)
	{
		if ($this->_cookies||true===$this->login())
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/getmessage?t=ajax-message&lang=zh_CN&count=$count&timeline=".($timeline?"1":"")."&day=$day&star=&frommsgid=$msgid&cgi=getmessage&offset=".intval($offset);
			$this->curlInit("single");
			$postfieldArray = array(
					"token"	=>	$this->webtoken,
					"ajax"	=>	1
			);
			$header = array(
					"X-Requested-With" => "XMLHttpRequest"
			);
			$result = $this->_curlHttpObject->post($url, $postfieldArray, $this->protocol."://mp.weixin.qq.com/cgi-bin/", $this->_cookies, $header);
			if ($result) {
				return json_decode($result, true);
			}
			else
			{
				return false;
			}
			
		}
	}
	//https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token=684658566&lang=zh_CN  cgi-bin/getmessage?t=wxm-message
	public function getMessageAjax2($day=0, $count=100, $offset=1, $msgid=999999999, $timeline=1)
	{
		if ($this->_cookies||true===$this->login())
		{
			$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/getnewmsgnum";
			$url=$this->protocol."://mp.weixin.qq.com/cgi-bin/message?t=ajax-message&lang=zh_CN&count=$count&day=$day&token=".$this->webtoken;
			$this->curlInit("single");
			$postfieldArray = array(
					"token"	=>	$this->webtoken,
					//'t'=>'ajax-response',
					"ajax"	=>	1
			);
			$header = array(
					"X-Requested-With" => "XMLHttpRequest"
			);
			$refer=$this->protocol."://mp.weixin.qq.com/cgi-bin/message?t=message/list&lang=zh_CN&count=$count&day=$day&token=".$this->webtoken;
			$refer = $this->protocol."://mp.weixin.qq.com/";
			$result = $this->_curlHttpObject->post($url, $postfieldArray, $refer, $this->_cookies, $header);
			echo '<pre>';print_r($result);die;
			if ($result) {
				return json_decode($result, true);
			}
			else
			{
				return false;
			}
			
		}
	}
	/**
	 * @name 得到确定的某条消息(因为微信两个时间戳有时不同, 所以这个接口效果不完美)
	 * @param string $datetime 
	 */
	public function getOneMessage($datetime=NULL, $type=NULL, $openid=NULL)
	{
		if (!$type) {
			$type = $this->getRevType();
		}
		if (!$datetime) {
			$datetime = $this->getRevCtime();
		}
		if (!$openid) {
			$openid = $this->getRevFrom();
		}
		
// 		file_put_contents("log.txt", "\n********".$this->getRevCtime()."*********\n",FILE_APPEND);
		$typeList = array(Wechat::MSGTYPE_TEXT=>1, Wechat::MSGTYPE_IMAGE=>2, Wechat::MSGTYPE_VOICE=>3, Wechat::MSGTYPE_VIDEO=>4, Wechat::MSGTYPE_LOCATION=>1);

		if ($openid && method_exists($this->_wechatcallbackFuns, "getAscStatusByOpenid") && is_array($userInfo = $this->_wechatcallbackFuns->getAscStatusByOpenid($openid)))
		{
// 			file_put_contents("log.txt", "A\n".serialize($userInfo),FILE_APPEND);
			if ($userInfo['fakeid'])
			{
// 				file_put_contents("log.txt", "B\n",FILE_APPEND);
				$singleMessage = $this->getSingleMessage($userInfo['fakeid'], 1, (string)(intval($datetime)-10));
				$singleMessageCount = count($singleMessage);
// 				file_put_contents("log.txt", (string)(intval($datetime)-0)."\n",FILE_APPEND);
				if ($singleMessageCount==1)
				{
// 					file_put_contents("log.txt", "\$singleMessageCount:$singleMessageCount\n",FILE_APPEND);
					if( $userInfo['fakeid']==$singleMessage[0]['fakeId'] && (empty($type) || $singleMessage[0]['type']==$typeList[$type]) )
					{
// 						file_put_contents("log.txt", serialize($singleMessage[0])."\n",FILE_APPEND);
						return $singleMessage[0];
					}
				}
				elseif ($singleMessageCount>1)//TODO 当前进度在这
				{
					for($i=0;$i<$singleMessageCount;$i++)
					{
						if ( $userInfo['fakeid']==$singleMessage[0]['fakeId'] && $datetime == $singleMessage[$i]['dateTime'])
						{
// 							file_put_contents("log.txt", serialize($singleMessage[$i])."\n",FILE_APPEND);
							return $singleMessage[$i];
						}
						
					}
// 					file_put_contents("log.txt", $singleMessageCount."\n",FILE_APPEND);

					for($i=0;$i<$singleMessageCount;$i++)
					{
						if( $userInfo['fakeid']==$singleMessage[$i]['fakeId'] && $singleMessage[$i]['type']==$typeList[$type])
						{
							
// 							file_put_contents("log.txt", serialize($singleMessage[$i])."\n",FILE_APPEND);
							return $singleMessage[$i];
						}
					}
				}
				else
				{
// 					file_put_contents("log.txt", "False\n",FILE_APPEND);
					return FALSE;
				}
			}
		}
		//获取40条最新的公共消息列表
		$messageList = $this->getMessageAjax(0, 40, 0, 99999999+intval(mt_rand(0, 99999)));
		$messageListCount = count($messageList);
		if ($messageListCount>0) {
			$matchMessageList = array();
			for($i=0;$i<$messageListCount;$i++)
			{
				if (($datetime?$datetime:$this->getRevCtime()) == $messageList[$i]['dateTime'] && ($type?($messageList[$i]['type']==$typeList[$type]):true))
				{
					$matchMessageList[] = $messageList[$i];
				}
				
			}
			if (count($matchMessageList)==1) {
				return $matchMessageList[0];
			}
		}
		return FALSE;
		
	}
    //TODO Working...... 待解决图文消息添加后获取fid问题。
    /**
     * 通过微信号直接发送图文
     * @param $wechatno 微信号
     * @param $newsArray 消息数组，格式:<p>array(
     * array('title'=>'','digest'=>'','author'=>'','image'=>'','content'=>'','sourceurl'=>''),
     * array('title'=>'','digest'=>'','author'=>'','image'=>'','content'=>'','sourceurl'=>''),
     * )</p>
     * @param string $session 会话通道
     * @return bool
     */
    public function sendPreNews($wechatno, $newsArray, $session=null)
    {
        //$this->processSession($session);
        $postfields = array();
        $newsArray = array_values($newsArray);
        if(count($newsArray) < 1)
        {
            return false;
        }
        $i = 0; //完备消息数量
        foreach($newsArray as $value)
        {
            if(preg_match('/^[0-9]{8,9}$/', $value['image']))
            {
                $postfields['fileid'.$i] = $value['image'];
            }
            elseif($fid = $this->mediaUpload($value['image'], Wechat::MSGTYPE_IMAGE,$session))
            {
                $postfields['fileid'.$i] = $fid;
            }
            else
            {
                continue;
            }
            $postfields['title'.$i] = $value['title'];
            $postfields['digest'.$i] = $value['desc']?$value['desc']:"";
            $postfields['author'.$i] = $value['author']?$value['author']:"";
            $postfields['content'.$i] = $value['content'];
            $postfields['sourceurl'.$i] = $value['sourceurl']?$value['sourceurl']:"";
            $i += 1;
        }
        if($i==0)
        {
            return false;
        }
        $postfields['count'] = $i;
        $postfields['error'] = 'false';
        $postfields['AppMsgId'] = "";
        $postfields['token'] = $this->webtoken;
        $postfields['ajax'] = 1;
        $postfields['preusername'] = $wechatno;
        $url = $this->protocol."://mp.weixin.qq.com/cgi-bin/operate_appmsg?sub=preview&t=ajax-appmsg-preview";
        $this->curlInit("single");
        $result = $this->_curlHttpObject->post($url, $postfields, $this->_referer, $this->getCookies());
        $result_json_decode = json_decode($result, true);
		echo '<pre>'.'发送';print_r($result_json_decode);
        if($result_json_decode && 'OK'==$result_json_decode['appMsgId'])
        {
            return $result_json_decode['appMsgId'];
        }
        else
        {
            return false;
        }
    }

	public function sendNews($id,$msgid)
	{
		 $this->curlInit("single");
		$post = array();
		$post['tofakeid'] = $id;
		$post['type'] = 10;
		$post['token'] = $this->webtoken;
		$post['fid'] = $msgid;
		$post['appmsgid'] = $msgid;
		$post['error'] = 'false';
		$post['ajax'] = 1;
        $referer = "https://mp.weixin.qq.com/cgi-bin/singlemsgpage?fromfakeid={$id}&msgid=&source=&count=20&t=wxm-singlechat&lang=zh_CN";
		$url = "https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
		$result = $this->_curlHttpObject->post($url, $post, $referer, $this->getCookies());
		return $result;
	}

	public function uploadFile($filepath,$type=2) {echo 'go';
		$this->curlInit("single");
		$referer = "http://mp.weixin.qq.com/cgi-bin/indexpage?t=wxm-upload&lang=zh_CN&type=2&formId=1";
		$t = time().strval(mt_rand(100,999));
		$post = array('formId'=>'');
		$postfile = array('uploadfile'=>$filepath);
		$submit = "http://mp.weixin.qq.com/cgi-bin/uploadmaterial?cgi=uploadmaterial&type=$type&token=".$this->webtoken."&t=iframe-uploadfile&lang=zh_CN&formId=	file_from_".$t;
		$result = $this->_curlHttpObject->post($submit, $postfile, $referer, $this->getCookies());
		print_r($result);
		preg_match("/formId,.*?\'(\d+)\'/",$tmp,$result);
		if (isset($matches[1])) {
			return $matches[1];
		}
		return false;
	}
	/**
	 * 获取图文信息列表
	 * @param $page 页码(从0开始)
	 * @param $pagesize 每页大小
	 * @return array
	 */
	public function getNewsList($page,$pagesize=10) {
		$this->curlInit("single");
		$t = time().strval(mt_rand(100,999));
		$type=10;
		$post = array();
		$post['token'] = $this->webtoken;
		$post['ajax'] = 1;
		$referer = "https://mp.weixin.qq.com/cgi-bin/indexpage?t=wxm-upload&lang=zh_CN&type=2&formId=1";
		$submit = "https://mp.weixin.qq.com/cgi-bin/operate_appmsg?token=".$this->webtoken."&lang=zh_CN&sub=list&t=ajax-appmsgs-fileselect&type=$type&r=".str_replace(' ','',microtime())."&pageIdx=$page&pagesize=$pagesize&subtype=3&formid=file_from_".$t;
		$response = $this->_curlHttpObject->post($submit, $postfields, $referer, $this->_cookies);
		
		return json_decode($response,true);
	}

	 
/**
     *  上传声音、图片、视频媒体消息
     * @param $filepath 媒体文件路径
     * @param $type 上传媒体类型
     * @param string $session 会话通道，默认为: default
     * @return bool|string  成功返回媒体fid，失败返回false
     */
    public function mediaUpload($filepath, $type, $session=null)
    {
        if(file_exists($filepath))
        {
            //$this->processSession($session);
            $positiveType = $this->getPositiveMsgType($type);
            $url = $this->protocol."://mp.weixin.qq.com/cgi-bin/uploadmaterial?cgi=uploadmaterial&type=$positiveType&token=$this->webtoken&t=iframe-uploadfile&lang=zh_CN&formId=file_from_".time();
            //        $url = "http://api.fzuer.com/weixin/fzuer/index.php?m=Request&a=index";
            $contentTypeList = array('jpg'=>'image/jpeg', 'png'=>'image/png', 'bmp'=>'image/bmp', 'jpeg'=>'image/jpeg', 'gif'=>'image/gif', 'mp3'=>'audio/mpeg3', 'wma'=>'audio/x-ms-wma', 'wav'=>'audio/wav', 'amr'=>'audio/amr', 'rm'=>'application/vnd.rn-realmedia', 'rmvb'=>'application/vnd.rn-realmedia-vbr', 'wmv'=>'video/x-ms-wmv', 'avi'=>'video/avi', 'mpg'=>'video/mpeg', 'mpeg'=>'video/mpeg', 'mp4'=>'video/mpeg4' );
            $fileSuffix = substr($filepath, strrpos($filepath, ".")+1);
            $uploadContentType = $contentTypeList[$fileSuffix];
            $postfields = array("uploadfile"=>"@".$filepath.";type=$uploadContentType");
            $ch = curl_init();
            $options = array(
                CURLOPT_RETURNTRANSFER => true,         // return web page
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,         // follow redirects
                CURLOPT_ENCODING       => "",           // handle all encodings
                CURLOPT_USERAGENT      => "",     // who am i
                CURLOPT_AUTOREFERER    => true,         // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 10,          // timeout on connect
                CURLOPT_TIMEOUT        => 10,          // timeout on response
                CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
                CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
                CURLOPT_SSL_VERIFYPEER => false,        //
            );
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
            $reqCookiesString = "";
            if(is_array($this->getCookies($session)))
            {
                foreach ($this->getCookies($session) as $key => $val)
                {
                    $reqCookiesString .=  $key."=".$val."; ";
                }
                curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
            }
            curl_setopt_array($ch, $options);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            $result = curl_exec($ch);
            //        var_dump($result);
            if(preg_match('%formId,[\s]{0,4}\'([0-9]*?)\'\\)%', $result,$resultMatch))
            {
                return $resultMatch[1];
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
	/**
	 * @name 得到指定分组的用户列表
	 * @param number $groupid
	 * @return Ambigous <boolean, string, mixed>
	 https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=10&pageidx=0&type=0&groupid=0&token=684658566&lang=zh_CN
	 */
	public function getfriendlist($groupid=0, $pagesize=100)
	{
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/contactmanagepage?t=user/index&token=$this->webtoken&t=wxm-friend&pagesize=$pagesize&groupid=$groupid";
		$referer = $this->protocol."://mp.weixin.qq.com/";
		$this->curlInit("single");
		$response = $this->_curlHttpObject->get($url, $referer, $this->_cookies);

		$tmp = "";
		if (preg_match('%<script id="json-friendList" type="json/text">([\s\S]*?)</script>%', $response, $match)) {
			$tmp = json_decode($match[1], true);
		}
		return empty($tmp)?false:$tmp;
	
	}
	//新接口 https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=10&pageidx=0&type=0&groupid=0&token=684658566&lang=zh_CN
	public function getfriendlist2($groupid=0, $pagesize=100)
	{
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pageidx=0&type=0&token=$this->webtoken&pagesize=$pagesize&groupid=$groupid";
		$referer = $this->protocol."://mp.weixin.qq.com/";
		$this->curlInit("single");
		$response = $this->_curlHttpObject->get($url, $referer, $this->_cookies);
//echo '<pre>';print_r($response);
		$tmp = "";
		if (preg_match('%contacts\"\:([\s\S]*?)(?=}\))%', $response, $match)) {
			$tmp = json_decode($match[1], true);
		}
		return empty($tmp)?false:$tmp;
	
	}
	
	/**
	 * 获取用户的fakeid
	 * @param callback $callback 处理匹配结果的回调函数，剥离出来方便大家自己的实现自己的逻辑，大致就是循环的查找，并写入数据库之类的
	 *
	 * 下面是示例：
	 * 		function callback($result, $key, $request, $otherCallbackArg){
	 * 			$reruen_tmp = false;
	 * 			dump($result);
	 * 			foreach ($otherCallbackArg['data'] as $data_key => $data_value)
	 	* 			{
	 * 				if(false !== strpos($result, substr(md5($data_value['openid']), 0, 16)))
	 	* 				{
	 *     	    		$subscribeusersModel = D("Subscribeusers");
	 *         	    	$condition['openid'] = $data_value['openid'];
	 *             	    $data = $subscribeusersModel->where($condition)->save(array('fakeid'=>$request['postfields']['fromfakeid']));
	 *                 	$otherCallbackArg['wechatObj']->putIntoGroup($request['postfields']['fromfakeid'], 101);
	 *                  $reruen_tmp = $data_value['openid'];
	 *                  break;
	 *               }
	 *          }
	 *          return $reruen_tmp;
	 *     };
	 *     print_r($this->wechatObj->getfakeid("callback"));
	 */
	/* public function getfakeid($callback)
	{
		//接下来是数据库的访问，大家可以按照自己的环境修改，接下来会通过回调函数解决。
		$subscribeusersModel = D("Subscribeusers");
		$data = $subscribeusersModel->where(' `fakeid` IS NULL and `unsubscribed`=0')->select();
		//$data 是当前fakeid为空的用户的列表数组
		if (!is_array($data))
		{
			die("none data");
		}
		$unfriendList = $this->getfriendlist(0);
		if (!$unfriendList){
			die("none friendlist");
		}
		$requestArray = array();
		foreach ($unfriendList as $key => $value)
		{
			// 			$requestArray[$key]['postfields']['createtime'] = time()-60000;
			$requestArray[$key]['postfields']['fromfakeid'] = $value['fakeId'];
			$requestArray[$key]['postfields']['opcode'] = 1;
			$requestArray[$key]['postfields']['token'] = $this->webtoken;
			$requestArray[$key]['postfields']['ajax'] = 1;
			$requestArray[$key]['referer'] = $this->protocol."://mp.weixin.qq.com/";
			$requestArray[$key]['cookiefilepath'] = $this->cookiefilepath;
			$requestArray[$key]['method'] = "post";
			$requestArray[$key]['url'] = $this->protocol."://mp.weixin.qq.com/cgi-bin/singlemsgpage?t=ajax-single-getnewmsg";
		}
		$this->curlInit("roll");
		$rollingCurlObj->setOtherCallbackArg(array('data'=>$data, 'wechatObj'=>$this));
		$response = $rollingCurlObj->setCallback($callback)->request($requestArray);
		// 		dump($response);
	} */
	
	/**
	 * 将用户放入制定的分组
	 * @param array $fakeidsList
	 * @param string $groupid
	 * @return boolean 放入是否成功
	 */
	public function putIntoGroup($fakeidsList, $groupid)
	{
		$fakeidsListString = "";
		if(is_array($fakeidsList))
		{
			foreach ($fakeidsList as $value)
			{
				$fakeidsListString .= $value."|";
			}
		}
		else
		{
			$fakeidsListString = $fakeidsList;
		}
		$postfields['contacttype'] = $groupid;
		$postfields['tofakeidlist'] = $fakeidsListString;
		$postfields['token'] = $this->webtoken;
		$postfields['ajax'] = 1;
		$referer = $this->protocol."://mp.weixin.qq.com/";
		$url = $this->protocol."://mp.weixin.qq.com/cgi-bin/modifycontacts?action=modifycontacts&t=ajax-putinto-group";
		$this->curlInit("roll");
		$response = $this->_wechatcallbackFuns->post($url, $postfields, $referer, $this->_cookies);
		$tmp = json_decode($response, true);
		$result = $tmp['ret']=="0"&&!empty($tmp)?true:false;
		return $result;
	}
	
	public function setWechatToolFun($class){
		if (is_string($class)) {
			$toolObj = new $class;
			if (is_object($toolObj)) {
				$this->_wechatcallbackFuns = $toolObj;
				return $this;
			}
			else{
				return false;
			}
		}
		elseif (is_object($class)){
			$this->_wechatcallbackFuns = $class;
			return $this;
		}
		else{
			return false;
		}
	}
	/**
	 * @return the $wechatOptions
	 */
	public function getCookies() {
		return $this->_wechatcallbackFuns->getCookies();
	}

	/**
	 * @return the $wechatOptions
	 */
	public function getToken() {
		return $this->_wechatcallbackFuns->getToken();
	}
	/**
	 * @return the $wechatOptions
	 */
	public function getWechatOptions() {
		return $this->wechatOptions;
	}

	/**
	 * 设置微信配置信息
	 * @param multitype:string  $wechatOptions
	 */
	public function setWechatOptions($wechatOptions) {
		$this->wechatOptions = array_merge($this->wechatOptions, $wechatOptions);
		return $this;
	}

	/**
	 * 设置cookie保存位置
	 * @param string $cookies cookie
	 */
	public function setCookies($cookies) {
		$this->_wechatcallbackFuns->setCookies($cookies);
		return $this;
	}
	/**
	 * 设置token保存
	 * @param string $token token
	 */
	public function setToken($token) {
		$this->_wechatcallbackFuns->setToken($token);
		return $this;
	}

	/**
	 * @param boolean $debug
	 */
	public function setDebug($debug) {
		$this->debug = $debug;
		return $this;
	}

	/**
	 * 设置是否自动附带发送openid开关,default：False
	 * @param boolean $autosendopenid
	 * @return Wechat
	 */
	public function setAutoSendOpenidSwitch($autosendopenid=FALSE) {
	$this->_autosendopenid = $autosendopenid;
	return $this;
}

	/**
	 * @设置被动关联动作开关
	 * @param boolean $switch 开关
	 * @param boolean $detailSwitch 是否获取用户详细信息开关
	 * @return Wechat
	 */
	public function setPassiveAscSwitch($switch, $detailSwitch=false) {
	$this->_passiveAssociationSwitch = $switch;
	$this->_passiveAscGetDetailSwitch = $detailSwitch;
	return $this;
}
	
	
}




/**
 * Rolling Curl Request Class
* @author Ligboy (ligboy@gamil.com)
* @copyright
* @example
*
*
*/
class CurlHttp {


	/* 单线程请求设置项 */

	/* 并发请求设置项 */
	private $limitCount = 10; //并发请求数量
	public $returninfoswitch = false;  //是否返回请求信息，开启后单项请求返回结果为:array('info'=>请求信息, 'result'=>返回内容, 'error'=>错误信息)

	//私有属性
	private $singlequeue = null;
	private $rollqueue = null;
	private $_requstItems = null;
	private $_callback = null;
	private $_result;
	private $_referer = null;
	private $_cookies = array();
	private $_resheader;
	private $_reqheader = array();
	private $_resurl;
	private $_redirect_url;
	private $referer;

	private $_singleoptions = array(
			CURLOPT_RETURNTRANSFER => true,         // return web page
			CURLOPT_HEADER         => true,        // don't return headers
// 			CURLOPT_FOLLOWLOCATION => true,         // follow redirects
			CURLOPT_NOSIGNAL      =>true,
			CURLOPT_ENCODING       => "",           // handle all encodings
			CURLOPT_USERAGENT      => "",           // who am i
			CURLOPT_AUTOREFERER    => true,         // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
			CURLOPT_TIMEOUT        => 120,          // timeout on response
			CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
			CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false,        //
	);
	private $_rolloptions = array(
			CURLOPT_RETURNTRANSFER => true,         // return web page
			CURLOPT_HEADER         => true,        // don't return headers
// 			CURLOPT_FOLLOWLOCATION => true,         // follow redirects
			CURLOPT_NOSIGNAL      =>true,
			CURLOPT_ENCODING       => "",           // handle all encodings
			CURLOPT_USERAGENT      => "",           // who am i
			CURLOPT_AUTOREFERER    => true,         // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
			CURLOPT_TIMEOUT        => 120,          // timeout on response
			CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
			CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false,        //
	);
		

	function singleInit($options = array()) {
		if (!$this->singlequeue) {
			$this->singlequeue = curl_init();
		}
		if ($options) {
			$this->_singleoptions = array_merge($this->_singleoptions, $options);
		}
	}
	function rollInit($options = array()) {
		if(!$this->rollqueue){
			$this->rollqueue = curl_multi_init();
		}
		if ($options) {
			$this->_rolloptions = array_merge($this->_rolloptions, $options);
		}
	}
	/**
	 * @name 返回Header数组
	 * @param resource $ch
	 * @return string
	 */
	private function getResRawHeader($ch, $result) {
		$ch_info = curl_getinfo($ch);
		$header_size = $ch_info["header_size"];
		$rawheader = substr($result, 0, $ch_info['header_size']);
		return $rawheader;
	}
	/**
	 * @name 返回Header数组
	 * @param resource $ch
	 * @return string
	 */
	private function getResHeader($ch, $result) {
		$header = array();
		$rawheader = $this->getResRawHeader($ch, $result);
		if(preg_match_all('/([^:\s]+): (.*)/i', $rawheader, $header_match)){
			for($i=0;$i<count($header_match[0]);$i++){
				$header[$header_match[1][$i]] = $header_match[2][$i];
			}
		}
		return $header;
	}

	/**
	 * @name 返回网页主体内容
	 * @param resource $ch
	 * @return string 网页主体内容
	 */
	private function getResBody($ch, $result) {
		$ch_info = curl_getinfo($ch);
		$body = substr($result, -$ch_info['download_content_length']);
		return $body;
	}

	/**
	 * @name 返回网页主体内容
	 * @param resource $ch
	 * @return array 网页主体内容
	 */
	private function getResCookies($ch, $result) {
		$rawheader = $this->getResRawHeader($ch, $result);
		$cookies = array();
		if(preg_match_all('/Set-Cookie:(?:\s*)([^=]*?)=([^\;]*?);/i', $rawheader, $cookie_match)){
			for($i=0;$i<count($cookie_match[0]);$i++){
				$cookies[$cookie_match[1][$i]] = $cookie_match[2][$i];
			}
		}
		return $cookies;
	}

	private function setReqCookies($ch, $reqcookies = array()) {
		$reqCookiesString = "";
		if(!empty($reqcookies)){
			if(is_array($reqcookies)){
				foreach ($reqcookies as $key => $val){
					$reqCookiesString .=  $key."=".$val."; ";
				}
				curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
			}
		}elseif(!empty($this->_cookies)) {
			foreach ($this->_cookies as $key => $val){
				$reqCookiesString .=  $key."=".$val."; ";
			}
			curl_setopt($ch, CURLOPT_COOKIE, $reqCookiesString);
		}
	}
	private function setResCookies($ch) {
		if(!empty($reqcookies)&&is_array($reqcookies)){
			$this->_cookies = array_merge($this->_cookies, $reqcookies);
		}
	}

	/**
	 * @param unknown $url
	 * @param mixed $postfields
	 * @param string $referer
	 * @param array $reqcookies
	 * @return unknown
	 */
	function post($url, $postfields=null, $referer=null, $reqcookies=null, $reqheader=array())
	{
		$this->singlequeue = curl_init($url);
		$options = array(
				CURLOPT_RETURNTRANSFER => true,         // return web page
				CURLOPT_HEADER         => true,        // don't return headers
// 				CURLOPT_FOLLOWLOCATION => true,         // follow redirects
				CURLOPT_ENCODING       => "",           // handle all encodings
				CURLOPT_USERAGENT      => "",     // who am i
				CURLOPT_AUTOREFERER    => true,         // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
				CURLOPT_TIMEOUT        => 120,          // timeout on response
				CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
				CURLOPT_POST            => true,            // i am sending post data
				CURLOPT_POSTFIELDS     => $postfields,    // this are my post vars
				CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
				CURLOPT_SSL_VERIFYPEER => false,        //
		);
		curl_setopt_array($this->singlequeue, $options);
		if($referer){
			curl_setopt($this->singlequeue, CURLOPT_REFERER, $referer);
		}
		elseif ($this->referer){
			curl_setopt($this->singlequeue, CURLOPT_REFERER, $this->referer);
		}
		
		$this->setReqheader($this->singlequeue, $reqheader);
		$this->setReqCookies($this->singlequeue, $reqcookies);

		$result = curl_exec($this->singlequeue);
		$resCookies = $this->getResCookies($this->singlequeue, $result);;
		if (is_array($resCookies)&&!empty($resCookies)) {
			$this->_cookies = array_merge($this->_cookies ,$resCookies);
		}
		$resHeader = $this->getResHeader($this->singlequeue, $result);
		if (is_array($resHeader)&&!empty($resHeader)) {
			$this->_resheader = $resHeader;
		}
		$this->_result = $this->getResBody($this->singlequeue, $result);
		curl_close($this->singlequeue);
		$this->singlequeue = null;
		return $this->_result;
	}
	/**
	 * @param unknown $url
	 * @param unknown $postfields
	 * @param unknown $referer
	 * @return unknown
	 */
	function get($url, $referer=null, $reqcookies=null, $reqheader=array())
	{
		$this->singlequeue = curl_init($url);
		$options = array(
				CURLOPT_RETURNTRANSFER => true,         // return web page
				CURLOPT_HEADER         => true,        // don't return headers
// 				CURLOPT_FOLLOWLOCATION => true,         // follow redirects
				CURLOPT_ENCODING       => "",           // handle all encodings
				CURLOPT_USERAGENT      => "",     // who am i
				CURLOPT_AUTOREFERER    => true,         // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
				CURLOPT_TIMEOUT        => 120,          // timeout on response
				CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
				CURLOPT_POST            => false,            // i am sending post data
				CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
				CURLOPT_SSL_VERIFYPEER => false,        //
				CURLOPT_REFERER        =>$referer,
		);
		curl_setopt_array($this->singlequeue, $options);
		if($referer){
			curl_setopt($this->singlequeue, CURLOPT_REFERER, $referer);
		}
		elseif ($this->referer){
			curl_setopt($this->singlequeue, CURLOPT_REFERER, $this->referer);
		}
		$this->setReqheader($this->singlequeue, $reqheader);
		$this->setReqCookies($this->singlequeue, $reqcookies);

		$result = curl_exec($this->singlequeue);
		$resCookies = $this->getResCookies($this->singlequeue, $result);
		if (is_array($resCookies)&&!empty($resCookies)) {
			$this->_cookies = array_merge($this->_cookies ,$resCookies);
		}
		$resHeader = $this->getResHeader($this->singlequeue, $result);
		if (is_array($resHeader)) {
			$this->_resheader = $resHeader;
		}
		$this->_result = $this->getResBody($this->singlequeue, $result);
		curl_close($this->singlequeue);
		$this->singlequeue = null;
		return $this->_result;
	}
	/**
	 * 并发行的curl方法
	 * @param unknown $requestArray
	 * @param string $callback
	 * @return multitype:multitype:
	 */
	function rollRequest($requestArray, $callback="")
	{
		$this->_requstItems = $requestArray;
		$requestArrayKeys = array_keys($requestArray);
		/* 		$requestArray = array(
		 array(
		 		'url' => "",
		 		'method' => "post",
		 		'postfields' => array(),
		 		'cookies' => "",
		 		'referer' => "",
		 ),
				array(
						'url' => "",
						'postfields' => array(),
						'cookies' => "",
						'referer' => "",
				),
		); */
		$this->rollqueue = curl_multi_init();
		$map = array();
		for ($i=0;$i<$this->limitCount && !empty($requestArrayKeys);$i++)
		{
			$keyvalue = array_shift($requestArrayKeys);
			$this->addToRollQueue( $requestArray, $keyvalue, $map );

		}

		$responses = array();
		do {
			while (($code = curl_multi_exec($this->rollqueue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

			if ($code != CURLM_OK) { break; }

			// 找到刚刚完成的任务句柄
			while ($done = curl_multi_info_read($this->rollqueue)) {
				// 处理当前句柄的信息、错误、和返回内容
				$info = curl_getinfo($done['handle']);
				$error = curl_error($done['handle']);
				if ($this->_callback)
				{
					//调用callback函数处理当前句柄的返回内容，callback函数参数有：（返回内容, 队列id）
					$result = call_user_func($this->_callback, curl_multi_getcontent($done['handle']), $map[(string) $done['handle']]);
				}
				else
				{
					//如果callback为空，直接返回内容
					$result = curl_multi_getcontent($done['handle']);
				}
				if ($this->returninfoswitch) {
					$responses[$map[(string) $done['handle']]] = compact('info', 'error', 'result');
				}
				else
				{
					$responses[$map[(string) $done['handle']]] = $result;
				}

				// 从队列里移除上面完成处理的句柄
				curl_multi_remove_handle($this->rollqueue, $done['handle']);
				curl_close($done['handle']);
				if (!empty($requestArrayKeys))
				{
					$addkey = array_shift($requestArrayKeys);
					$this->addToRollQueue ( $requestArray, $addkey, $map );
				}
			}

			// Block for data in / output; error handling is done by curl_multi_exec
			if ($active > 0) {
				curl_multi_select($this->rollqueue, 0.5);
			}

		} while ($active);

		curl_multi_close($this->rollqueue);
		$this->rollqueue = null;
		return $responses;
	}
	/**
	 * @param requestArray
	 * @param map
	 * @param keyvalue
	 */
	private function addToRollQueue($requestArray, $keyvalue, &$map) {
		$ch = curl_init();
		curl_setopt_array($ch, $this->_rolloptions);
		//检查提交方式，并设置对应的设置，为空的话默认采用get方式
		if ("post" === $requestArray[$keyvalue]['method'])
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestArray[$keyvalue]['postfields']);
		}
		else
		{
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}

		
		if($requestArray[$keyvalue]['referer']){
			curl_setopt($ch, CURLOPT_REFERER, $requestArray[$keyvalue]['referer']);
		}
		elseif ($this->referer){
			curl_setopt($ch, CURLOPT_REFERER, $this->referer);
		}
		$this->setReqheader($ch, $requestArray[$keyvalue]['header']);
		//cookies设置
		$this->setReqCookies($ch, $requestArray[$keyvalue]['cookies']);

		curl_setopt($ch, CURLOPT_URL, $requestArray[$keyvalue]['url']);
		curl_setopt($ch, CURLOPT_REFERER, $requestArray[$keyvalue]['referer']);
		curl_multi_add_handle($this->rollqueue, $ch);
		$map[(string) $ch] = $keyvalue;
	}

	/**
	 * 返回当前并行数
	 * @return the $limitCount
	 */
	public function getRollLimitCount() {
		return $this->limitCount;
	}

	/**
	 * 设置并发性请求数量
	 * @param number $limitCount
	 */
	public function setRollLimitCount($limitCount) {
		$this->limitCount = $limitCount;
		return $this;
	}

	/**
	 * 设置回调函数
	 * @param field_type $_callback
	 */
	public function setCallback($_callback) {
		$this->_callback = $_callback;
		return $this;
	}

	public function getResult() {
		return $this->_result;
	}

	public function getRawHeader() {
		return $this->_resheader;
	}

	public function getCookies() {
		return $this->_cookies;
	}

	public function setCookies($_cookies) {
		$this->_cookies = $_cookies;
		return $this;
	}

	/**
 * @param unknown_type $reqheader
 */
public function setHeader($header) {
	$this->_reqheader = array_merge($this->_reqheader, $header);
	return $this;
}
	/**
 * @param unknown_type $reqheader
 */
private function setReqheader($ch, $reqheader) {
	$reqheader = array_merge($this->_reqheader, $reqheader);
	if (is_array($reqheader)) {
		$rawReqHeader = array();
		foreach ($reqheader as $key => $value){
			$rawReqHeader[] = "$key: $value";
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $rawReqHeader);
		$this->_reqheader = array();
	}
	return $this;
}
	



}
