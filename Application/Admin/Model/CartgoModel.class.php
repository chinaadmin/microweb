<?php
/**
 * 发货管理模型
 * @author xiongzw
 * @date 2015-08-10
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class CartgoModel extends AdminbaseModel{
	protected $tableName = "order_send";
	
	/**
	 * 发货列表视图
	 */
	public function listView(){
		$viewFields = array(
				"OrderSend"=>array(
					"send_id",
					"order_id",
					"send_sn",
					"send_type",
					"send_num",
					"send_time",
					"logistics",
					"stores_id",
					"_type"=>"LEFT"
	        	),
				"OrderReceipt"=>array(
				    "mobile",
					"name",
					"_on"=>"OrderSend.order_id = OrderReceipt.order_id",
					"_type"=>"LEFT"	
		        ),
				"Order"=>array(
				   "shipping_status",
					"_as"=>"Orders",
					"_on"=>"OrderSend.order_id = Orders.order_id"		
		        )
		);
		return $this->dynamicView($viewFields);
	}
	
	/**
	 * 获取列表物流、门店信息
	 * @param $lists
	 */
	public function getLists($lists){
		//获取物流信息
		$lc_ids = array_column($lists, "logistics");
		if($lc_ids){
		  $logistics = D("Admin/Logistics")->getLogisticsById($lc_ids,"lc_id,lc_name");
		  if($logistics){
			  foreach ($lists as &$v){
			  	foreach ($logistics as $vo){
			  		if($v['logistics'] == $vo['lc_id']){
			  	    	$v['the_name'] = $vo['lc_name'];
			  		}
			  	}
			  }
		  }
		}
		//获取门店信息
		$stores_id = array_column($lists, "stores_id");
		if($stores_id){
			$stores = D("Stores/Stores")->getStoresByIds($stores_id,"stores_id,name");
			if($stores){
				foreach ($lists as &$v){
					foreach ($stores as $vo){
						if($v['stores_id'] == $vo['stores_id']){
						 $v['the_name'] = $vo['name'];
						}
					}
				}
			}
		}
		return $lists;
	}
	
	/**
	 * 发货详情视图
	 */
	public function infoView(){
		$viewFields = array(
				"OrderSend"=>array(
						"send_id",
						"send_sn",
						"send_type",
						"send_num",
						"send_time",
						"logistics",
						"stores_id",
						"delivery",
						"ware_id",
						"send_remark",
						"_type"=>"LEFT"
				),
				"OrderReceipt"=>array(
						"mobile",
						"name",
						"address",
						"_on"=>"OrderSend.order_id = OrderReceipt.order_id",
						"_type"=>"LEFT"
				),
				"Order"=>array(
						"order_sn",
						"pay_time",
						"shipping_status",
						"_as"=>"Orders",
						"_on" =>"OrderSend.order_id = Orders.order_id",
						"_type"=>"LEFT"
		        ),
				"AdminUser"=>array(
				        "nickname",
						 "_on" =>"OrderSend.handle = AdminUser.uid",
						 "_type"=>"LEFT"	
		        ),
				"User"=>array(
				        "username",
						"_on" => "Orders.uid=User.uid"		
		         )
		);
		return $this->dynamicView($viewFields);
	}
	
	/**
	 * 获取发货详情
	 */
	public function getInfo($send_id){
		$infoView = $this->infoView();
		$where = array(
				"OrderSend.send_id" => $send_id
		);
		$info = $infoView->where($where)->find();
	    if($info){
			if($info['send_type'] == 1){
				$info['the_name'] = current(D("Admin/Logistics")->getById($info['logistics'],'lc_name'));
			}else{
				$info['the_name'] = current(D("Stores/Stores")->getStoresById($info['stores_id'],'name'));
			}
			//发件人信息
			if($info['send_type'] !=0 && $info['ware_id']){
				$info['ware'] = D("Admin/WareHouse")->getWareById($info['ware_id']);
			}
			//配送人
			if($info['send_type']==2 && $info['delivery']){
				$result = D("Admin/Admin")->getUserById($info['delivery'])->toArray();
				$info['delivery'] = $result['result']['nickname'];
			}
	    }
		return $info;
	}
	
	/**
	 * 通过订单id获取配送信息
	 * @param  $order_id
	 */
	public function dispatching($order_id){
		$where = array(
				"order_id"=>$order_id
		);
		$data = $this->where($where)->find();
		if($data){
			if($data['send_type'] == 1){  //物流发货
				$data['logistics_name'] = D("Admin/Logistics")->getById($data['logistics'],'lc_name');
			}else{
				$data['stores_name'] = current(D("Stores/Stores")->getStoresById($data['stores_id'],'name'));
				if($data['send_type']==2){
					$result = D("Admin/Admin")->getUserById($data['delivery'])->toArray();
					$data['delivery'] = $result['result']['nickname'];
					$data['delivery_mobile'] = $result['result']['mobile'];
				}
			}
		}
		return $data;
	}
}