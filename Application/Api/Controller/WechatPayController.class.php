<?php
/**
 * 微信支付
 * @author xiongzw
 * @date 2015-08-14
 */
namespace Api\Controller;
class WechatPayController extends ApiBaseController{
	//js发起支付
	public function jsApiPay(){
 		require_cache(VENDOR_PATH."WechatPay/WxPay.JsApiPay.php");
    	$tools = new \JsApiPay();
   		$openid = $tools->GetOpenid();
 		//$openid = "oo54huKojlQ29FeqDy_JV5F_9dpk";
 		$data = $this->unifiedOrde($openid);
        /*$data=[
            "appId"=> "wxc1b6e5d6476e7d04",
            "nonceStr"=>  "r2h005n6cxd1cpeo4b8g6psfub9ueky3",
            "package"=>  "prepay_id=wx2015081616321513544cd7770394828360",
            "signType"=>  "MD5",
            "timeStamp"=>  "1439713936",
            "paySign"=> "4CF19CE8DF2CF609C7F6481A5B9A663E"
          ];
        $data =  json_encode($data);*/
		$this->assign("jsApiParameters",$data);
		$this->display("jsApiPay");
	} 
	
	/**
	 * 获取code
	 */
	public function getCode(){
		$wechatApi= "https://open.weixin.qq.com/connect/oauth2/authorize?";
		$redirect_uri = "http://api.tp-bihaohuo.cn/WechatPay/jsApiPay.html";
		$options = array(
				'appid' =>C('THINK_SDK_WECHAT.APP_KEY'),
				'redirect_uri' => $redirect_uri,
				'response_type' => 'code',
				'scope' => 'snsapi_userinfo',
				'state' => md5(time().rand(100,999))
		);
		$url = $wechatApi.http_build_query($options)."#wechat_redirect";
		redirect($url);
	}
	/**
	 * 微信下单
	 * @param  $openid
	 * @return Ambigous <\Common\Org\Util\Ambigous, json数据，可直接填入js函数作为参数, string>
	 */
	public function unifiedOrde($openid) {
		$pay = new \Common\Org\Util\WechatPay ();
		$pay->unifiedParam = array (
				"body" => "友成肉松饼",
				"attach" => "111",
				"out_trade_no" => "136425090120150815110069",
				"total_fee" => '0.01',
				"goods_tag" => "test",
				"notify_url" => "http://paysdk.weixin.qq.com/example/notify.php",
				"trade_type" => "JSAPI",
				"openid" => $openid 
		);
		return $pay->unifiedOrder ();
	}
	
	/**
	 * 商户退款
	 */
	public function refund(){
		if(IS_POST){
			$pay = new \Common\Org\Util\WechatPay ();
			$pay->refund();
			exit;
		}
		$this->display();
	}
}