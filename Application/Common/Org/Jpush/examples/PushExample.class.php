<?php
/**
 * 该示例主要为JPush Push API的调用示例
 * HTTP API文档:http://docs.jpush.io/server/rest_api_v3_push/
 * PHP API文档:https://github.com/jpush/jpush-api-php-client/blob/master/doc/api.md#push-api--构建推送pushpayload
 */
namespace Common\Org\Jpush\examples;
use Common\Org\Jpush\src\JPush\JPush;
class PushExample{
	private $br = '<br/>';
	//private $app_key = 'd1f6a7befb1e74fef5a95d62';
	//private $master_secret = 'b8e7455104390bbcf018435a';
	
	private $app_key = 'd83db24a7dc7f930fa8c7508';
	private $master_secret = 'a6f9d1045b59858d9093117f';
	private $client = null;

	// 初始化
	public function __construct(){
		$this -> client = new JPush($this -> app_key, $this -> master_secret);
	}
	

	// 简单推送示例
	public function push($data){
		$result =$this -> client->push()
		->setPlatform('all')
		->addAllAudience()
		->setNotificationAlert($data)
		->send();

		//echo 'Result=' . json_encode($result) . $br;
		return json_encode($result);
	}
	

	// 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
	public function complete_push(){
		$result = $this -> client->push()
		->setPlatform(array('ios', 'android'))
		->addAlias('alias1')
		->addTag(array('tag1', 'tag2'))
		->setNotificationAlert('Hi, JPush')
		->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2"))
		->addIosNotification("Hi, iOS notification", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
		->setMessage("msg content", 'msg title', 'type', array("key1"=>"value1", "key2"=>"value2"))
		->setOptions(100000, 3600, null, false)
		->send();

		//echo 'Result=' . json_encode($result) . $br;
		return json_encode($result);
	}
	


	// 指定推送短信示例(推送未送达的情况下进行短信送达, 该功能需预付短信费用, 并调用Device API绑定设备与手机号)
	public function appoint_push(){
		$result = $this -> client->push()
		->setPlatform('all')
		->addTag('tag1')
		->setNotificationAlert("Hi, JPush SMS")
		->setSmsMessage('Hi, JPush SMS', 60)
		->send();

		//echo 'Result=' . json_encode($result) . $br;
		return json_encode($result);
	}
	
}