<?php
/**
 * 订单支付
 * @author xiongzw
 * @date 2015-12-11
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
use Common\Org\ThinkPay\PayVo;
use Common\Org\ThinkPay\ThinkPay;
//use User\Org\Util\User;
class PayController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
	}
	/**
	 * 账户余额支付
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         token token值
	 *         orderId 订单id
	 *         </code>
	 */
	public function accountPay(){
		$order_id = I('post.orderId');
		//判断是否能支付
		$result = D('Chips/Pay')->judgeOrder($order_id);
		if($result->isSuccess()){
			M('CrowdfundingOrder')->where(['cor_order_id'=>$order_id])->data(['cor_pay_type'=>'ACCOUNT'])->save();
			$result = D('Chips/Pay')->accountPay($order_id);
		}
		$this->ajaxReturn($result);
	}
	
	/**
	 * 移动支付
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         token token值
	 *         type 类型 0为订单，1为充值
	 *         orderId 订单id
	 *         amount 充值金额
	 *         </code>
	 */
	public function mobilePay(){
		$pay_type = 'ALIPAY';
		$order_id = I ( 'post.orderId' );
		//判断是否能支付
		$result = D('Chips/Pay')->judgeOrder($order_id);
		if(!$result->isSuccess()){
			$this->ajaxReturn($result);
		}
		$order_info = M ('CrowdfundingOrder')->field (true)->where ([ 'cor_order_id' => $order_id ])->find ();
		if (empty ( $order_info )) {
			$this->ajaxReturn ( $this->result->error ( '该订单不存在' ) );
			exit ();
		}
		if ($order_info ['cor_pay_type'] != 'ALIPAY') {
			M ('CrowdfundingOrder')->where (['cor_order_id' => $order_id ])->save (['cor_pay_type' => 'ALIPAY']);
		}
		
		$order_id = $order_info ['cor_order_id'];
		$order_sn = $order_info ['cor_order_sn'];
		$amount = $order_info ['cor_should_pay'];
			
	
		$thinkpay_config = C('THINK_PAY.common');
		$thinkpay_config['sign_type'] = 'RSA';
		$chips = C("THINK_PAY.chips");
		$thinkpay_config['return_url'] = $chips['return_url'];
		$thinkpay_config['notify_url'] = $chips['notify_url'];
		C('THINK_PAY.common',$thinkpay_config);
		$thinkpay = ThinkPay::getInstance($pay_type);
		$thinkpay->setProcess(ThinkPay::PROCESS_PAY)
		->setMode(ThinkPay::MODE_STRING)
		->setService('mobile');
		$pay_vo = new PayVo();
		$pay_vo->setFee($amount)
		->setOrderNo($order_sn)
		->setOrderId($order_id)
		//->setCallback('Cart/paysuccess?id='.$order_info['order_id'])
		->setOrderType(0);//订单类型
		$pay_param = $thinkpay->submit($pay_vo);
		$this->ajaxReturn($this->result->content(['data'=>$pay_param])->success());
	}
	
	
	/**
	 * 微信支付
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         token token值
	 *         orderId 订单id
	 *         openid openid
	 *         </code>
	 */
	public function wechatPay(){
		$order_id = I('post.orderId');
		//判断是否能支付
		$result = D('Chips/Pay')->judgeOrder($order_id);
		if(!$result->isSuccess()){
			$this->ajaxReturn($result);
		}
		$openid = I('post.openid');
		$trade_type = I('post.tradeType','JSAPI');
		$order_info = M('CrowdfundingOrder')->field(true)->where(['cor_order_id'=>$order_id])->find();
		if(empty($order_info)){
			$this->ajaxReturn($this->result->error('该订单不存在'));
			exit;
		}
		$wechat_type = 'WEIXIN';
		if(strtoupper($trade_type)!='JSAPI'){
			$wechat_type .= "#".$trade_type;
		}
		M('CrowdfundingOrder')->where(['cor_order_id'=>$order_id])->data(['cor_pay_type'=>$wechat_type])->save();
		$pay_vo = new PayVo();
		$result = $pay_vo->setFee($order_info['cor_should_pay'])
		->setOrderNo($order_info['cor_order_sn'])
		->setOrderId($order_info['cor_order_id'])
		->setOrderType(0)->record();//订单类型
		if($result === false){
			$this->ajaxReturn($this->result->error('添加订单记录失败'));
			exit;
		}
	
		$subject = $pay_vo->getTitle();
		if(empty($subject)){
			$subject = $pay_vo->getOrderNo();
		}
	
		$pay = new \Common\Org\Util\WechatPay ();
		$pay->unifiedParam = array (
				"body" =>$subject,
				"out_trade_no" => $pay_vo->getOrderNo(),
				"total_fee" => $pay_vo->getFee(),
				"notify_url" => U('Chips/Notice/wechatPaynotify',[],true,true),
				"trade_type" => $trade_type,
				"openid" => $openid
		);
		$returnData = $pay->unifiedOrder();
		if(is_string($returnData)){
			$returnData = json_decode($pay->unifiedOrder(),true);
		}
		$returnData = $this->makeSign($trade_type, $returnData);
		$this->ajaxReturn($this->result->content(['data'=>$returnData])->success());
	}
	
	public function makeSign($type,$return){
		if(strtoupper($type)=='APP'){
			require_once (VENDOR_PATH . "WechatPay/WxPay.JsApiPay.php");
			$key = \WxPayConfig::$config['app']['KEY'];
			$return['key'] = base64_encode('jitujituan'.$key);
			return $return;
		}
		return $return;
	}
}