<?php
/**
 * 微信授权登录SDK
 * @author xiongzw
 * @date 2014-12-17
 */
use Common\Org\ThinkSDK\ThinkOauth;
use Think\Exception;
class WechatSDK extends ThinkOauth{
	/**
	 * 获取tokenUrl
	 */
	protected  $GetAccessTokenURL = "https://api.weixin.qq.com/sns/oauth2/access_token?";
	/**
	 * API根路径
	 * @var string
	 */
	protected $ApiBase = 'https://api.weixin.qq.com/sns/';
	
	/**
	 * 获取token
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Org\ThinkSDK\ThinkOauth::getAccessToken()
	 */
	public function getAccessToken($code, $extend = null){
		$params = array(
				'appid'  => $this->AppKey,
				'secret' => $this->AppSecret,
				'code'   => $code,
				'grant_type' => 'authorization_code'
		);
		$data = $this->http($this->GetAccessTokenURL, $params, 'POST');
		$this->Token = $this->parseToken($data, $extend);
		return $this->Token;
	}
	
	public function call($api, $param = '', $method = 'GET', $multi = false){
		$params = array(
				'access_token'       => $this->Token['access_token'],
				'openid'             => $this->Token['openid'],
		);
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}
	/**
	 * 刷新获取
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Org\ThinkSDK\ThinkOauth::openid()
	 */
	public function openid(){
		$data = $this->Token;
		if(isset($data['openid']) && isset($data['access_token']))
			return $data;
		elseif($data['refresh_token']){
			$params = array(
					'appid'  => $this->AppKey,
					'secret' => $this->AppSecret,
					'refresh_token'   => $data['refresh_token'],
					'grant_type' => 'refresh_token'
			);
			$data = $this->http($this->url('oauth2/refresh_token'), array($params));
			$data = json_decode(trim(substr($data, 9), " );\n"), true);
			if(isset($data['openid']) && isset($data['access_token']))
				return $data;
			else
				throw new Exception("获取用户openid出错：{$data['errmsg']}");
		} else {
			throw new Exception('没有获取到openid！');
		}
	}
	
	protected function parseToken($result, $extend){
		$data = json_decode($result,true);
		if($data['access_token'] && $data['expires_in']){
			$this->Token    = $data;
			if(!isset($data['openid']) || !isset($data['access_token'])){
				$data = $this->openid();
			}
			return $data;
		} else
			throw new Exception("获取微信 ACCESS_TOKEN 出错：{$result}");
	}
}