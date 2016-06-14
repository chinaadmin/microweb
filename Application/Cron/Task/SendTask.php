<?php
/**
 * 物流发货
 */
class SendTask extends \Think\Controller{
	//返回信息
	private $message = "";
	public function run(){
		$send_model = D("Cron/Send");
		$send = $send_model->getSend();
		if($send['send_id'] && $send['send_num'] && $send['code'] && $send['order_id'] && $send['handle']){
			if($send_model->setSend($send['send_id'], $send['order_id'],$send['code'], $send['send_num'],$send['handle'])){
				$this->message = "执行了订单状态变更计划任务";
			}else{
				$this->message = "执行发货对接失败";
			}
		}else{
			$this->message = "执行了订单状态变更计划任务";
		}
	}
	
	/**
	 * 返回信息
	 * @return string
	 */
	public function getMessage(){
		return $this->message;
	}
}