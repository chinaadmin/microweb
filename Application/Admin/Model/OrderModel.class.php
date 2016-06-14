<?php

/**
 * 订单模型
 * @author xiongzw
 * @date 2015-05-20
 */
namespace Admin\Model;

use Common\Model\AdminbaseModel;

class OrderModel extends AdminbaseModel {
	protected $tableName = "order";
	public $order_status = [ 
			// 支付状态
			'pay_status' => [ 
					0 => '未付款',
					1 => '付款中',
					2 => '已付款',
					3 => '已退款' 
			],
			// 发货状态
			'shipping_status' => [ 
					0 => '未发货',
					1 => '已发货',
					2 => '已收货',
					3 => '已退货' 
			],
			// 订单状态
			'status' => [ 
					0 => '待确认',
					1 => '已确认',
					2 => '已取消',
					3 => '无效',
					4 => '已关闭',
					5 => '已过期',
					6 => '已完成' 
			],
			// 配送方式
			'shipping_type' => [ 
					0 => '门店自提',
					1 => '普通快递',
					2 => '送货上门' 
			],
			// 是否需要发票
			'needs_invoice' => [ 
					0 => '不需要',
					1 => '需要' 
			],
			//来源
			'source' => [
			        3=> 'PC端',
			        2=> '手机端',
			        1=> '微商城'
			]
	];
	/**
	 * 格式化状态
	 *
	 * @param array $status        	
	 */
	public function formatStatus(Array $status) {
		$data = array ();
		array_walk ( $status, function (&$item, $key) use(&$data) {
			if (is_array ( $item )) {
				foreach ( $item as $k => &$vs ) {
					$item [$k] = array (
							'text' => $vs 
					);
				}
				$data [$key] = $item;
			} else {
				$data [$key] = array (
						'text' => $item 
				);
			}
		} );
		return $data;
	}
	
	/**
	 * 订单试图模型
	 */
	public function viewModel($field = array()) {
		$viewFields = array (
				'Order' => array (
						"order_id",
						"order_sn",
						"uid",
						"add_time",
						"needs_invoice",
						"pay_time",
						"pay_type",
						"order_amount",
						"postscript",
						"status",
						"invoice_id",
						"pay_status",
						"shipping_status",
						"delivery_time",
						"handle",
						"money_paid",
						"goods_amount",
						"delivery_add_time",
						"seller_postscript",
						"shipping_type",
						"stores_id",
                        "stores_time",
						"discount_price",
						"order_code",
						"shipping_time",
                        "coupon_id",
                        "coupon_price",
						"confirm_time",
                        "integral",
                        "integral_price",
                        "shipment_price",
                        "source",
						"order_discount",
                        "receiving_time",
						'_type' => 'LEFT',
						'_as' => "Orders" 
				),
				'User' => array (
						"username",
						"aliasname",
						"mobile",
						'_on' => 'Orders.uid=User.uid',
						'_type' => 'LEFT' 
				),
				'OrderReceipt' => array (
						"name" => 'receipt_name',
						'mobile' => 'receipt_mobile',
						"tel",
						"zipcode",
						"localtion",
						"address",
						'_on' => "Orders.order_id=OrderReceipt.order_id",
						'_type' => 'LEFT' 
				),
				"Payment" => array (
						"code",
						"name" => 'payment_name',
						"_on" => "Orders.pay_type=Payment.code",
						"_type" => "LEFT" 
				),
				"Invoice" => array (
						"type" => "invoice_type",
						"invoice_payee",
						"invoice_content",
						"_on" => "Orders.invoice_id=Invoice.invoice_id" 
				) 
		);
		return $this->dynamicView ( $viewFields );
	}
	
	/**
	 * 格式化订单列表
	 *
	 * @param $lists 订单数组        	
	 */
	public function formatList($lists) {
		if (! empty ( $lists )) {
			$order_ids = array_column ( $lists, "order_id" );
			$goods = $this->getGoodsById ( $order_ids );
			foreach($goods as &$v){
				if($v['promotions_discount']){  //促销折后商品单价
					$v['goods_price'] = discountAmount($v['goods_price'],$v['promotions_discount']);
				}
			}
			$send_order = $this->canSend($order_ids);
			foreach ( $lists as &$v ) {
				foreach ( $goods as $vo ) {
					if ($v ['order_id'] == $vo ['order_id']) {
						$v ['goods'] [] = $vo;
					}
				}
				if($send_order){
					if(in_array($v['order_id'], $send_order)){
						$v['send'] = 1;
					}else{
						$v['send'] = 0 ;
					}
				}else{
					$v['send'] =0;
				}
			}
		}
		return $lists;
	}
	
	/**
	 * 获取不能发货的订单
	 * @param $order_id 订单id
	 */
	public function canSend($order_id,$shipping_type=0){
		$where = array(
				"order_id" => array("in",(array)$order_id),
				"pay_status"=>2,
				"shipping_status"=>0,
				"status"=>1,
				"shipping_type"=>$shipping_type 
		);
		$order_id = $this->where($where)->getField("order_id",true);
		if($order_id){
		 //有退款退货未审核的订单不能发货
// 		 $where = array(
// 			"order_id" => array('in',$order_id),
// 		 	"refund_status"=>0,
// 		 	"delete_time"=>0
// 		 );
// 		 $refund_order = M("Refund")->where($where)->getField("order_id",true);
// 		 if($refund_order){
// 		 	$order_id = array_diff((array)$order_id, (array)$refund_order);
// 		 }
// 		 if($order_id){
// 		 	//订单中商品全部退款退货不发货
// 		 	$where = array(
// 		 			"order_id"=>array("in",$order_id),
// 		 			"refund_status"=>0
// 		 	);
// 		 	$order_id = M("OrderGoods")->where($where)->getField("order_id",true);
// 		 }
			$where = array(
				"order_id"=>array("in",$order_id),	 			
			);
			$orderGoods = M("OrderGoods")->where($where)->select();
			$where = array(
	 			"order_id"=>array("in",$order_id),
	 			"delete_time"=>0
			);
			$refunds = M("Refund")->where($where)->select();//退款订单
			$return_data = array();
			foreach($orderGoods as $v){
		 		if($refunds){  //未审核的订单不发货、全部退货商品不发货
			 		foreach($refunds as $vs){
			 			if($v['rec_id']==$vs['rec_id'] && $v['number']>$vs['refund_num'] && $vs['refund_status']!=0){
			 				$return_data[]=$v['order_id'];
			 			}
			 		}
		 		}
		 	}
		 	if($return_data){
		 	 $order_id = $return_data;
		 	}
		}
		return $order_id;
	}
	
	/**
	 * 通过订单id获取商品详情
	 *
	 * @param $order_id 订单id        	
	 */
	public function getGoodsById($order_id) {
		$where = array ();
		if (is_array ( $order_id )) {
			$where ['order_id'] = array (
					'in',
					$order_id 
			);
		} else {
			$where ['order_id'] = $order_id;
		}
		$data = $this->goodsView ()->where ( $where )->select ();
		$rec_ids = array_column($data, "rec_id");
		$refunds = D("Admin/Refund")->getByRec($rec_ids,"rec_id,refund_status");
		if($data && $refunds){
		  foreach($data as &$v){
			 foreach($refunds as $vo){
			 	if($v['rec_id'] == $vo['rec_id']){
			 		$v['refundstatus'] = $vo['refund_status'];
			 	}
			 }
		  }
		}
		$this->getPic ( $data );
		return $data;
	}
	
	/**
	 * 通过订单rec_id获取单个商品详情
	 *
	 * @param $rec_id 订单rec_id      	
	 */
	public function getGoodsByInfo($rec_id) {
		$where = array ();
		if (is_array ( $rec_id )) {
			$where ['rec_id'] = array (
					'in',
					$rec_id 
			);
		} else {
			$where ['rec_id'] = $rec_id;
		}
		$data = $this->goodsView ()->where ( $where ) -> select ();
		$rec_ids = array_column($data, "rec_id");
		$refunds = M("order_goods")->getByRec($rec_ids,"rec_id,refund_status");
		if($data && $refunds){
		  foreach($data as &$v){
			 foreach($refunds as $vo){
			 	if($v['rec_id'] == $vo['rec_id']){
			 		$v['refundstatus'] = $vo['refund_status'];
			 	}
			 }
		  }
		}
		$this->getPic ( $data );
		return $data[0];
	}
	
	/**
	 * 通过订单id获取订单
	 * @param $order_id
	 */
	public function getByOrderId($order_id,$field=true){
		$where = array(
				'order_id'=>$order_id,
				'delete_time'=>0
		);
		return $this->field($field)->where($where)->select();
	}

	/**
	 * 通过订单id获取退货订单
	 * @param $order_id
	 */
	public function getRefundList($order_id){
		return  M("Refund")->where(['order_id'=>$order_id])->select();
	}

	/**
	 * 通过订单id获取订单
	 * @param $order_id
	 */
	public function getOrderInfo($order_id){
		return M('Order')->where(['order_id'=>$order_id])->find();
	}

	
	/**
	 * 获取图片
	 *
	 * @param array $data        	
	 */
	public function getPic(&$data) {
		$attr = array ();
		foreach ( $data as $key => &$v ) {
			$v ['norms_value'] = json_decode ( $v ['norms_value'], true );
			if ($v ['norms_value']) {
				foreach ( $v ['norms_value'] as $vo ) {
					if ($vo ['photo']) {
						$attr [$key] = $vo ['photo'];
						$v ['norms_attr'] = $vo ['photo'];
					}
				}
			}
			if (empty ( $v ['norms_attr'] )) {
				$attribute = json_decode ( $v ['attribute_id'], true );
				if ($attribute ['default']) {
					$attr [$key] = $attribute ['default'];
				} else {
					$attr [$key] = $attribute ['0'];
				}
			}
		}
		$attr = D ( 'Upload/AttachMent' )->getAttach ( $attr );
		foreach ( $data as &$v ) {
			foreach ( $attr as $vo ) {
				if ($vo ['att_id'] == $v ['norms_attr'] || in_array ( $vo ['att_id'], json_decode ( $v ['attribute_id'], true ) )) {
					$v ['pic'] = $vo ['path'];
				}
			}
		}
	}
	
	/**
	 * 商品试图
	 */
	public function goodsView() {
		$viewFields = array (
				'OrderGoods' => array (
						"rec_id",
						"goods_price",
						"order_id",
						"goods_id",
						"market_price",
						"number",
						"norms",
						"norms_value",
						"refund_status",
						"goods_comment_status",
						"promotions_discount",
						"promotions_id",
						'_type' => 'LEFT' 
				),
				'Goods' => array (
						"code",
						"name",
						"attribute_id",
						'_on' => 'OrderGoods.goods_id=Goods.goods_id',
						"_type"=>"LEFT" 
				),
				"Refund" => array(
				        "refund_status"=>"refundStatus",
						"refund_num",
						'_on' => 'OrderGoods.rec_id=Refund.rec_id'
				) 
		);
		return $this->dynamicView ( $viewFields );
	}
	/**
	 * 获取订单日志
	 *
	 * @param String $order_id
	 *        	订单id
	 */
	public function order_record($order_id) {
		$where = array (
				"order_id" => $order_id 
		);
		$data = M ( "OrderAction" )->where ( $where )->select ();
		$manager = array ();
		$user = array ();
		foreach ( $data as $v ) {
			if ($v ['is_seller']) {
				$manager [] = $v ['handle'];
			} else {
				$user [] = $v ['handle'];
			}
		}
		if ($manager) {
			$manager = M ( 'AdminUser' )->where ( array (
					'uid' => array (
							'in',
							$manager 
					) 
			) )->select ();
		}
		if ($user) {
			$user = M ( 'User' )->where ( array (
					'uid' => array (
							'in',
							$user 
					) 
			) )->select ();
		}
		$arr = array_merge ( $manager, $user );
		foreach ( $data as &$v ) {
			foreach ( $arr as $vo ) {
				if ($v ['handle'] == $vo ['uid']) {
					$v ['username'] = $vo ['username'];
				}
			}
		}
		return $data;
	}
	
	/**
	 * 导出订单
	 *
	 * @param array $lists
	 *        	要导出的数据
	 */
	public function exportExcel($lists) {
		$excel = new \Admin\Org\Util\ExcelComponent ();
		$excel = $excel->createWorksheet ();
		$pay_status = $this->order_status ['pay_status'];
		$shipping = $this->order_status ['shipping_status'];
		$shipping_type = $this->order_status ['shipping_type'];
		$needs_invoice = $this->order_status ['needs_invoice'];
		$status = $this->order_status ['status'];
		$excel->head ( array (
				'订单编号',
				'会员名',
				'支付方式',
				'总金额',
				'订单状态',
				'支付状态',
				'发货状态',
				'买家留言',
				'收货人姓名',
				'运送方式',
				'联系电话',
				'联系手机',
				'订单创建时间',
				'订单付款时间',
				'商品名称',
				'商品单价',
				'宝贝数量',
				'订单备注',
				'是否需要发票' 
		) );
		$sn = "export";
		$data = array ();
		$key = 0;
		foreach ( $lists as  $v ) {
			$sn = (string)$v ['order_sn'];
			$data [$key] ["order_sn"] = $v ["order_sn"];
			$data [$key] ["username"] = $v ["username"];
			$data [$key] ["pay_type"] = $v ["pay_type"];
			$data [$key] ["goods_amount"] = $v ['goods_amount'];
			$data [$key] ["status"] = $this->order_status['status'][$v["status"]];//订单状态
			$data [$key] ["pay_status"] = $pay_status [$v ['pay_status']];
			$data [$key] ["shipping_status"] = $shipping [$v ['shipping_status']];
			$data [$key] ["postscript"] = $v ["postscript"];
			$data [$key] ["receipt_name"] = $v ["receipt_name"];
			$data [$key] ["shipping_type"] = $v ["shipping_type"];
			$data [$key] ["tel"] = $v ['tel'];
			$data [$key] ["receipt_mobile"] = $v ["receipt_mobile"];
			$data [$key] ['add_time'] = date ( 'Y-m-d H:i:s', $v ['add_time'] );
			$data [$key] ['pay_time'] = date ( 'Y-m-d H:i:s', $v ['pay_time'] );
			$data [$key] ["goods_name"] = $v ['goods'][0]['name'];
			$data [$key] ['goods_number'] = $v ['goods'][0]['number'];
			$data [$key] ['goods_price'] = $v ['goods'][0]['goods_price'];
			$data [$key] ['seller_postscript'] = $v ['seller_postscript'];
			$data [$key] ['needs_invoice'] = $needs_invoice [$v ['needs_invoice']];
			$this->addGoodsRow($v['goods'],$key,$data);
			$key++;
		}
		$excel->listData ( $data, array (
				"order_sn",
				"username",
				"pay_type",
				"goods_amount",
				"status",
				"pay_status",
				"shipping_status",
				"postscript",
				"receipt_name",
				"shipping_type",
				"tel",
				"receipt_mobile",
				"add_time",
				"pay_time",
				"goods_name",
				"goods_price",
				"goods_number",
				"seller_postscript",
				"needs_invoice" 
		));
		$excel->output ( $sn . ".xlsx" );
	}
	//增加产品行
	private function addGoodsRow($goodsArr,&$key,&$data){
		if(count($goodsArr) <= 1){
			return;
		}
		unset($goodsArr[0]);
		foreach ($goodsArr as $v){
			$key++;
			$data [$key] ["goods_name"] = $v['name'];
			$data [$key] ["goods_number"] = $v['number'];
			$data [$key] ["goods_price"] = $v['goods_price'];
		}
		
	}
	/**
	 * 订单确认
	 *
	 * @param array $order_id        	
	 * @param $uid 用户id        	
	 */
	public function orderConfirm(array $order_id, $uid) {
		$where = array (
				"pay_status" => 2,
				"status" => 0,
				"order_id" => array (
						'in',
						$order_id 
				) 
		);
		$result = $this->setData ( $where, array (
				'status' => 1,
				'handle' => $uid,
				'confirm_time' => NOW_TIME 
		) );
		$this->orderRecordAction ( $order_id, $uid );
		$this->sendMessage ( $order_id );
		return $result;
	}
	
	/**
	 * 记录订单日志、订单提货码
	 *
	 * @param $order_id 订单id        	
	 * @param $uid 用户id        	
	 */
	public function orderRecordAction(array $order_id, $uid) {
		$codes = array ();
		$data = array ();
		foreach ( $order_id as $key => $v ) {
			$code = $this->getCode ();
			$codes [$v] = $code;
			$data [$key] = array (
					'order_id' => $v,
					"action" => json_encode ( array (
							'status' => 1 
					) ),
					'is_seller' => 1,
					'handle' => $uid,
					'remark' => '商家确认了订单',
					'front_remark' => '您的订单审核通过，已分给指定门店，本次交易的提货码是：' . $code,
					'add_time' => NOW_TIME,
					'type' => 0 
			);
		}
		if ($codes) {
			$sql = array2UpdateSql ( $codes, C ( 'DB_PREFIX' ) . "order", 'order_code', 'order_id' );
			$this->execute ( $sql );
		}
		if ($data) {
			M ( "OrderAction" )->addAll ( $data );
		}
	}
	
	/**
	 * 订单确认成功后发生邮件、短信信息
	 *
	 * @param $uid 操作用户id        	
	 * @param $order_id 订单id        	
	 */
	public function sendMessage(Array $order_id) {
		$viewFields = array (
				"User" => array (
						"email",
						"username",
						"_type" => "LEFT" 
				),
				"Order" => array (
						"order_id",
						"order_sn",
						"order_code",
						"shipping_type",
						"_as" => "Orders",
						"_on" => "User.uid = Orders.uid",
						"_type" => "LEFT" 
				),
				"Stores" => array (
						"name",
						"phone",
						"_on" => "Orders.stores_id=Stores.stores_id",
						"_type" => "LEFT" 
				),
				"OrderReceipt" => array (
						"mobile",
						"_on" => "Orders.order_id=OrderReceipt.order_id" 
				) 
		);
		$where = array (
				"Orders.order_id" => array (
						'in',
						$order_id 
				) 
		);
		$data = $this->dynamicView ( $viewFields )->where ( $where )->select ();
		$messageObj = new \Common\Org\Util\MobileMessage ();
		$codes = array ();
		$arr = array ();
		foreach ( $data as $key => $v ) {
			if($v["shipping_type"]==0){
				$code = $v ['order_code'];
				if(empty($code)){
					$code = $this->getCode();
					$arr[$v['order_id']] = $code;
				}
					// 短信模版
				$template = getTempContent ( "pk_up_code", 1, array (
						"trade_name" => $v ['name'],
						"code" => $code,
						"stores_tel" => $v ['phone'] 
				) );
				$mobileTemplate = html_entity_decode ( strip_tags ( $template ) );
				// 发送短信
				$mobileResult = $messageObj->mobileSend ( $v ['mobile'], $mobileTemplate );
				// 发送邮件
				// $emailResult = sendMail ( $v ['email'], $v ['username'], "必好货商品提取码", $template );
				if ($mobileResult) {
					$codes [$v ['order_id']] = NOW_TIME;
				}
			}
		}
		if($arr){
			$codeSql = array2UpdateSql ( $arr, C ( 'DB_PREFIX' ) . "order", 'order_code', 'order_id' );
			$this->execute ( $codeSql );
		}
		if ($codes) {
			$sql = array2UpdateSql ( $codes, C ( 'DB_PREFIX' ) . "order", 'code_time', 'order_id' );
			return $this->execute ( $sql );
		}
		return true;
	}
	
	/**
	 * 提货码
	 */
	public function getCode() {
		$code = rand_string ( 6, 1 );
		$where = array (
				"order_code" => $code 
		);
		if ($this->where ( $where )->find ()) {
			$this->getCode ();
		}
		return $code;
	}
	
	/**
	 * 批量确认订单过滤
	 *
	 * @param $order_id 订单id        	
	 * @return array
	 */
	public function orderFilter(Array $order_id) {
		$where = array (
				'order_id' => array (
						'in',
						$order_id 
				),
				'pay_status' => 2,
				'status' => 0 
		);
		return $this->where ( $where )->getField ( "order_id", true );
	}
	
	/**
	 * 通过订单id或者订单编号获取订单信息
	 *
	 * @param $order 订单id|订单编号        	
	 * @return array
	 */
	public function getOrder($order, $field = true) {
		$where = array (
				"order_id|order_sn" => $order 
		);
		return $this->field ( $field )->where ( $where )->find ();
	}
	
	/**
	 * 批量获取订单信息
	 * @param  $order 订单id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, mixed, multitype:, unknown, object>
	 */
	public function getOrders($order,$field=true){
		$where = array(
				"order_id"=>array("in",(array)$order)
		);
		return $this->field($field)->where($where)->select();
	}
	
	/**
	 * 
	 * @param $order_id 订单id        	
	 * @param string $field        	
	 * @return Ambigous <\Think\mixed, boolean, multitype:, unknown, mixed, object>
	 */
	public function getOrderAction($order_id, $type = 1, $field = true, $extend_where = []) {
		$where = array (
				"order_id" => $order_id,
				"type" => $type 
		);
		if(is_array($extend_where) && $extend_where){
			$where = array_merge($where,$extend_where);
		}
		return M ( "OrderAction" )->field ( $field )->where ( $where )->select ();
	}
	
	/**
	 * 获取订单收货地址
	 * @param $order_id 订单id
	 */
	public function getReceipt($order_id,$field=true){
		$where = array(
				'order_id' => array('in',(array)$order_id)
		);
		return M("OrderReceipt")->field($field)->where($where)->select();
	}
	
	/**
	 * 獲取要退款商品詳情
	 */
	public function getRefundInfo($rec_id){
		$viewFields = array(
				"OrderGoods"=>array(
					"rec_id",
					"goods_price",
					"number",
					"norms_value",
					"_type"=>"LEFT"
		         ),
				"Order"=>array(
					"_as"=>"Orders",
					"order_sn",
					"uid",
					"money_paid",
					"goods_amount",
					"shipment_price",
					"order_id",

					'coupon_price', // 优惠券金额

					"_on" =>"OrderGoods.order_id=Orders.order_id",
					"_type"=>"LEFT"
		         ),
				"Goods"=>array(
						"goods_id",
						"attribute_id",
						"name",
						"_on"=>"OrderGoods.goods_id=Goods.goods_id"
				 )
		);
		$viewModel = $this->dynamicView($viewFields);
		$where = ['rec_id'=>$rec_id];
		$data = $viewModel->where($where)->find();
		//商品价格
		$discount = number_format(($data['money_paid']-$data['shipment_price'])/$data['goods_amount'],2);
		// $data['goods_price'] = $discount * $data['goods_price'];
		$data['norms_value'] = json_decode($data['norms_value'],true);
		if (!empty ( $data )) {
			$attribute = json_decode ( $data ['attribute_id'], true );
			$attr = $attribute ['default'] ? $attribute ['default'] : $attribute [0];
		}
		$photo = current(D ( 'Upload/AttachMent' )->getAttach ( $attr ));
		$data['photo'] = $photo['path'];
		return $data; 
		$goodsList = M('Order_goods')->where(['order_id'=>$order_id])->select();
	}

	/**
	 * 获取退货订单数据
	 * @param $order_id
	 */
	public function getRefundLists($order_id){
		if($order_id){
			$refundMoney=0;
			$refundList = M('Refund')->where(['order_id'=>$order_id])->select();
			
			if(!empty($refundList)){
				foreach($refundList as $key => $v){
					$refundMoney+=$v['refund_money'];
				}
			}
			return $refundMoney;
		}
	}

	/**
	 * 获取退货订单数据
	 * @param $order_id
	 */
	public function getGoodsLists($order_id){
		if($order_id){
			$goodsMoney=0;
			$goodsList = M('Order_goods')->where(['order_id'=>$order_id])->select();
			
			if(!empty($goodsList)){
				foreach($goodsList as $key => $v){
					$goodsMoney+=$v['goods_price'];
				}
			}
			return $goodsMoney;
		}
	}
	
	/**
	 * 获取订单商品详情
	 * @param $rec_id
	 * @param string $field
	 */
	public function orderGoods($rec_id,$field=true){
		return M("OrderGoods")->field($field)->where(['rec_id'=>$rec_id])->find();
	}
}