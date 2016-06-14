<?php
/**
 * 微信支付模型
 * @author xiongzw
 * @date 2015-08-14
 */
namespace Api\Model;
class WechatPayModel extends ApiBaseModel{
	Protected $autoCheckFields = false;
	//微信支付统一下单
	public function unifiedOrder($openId){
		require_once(VENDOR_PATH."WechatPay/WxPay.JsApiPay.php");
		//require_once(VENDOR_PATH."WechatPay/WxPay.Data.php");
		$tools = new \JsApiPay();
		$input = new \WxPayUnifiedOrder();
		$input->SetBody("test");    //设置商品或支付单简要描述
		$input->SetAttach("test"); //设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
		$input->SetOut_trade_no(\WxPayConfig::MCHID.date("YmdHis")); //设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
		$input->SetTotal_fee("1"); //设置订单总金额，只能为整数，详见支付金额
		$input->SetTime_start(date("YmdHis")); //设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
		$input->SetTime_expire(date("YmdHis", time() + 600)); //设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
		$input->SetGoods_tag("test"); //设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
		$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php"); //设置接收微信支付异步通知回调地址
		$input->SetTrade_type("JSAPI");   //设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		$jsApiParameters = $tools->GetJsApiParameters($order);
		//获取共享收货地址js函数参数
		$editAddress = $tools->GetEditAddressParameters();
		return $jsApiParameters;
	}
}