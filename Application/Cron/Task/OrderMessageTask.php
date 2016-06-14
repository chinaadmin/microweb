<?php
/**
 * 订单提货码
 * @author xiongzw
 */
class OrderMessageTask extends \Think\Controller{
	//返回信息
	private $message = "";
	public function run(){
		$order_model = D("Cron/OrderMessage");
		$order = $order_model->getNoCode();
		if(empty($order)){
			$this->message = "执行了订单提货码变更计划任务";
			return false;
		}else{
			if($order_model->sendMessag($order)===false){
				$this->message = "发送订单确认短信,出现失败状况";
				return false;
			}
		}
		G('order_send_end');
		//超过20秒停止运行同步接口（等待下次计划任务执行）
		if(G('cron_begin','order_send_end',2) > 20){
			$this->message = "执行了订单状态变更计划任务";
			return false;
		}
		//递归执行
		$this->run();
	}
	
   /**
     * 返回信息
     * @return string
     */
    public function getMessage(){
    	return $this->message;
    }
}