<?php
/**
 * 手机短信类
 * @author wxb 
 * @date 2015/5/21
 */
namespace Common\Org\Util;
class MobileMessage {
	private $username;
	private $password;
	private $userid;
	private $debug = false;
 	private $getWay = 'http://114.215.136.186:9008/servlet/UserServiceAPI';
 	private $getWayNew = 'http://115.28.50.135:8888/sms.aspx';
   function __construct(){
		$this->username = C('JT_CONFIG_WEB_SORT_MESSAGE_ACCOUNT');		
		$this->password = C('JT_CONFIG_WEB_SORT_MESSAGE_PASSWORD');		
		$this->userid = C('JT_CONFIG_WEB_SORT_MESSAGE_UID');	
	} 
	/**
	 * 通过用户id发送短信
	 * @param array|str $uid 用户id
	 * @param 短信模板变量 $arr
	 * @param 短信模板代码 $messCode
	 */
	function sendMessByUid(array $uid,$arr,$messCode = 'bind_mobile') {
		 //获取手机号
		 $where['uid'] = ['in',$uid];
		 $mobile = M('user')->where($where)->getField('mobile',true);
		 $mobile = implode(',',$mobile);
		 //获取发送内容
		 $content = getTempContent($messCode, 1, $arr);
		 //发送短信
		return $this->mobileSend($mobile,$content);
	}
	/**
	 * 通过用户电话发送短信
	 * @param array|str $mobile 电话号 ,分隔的字窜或者数组
	 * @param 短信模板变量 $arr
	 * @param 短信模板代码 $messCode
	 */
	function sendMessByTel($mobile,$arr,$messCode = 'bind_mobile') {
		//获取手机号
		if(is_array($mobile)){
			$mobile = implode(',', $mobile);
		}
		//获取发送内容
		$content = getTempContent($messCode, 1, $arr);
        $content = trim(html_entity_decode( strip_tags ( $content ) ));
		//发送短信
		if(!$this->mobileSend($mobile,$content)){	//如果第一次发送失败，则在发送一次
			return $this->mobileSend($mobile,$content);
		}else{	//
			return true;
		}
	}
	/**
	 * 发送手机短信
	 * @param array|string $mobMix 手机号 如果是字符窜用,号隔开
	 * @param string $content
	 * @param string $isLongSms 0:普通短信 1:长短信
	 * @return boolean|string true 成功 其它:失败
	 * @author wxb 2015/5/21
	 */
	 function mobileSend($mobile,$content,$isLongSms = 0){
	  	return $this->newSendMessage($mobile, $content); //新发送接口
	 	exit; 
		if(!$mobile || !$content){ //传参有误
			return  false;
		}else if(!$this->getRemainderMes()){//短信已用完
			return  false;
		}
		if(is_array($mobile)){
			$mobile = implode(',',$mobile);
		}
		$content=iconv('UTF-8', 'GB2312',$content);
		$data = [
				'method'=>'sendSMS',
				'username'=>$this->username,
				'password'=>base64_encode($this->password),
				'mobile'=>$mobile,
				'content'=>$content,
				'smstype'=>1,
				'isLongSms'=>$isLongSms,
			 ];
	   $curl = new Curl();
   	   $res = strtolower(trim($curl->st_post($this->getWay,$data)));
   	   if(strpos($res, 'success') === 0){ //如果成功返回 success;批号ID   否则返回 failure;错误提示
   	   		return true;
   	   }else{
   	   		return false;
   	   }
	}
	/**
	 * 查看用户账号剩余可用短信条数
	 */
	 function  getRemainderMes(){
	 	return true;//取消查询 经常查询报查询频繁
		$data = [
				'method' => 'getRestMoney',
				'username' => $this->username,
				'password' => base64_encode($this->password)
		];
		$curl = new Curl();
		$res = 0;
		$res = trim($curl->st_get($this->getWay,$data));
		/* memberSmsNum=0;gateSmsNum=9969;kaSmsNum=0
		memberSmsNum=0 表示会员帐号剩余短信数量
		gateSmsNum=9969 表示网关帐号剩余短信数量
		kaSmsNum=0 表示卡发帐号剩余短信数量 */
		$res = explode('=',explode(';',$res)[1])['1'];
		return $res;
	}
	/**
	 * 获取唯一code
	 * @param $userType 用途   
	 * @param $time 有效期    单位：秒
	 * @return integer
	 */
	public function getCode($userType=1,$time=0,$len = 6,$type = 1,$addChars = ''){
		$code = rand_string($len,$type,$addChars);
		$where = array(
				'code'=>$code,
				'type'=>$type
		);
		if($time){
			$where['add_time'] = array(
					"ELT",time()-$time
			);
		}
		$hasCode = M('Code')->where($where)->find();
		if($hasCode){
			$this->getCode($userType,$len,$type,$addChars);
		}
		return $code;
	}
	function test($mobile = 18823809122){
		$this->debug = true;
		$arr['mobile_code'] = rand(100000,999999);
		$this->sendMessByTel($mobile, $arr,'bind_mobile');
	}
	function newSendMessage($mobile,$content){
		$post_data = array();
		$post_data['userid'] = $this->userid;
		$post_data['account'] = $this->username;
		$post_data['password'] = $this->password;
		$post_data['content'] = $content; //短信内容需要用urlencode编码下
		$post_data['mobile'] = $mobile;
		$post_data['action'] = 'send';
		$post_data['sendtime'] = ''; //不定时发送，值为0，定时发送，输入格式YYYYMMDDHHmmss的日期值
		$url = $this->getWayNew;
		$o = '';
		foreach ($post_data as $k=>$v)
		{
			$o .= "$k=".urlencode($v).'&';
		} 
		$post_data=substr($o,0,-1);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//如果成功只将结果返回，不自动输出任何内容。
		$xmlData = curl_exec($ch);
		curl_close($ch);
		return $this->dealReturn($xmlData) === true;
	}
	private function dealReturn($xmlData){
		/* $string = '<returnsms>
		 <returnstatus>Success</returnstatus>
		 <message>ok</message>
		 <remainpoint>4</remainpoint>
		 <taskid>85888</taskid>
		 <successcounts>1</successcounts></returnsms>';
		 $xml = simplexml_load_string($string);
		 $login = $xml->returnstatus;//这里返回的依然是个SimpleXMLElement对象
		 $login = (string) $xml->returnstatus;//在做数据比较时，注意要先强制转换
		 print_r($login);
		 exit; */
		$xmlData= simplexml_load_string($xmlData);
		if($this->debug){
			var_dump($xmlData);	
			exit;
		}
		if((string)$xmlData->returnstatus == 'Success'){
			return true;
		}
		return (string)$xmlData->message;
	}
}