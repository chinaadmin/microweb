<?php
/**
 * jssdk注入接口
 * @author xiongzw
 * @date 2015-08-15
 */
namespace Api\Controller;
class JsSdkController extends ApiBaseController{
	private $appId;
	private $appSecret;

	public function _initialize(){
		parent::_initialize();
// 		$this->appId = "wxc1b6e5d6476e7d04";
// 		$this->appSecret = "7ddef132152b8d802bbd83cb2251398b";
		$this->appId = C("THINK_SDK_WECHAT.APP_KEY");
		$this->appSecret = C("THINK_SDK_WECHAT.APP_SECRET");
	}
	//获取注入
	public function getSignPackage() {
		$jsapiTicket = $this->getJsApiTicket();

		// 注意 URL 一定要动态获取，不能 hardcode.
// 		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
// 		$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	    $url = I("post.url","");
		$timestamp = time();
		$nonceStr = $this->createNonceStr();

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

		$signature = sha1($string);

		$signPackage = array(
				"appId"     => $this->appId,
				"nonceStr"  => $nonceStr,
				"timestamp" => $timestamp,
				"url"       => $url,
				"signature" => $signature,
				"rawString" => $string
		);
		$this->ajaxReturn($this->result->content(['jssdk'=>$signPackage])->success());
		//return $signPackage;
	}
	//随机字符
	private function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

	private function getJsApiTicket() {
		// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
		//$data = json_decode(file_get_contents("jsapi_ticket.json"));
		$data = F("jsapi_ticket");
		if ($data->expire_time < time()) {
			$accessToken = $this->getAccessToken();
			// 如果是企业号用以下 URL 获取 ticket
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
			$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
			$res = json_decode($this->httpGet($url));
			$ticket = $res->ticket;
			if ($ticket) {
				$data->expire_time = time() + 7000;
				$data->jsapi_ticket = $ticket;
				F("jsapi_ticket",$data);
				//$fp = fopen("jsapi_ticket.json", "w");
				//fwrite($fp, json_encode($data));
				//fclose($fp);
			}
		} else {
			$ticket = $data->jsapi_ticket;
		}

		return $ticket;
	}

	public function validateMp(){
		$this->authToken();
		$wxaccessToken = $this->getAccessToken();
		if(!empty($wxaccessToken) && !empty($this->user_id)){
			$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$wxaccessToken}&lang=zh_CN";
			$userConectModel = M('user_connect');
			$where = [
				'uid' => $this->user_id
			];
			$openId = $userConectModel->where($where)->getField('openid');
			if ($openId){
				$url .= "&openid={$openId}";
				$wxRes = $this->httpGet($url);
				$wxRes = json_decode($wxRes , true);
				$res = [];
				if(isset($wxRes['subscribe'])){
					$res['subscribe'] = $wxRes['subscribe'];
				} else {
					$res['subscribe'] = '0';
				}
			} else {
				$res = [
					'subscribe' => '0'
				];
			}
			$this->ajaxReturn($this->result->content($res)->success());
		}else{
			$this->ajaxReturn($this->result->error());
		}
	}

	//获取accessToken
	private function getAccessToken() {
		// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		//$data = json_decode(file_get_contents("access_token.json"));
		$data = F("access_token");
		if ($data->expire_time < time()) {
			// 如果是企业号用以下URL获取access_token
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
			$res = json_decode($this->httpGet($url));
			$access_token = $res->access_token;
			if ($access_token) {
				$data->expire_time = time() + 7000;
				$data->access_token = $access_token;
				F("access_token",$data);
// 				$fp = fopen("access_token.json", "w");
// 				fwrite($fp, json_encode($data));
// 				fclose($fp);
			}
		} else {
			$access_token = $data->access_token;
		}
		return $access_token;
	}

	private function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}
}
