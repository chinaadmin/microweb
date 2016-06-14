<?php
/**
 * 百度推送
 * @author xiongzw
 * @date 2015-11-27
 * @url http://push.baidu.com/doc/restapi/msg_struct
 */
namespace Common\Org\Push;
abstract  class BaiduPush{ 
	//protected $apiKey="";   //应用apiKey的值
	//protected $secretKey=""; //应用secretKey的值
	//推送范围  0：tag组播 1：广播 2：批量单播 3：组合运算 4：精准推送 5：LBS推送 6：系统预留  7：单播
	protected $range_type = "7";
	
	//推送设备 
	protected $channel_id = null; 
	
	//设备类型:安卓设备
	const DEVICE_ANDROID = '3';  //android
	
	//设备类型:IOS设备
	const DEVICE_IOS = '4';          //ios
	
	//推送设备类型
	protected $push_device_type = self::DEVICE_ANDROID;
	
	//消息类型：通知
    const MESSAGE_NOTIFICATION = '1';
    
    //消息类型：透传消息
    const MESSAGE_MESSAGE = '0';
    
	//消息推送类型 0:透传 1：通道
	protected  $msg_type = self::MESSAGE_NOTIFICATION;
	
	protected static $_instance = null;
	
	//android消息格式
	protected $androidMsg = [ 
			"title" => "", // 必选
			"description" => "", // 必选
			"notification_builder_id" => 0, // 可选
			"notification_basic_style" => 7, // 可选
			"lightapp_ctrl_keys" => [  // 可选
					"display_in_notification_bar" => 0, // 默认为0,不在通知栏展示;1,在通知栏展示
					"enter_msg_center" => 0  // 默认为0,不进入消息中心; 1,进入消息中心
			],
			"open_type" => 0, // 可选
			"net_support" => 1, // 可选
			"user_confirm" => 0, // 可选
			"url" => "", // 可选
			"pkg_content" => "", // 可选
			"pkg_name" => "", // 可选
			"pkg_version" => "", // 可选
			"custom_content" => [ 
				
			] 
	];
	
	//ios推送消息格式
	protected $iosMsg = [ 
			"aps" => [ 
					"alert" => "",
					"sound" => "", // 可选
					"badge" => 0, // 可选
					"content-available" => 1 
			],
			"lightapp_ctrl_keys" => [ 
					"display_in_notification_bar" => 1, // 默认为0,不在通知栏展示; 1,在通知栏展示
					"url" => "",
					"enter_msg_center" => 0  // 默认为0,不进入消息中心; 1,进入消息中心
			] 
	];
	
	/**
	 * 构造方法
	 * @param string $apiKey 
	 * @param string $secretKey
	 * @param $curlopts
	 */
	public function getInstance(){
	   require_once dirname(__FILE__)."/sdk/sdk.php";
	   if(!(self::$_instance instanceof  \PushSDK)){
			self::$_instance = new \PushSDK($this->apiKey,$this->secretKey);
		}
		if(empty($this->apiKey) || empty($this->secretKey)){
			$this->setKey();
		}
	   return self::$_instance;
	}
	
	/**
	 * 设置key值
	 * @param  $apiKey
	 * @param  $secretKey
	 */
	public function setKey($apiKey='',$secretKey=''){
		$defaultApi = $this->push_device_type==self::DEVICE_ANDROID?C("JT_CONFIG_WEB_PUSH_ANDROID_API"):C("JT_CONFIG_WEB_PUSH_IOS_API");
		$defaultSecret = $this->push_device_type==self::DEVICE_ANDROID?C("JT_CONFIG_WEB_PUSH_ANDROID_SECRET"):C("JT_CONFIG_WEB_PUSH_IOS_SECRET");
		$this->apiKey = $apiKey?$apiKey:$defaultApi;
		$this->secretKey = $secretKey?$secretKey:$defaultSecret;
  		self::$_instance->setApiKey(trim($this->apiKey));
  		self::$_instance->setSecretKey(trim($this->secretKey));
		return $this;
	}
	/**
	 * 设置推送消息类型
	 * @param  $msg_type 0:透传消息 1：通知
	 */
	public function setMsgType($msg_type=self::MESSAGE_NOTIFICATION){
	      $this->msg_type = $msg_type;
	      return $this;
	}
	/**
	 * 设置推送设备类型
	 * @param  $device_type 推送设备类型
	 */
	public function setDeviceType($device_type=self::DEVICE_ANDROID){
		$this->push_device_type = $device_type;
		return $this;
	}
	/**
	 * 设置推送用户
	 * @param  $channel_id
	 * @return \Common\Org\Push\BaiduPush
	 */
	public function setChannelId($channel_id){
		$this->channel_id = $channel_id;
		return $this;
	}
	/**
	 * 设置通知消息范围
	 */
	public function setRangetype($range_type){
		$this->range_type = $range_type;
		return $this;
	}
	/**
	 * 设置安卓推送消息内容
	 * @param  $key
	 * @param  $value
	 */
	public function setAndroidMsg($key,$value){
		$this->androidMsg[$key] = $value;
		return $this;
	}
	/**
	 * 设置ios推送消息内容
	 * @param $key
	 * @param $value
	 * @return \Common\Org\Push\BaiduPush
	 */
	protected function setIosMsg($key,$value){
		$this->iosMsg[$key] = $value;
		return $this;
	}
	
    /**
     * 设置通道
     * @return mixed
     */
	abstract protected function setChannel();
	/**
	 * 推送消息
	 * @param  $content 推送内容
	 * @param  $param 参数
	 */
	abstract public function pushMsg($content,$param=[]);
}
