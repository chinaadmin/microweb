<?php
/**
 * 订单取货码模型
 * @author xiongzw
 * @date 2015-07-08
 */
namespace Cron\Model;
use Common\Model\BaseModel;
class OrderMessageModel extends BaseModel{
	protected $tableName = "order";
	/**
	 * 获取验证码发送失败的订单
	 * @param 每次获取条数
	 * @return array 
	 */
	public function getNoCode($limit=10){
		$where = array(
			"shipping_type"=>0,
			"pay_status"=>2,
		    "shipping_status"=>0,
			"status"=>1,
			"code_time"=>0,
			"delete_time"=>0
		);
		$data = $this->where($where)->limit($limit)->getField("order_id",true);
		return $data;
	}
	/**
	 * 发送短信
	 * @param array $orderIds
	 */
	public function sendMessag(array $orderIds){
		D("Admin/Order")->sendMessage($orderIds);
	}
}