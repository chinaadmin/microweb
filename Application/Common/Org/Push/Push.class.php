<?php
/**
 * 消息推送类
 * @author xiongzw
 * @date 2015-11-26
 */
namespace Common\Org\Push;
class Push extends BaiduPush{ 
	//http://www.tp-bihaohuo.cn:2123/
	//protected $html_url = "http://workerman.net:2121/"; //html推送服务器url
	protected $html_url = "http://www.tp-bihaohuo.cn:2121/"; //html推送服务器url
	/**
	 * web推送
	 * @param $to_uid 推送的用户
	 * @param $content 推送内容
	 * 
	 */
	public function html_push($content,$to_uid='',$type="publish"){ 
		// 推送的url地址，上线时改成自己的服务器地址
		$push_api_url = $this->html_url;
		$post_data = array (
				"type" => $type,
				"content" => $content,
				"to" => $to_uid
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
		$return = curl_exec ( $ch );
		curl_close ( $ch );
		return $return;
	}
	
	/**
	 * 设置推送通道
	 */
	protected function setChannel(){
	   $method = "";
	   switch($this->range_type){
	   	 case 7: //单播
	   	 	     $method = "pushMsgToSingleDevice";
	   	 	     break;
	   	 case 1: //广播
	   	 	    $method = "pushMsgToAll";
	   	 	    break;
	   	 case 2: //批量单播
	   	 	    $method = "pushBatchUniMsg";
	   	 	    break;
	   	 
	   }
	   return $method;
	}
	
	/**
	 * 推送消息
	 */
	public function pushMsg($msg,$param=[]){
		$channel = $this->setChannel();
		$defaultParam = $this->setDefaultParam();
		$param = array_merge($defaultParam,$param);
		$sdk = $this->getInstance();
		$msg = $this->setMsg($msg);
		if($this->range_type==1){
			$return = $sdk->$channel($msg,$param);
		}else{
			$return = $sdk->$channel($this->channel_id,$msg,$param);
		}
		if($return === false){
			print_r($sdk->getLastErrorCode());
			print_r($sdk->getLastErrorMsg());
		}
		return $return;
	}
	
	/**
	 * 设置推送参数
	 */
	protected function setDefaultParam(){
		$param = array(
				"msg_type"=>$this->push_device_type==self::DEVICE_ANDROID?$this->msg_type:1,
		);
		if($this->push_device_type==self::DEVICE_IOS){
			$param["deploy_status"] = 1;
		}
		return $param;
	}
	
	/**
	 * 设置推送消息
	 */
	public function setMsg($msg){
		if($this->push_device_type==self::DEVICE_ANDROID){
			$this->androidMsg($msg);
			return $this->androidMsg;
		}
		if($this->push_device_type==self::DEVICE_IOS){
			$this->iosMsg($msg);
			return $this->iosMsg;
		}
	}
	
	/**
	 * 设置android信息
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Org\Push\BaiduPush::setAndroidMsg()
	 */
	protected function androidMsg($msg){
		$this->setAndroidMsg('title',$msg['title']);
		$this->setAndroidMsg('description',$msg['content']);
		$this->setAndroidMsg('custom_content', $msg['data']?$msg['data']:[]);
		foreach($msg as $key=>$v){
			if($key=='content' || $key=='title' || $key='data'){
				continue;
			}
			$this->setAndroidMsg($key,$v);
		} 
	}
	
	/**
	 * 设置iods信息
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Org\Push\BaiduPush::setIosMsg()
	 */
	protected function iosMsg($msg){
		$this->iosMsg['aps']['alert'] = $msg['content'];
		foreach($msg as $key=>$v){
			if($key=='content' || $key=='title' || $key='data'){
				continue;
			}
			$this->setIosMsg($key,$v);
		}
		if($msg['data']){
			$this->iosMsg = array_merge($this->iosMsg,$msg['data']);
		}
	}
	
}