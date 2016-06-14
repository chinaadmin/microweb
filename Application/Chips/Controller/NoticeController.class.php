<?php
/**
 * 支付通知
 * @author xiongzw
 * @date 2015-12-14
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
use Common\Org\ThinkPay\ThinkPay;
use Think\Log;
class NoticeController extends ApiBaseController{

	/**
	 * 微信支付结果返回
	 */
	public function wechatPaynotify(){
		require_once (VENDOR_PATH . "WechatPay/WxPay.Api.php");
		require_once (VENDOR_PATH . "WechatPay/WxPay.Data.php");
		require_once (VENDOR_PATH . "WechatPay/WxPay.Notify.php");
		$notify = new \WxPayNotify();
		$notify->Handle(false,2);
	}
	
	/**
	 * 支付通知返回
	 */
	public function notify(){
		Log::record('支付返回：param='.json_encode($_REQUEST) .',post='.json_encode($_POST) .',get='.json_encode($_GET),Log::DEBUG);
		$type = I('get.type');
		$method = I('get.method');
	
		$pay = ThinkPay::getInstance($type);
		$pay->setProcess(ThinkPay::PROCESS_PAY);
		if (IS_POST && !empty($_POST)) {
			$notify = $_POST;
		} elseif (IS_GET && !empty($_GET)) {
			$notify = $_GET;
			unset($notify['method']);
			unset($notify['type']);
		} else {
			exit('Access Denied');
		}
		//验证
		if ($pay->verifyNotify($notify)) {
			//获取订单信息
			$info = $pay->getInfo();
			if ($info['status']) {
				$payment_log_model = M("PaymentLog");
				$is_exist = $payment_log_model->where([
						'order_sn' => $info['out_trade_no'],
						'trade_no' => $info['trade_no']
						])->field(true)->find();//过滤已处理过的
				if(empty($is_exist)) {
					$payinfo = $payment_log_model->field(true)->where(['order_sn' => $info['out_trade_no']])->order('add_time desc')->find();
					$payment_data = [
					'update_time' => time(),
					'trade_no' => $info['trade_no'],
					'details' => $info['details'],
					'notify_id'=>$info['notify_id'],
					'buyer'=>$info['buyer'],
					'seller'=>$info['seller']
					];
					if ($payinfo['status'] == 0) {
						switch($payinfo['order_type']){
							case 1://充值
								$result = D('Home/Recharge')->paying($payinfo['order_id']);
								break;
							case 0://订单
							default:
								$result = D('Chips/Pay')->paying($payinfo['order_id']);
								break;
						}
						if ($result->isSuccess()) {
							$payment_data['status'] = 1;
							$payment_log_model->where(['log_id' => $payinfo['log_id']])->setField($payment_data);
						} else {
							$this->error("支付失败！");
						}
					}else{
						$payment_data['status'] = 0;
						$payment_log_model->where(['log_id' => $payinfo['log_id']])->setField($payment_data);
					}
				}else{
					$payinfo = $is_exist;
				}
				if ($method == "return") {
					$this->redirect($payinfo['callback']);
				} else {
					$pay->notifySuccess();
				}
			} else {
				$destination = C('LOG_PATH').date('y_m_d').'.orderPayInfo';
				$con = var_export($info,true);
				\Think\Log::write($con,'INFO','',$destination);
				$this->error("支付失败！");
			}
		} else {
			E("Access Denied");
		}
	}
	
	/**
	 * 退款通知返回
	 */
	public function refund_notify(){
		$type = I('get.type');
		$method = I('get.method');
	
		$pay = ThinkPay::getInstance($type);
		$pay->setProcess(ThinkPay::PROCESS_REFUND);
		if (IS_POST && !empty($_POST)) {
			$notify = $_POST;
		} elseif (IS_GET && !empty($_GET)) {
			$notify = $_GET;
			unset($notify['method']);
			unset($notify['type']);
		} else {
			exit('Access Denied');
		}
	
		//验证
		if ($pay->verifyNotify($notify)) {
			//获取订单信息
			$info = $pay->getInfo();
			$payment_refund_log_model = M("PaymentRefundLog");
			$is_exist = $payment_refund_log_model->where([
					'batch_no' => $info['batch_no'],
					//'status'=>1
					'success_num' => ['exp',' = batch_num']
					//'success_num' => ['GT',0]
					])->count();//过滤已处理过的
			if(empty($is_exist)) {
				$payment_refund_x = M('PaymentRefundX')->where(['batch_no' => $info['batch_no']])->field(true)->select();
				$refund_ids = array_column($payment_refund_x,'refund_id','trade_no');
				foreach($info['result_data'] as &$v){
					$v['refund_id'] = $refund_ids[$v['trade_no']];
				}
				$result = D('Admin/Refund')->refund($info['result_data']);
				if ($result->isSuccess()) {
					$payment_refund_log_model->where(['batch_no' => $info['batch_no']])->save([
							'notify_id'=>$info['notify_id'],
							'status'=>1,
							'success_num'=>$info['success_num'],
							'details'=>$info['details']
							]);
				}else {
					$this->error("退款失败！");
				}
			}
			if ($method == "return") {
				//$this->redirect('Cart/paysuccess',['id'=>$payinfo['order_id']]);
			} else {
				$pay->notifySuccess();
			}
		} else {
			E("Access Denied");
		}
	}
}