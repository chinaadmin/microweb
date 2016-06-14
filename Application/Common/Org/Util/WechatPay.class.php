<?php
/**
 * 微信支付类库
 * @author xiongzw
 * @date 2015-08-15
 */
namespace Common\Org\Util;
class WechatPay{
	public $unifiedParam = array(
			"body"=>"", //设置商品或支付单简要描述
			"attach"=>"", //设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
			"out_trade_no"=>"",//设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
			"total_fee" => '',//设置订单总金额，只能为整数，详见支付金额
			"time_start" => "",//设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
			"time_expire" =>"",//设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
			"goods_tag" =>"",//设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
			"notify_url"=>"",//设置接收微信支付异步通知回调地址
			"trade_type"=>"JSAPI",//设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
			"openid" =>""
	);
	public $refund = array();
	/**
	 * 微信支付统一下单
	 * @param  $openId
	 * @return Ambigous <json数据，可直接填入js函数作为参数, string>
	 */
	public function unifiedOrder() {
		require_once (VENDOR_PATH . "WechatPay/WxPay.JsApiPay.php");
		if($this->unifiedParam ['trade_type']=='JSAPI'){
		 $tools = new \JsApiPay ();
		}
		$input = new \WxPayUnifiedOrder ();
		$input->SetBody ( $this->unifiedParam ['body'] ); // 设置商品或支付单简要描述
		$input->SetAttach ( $this->unifiedParam ['attach'] ); // 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
		$input->SetOut_trade_no ( $this->unifiedParam ['out_trade_no'] ); // 设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
		$input->SetTotal_fee ($this->unifiedParam ['total_fee'] * 100); // 设置订单总金额，只能为整数，详见支付金额
		$input->SetTime_start ( $this->unifiedParam ['time_start'] ? $this->unifiedParam ['time_start'] : date ( "YmdHis" ) ); // 设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
		$input->SetTime_expire ( $this->unifiedParam ['time_expire'] ? $this->unifiedParam ['time_expire'] : date ( "YmdHis", time () + 600 ) ); // 设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
		$input->SetGoods_tag ( $this->unifiedParam ['goods_tag'] ); // 设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
		$notify_url = $this->unifiedParam ['notify_url'] ? $this->unifiedParam ['notify_url'] : "http://paysdk.weixin.qq.com/example/notify.php";
		$input->SetNotify_url ( $notify_url ); // 设置接收微信支付异步通知回调地址
		$input->SetTrade_type ( $this->unifiedParam ['trade_type'] ); // 设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
		$input->SetOpenid ( $this->unifiedParam ['openid'] );
		$order = \WxPayApi::unifiedOrder ( $input );
		if($this->unifiedParam ['trade_type']=='JSAPI'){
		 $returnData = $tools->GetJsApiParameters ( $order );
		}else{
			$returnData = $order;
		}
		return $returnData;
	}
	/**
	 * 统一下单设置参数
	 * @param  $key
	 * @param  $value
	 * @return 
	 */
	public function _setParam($key,$value){
		return $this->unifiedParam[$key]=$value;
	}
	
	/**
	 * 设置收获共享地址
	 */
	public function getAddress() {
		require_once (VENDOR_PATH . "WechatPay/WxPay.JsApiPay.php");
		$tools = new \JsApiPay ();
		// 获取共享收货地址js函数参数
		return $tools->GetEditAddressParameters ();
	}
	
	/**
	 * 微信退款
	 * @param <code>
	 *        out_trade_no //商户订单号
	 *        total_fee  //订单总金额
	 *        refund_fee //退款金额
	 *        refund_no  //退款单号
	 *        </code>
	 */
	public function refund($data){
		require_once (VENDOR_PATH . "WechatPay/WxPay.Api.php");
		$out_trade_no = $data['out_trade_no'];
		$total_fee = intval($data['total_fee']*100);
		$refund_fee = intval($data['refund_fee']*100);
		$input = new \WxPayRefund();
		$input->SetOut_trade_no($out_trade_no);
		$input->SetTotal_fee($total_fee);
		$input->SetRefund_fee($refund_fee);
		$pay_type = explode("#", $data['pay_type']);
		$pay_type[1] = $pay_type[1]?$pay_type[1]:'JSAPI'; 
		if($pay_type[1] && strtoupper($pay_type[1])=='APP'){
			$mchid = \WxPayConfig::$config['app']['MCHID'];
		}else{
			$mchid = \WxPayConfig::$config['default']['MCHID'];
		}
		$input->SetTrade_type($pay_type[1]);
		$input->SetOut_refund_no(empty($data['refund_no'])?$mchid.date("YmdHis"):$data['refund_no']);
		$input->SetOp_user_id($mchid);
		return \WxPayApi::refund($input);
	}
}