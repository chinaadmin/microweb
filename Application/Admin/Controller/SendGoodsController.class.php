<?php
/**
 * 商品发货操作
 * @author xiongzw
 * @date 2015-06-04
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class SendGoodsController extends AdminbaseController{
	protected $send_model;
	public function _initialize(){
		parent::_initialize();
		$this->send_model = D('Admin/SendGoods');
	}
	/**
	 * 发货
	 */
	public function send() {
		$order_id = I ( 'post.order_id', '' );
		$handle = I ( 'post.uid', '' );
		if (! $order_id || ! $handle) {
			$this->ajaxReturn ( $this->result->error ( "未知错误" )->toArray () );
			exit;
		}
		$where = [ 
				'order_id' => $order_id 
		];
		$order_info = M ( "Order" )->where ( $where )->field ( true )->find ();
		$data = array (
				"delivery_time" => NOW_TIME,
				"delivery_add_time" => NOW_TIME,
				"handle" => $handle,
				"shipping_status" => 1,
				"shipping_time" => NOW_TIME,
				"receiving_time"=>NOW_TIME,
		);
		if (M ( "Order" )->where ( $where )->save ( $data )) {
			$this->send_model->recSend ( $order_id, $this->user ['uid'] );
			$this->ajaxReturn ( $this->result->success ()->toArray () );
		} else {
			$this->ajaxReturn ( $this->result->error ( "未知错误" )->toArray () );
		}
	}
	
	/**
	 * 物流发货
	 */
	public function sendLogistics(){
		$logistics = I("post.logistics",'');
		$sender = I("post.sender_people",'0','intval');
		$logistics['sender'] = $sender;
		$result = $this->send_model->sendLogistics($logistics,$this->user['uid'],1);
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 送货上门发货 
	 */
	 public function sendDoor(){
	 	$data = I('post.door','');
	 	$result = $this->send_model->sendLogistics($data,$this->user['uid'],2);
	 	$this->ajaxReturn($result->toArray());
	 }
	
	/**
	 * 获取物流、物流订单数据
	 */
	public function getLogOrder(){
		if(IS_AJAX){
			$order_ids = I("post.order_id",'');
			$result = $this->send_model->forder($order_ids,1);
			$this->ajaxReturn($result->toArray());
		}
	}
	
	/**
	 * 物流发货获取运单号
	 */
	public function getSendCode(){
		$data = I('post.logistics','');
		$ware_id = I('request.ware_id',0,'intval');
		$orders = array_keys($data);
		$code = array();
		foreach($orders as $key=>$v){
		   $code[$key]['code'] = $this->send_model->getSendCode($v,$ware_id);
		   $code[$key]['order_id']=$v;
		}
		$this->ajaxReturn($code);
	}
	
	/**
	 * 获取物流订单打印数据
	 */
	public function getPrintLogistics(){
		if(IS_AJAX){
			$order_ids = I("post.order_id",'');
			$result = $this->send_model->forderByPrint($order_ids);
			$this->ajaxReturn($result->toArray());
		}
	}
	
	/**
	 * 销售发货单
	 */
	public function salesList(){
		$order_ids = I("post.orders",'');
		$data = $this->send_model->getSales($order_ids);
		$this->assign('lists',$data);
		$this->display("salesList");
	}
	
	/**
	 * 上门送货数据
	 */
	public function delivery(){
		$order_ids = I("post.order_id",'');
		$result = $this->send_model->forder($order_ids,2);
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 通过门店id获取门店成员信息
	 */
	public function getStoresUser(){
		if(IS_AJAX){
			$stores_id = I("post.stores_id",0,'intval');
			if($stores_id){
			  $user = D("Stores/StoresUser")->getUsers($stores_id);
			  $user = current($user);
			  $this->ajaxReturn($this->result->content($user)->success()->toArray());
			}
		}
		$this->ajaxReturn($this->result->error()->toArray());
	}
	
	/**
	 * 快递单模板
	 */
	public function getTemplate(){
		$et_id = I("request.et_id",0,'intval');
		$order_ids = I("post.print_order","");
		$sender=I("request.sender",0,'int');
		$result = $this->send_model->getTemplate($et_id,$sender,$order_ids);
		if(IS_AJAX){
			$this->ajaxReturn($result->toArray());
		}else{
			if($result->isSuccess()){
				$data = $result->toArray();
				$this->assign("lists",$data['result']);
				$this->display("getTemplate");
			}else{
				$this->error("未知错误！");
			}
		}
	}
}