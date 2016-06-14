<?php
/**
 * 微信现金红包控制器
 * author: xiaohuakang
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
require_once (VENDOR_PATH . "WechatPay/WxPay.JsApiPay.php"); // 微信js支付工具类
class RedPackController extends ApiBaseController{
	/**
	 * 发送现金红包
	 */
	public function sendRedPack(){
		// 发送红包数据
		$data = [
			'nonce_str' => $this->getRand(), // 随机字符串
			'mch_billno' => \WxPayConfig::$config['default']['MCHID'].date('YmdHis').rand(1000, 9999), // 商户订单号 1264250901
			'mch_id' => \WxPayConfig::$config['default']['MCHID'], // 商户号 1264250901
			'wxappid' => \WxPayConfig::$config['default']['APPID'], // 公众账号appid wxc1b6e5d6476e7d04
			'send_name' => '必好货测试', // 商户名称
			're_openid' => $this->getOpenid(),  // 用户openid oo54huKjwXci7_y92XRsmEVmyWbU
			'total_amount' => 100, // 付款金额，单位为分
			'total_num' => 1, // 红包发放总人数
			'wishing' => '赏你的，拿去花,别问我为什么，有钱！任性！', // 红包祝福语
			'client_ip' => $_SERVER['REMOTE_ADDR'], // 调用接口的机器Ip地址
			'act_name' => '单身狗什么的就别领了', // 活动名称
			'remark' => '单身什么的真的好意思领吗？' // 备注
		];
		$data['sign'] = $this->createSign($data); // 签名
		$postXml = $this->arrayToXml($data);
		$resultXml = \WxPayApi::sendRedPack($postXml);
		return $resultXml;
	}

	/**
	 * 显示用户openid
	 */
	public function showOpenId(){
		echo $this->getOpenid();
	}

	/**
	 * 获取openid
	 */
	public function getOpenid(){
		$wxTools = new \JsApiPay();	// js支付工具类
		$openid = $wxTools->GetOpenid(); // 获取用户openid  调试微信openid：oo54huKjwXci7_y92XRsmEVmyWbU
		return $openid;
	}

	/**
	 * 生成随机数
	 */
	private function getRand() {
		$str = '1234567890abcdefghijklmnopqrstuvwxyz';
		for($i = 0; $i < 30; $i ++) {
			$j = rand ( 0, 35 );
			$t1 .= $str [$j];
		}
		return $t1;
	}

	/**
	 * 拼接签名字符串
	 */
	public function formatQueryParamMap($paramMap){
		$buff = "";
		ksort($paramMap);
		foreach ($paramMap as $k => $v) {
			if( null != $v && "null" != $v && "sign" != $k){
				$buff .= $k . "=" . $v . "&";
			}
		}
		$buff = trim($buff, "&");
		return $buff;
	}

	/**
	 * 生成签名
	 */
	public function createSign($param){
		$stringA = $this->formatQueryParamMap($param); // 拼接签名字符串
		$stringSignTemp = $stringA . "&key=" . \WxPayConfig::$config['default']['KEY']; // key: Q6ps21IijPH3YFHwqqaYFB5yHO9MgjFw
		$sign = strtoupper(md5($stringSignTemp));
		return $sign;
	}

	/**
	 * 数组转xml
	 */
	public function arrayToXml($arr){
		$xml = "<xml>";
		foreach($arr as $k => $v){
			if(is_numeric($v)){
				$xml .= "<". $k .">" . $v . "</" . $k . ">";
			}else{
				$xml .= "<". $k . "><![CDATA[" . $v . "]]></" . $k . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}

}