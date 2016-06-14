<?php
/**
 * 后台首页模型
 * @author xiongzw
 * @date 2015-06-16
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class IndexModel extends AdminbaseModel{
	Protected $autoCheckFields = false;
	/**
	 * 获取状态订单数
	 * @param  $status
	 */
	public function ordersStatus($status,$stores_id=0,$pay_status=null,$shipping_status=null){
		$where = array(
				"status" => $status,
				"delete_time"=>0
		);
		if(!is_null($pay_status)){
			$where['pay_status'] = $pay_status;
		}
		if(!is_null($shipping_status)){
			$where['shipping_status'] = $shipping_status;
		}
		if($stores_id){
			$where['stores_id'] = $stores_id;
		}
		return M("Order")->where($where)->count();
	}
	/**
	 * 获取退款状态数
	 * @param $status 退款状态
	 */
	public function refundStatus($status,$stores_id=0){
		$where = array(
				"refund_status" => $status,
				"delete_time"=>0
		);
		if($stores_id){
			$orders_id = M("Order")->where("stores_id={$stores_id}")->getField("order_id",true);
			if($orders_id){
				$where['order_id'] = array('in',$orders_id);
			}
		}
		return M("Refund")->where($where)->count();
	}
	
	/**
	 * 库存警告数
	 */
	public function stockWarning(){
		$num = C("JT_CONFIG_WEB_STOCK_WARNING_NUM");
		$num = $num>0?$num:10;
		$where = array(
				"stock_number"=>array("LT",$num),
				"delete_time"=>0
		);
		return M("Goods")->where($where)->count();
	}
	/**
	 * 今日/昨日订单数、付款数
	 * @param $type 1:今日  2：昨日
	 */
	public function orderDay($type=1,$stores_id=0,$field="add_time"){
		if($type==1){
			$time = date('Y-m-d');
		}else{
			$time = date('Y-m-d',strtotime("-1 day"));
		}
		$where = array(
				"_string" => "FROM_UNIXTIME(".$field.", '%Y-%m-%d')='".$time."'",
				"delete_time"=>0
		);
		if($stores_id){
			$where['stores_id'] = $stores_id;
		}
		return M("Order")->where($where)->count();
	}
	/**
	 * 获取会员记录数
	 * @param number $type
	 */
	public function userCount($type=1){
		$where = array(
				"delete_time"=>0
		);
		if($type==1){
			$time = date('Y-m-d');
		}
		if($type==2){
			$time = date('Y-m-d',strtotime("-1 day"));
		}
		if(!empty($time)){
			$where = array(
					"_string" => "FROM_UNIXTIME(add_time, '%Y-%m-%d')='".$time."'"
			);
		}
		return M("User")->where($where)->count();
	}
	/**
	 * 商品记录数
	 */
	public function goodsCount($is_sale=null){
		$where = array(
				"delete_time"=>0
		);
		if(!is_null($is_sale)){
			$where['is_sale'] = $is_sale;
		}
		return M("Goods")->where($where)->count();
	}
}