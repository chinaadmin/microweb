<?php
/**
 * 物流发货，对接快递100
 * @author xiongzw
 * @date 2015-08-25
 */
namespace Cron\Model;
use Common\Model\BaseModel;
class SendModel extends BaseModel{
	protected $tableName = "order_send";
	
	/**
	 * 获取发货数据
	 */
	public function getSend(){
		$where = array(
				"send_status"=>1,
				"send_type"=>1
		);
		$send = $this->where($where)->find();
		if($send['logistics']){
			$send['code'] = M("LogisticsCompany")->where(["lc_id"=>$send['logistics']])->getField("lc_code");
		}
		return $send;
	}
	
	/**
	 * 执行发货
	 * @param Integer $send_id 发货id
	 * @param String  $code 物流公司代码
	 * @param String  $number 订单号
	 */
	public function setSend($send_id,$order_id,$code,$number,$uid){
		M("OrderSend")->where(['send_id'=>$send_id])->save(['send_status'=>-1]);
		$this->startTrans();
		//对接快递100
		$result = D("Logistics/Logistics","Service")->callKauaiDi100($code,$number);
		//发货状态
		$return = M("OrderSend")->where(['send_id'=>$send_id])->save(['send_status'=>2,'send_time'=>NOW_TIME]);
		//更改订单状态
		$order_result = M("Order")->where(['order_id'=>$order_id])->save(
				[
				'shipping_status' =>1,
				'shipping_time' => NOW_TIME
				]
		);
		//记录发货日志
		$rec_result = D("Admin/SendGoods")->sendRecord($order_id, $uid,1);
		if($result=="SUCCESS" && $return!==false && $order_result!==false && $rec_result!==false){
			$this->commit();
			return true;
		}else{
			$this->rollback();
			return false;
		}
	}
}