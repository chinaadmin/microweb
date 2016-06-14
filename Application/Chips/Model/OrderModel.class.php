<?php
/**
 * 众筹订单
 * @author xiongzw
 * @date 2015-12-10
 */
namespace Chips\Model;
use Api\Model\ApiBaseModel;
class OrderModel extends ApiBaseModel{
	protected $autoCheckFields = false;
	protected $exitChipsPayStatus = array('2','3','4'); //退出众筹时支付状态
	protected $objContainer = [];//保存对象,避免多次实例化
	
	/**
	 * 生成众筹订单
	 * @param $cdId 方案id
	 * @param $goods 方案商品
	 * @param $uid 用户id
	 * @param $recommendUid 推荐人
	 * @param $mark 买家备注
	 * @param $is_pay 是否支付
	 * @return \Common\Org\Util\Results
	 */
	public function creatOrder($cdId,$goods,$data,$is_pay=false){
		
	  $info = M("CrowdfundingDetail")->where(['cd_id'=>$cdId])->find();
	  // $discount = D("Chips/Chips")->userDiscount($info['fk_cr_id'], $data['uid']);	// 去掉员工价
	  $discount = 10;

	  if(empty($info)){
	  	return $this->result()->error("方案不存在！");
	  }
	  if($data['shipping_type'] == 1 || $data['shipping_type'] == 2){ //普通快递不保留门店信息
	  	if($data['shipping_type'] == 1){
			$data['store_id'] = 0;
		}
	  }else if(!$data['store_id']){
	  	 return $this->result()->error("门店信息不能为空");
	  }
	 
	  if(!$data['addressId'] &&  $data['shipping_type']){
	  	return $this->result()->error("地址信息不能为空");
	  }
	  
	  $termilly = M("CrowdfundingPerpay")->where(['fk_cd_id'=>$cdId])->order("cp_term_index asc")->select();
	  $this->formatTermilly($termilly,$info['cd_period_count']);
	  $goods = $this->merageGoods($goods);
		if(!$goods){
	  	 return $this->result()->error("商品错误！");
	  }
	 
      $order = array();
      $order_goods = array();
      $count_money = 0;
      $com_ordersn = $this->ordersn();
      $recommendData = [];
      $order_id = '';
      $order_sn = '';
      $store_id = I("request.storesId",0,'intval');
	 
      $shipping_address_model = D('Home/ShippingAddress');
      $shipping_address = $shipping_address_model->where(['address_id'=>$data['addressId']])->find();
	  foreach($termilly as $key=>$v){
	  	$money = 0;
	  	$num = 0;
	  	//订单
	  	$order[$key] = array( 
	  			'cor_order_id'=>uniqueId(),
	  			'cor_add_time'=>NOW_TIME,
	  			'cor_start_time'=>0,
	  			'cor_end_time'=>0,
	  			// 'cor_order_sn'=>$this->ordersn(),
	  			'cor_order_sn'=>$com_ordersn . '_' . ( $key + 1 ), // 将子订单号修改为与总订单号关联
	  			'fk_cd_id'=>$cdId,
	  			'cor_term_index'=>$v['cp_term_index'],
	  			'cor_uid'=>$data['uid'],
	  			'cor_should_pay'=>'',
	  			'cor_pay_amount'=>'',
	  			'cor_order_status'=>0,
	  			'cor_shipping_status'=>0,
	  			'cor_recommend_uid' => $data['recommendUid'],
	  			'fk_com_ordersn' => $com_ordersn,
	  			'cor_message' => $data['mark'],
	  			'cor_delivery_type' => $data['shipping_type'],
	  			'cor_store_id' => $data['store_id'],
	  			'cor_pay_type'=>empty($data['pay_type'])?'':$data['pay_type'],
	  			'cor_pay_status'=>0,
	  			'cor_pay_time'=>0,
				'cor_shipping_time'=>is_null($data['cor_shipping_time'])?0:$data['cor_shipping_time'],
	  			'cor_sign_time' => empty($data['cor_sign_time']) ? '' : strtotime($data['cor_sign_time']),	//合同签订时间
	  			'cor_remark' => empty($data['cor_remark']) ? '' : $data['cor_remark']	//备注


	  	);
	  	//订单商品 
	  	foreach($goods as $vo){
	  		$order_goods[] = array(
	  			'fk_cg_id'=>$vo['cg_id'],
	  			'cog_count'=>$vo['num'],
	  			'fk_cor_order_id'=>$order[$key]['cor_order_id'],
 	  			'cog_goods_name'=>$vo['cg_goods_name'],
 	  			'cog_market_price'=>$vo['cg_market_price'],
	  			'cog_goods_price'=>$v['good_price'] ? $v['good_price'] : $v['cp_pay_money'],
	  			'cog_discount' => $discount,
	  			'cog_shipping_status'=>0,

	  			'back_up_goods' => isset($vo['back_up_goods']) ? $vo['back_up_goods'] : '' // 备选商品
	  		);
	  		$num+=$vo['num'];
	  	}
	  	//订单金额
	  	$money = $v['cp_pay_money']*$num*($discount/10);
	  	$order[$key]['cor_should_pay'] = $money;
	  	$order[$key]['cor_pay_amount'] = $money;
	  	//总订单金额
	  	$count_money += $money;
	  	if($key==0){
	  		$order_id = $order[$key]['cor_order_id'];
	  		$order_sn = $order[$key]['cor_order_sn'];
	  		//已支付
	  		if($is_pay){
	  			$order[$key]['cor_pay_status'] = 2;
	  			$order[$key]['cor_pay_time'] = NOW_TIME;
	  			$order[$key]['cor_order_status'] = 1; //订单变为已确认
	  		}
	  	}
	  	//推荐人纪录
	  	if($data['recommendUid']){
		  	$recommendData[$key]['fk_cor_order_id'] = $order[$key]['cor_order_id'];
		  	$recommendData[$key]['cr_uid'] = $data['recommendUid'];
		  	$recommendData[$key]['cr_recommended_uid'] = $data['uid'];
		  	$recommendData[$key]['cr_receive_status'] = 0;
		  	$recommendData[$key]['cr_add_time'] = NOW_TIME;
	  	}
	  }
	  //插入总订单
	  $order_count = array(
	  		'jt_com_ordersn'=>$com_ordersn,
	  		'jt_com_uid'=>$data['uid'],
	  		'jt_com_crowdfunding_plan'=>$info['cd_name'],
	  		'jt_com_cd_id'=>$info['cd_id'],
	  		'jt_com_money'=>$count_money,
	  		'jt_com_recommend_uid'=>$data['recommendUid'],
	  		'jt_com_start_time'=>'0',
	  		'jt_com_end_time'=>'0',
	  		'jt_com_add_time'=>NOW_TIME,
	  		'jt_com_order_status'=>0,
	  		'fk_cr_id'=>$info['fk_cr_id'],
	  		'jt_com_type'=>empty($data['jt_com_type'])?1:$data['jt_com_type'],
	  		'staff_uid'=>empty($data['staff_uid'])?'0':$data['staff_uid'],
	  		'jt_com_receipt_name' => empty($shipping_address['name'])?"0":$shipping_address['name']
	  );
	  $this->startTrans(); //开启事物
	  $order_count_result = M('CrowdfundingOrderMakefile')->add($order_count); //添加总订单
	  $order_result = M('CrowdfundingOrder')->addAll($order);     //添加订单
	  $order_goods_result = M('CrowdfundingOrderGoods')->addAll($order_goods);//订单商品
	  //添加收货地址
	  if($order_count_result &&  ($data['shipping_type'] != 0)){
	  	$ressResult = $this->address($order_count_result,$data,$shipping_address);

	  	// 有新订单后台消息提醒
	  	// $order_message = array(
		// 		'order_sn' => $com_ordersn,
		// 		'order_type' => 1,
		// 		'message_add_time' => time()
		// );
	  	// $order_message_result = M('OrderMessage')->add($order_message); //添加订单提醒消息

	  }
	 
	  $add_crowdfunding_recommend = true;
	  if($data['recommendUid']){ //增加推荐人信息
		  $add_crowdfunding_recommend = M('crowdfunding_recommend')->addAll($recommendData);
	  }
	  if($order_count_result==false || $order_result==false || $order_goods_result==false || $add_crowdfunding_recommend == false){
	  	$this->rollback();
	  	return $this->result()->error('生成订单失败!');
	  }else{
	  	$this->commit();
	  	$this->checkPay($order_id,$order[0]['cor_pay_type'],$is_pay);
	  	if($discount < 10){ //内部员工折扣
		  	$this->record_inside_discount($data['uid'],$order[0]['cor_order_sn']);
	  	}
	  	return $this->result()->content(['orderId'=>$order_id,'orderSn' => $order_sn])->success();
	  }
	}

	/**
	* 编辑众筹订单
	* @param $orderList 订单详情
	* @param $cdId 方案id
	* @param $goods 方案商品
	* @param $uid 用户id
	* @param $recommendUid 推荐人
	* @param $mark 买家备注
	* @param $is_pay 是否支付
	* @return \Common\Org\Util\Results
	*/
	public function saveOrders($orderList,$cdId,$goods,$data,$is_pay=false){
		$info = M("CrowdfundingDetail")->where(['cd_id'=>$cdId])->find();
		// $discount = D("Chips/Chips")->userDiscount($info['fk_cr_id'], $data['uid']); // 去掉员工价
		$discount = 10;

		if(empty($info)){
			return $this->result()->error("方案不存在！");
		}
		if($data['shipping_type'] == 1 || $data['shipping_type'] == 2){ //普通快递不保留门店信息
			if($data['shipping_type'] == 1){
				$data['store_id'] = 0;
			}
		}else if(!$data['store_id']){
			return $this->result()->error("门店信息不能为空");
		}

		if(!$data['addressId'] &&  $data['shipping_type']){
			return $this->result()->error("地址信息不能为空");
		}

		$termilly = M("CrowdfundingPerpay")->where(['fk_cd_id'=>$cdId])->order("cp_term_index asc")->select();
		$this->formatTermilly($termilly,$info['cd_period_count']);
		$goods = $this->merageGoods($goods);
		if(!$goods){
			return $this->result()->error("商品错误！");
		}

		$order = array();
		$order_goods = array();
		$count_money = 0;
// 		$com_ordersn = $this->ordersn();
		$recommendData = [];
		$order_id = '';
		$order_sn = '';
		$store_id = I("request.storesId",0,'intval');

		$shipping_address_model = D('Home/ShippingAddress');
		$shipping_address = $shipping_address_model->where(['address_id'=>$data['addressId']])->find();
		foreach($termilly as $key=>$v){
			$money = 0;
			$num = 0;
			//订单
			$order[$key] = array(
				'cor_order_id'=>uniqueId(),
				'cor_add_time'=>NOW_TIME,
				'cor_start_time'=>0,
				'cor_end_time'=>0,
				// 'cor_order_sn'=>$this->ordersn(),
				'cor_order_sn'=> $orderList[0]['jt_com_ordersn'] . '_' . ( $key + 1 ), // 修改为与总订单号对应
				'fk_cd_id'=>$cdId,
				'cor_term_index'=>$v['cp_term_index'],
				'cor_uid'=>$data['uid'],
				'cor_should_pay'=>'',
				'cor_pay_amount'=>'',
				'cor_order_status'=>0,
				'cor_shipping_status'=>0,
				'cor_recommend_uid' => $data['recommendUid'],
				'fk_com_ordersn' => $orderList[0]['jt_com_ordersn'],
				'cor_message' => $data['mark'],
				'cor_delivery_type' => $data['shipping_type'],
				'cor_store_id' => $data['store_id'],
				'cor_pay_type'=>empty($data['pay_type'])?'':$data['pay_type'],
				'cor_pay_status'=>0,
				'cor_pay_time'=>0,
				'cor_shipping_time'=>is_null($data['cor_shipping_time'])?0:$data['cor_shipping_time'],
				'cor_sign_time' => empty($data['cor_sign_time']) ? '' : strtotime($data['cor_sign_time']),	//合同签订时间
				'cor_remark' => empty($data['cor_remark']) ? '' : $data['cor_remark']	//备注
			);
			//订单商品
			foreach($goods as $vo){
				$order_goods[] = array(
					'fk_cg_id'=>$vo['cg_id'],
					'cog_count'=>$vo['num'],
					'fk_cor_order_id'=>$order[$key]['cor_order_id'],
					'cog_goods_name'=>$vo['cg_goods_name'],
					'cog_market_price'=>$vo['cg_market_price'],
					'cog_goods_price'=>$v['good_price'] ? $v['good_price'] : $v['cp_pay_money'],
					'cog_discount' => $discount,
					'cog_shipping_status'=>0,

					'back_up_goods' => isset($vo['back_up_goods']) ? $vo['back_up_goods'] : '' // 备选商品
				);
				$num+=$vo['num'];
			}
			//订单金额
			$money = $v['cp_pay_money']*$num*($discount/10);
			$order[$key]['cor_should_pay'] = $money;
			$order[$key]['cor_pay_amount'] = $money;
			//总订单金额
			$count_money += $money;
			if($key==0){
				$order_id = $order[$key]['cor_order_id'];
				$order_sn = $order[$key]['cor_order_sn'];
				//已支付
				if($is_pay){
					$order[$key]['cor_pay_status'] = 2;
					$order[$key]['cor_pay_time'] = NOW_TIME;
					$order[$key]['cor_order_status'] = 1; //订单变为已确认
				}
			}
			//推荐人纪录
			if($data['recommendUid']){
				$recommendData[$key]['fk_cor_order_id'] = $order[$key]['cor_order_id'];
				$recommendData[$key]['cr_uid'] = $data['recommendUid'];
				$recommendData[$key]['cr_recommended_uid'] = $data['uid'];
				$recommendData[$key]['cr_receive_status'] = 0;
				$recommendData[$key]['cr_add_time'] = NOW_TIME;
			}
		}
		//更新总订单
		$order_count = array(
			'jt_com_uid'=>$data['uid'],
			'jt_com_crowdfunding_plan'=>$info['cd_name'],
			'jt_com_cd_id'=>$info['cd_id'],
			'jt_com_money'=>$count_money,
			'jt_com_recommend_uid'=>$data['recommendUid'],
			'jt_com_start_time'=>'0',
			'jt_com_end_time'=>'0',
			'jt_com_add_time'=>NOW_TIME,
			'jt_com_order_status'=>0,
			'fk_cr_id'=>$info['fk_cr_id'],
			'jt_com_type'=>empty($data['jt_com_type'])?1:$data['jt_com_type'],
			'staff_uid'=>empty($data['staff_uid'])?'0':$data['staff_uid'],
			'jt_com_receipt_name' => empty($shipping_address['name'])?"0":$shipping_address['name'],
		    'jt_offline_check' => '-1'    //还原审核状态
		);
		$this->startTrans(); //开启事物
		
		//删除之前的子订单和子单里的商品
		$orderIds = array();
		foreach($orderList as $i => $val){
		    $orderIds[] = $val['cor_order_id'];
		}
		$wheres = array();
		$wheres['fk_cor_order_id'] = array('IN',$orderIds);
		$delete_order = M('CrowdfundingOrder')->where(['fk_com_ordersn'=>$orderList[0]['fk_com_ordersn']])->delete();//删除子订单
		$delete_goods = M('CrowdfundingOrderGoods')->where($wheres)->delete();//删除商品
		
		$order_count_result = M('CrowdfundingOrderMakefile')->where(['jt_com_id'=>$orderList[0]['jt_com_id']])->save($order_count); //添加总订单
		$order_result = M('CrowdfundingOrder')->addAll($order);    //添加订单
		$order_goods_result = M('CrowdfundingOrderGoods')->addAll($order_goods);//订单商品
		//添加收货地址
		if($order_count_result &&  ($data['shipping_type'] != 0)){
			$ressResult = $this->address($orderList[0]['jt_com_id'],$data,$shipping_address);
		}

		$add_crowdfunding_recommend = true;
		if($data['recommendUid']){ //增加推荐人信息
			$add_crowdfunding_recommend = M('crowdfunding_recommend')->addAll($recommendData);
		}
		if(!$delete_order || !$delete_goods || $order_count_result==false || $order_result==false || $order_goods_result==false || $add_crowdfunding_recommend == false){
			$this->rollback();
			return $this->result()->error('生成订单失败!');
		}else{
			$this->commit();
			$this->checkPay($order_id,$order[0]['cor_pay_type'],$is_pay);
			if($discount < 10){ //内部员工折扣
				$this->record_inside_discount($data['uid'],$order[0]['cor_order_sn']);
			}
			return $this->result()->content(['orderId'=>$order_id,'orderSn' => $order_sn])->success();
		}
	}

	//纪录内部员工使用折扣
	private function record_inside_discount($uid,$order_sn){
		$data = [];
		$data['ud_uid'] = $uid;
		$data['ud_inside_discount_order_sn'] = $order_sn;
		M('user_inside_discount')->add($data);
	}
	//检查是否有后台直接支付的订单
	private function checkPay($order_id,$payType,$is_pay){
		if(!$is_pay){
			return  false;
		}
		D('Chips/Pay')->paying($order_id,$payType);
	}
	/**
	 * 订单收获地址
	 */
	private function address($order_id,$data,$shipping_address){
		$order_receipt_data = [];
		$order_receipt_data['order_id'] = $order_id;
		$order_receipt_data['name'] = $shipping_address['name'];
		$order_receipt_data['mobile'] = $shipping_address['mobile'];
		switch($data['shipping_type']){
			case 0://门店自提
				//break;
			default:
				$order_receipt_data['province'] = $shipping_address['user_provice'];
				$order_receipt_data['city'] = $shipping_address['user_city'];
				$order_receipt_data['county'] = $shipping_address['user_county'];
				$order_receipt_data['localtion'] = $shipping_address['user_localtion'];
				$order_receipt_data['address'] = $shipping_address['user_detail_address'];
				break;
		}
		return M('OrderReceipt')->add($order_receipt_data);
	}
	
	/**
	 * 生成订单号
	 * @return string
	 */
	public function ordersn() {
		//mt_srand((double) microtime() * 1000000);
		// return 'ZC_'.date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
		return 'ZC'.date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT); // 去掉订单号的 "_" 
	}
	
	/**
	 * 获取订单商品详情
	 * @param $goods
	 */
	private function merageGoods($goods){
		$cgIds = array_column($goods, "cgId");
		if($cgIds){
		 $goods_info = D("Chips/Chips")->getChipsGoods($cgIds);
		 foreach($goods_info as &$v){
		 	foreach ($goods as $vo){
		 		if($v['cg_id'] == $vo['cgId']){
		 			$v['num'] = $vo['num'];

		 			$v['back_up_goods'] = $vo['back_up_goods']; //备选商品

		 		}
		 	}
		 }
		}
		return empty($goods_info)?false:$goods_info;
	}
	
	/**
	 * 返回订单数据订单
	 * @param 总订单数据
	 */
	public function formatOrder($countOrder){
		$return_data = array();
		$cdIds = array_column($countOrder, "jt_com_cd_id");
		//方案
		if($cdIds){
		 $scheme = M("CrowdfundingDetail")->where(['cd_id'=>array('in',$cdIds)])->select();
		}
		//获取子订单
		if($countOrder){
			$orderSns = array_column($countOrder, "jt_com_ordersn");
			$childOrder = $this->getOrders($orderSns);
			foreach ($countOrder as $key=>$v){
				$return_data[$key] = array(
						'countId' => $v['jt_com_id'],
						'countSn' => $v['jt_com_ordersn'],
						'countMoney' => $v['jt_com_money'],
						'ifCanExit' => $this->ifCanExit($v['jt_com_ordersn']),
						'child' => $childOrder[$v['jt_com_ordersn']]
				);
				if($scheme){
					foreach ($scheme as $vo){
						if($vo['cd_id'] == $v['jt_com_cd_id']){
							$return_data[$key]['cdName'] = $vo['cd_name'];
							$return_data[$key]['cdSubName'] = $vo['cd_subhead'];
						}
					}
				}
			}
		}
		return $return_data;
	}
	
	
	/**
	 * 批量获取子订单
	 * @param $orderSns 总订单号
	 */
	public function getOrders($orderSns){
		$return_data = array();
		$where = array(
				'fk_com_ordersn'=>array('in',(array)$orderSns)
		);
		$orders = M("CrowdfundingOrder")->where($where)->order("cor_term_index asc")->select();
		//获取方案
		$cdIds = array_unique(array_column($orders, 'fk_cd_id'));
		$schemes = M("CrowdfundingDetail")->where(['cd_id'=>array('in',$cdIds)])->select();
		$scheme = array();
		$schemes = array_map(function($info)use(&$scheme){
			$scheme[$info['cd_id']] = $info['cd_period_unit']==1?"年":($info['cd_period_unit']==2?"季":"月");
		}, $schemes);
		$order_ids = array_column($orders, "cor_order_id");
		$goods = $this->orderGoods($order_ids);
		//订单商品
		if($goods){
			foreach($orders as &$v){
				$i=0;
				foreach($goods as $vo){
					if($v['cor_order_id'] == $vo['fk_cor_order_id']){
						$v['goods'][$i] = array(
							'name'=>$vo['cg_goods_name'],
							'num'=>$vo['cog_count'],
							'status'=>$vo['cog_shipping_status'],
							'photo'=>fullPath($vo['thumb']),	
							'mail_no' => $this->getMailNo($v['cor_order_id'],$vo['cog_id']),

							'goods_id' => $vo['cog_id'] // 增加商品id，用于微商城确认收货

						);
						$i++;
					}
				}
			}
		}
		if($orders){
			foreach($orders  as $key=>$vs){
				$return_data[$vs['fk_com_ordersn']][] = array(
						'orderId'=>$vs['cor_order_id'],
						'orderSn'=>$vs['cor_order_sn'],
						'term'=>"第".$vs['cor_term_index'].$scheme[$vs['fk_cd_id']],
						'shouldPay'=>$vs['cor_should_pay'],
						'amountPay'=>$vs['cor_pay_amount'],
						'payStatus'=>$vs['cor_pay_status'],
						'canPay'=> 0,
						'goods'=>$vs['goods']
				);
			}
		}
		foreach ($return_data as $k => &$vd){
			foreach ($vd as $kIn => &$vIn){
				$canPay = 0;
				if($vIn['payStatus'] == 0){
					if(($kIn == 0 ) || ($vd[$kIn - 1]['payStatus'] == 2)){
						$canPay = 1;
					}	
				}
				$vIn['canPay'] = $canPay;
			}
		}
		return $return_data;
	}
	
	/**
	 * 订单商品模型
	 */
	public function goodsView(){
		$viewFields = array (
				"CrowdfundingOrderGoods" => array (
						'cog_id',
						"cog_count",
						"cog_market_price",
						"cog_goods_price",
						"cog_discount",
						"fk_cor_order_id",
						"cog_shipping_status",
						"_type" => "LEFT"
				),
				"CrowdfundingGoods" => array (
						"cg_goods_name",
						"cg_att_id",
						"cg_id",
						"_on" => "CrowdfundingOrderGoods.fk_cg_id=CrowdfundingGoods.cg_id",
						'_type' => 'left'
				)
		);
		return $this->dynamicView ( $viewFields );
	}
	
	/**
	 * 获取订单商品
	 */
	public function orderGoods($orders){
		$model = $this->goodsView();
		$where = array(
				'fk_cor_order_id'=>array('in',(array)$orders)
		);
		$data = $model->where($where)->select();
		D("Home/List")->getThumb($data,false,'cg_att_id');
		return $data;
	}
	
	/**
	 * 退出众筹
	 * @param $countOrder 总订单号
	 */
	public function exitChips($countOrder){
		$return = $this->result();
		$model = M("CrowdfundingOrderMakefile");
		$order_model = M("CrowdfundingOrder");
		if(!$model->where(['jt_com_ordersn'=>$countOrder])->find()){
			return $return->error("订单错误！");
		}
		$where = array(
				'fk_com_ordersn'=>$countOrder,
				'cor_pay_status'=>array("in",$this->exitChipsPayStatus)
		);
		if($order_model->where($where)->select()){
			return $return->error("该订单您已认筹，不能退出！");
		}else{
			$where = array(
					'fk_com_ordersn'=>$countOrder
			);
			$this->startTrans();
			$return1 = $model->where(['jt_com_ordersn'=>$countOrder])->delete();
			$return2 = $order_model->where($where)->delete();
			if($return1!=false && $return2!=false){
				$this->commit();
				return $return->success("退出众筹成功！");
			}else{
				$this->rollback();
				return $return->error();
			}
		}
	}
	/**
	 *是否可以退出众筹 
	 * @param string $countOrder 总订单号
	 * @return 0|1 	 0:可以退出 1:不可以退出
	 */
	function ifCanExit($countOrder){
		if(!$this->objContainer['OrderMakefile']){
			$this->objContainer['OrderMakefile'] = M("CrowdfundingOrderMakefile");
		}
		if(!$this->objContainer['Order']){
			$this->objContainer['Order'] = M("CrowdfundingOrder");
		}
		$model = $this->objContainer['OrderMakefile'];
		$order_model = $this->objContainer['Order'];
		if(!$model->where(['jt_com_ordersn'=>$countOrder])->find()){
			return 0;
		}
		$where = array(
				'fk_com_ordersn'=>$countOrder,
				'cor_pay_status'=>array("in",$this->exitChipsPayStatus)
		);
		if($order_model->where($where)->find()){//存在已支付过的订单
			return 0;
		}
		return 1;
	}
	//订单纪录
	public function _recordOrderAction(){
		
	}
	//获取某商品快递单号
	function getMailNo($orderId,$orderGoodId){
		if(!$this->objContainer['order_send']){
			$this->objContainer['order_send'] = D('Admin/SendGoods');
		}
		$where['order_id'] = $orderId;
		$where['rec_ids'] = json_encode([$orderGoodId]);
		return $this->objContainer['order_send']->where($where)->getField('send_num');
	}
	/**
	 * 格式化支付期数数组
	 * @param array $termilly  每期支付二维数组
	 * @param int $period_count
	 */
	private function formatTermilly(&$termilly,$period_count){
		$count = count($termilly);
		if($count == $period_count){
			return;
		}
		for ($i = $count + 1; $i <= $period_count ; $i++){
			$tmp = [];
			$tmp['cp_term_index'] = $i;
			$tmp['good_price'] = $termilly[0]['cp_pay_money'];
			$tmp['cp_pay_money'] = 0;
			$termilly[$i-1] = $tmp;
		}
	}
}