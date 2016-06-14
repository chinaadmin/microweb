<?php
/**
 * 退款退货模型
 * @author xiongzw
 * @date 2015-07-02
 */
namespace Api\Model;
class RefundModel extends ApiBaseModel{
	public $refund_status = array(
			"-1"=>'未通过',
			"0" => '待审核',
			"1" =>"待退款",
			"2"=>'退款中',
			"3"=>"已退款",
			"4"=>"退款失败",
			"5"=>"待退货",
			"6"=>"已取消"
	);
	/**
	 * 订单商品退款页面
	 * 
	 * @param
	 *        	$rec_id
	 * @param string $field        	
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, unknown, mixed, string, object>
	 */
	public function recDate($rec_id, $field = true) {
		if (empty ( $rec_id )) {
			return $this->result ()->error ( "订单商品不能为空", "REC_REQUIRE" );
		} else {
			$data = D("Home/Refund")->recInfo ( $rec_id, $field );
			if ($data) {
				$data = $this->formatRec ( $data );
				$reason = D ( "Admin/Refund" )->refund_reasons;
				foreach($reason as $key=>&$v){
					$data['reason'][] = array(
							'name'=>$key,
							'value'=>$v
					);
				}
				return $this->result ()->success ()->content ( [ 
						'data' => $data 
				] );
			} else {
				return $this->result ()->error ();
			}
		}
	}
	
	public function formatShow($data){
		$return_data = array(
				'recId'=>$data['rec_id'],
				'maxNum'=>$data['number'],
				'price'=>$data['goods_price'],
				'photo'=>fullPath($data['photo']),
				'orderSn'=>$data['order_sn'],
				'name'=>$data['name'],
				'normsValue'=>$data['norms_value']
		);
		$reason = D ( "Admin/Refund" )->refund_reasons;
		foreach($reason as $key=>&$v){
			$return_data['reason'][] = array(
					'name'=>$key,
					'value'=>$v
			);
		}
		return $return_data;
	}
	/**
	 * 格式化商品数据
	 * 
	 * @param
	 *        	$data
	 */
	public function formatRec($data) {
		$return_array = array (
				"recId" => $data ['rec_id'],
				"maxNum" => $data ['number'],
				"price" => $data ['goods_price'] 
		);
		return $return_array;
	}
	/**
	 * 格式化退款退货列表
	 * @param array $refund
	 * @return array
	 */
	public function formatRefund($refund){
		$return_array = array();
		$rec_id = array_column($refund,"rec_id");
		if($rec_id){
		 $rec = $this->getRec($rec_id,'rec_id,norms_value');
		}
		foreach($refund as $key=>$v){
			$return_array[$key] = array(
					'refundId'=> $v['refund_id'],
					'refundSn'=> $v['refund_sn'],
					'refundMoney'=>$v['refund_money'],
					'refundStatus'=>$this->refund_status[$v['refund_status']],
					'orderSn' => $v['order_sn'],
// 					'name' => $v['name'],
// 					'photo'=> fullPath($v['thumb']),
					'recId'=>$v['rec_id']
// 					'norms_value' => ""
// 					'price'=>$v['price']
			);
// 			if($rec){
// 				foreach($rec as $vo){
// 					if($v['rec_id'] == $vo['rec_id']){
// 						$return_array[$key]['norms_value'] = json_decode($vo['norms_value']);
// 					}
// 				}
// 			}
			//商品信息
			$info['rec_id']=explode(",",$v['rec_id']);
			// 		print_r($info['rec_id']);exit;
			foreach($info['rec_id'] as $key2=>$v2){
				if($v2){
					$swhere = array (
							"rec_id" => $v2
					);
					$sgoods = M("OrderGoods")->where($swhere)->find();
					// 				print_r($sgoods);
					$gw = array (
							"goods_id" => $sgoods['goods_id']
					);
					$g = M("Goods")->where($gw)->find();
					$attribute = json_decode ( $g ['attribute_id'], true );
					$attr = $attribute ['default'] ? $attribute ['default'] : $attribute [0];
			
					$infos = D ( 'Upload/AttachMent' )->getAttach ( $attr );
					$infos = $infos [0] ['path'];
					$return_array[$key]['goodsdata'][$key2] = array(
							"name"=>$g ['name'],
							"price"=>$sgoods ['goods_price'],
							"number"=>$sgoods ['number'],
							"photo"=>fullPath($infos),
							"norms_value"=>json_decode ( $sgoods ['norms_value'], true )
					);
				}
			}
			
			
		}
		return $return_array;
	}
	
	/**
	 * 获取订单退款商品
	 */
	public function getRec($rec_id,$field=true){
		$where = array(
				"rec_id"=>array("in",(array)$rec_id)
		);
		return M("OrderGoods")->field($field)->where($where)->select();
	}
	
	/**
	 * 格式化退款详情
	 * @param  $info
	 * @return array
	 */
	public function formatInfo($info){
		if(!empty($info['norms_value'])){
			foreach($info['norms_value'] as &$v){
				unset($v['id']);
				unset($v['photo']);
			}
		}
		$return_array = array(
				'refundNum' => $info['refund_num'],
				'description'=>$info['description'],
				'refundTime' => date('Y-m-d H:i:s',$info['refund_time']),
				'refundSn' => $info['refund_sn'],
				'refundMoney'=>$info['refund_money'],
				'refundStatus'=>$info['refund_status'],
				'refundMark'=>$info['refund_mark'],
				'refundReasons'=>$info['refund_reasons'],
				'orderSn'=>$info['order_sn'],
				'payTime' => date('Y-m-d H:i:s',$info['pay_time']),
				'shippingStatus'=>$info['shipping_status'],
				'name' => $info['name'],
				'photo' => fullPath($info['pic']),
				'normsValue'=>$info['norms_value'],
		);
		foreach($info['action'] as $key=>$v){
			$return_array['action'][$key] = array(
					"frontRemark"=>$v['front_remark'],
					"addTime" => date('Y-m-d H:i:s',$v['add_time'])
			);
		}
		foreach($info['voucher'] as $key=>$v){
			if($v){
				$pic = D ( 'Upload/AttachMent' )->getAttach ( $v );
				$pic = $pic [0] ['path'];
				$return_array['voucher'][$key] = array(
						"pic"=>fullPath($pic)
				);
			}else{
				$return_array['voucher'] = [];
			}
			
		}
		return $return_array;
	}

	/**
	 * 最多退款金额
	 * @param string $orderId 订单id
	 */
	public function maxRefundMoney($orderId){
		$orderInfo = M('Order')->field('money_paid, shipment_price, shipping_status')->find($orderId); // 订单信息
		//	计算退款金额
		if(0 == $orderInfo['shipping_status']){ // 未发货订单退款为实际付款金额
			$money = number_format($orderInfo['money_paid'], 2);
		}else{ // 已发货订单退款为 实际付款金额 - 运费
			$money = number_format($orderInfo['money_paid'] - $orderInfo['shipment_price'], 2);
		}
		return $money;
	}


}