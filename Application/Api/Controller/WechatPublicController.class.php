<?php
/**
 * 微信公众号获取用户信息
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
use Common\Org\ThinkSDK\ThinkOauth;
class WechatPublicController extends ApiBaseController{
	protected $sns;
	protected $type ="wechat";
	public function _initialize(){
		parent::_initialize();
		$this->sns = ThinkOauth::getInstance ( $this->type );
	}
	/**
	 * 获取授权code
	 */
	public function getCode(){
		$wechatApi= "https://open.weixin.qq.com/connect/oauth2/authorize?";
		$redirect_uri = C('THINK_SDK_WECHAT.CALLBACK');
		$jump_url = I("request.jump_url","");
		$redirect_uri = empty($jump_url)?$redirect_uri:$redirect_uri."?jump=".$jump_url;
		$options = array(
				'appid' =>C('THINK_SDK_WECHAT.APP_KEY'),
				'redirect_uri' => $redirect_uri,
				'response_type' => 'code',
				'scope' => 'snsapi_userinfo',
				'state' => md5(time().rand(100,999))
		);
		$url = $wechatApi.http_build_query($options)."#wechat_redirect";
		redirect($url);
		//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxc1b6e5d6476e7d04&redirect_uri=http%3A%2F%2Fapi.tp-bihaohuo.cn%2FWechatPublic%2Fcallback&response_type=code&scope=snsapi_userinfo&state=dd95004155b70782279eb1326c8822c9#wechat_redirect
	    //https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx0d337325bf4322c5&redirect_uri=http%3A%2F%2Fapi.bihaohuo.cn%2FWechatPublic%2Fcallback&response_type=code&scope=snsapi_userinfo&state=b5b121b0584274b918312c42fd15754f#wechat_redirect
	}
	
	/**
	 * 回调获取用户信息
	 * @param  $code
	 */
	public function callback($code=''){
	    $type = $this->type;
		try {
			$token = $this->sns->getAccessToken ( $code );
		}catch (\Exception $e){
			$this->ajaxReturn($this->result->error());
		}
		if(is_array($token) && !empty($token)){
			$user_info = A ( 'Home/Login', 'Event' )->$type ( $token );
			$user_info['openid'] = $token['openid'];
			$user_info['unionid'] = empty($token['unionid'])?'':$token['unionid'];
// 			$info = urlencode(json_encode($user_info));
// 			$jump_url = urldecode(I("request.jump",""))."?info=".$info;
// 			redirect($jump_url);
			//$this->ajaxReturn($this->result->content(['info'=>$user_info])->success());
			$key = "wechat".uniqid().rand_string(6,1);
			F($key,$user_info);
			$mobile_url = C("JT_CONFIG_WEB_MOBILE_WAP_URL");
			$mobile_url = empty($mobile_url)?"http://m.tp-bihaohuo.cn":$mobile_url;
			$jump_url = urldecode(I("request.jump",$mobile_url))."?info=".$key;
			redirect($jump_url);
		}else{
			$this->ajaxReturn($this->result->error());
		}
	}
	/**
	 * 获取缓存的微信用户信息
	 */
	public function getInfo(){
		$key = I("request.key","");
		$info = F($key);
		F($key,null);
		$this->ajaxReturn($this->result->content(['info'=>$info])->success());
	}
}