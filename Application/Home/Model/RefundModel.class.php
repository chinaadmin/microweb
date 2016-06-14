<?php
/**
 * 前台退款模型
 * @author xiongzw
 * @date 2015-06-01
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class RefundModel extends HomebaseModel{
	protected $_validate = array (
			[ 
					'rec_id',
					'require',
					'REC_REQUIRE::退货商品id不能为空' 
			],
			[ 
					'rec_id',
					'existRecId',
					'REFUND_ALREADY::该商品已申请退货,请不要重复提交！',
					self::EXISTS_VALIDATE ,
					'callback',
					self::MODEL_INSERT 
			],
			[ 
					'refund_num',
					'/^[1-9]+[0-9]*$/',
					'REFUND_NUM_REQUIRE::退款数量不能小于1' 
			],
			[ 
					'refund_num',
					'checkNum',
					'REFUND_NUM_SPILL::退款数量不能大于最大退款数',
					self::EXISTS_VALIDATE,
					'callback' 
			],
			[ 
					'refund_reasons',
					'/^[1-9]+$/',
					'REFUND_REASON_REQUIRE::退款说明不能为空' 
			] 
	);
	/**
	 * 验证退货数量
	*/
	public function checkNum(){
		$rec_id = I('post.recId','');
		$rec = $this->recInfo($rec_id);
		$num = I('post.number',0,'intval');
		if($num && $num<=$rec['number']){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 验证商品是否已申请退货
	 */
	public function existRecId(){
		$rec_id = I('post.recId','');
		if($this->exitsRefund($rec_id)){
			return false;
		}else{
			return true;
		}
	}
	/**
	 * 添加退款退货
	 * @param array $data 要添加的数据
	 * @param int $updata 是否是更新
	 * @param $type 0:前台退款退货  1：后台申请退款退货
	 * @param $isAll true:整单退款退货  false：部分商品退款退货
	 * @return mixed
	 */
	public function addRefund($data,$updata = false,$type=0,$isAll=true){
		$order_id =$data['order_id'];
		$order = M("Order")->field("receiving_time,pay_status,status")->where(['order_id'=>$order_id])->find();
		if($order && $order['pay_status']==2 && in_array($order['status'],array(0,1,6))){
			if($order['receiving_time'] && time()-$order['receiving_time']>7*24*3600 && false){
				return $this->result()->error("收货7日后不能退款退货！","REFUND_7_DAY");
			}
			$this->startTrans();
			if($updata){
				unset($data['refund_time']);
				$result = $this->setData(['refund_id' => $data['refund_id']],$data);
			}else{
				$result = $this->addData($data,false,true);
			}
			$where = array(
					"rec_id"=>array('in',$data['rec_id'])
			);

			//判断是否整单退款
			if($isAll){
				$rec_result = M('OrderGoods')->where($where)->save(array("refund_status"=>1));
			}else{
				$rec_result = M('OrderGoods')->where($where)->save(array("refund_status"=>2));
			}
			
			if($result->isSuccess() && $rec_result!==false){
				$this->commit();
				if(!$type){
					$refund_id = $result->getResult()?$result->getResult():$data['refund_id']; 
					$this->refund_record($order_id,$data['refund_uid'],1,$refund_id);
				}
			}else{
				$this->rollback();
			}
			return $result;
		}else{
			return $this->result()->error();
		}
	}
	/**
	 * 取消退款退货
	 */
	public function cancelRefund($refund_id){
		// $result = $this->setData(['refund_id' => $refund_id],['refund_status' => 6]);

		// 用户取消订单时增加审核时间和完成时间
		$result = $this->setData(['refund_id' => $refund_id], ['refund_status' => 6, 'examine_time' => NOW_TIME, 'completion_time' => NOW_TIME]);

		if($result->isSuccess()){
			$data = $this->where(['refund_id' => $refund_id])->find();
			$where = array(
					"rec_id"=>$data['rec_id']
			);
			M('OrderGoods')->where($where)->save(array("refund_status"=>0));
			$res = $this->refund_record($data['order_id'],$data['refund_uid'],2,$refund_id);
			if($res){
				return $this->result()->success();
			}else{
				return $this->result()->error();
			}
		}else{
			return $this->result()->error();
		}
	}
	/**
	 * 订单商品详情
	 *
	 * @param unknown $rec_id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, unknown, mixed, string, object>
	 */
	public function recInfo($rec_id, $field = true) {
		return M ( "OrderGoods" )->field ( $field )->where ( [
				'rec_id' => $rec_id
				] )->find ();
	}
	/**
	 * 退款分配数据
	 * @param $rec_id 订单商品id
	 * @return array
	 */
	public function assign($rec_id){
		//$rec = $this->recInfo($rec_id);
		$rec = $this->getRefundInfo($rec_id);
		$assign = array(
				"recId"=>$rec_id,
				"max_money" => sprintf("%.2f", $rec['goods_price']*$rec['number']),
				"price" => $rec['goods_price'],
				"max_number"=>$rec['number'],
				"reason" => D ( "Admin/Refund" )->refund_reasons
		);
		return $assign;
	}
	
	/**
	 * 获取退款详情
	 * @param $rec_id
	 */
	public function getRefundInfo($rec_id){
		$rec = $this->recInfo($rec_id);
		$goods_discount = $rec['promotions_discount']?$rec['promotions_discount']:10;
		$rec['goods_price'] = ($goods_discount*$rec['goods_price'])/10;
		$order = M("Order")->field("goods_amount,money_paid,shipment_price")->where(['order_id'=>$rec['order_id']])->find();
		$discount = ($order['money_paid']-$order['shipment_price'])/$order['goods_amount'];
		$rec['goods_price'] = $discount * $rec['goods_price'];
		return $rec;
	}
	
    /**
	 * 视图模型
	 */
	public function viewModel() {
		$viewFileds = array (
				"Refund" => array (
						"refund_id",
						"refund_sn",
						"refund_money",
						"rec_id",
						"refund_status",
						"_type" => "LEFT" 
				),
				"Order" => array (
						"order_sn",
						"_as" => "Orders",
						"_on" => "Refund.order_id=Orders.order_id",
						"_type" => "LEFT" 
				),
				"Goods" => array (
						"name",
						"price",
						"attribute_id",
						"goods_id",
						"_on" => "Refund.goods_id=Goods.goods_id" 
				)
		);
		return $this->dynamicView ( $viewFileds );
	}
	/**
	 *
	 * @param $rec_id 订单商品id        	
	 * @param $order_id 订单id        	
	 * @param string $field
	 *        	查询字段
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, unknown, mixed, string, object>
	 */
	public function getOrderGoods($rec_id, $order_id, $field = true) {
		$where = array (
				'rec_id' => $rec_id,
				'order_id' => $order_id 
		);
		return M ( "OrderGoods" )->field ( $field )->where ( $where )->find ();
	}
	/**
	 * 生成唯一退款编号
	 */
	public function refund_sn() {
		//$refund_sn = date ( 'YmdHis', time () ) . rand_string ( 6, 1 );
		$refund_sn = "T".date ( 'yHis', time () ) . rand_string ( 3, 1 );
		$where = array (
				'refund_sn' => $refund_sn 
		);
		if ($this->where ( $where )->find ()) {
			$this->refund_sn ();
		}
		return $refund_sn;
	}
	/**
	 * 订单是否已申请
	 * 
	 * @param
	 *        	$订单商品id
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
	 */
	public function exitsRefund($rec_id) {
		$where = array (
				"rec_id" => $rec_id,
				"refund_status" => array (
						'not in',
						'-1,6'
				) 
		);
		return $this->where ( $where )->find ();
	}
	/**
	 * 提交申请退款或取消退款退货 日志
	 * 
	 * @param $order_id 订单id        	
	 * @param $uid 用户id        	
	 * @param int $type  日志类型 1:申请退款 退货 2：取消退款退货 申请        	
	 * @param text $extend  扩展字段        	
	 * @return Ambigous <\Think\mixed, boolean, string, unknown>
	 */
	public function refund_record($order_id, $uid,$type = 1,$extend = '') {
		$data = array (
				"order_id" => $order_id,
				"is_seller" => 0,
				"handle" => $uid,
				"add_time" => NOW_TIME,
				"type"=>1
		);
		if($type == 1){
			$tmp = [
					"action" => json_encode ( array (
							'refund_status' => 0
					) )
			];
			if(M('order_action')->where(['extend' => $extend])->count()){
				$tmp["remark"] = "您修改了退款申请，等待商家审核";
				$tmp["front_remark"] = "您修改了退款申请，等待商家审核";
			}else{
				$tmp["remark"] = "您提交退款申请，等待商家审核";
				$tmp["front_remark"] = "您提交了退款退货申请，请等待审核";
			}
		}else{
			$tmp = [
					"action" => json_encode ( array (
							'refund_status' => 1
					) ),
					"remark" => "您已取消退款退货申请",
					"front_remark"=>"您已取消退款退货申请",
			];
		}
		$data['extend'] = $extend;
		$data = array_merge($data,$tmp);
		return M ( "OrderAction" )->add ( $data );
	}
	
	/**
	 * 退款详情视图
	 * @return Ambigous <\Common\Model\mixed, \Think\Model\ViewModel, \Think\Model\RelationModel>
	 */
	public function infoView(){
		$viewFileds = array (
				"Refund"=>array(
					'refund_id',	
					"order_id",
					"refund_num",
					"description",
					"refund_time",
					"refund_sn",
					"voucher",
					"refund_money",
					"refund_status",
					"refund_mark",
					"refund_reasons",
					'rec_id',	

					'examine_time', // 审核时间
					'completion_time', // 订单完成时间

					"_type"=>"LEFT"
		         ),
				"Order"=>array(
						"order_sn",
						"pay_time",
						"shipping_status",
						"_on"=>"Orders.order_id=Refund.order_id",
						"_as"=>"Orders",
						"_type"=>"LEFT"
		         ),
				"OrderGoods"=>array(
						"norms_value",
						"_type"=>"LEFT",
						"_on"=>"Refund.rec_id=OrderGoods.rec_id"
		        ),
				"Goods"=>array(
					"name",
					"attribute_id",
					"_on"=>"Refund.goods_id=Goods.goods_id"	
		        )
		);
		return $this->dynamicView($viewFileds);
	}
	
	/**
	 * 退货详情
	 * @param  $refund_id
	 */
	public function getInfo($refund_id){
		//$refund_model = D ( 'Home/Refund' );
		$where = array (
				"Refund.refund_id" => $refund_id,
				"Refund.delete_time" => 0
		);
		$info = $this->infoView ()->where ( $where )->find ();
		
		$reasons = D("Admin/Refund")->refund_reasons;
		$info['refund_reasons'] = $reasons[$info['refund_reasons']];
		$attr = '';
		if ($info ['norms_value']) {
			$info ['norms_value'] = json_decode ( $info ['norms_value'], true );
			foreach ( $info ['norms_value'] as $v ) {
				if ($v ['photo']) {
					$attr = $v ['photo'];
				}
			}
		}else{
			$info ['norms_value'] = [];
		}
		if (empty ( $attr )) {
			$attribute = json_decode ( $info ['attribute_id'], true );
			$attr = $attribute ['default'] ? $attribute ['default'] : $attribute [0];
		}
		$info ['pic'] = D ( 'Upload/AttachMent' )->getAttach ( $attr );
		$info ['pic'] = $info ['pic'] [0] ['path'];
		//凭证图片
		$info ['voucher'] = json_decode ( $info ['voucher'], true );
		
		return $info;
	}
	
	/**
	 * 退款金额是否含运费
	 * @param $rec_id 订单商品id
	 * @param $order_id 订单id
	 * @param $num 退款数量
	 */
	public function getRefundMoney($order_id,$rec_id,$num){
		$order = M("Order")->field("shipment_price,shipping_status")->where(["order_id"=>$order_id])->find();
		if($order['shipping_status']==0){    
			//是否全退
			$where = array(
					"order_id"=>$order_id,
					"refund_status"=>0
			);
			$order_goods = M("OrderGoods")->where($where)->select();
			if(count($order_goods)==1){
				$order_goods = current($order_goods);
				if($order_goods['rec_id'] == $rec_id && $order_goods['number']==$num){
					return $order['shipment_price'];
				}
			}
		}
		return 0;
	}

	/**
	 * 用户取消退款后后台可执行修改订单操作
	 * @param $dat refund要修改的数据
	 * @param $refund_id 退货单id
	 * @param $rec_id 订单商品id
	 */
	public function saveRefundRecord($dat,$refund_id,$rec_id){
		$this->startTrans();

		$res = M('Refund')->where(['refund_id'=>$refund_id])->save($dat);
		if($res){
			$result = M('OrderGoods')->where(['rec_id'=>$rec_id])->save(['refund_status'=>2]);
			if(!$result){
				$this->rollback();
				return false;
			}else{
				$this->commit();
				return true;
			}
		}
		return false;
	}

	/**
	 * 用户取消退款后后台可执行修改订单操作  ###整单退款###
	 * @param $dat refund要修改的数据
	 * @param $refund_id 退货单id
	 * @param $rec_id 订单商品id
	 */
	public function saveRefundAll($dat,$refund_id,$order_id){
		$this->startTrans();

		$res = M('Refund')->where(['refund_id'=>$refund_id])->save($dat);
		if($res){
			$result = M('OrderGoods')->where(['order_id'=>$order_id])->save(['refund_status'=>1]);
			
			$this->commit();
			return true;
		}
		return false;
	}

}