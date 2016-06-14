<?php
/**
 * 商品发货模型
 * @author xiongzw
 * @date 2015-06-04
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class SendGoodsModel extends AdminbaseModel{
	protected $tableName = "order_send";
	/**
	 * 获取要发货的商品
	 * @param  $order_id 订单id
	 * @return Ambigous <\Think\mixed, NULL, mixed, unknown, multitype:Ambigous <unknown, string> unknown , object>
	 */
	public function getSendGoods($order_id,$type=1,$field=true){
		$where = array(
				"order_id"=>array('in',$order_id),
				"refund_status" => 0
		);
		//退货商品
		if($type==1){
		   return M("OrderGoods")->where($where)->getField("rec_id",true);
		}else{
		   return M("OrderGoods")->where($where)->field($field)->select();
		}
	}
	
	/**
	 * 通过订单id获取发货基本信息
	 * @param $order_id订单id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, mixed, multitype:, unknown, object>
	 */
	public function getSends($order_id,$field=true){
		return $this->field($field)->where(['order_id'=>['in',$order_id]])->select();
	}
	
    /**
     * 过滤快递发货订单/有退款退货订单未审核不发货
     * @param $order_id 订单id
     * @param $shipping_type 发货方式 0：自提  1：物流 2：送货上门
     */
	public function forder($order_id,$shipping_type){
		if(empty($order_id)){
			return $this->result()->error("请选择要发货的订单！");
		}
		$where = array(
				"order_id" => array("in",$order_id),
				"shipping_status" =>0,
				"pay_status"=>2,
				"status" => 1,
				"shipping_type" => $shipping_type
		);
		$data = M("Order")->field("order_id,order_sn,stores_id")->where($where)->select();
		
		if($data){
			$order_id = array_column($data, "order_id");
			$refunds  = M("Refund")->field("order_id,refund_status")->where(['order_id'=>['in',$order_id]])->select();
			//过滤退款未审核的订单
			if($refunds){
				foreach($data as $key=>$vo){
					foreach($refunds as $v){
						if($vo['order_id'] == $v['order_id']){
							if($v['refund_status'] == 0){ //未审核退款单
								unset($data[$key]);
							}
						}
					}
				}
			}
			if(empty($data)){
			    return $this->result()->error("该订单已申请退货！");
			}
		}
		return $this->orderRec($data,$shipping_type);
	}
	
	/**
	 * 过滤物流订单打印
	 * @param $order_id 订单id
	 */
	public function forderByPrint($order_id){
		if(empty($order_id)){
			return $this->result()->error("请选择符合条件的订单！");
		}
		if(!is_array($order_id)){
			$order_id = explode(',', $order_id);
		}
		$where = array(
				"shipping_type"=>1,
				"shipping_status"=>array('in',"0"),
				"order_id"=>array('in',$order_id)
		);
		$data = M("Order")->field("order_id,order_sn,shipping_status")->where($where)->select();
		$result = $this->orderRec($data);
		if($result->isSuccess()){
			$datas = $result->toArray();
			$datas = $datas['result'];
			if($datas){
				$orders = array_column($datas['order'],"order_id");
				//获取快递单信息
				$viewFields = array(
						'OrderSend' => array (
								'order_id',
								'send_num',
								'_type' => 'LEFT'
						),
						'LogisticsCompany' => array (
								'lc_name',
								'lc_id',
								'_on' => 'OrderSend.logistics=LogisticsCompany.lc_id',
								'_type' => 'LEFT'
						)
				);
				$where = array(
						'OrderSend.order_id' => array("in",$orders)
				);
				$sendData = $this->dynamicView($viewFields)->where($where)->select();
				foreach($datas['order'] as &$v){
					foreach($sendData as $vo){
						if($vo['order_id'] == $v['order_id']){
							$v['send_num'] = $vo['send_num'];
							$v['logistics'] = $vo['lc_name'];
							$v['lc_id'] = $vo['lc_id'];
						}
					}
				}
				//获取收获人信息
				//$this->orderReceipt($datas['order']);
				//获取物流模版
				$datas['express'] = D("Admin/ExpressTemplate")->getTemplate("et_name,et_id,et_company_id");
				//获取发件人
				//$datas['sender'] = D("Admin/WareHouse")->getWares();
				return $this->result()->content($datas)->success();
			}
		}
		return $result;
	}
	
    /**
     * 快递发货获取物流模版
     * @param  $et_id 模版id
     * @param  $sender 发件人信息
     * @param  $order_ids 订单id
     */
	public function getTemplate($et_id,$sender,$order_id){
		if(empty($et_id) || empty($order_id)){
			return $this->result()->error();
		}
		$receipt = D("Admin/Order")->getReceipt($order_id);
		$orders = D("Admin/Order")->getOrders($order_id,"order_sn,order_id,seller_postscript");
// 	 	$sends = $this->getSends($order_id,"order_id,rec_ids,ware_id,send_remark");
		$ware_dis = 1;//只有一个仓库
		$sender = D("Admin/WareHouse")->getWareById($ware_dis);
		$sends = [];
		$m = M('order_goods');
		foreach($order_id as $v_order){
			$tmp = [];
			$tmp['order_id'] = $v_order; 
			$tmp['rec_ids'] = $m->where(['order_id' => $v_order])->sum('number');//商品数量 
			$tmp['sender'] = $sender;
			$sends[] = $tmp;
		}
		$data = array();
		if($receipt){
			foreach($sends as $key=>$v){
				$data[$key] = array(
						"orNu" => '',        //订单号
						"senderName"=>$v['sender']['ware_username'],    //发件人姓名
						"senderTel"=>$v['sender']['ware_mobile'],     //发件人电话
						"remark" => $v['send_remark'],      //订单备注
						"senderAdd"=>$v['sender']['ware_address'],     //发件人地址
						"senderPostcode"=>$v['sender']['ware_zipcode'],//发件人邮编
// 						"recipientsName"=>$v['name'], //收件人姓名
// 						"recipientsTel"=>$v['mobile'], //收件人手机
// 						"recipientsAddr"=>$v['address'],//收件人地址
// 						"recipientsPostcode"=>$v['zipcode'],//收件人邮编
						"recipientsGoodsCount"=>$v['rec_ids']//商品数量
				);
				foreach($orders as $vo){
					if($vo['order_id'] == $v['order_id']){
						$data[$key]['orNu'] = $vo['order_sn'];
					}
				}
				foreach($receipt as $vs){
					if($vs['order_id'] == $v['order_id']){
						$data[$key]["recipientsName"] = $vs['name'];
						$data[$key]["recipientsTel"] = $vs['mobile'];
						$data[$key]["recipientsAddr"] = $vs['address'];
						$data[$key]["recipientsPostcode"] = $vs['zipcode'];
					}
				}
			}
		}
		if($data){
			$template = D("Admin/ExpressTemplate")->getDyTemplate($et_id,$data,2);
			if(!empty($template)){
				return $this->result()->content($template)->success();
			}
		}
		return $this->result()->error();
	}
	
	/**
	 * 订单商品数据
	 * @param $data 订单数据
	 * $shipping_type 发货类似 0：自提 1：物流 2：上门送货
	 */
	public function orderRec($data,$shipping_type=null){
		if(!empty($data)){
			$order_id = array_column($data, "order_id");
			$recData = $this->getRecData($order_id);
			$rData = array();
			if($recData){
				foreach($data as $key=>&$v){
					foreach ($recData as $vo){
						if($v['order_id'] == $vo['order_id']){
							if($vo['norms_value']){
								$vo['norms_value'] = json_decode($vo['norms_value'],true);
								$str = "";
								foreach ($vo['norms_value'] as $vs){
									$str.=$vs['value'].".";
								}
								$vo['norms_value'] = trim($str,".");
							}
							$rData['order'][$key] = array(
									'order_id'=>$v['order_id'],
									'order_sn'=>$v['order_sn'],
									'stores_id'=>$v['stores_id'],
									'rec' => array($vo)
							);
						}
					}
				}
			}
			if(!empty($rData)){
				if(!is_null($shipping_type)){
					if($shipping_type==1){
						$rData['logistics'] = D("Admin/Logistics")->getLogistics();
						$rData['sender'] = D("Admin/WareHouse")->getWares();
						if(empty($rData['logistics'])){
							return $this->result()->error("物流数据为空，请先添加物流信息！");exit;
						}
					}
					if($shipping_type==2){
						$this->orderReceipt($rData['order']);
						$rData['stores'] = D("Stores/Stores")->getStores("stores_id,name");
						$stores_id = array_column($rData['order'], 'stores_id');
						$rData['StoresUser'] = D("Stores/StoresUser")->getUsers($stores_id);
					}
				}
				return $this->result()->content($rData)->success();exit;
			}
		}
		return $this->result()->error("没有符合条件的订单！");
	}
	
	/**
	 * 订单数组组合发货地址
	 * @param $order
	 */
	private function orderReceipt(&$order){
		$order_ids = array_column($order, 'order_id');
		$receipt = D("Admin/Order")->getReceipt($order_ids,'order_id,address,mobile,name');
		if($order && $receipt){
			foreach($order as &$v){
				foreach($receipt as $vo){
					if($vo['order_id'] == $v['order_id']){
						$v['address'] = $vo['address'];
						$v['mobile'] = $vo['mobile'];
						$v['name'] = $vo['name'];
					}
				}
			}
		}
	}
	
	/**
	 * 订单商品试图模型
	 */
	public function recGoodsView(){
		$viewFields = array(   
			'OrderGoods' => array (
						'rec_id',
					    'norms_value',
					    'number',
					    'market_price',
					    'goods_price',
					    'order_id',
					    '_type' => 'LEFT'
				),
			'Goods' => array (
						'name',
						'_on' => 'OrderGoods.goods_id=Goods.goods_id',
					    '_type' => 'LEFT'
			)
		 );
		return $this->dynamicView($viewFields);
	}
	
	/**
	 * 获取符合条件的发货商品单
	 * @param $order_id 订单id
	 */
	public function getRecData($order_id) {
		$where = array (
				"OrderGoods.order_id" => array (
						'in',
						$order_id 
				),
				"OrderGoods.refund_status" => 0 
		);
		//未打印发货单
		$where = [];
		return $this->recGoodsView ()->where ( $where )->select ();
	}
	
	/**
	 * 处理发货
	 * @param  $logistics 提交的物流
	 * @param $shipping_type 发送方式  0:自提  1：物流  2：门店配送
	 * @param $uid 用户id
	 */
	public function sendLogistics($logistics,$uid,$shipping_type){
		if(empty($logistics)){
			return $this->result()->error();
		}
		
		
		$data = array();
		$error = array();
		$i=0;
		$j = 0;
		//物流发货信息
		if($shipping_type==1){
			$sender = $logistics['sender'];
			if(empty($sender)){
				return $this->result()->error("发件人信息为空！");
			}
			unset($logistics['sender']);
		}

		foreach($logistics as $key=>&$v){
			$rec_id = M('order_goods') -> where(['order_id' => $key]) -> Field('rec_id')-> select();
			$user_id = M('order') -> where(['order_id' => $key]) -> getField('uid');
			if($key && $v['number'] && $v['rec'] && $v['logistics']){ //物流发货
				$data[$i] = array(
					"rec_ids"=>json_encode($v['rec']),
					"send_sn"=>$this->createCode(),
					"send_time"=>NOW_TIME,
					"order_id" => $key,
					"send_type" => $shipping_type,
					"send_remark" => '',
					"send_num" => $v['number'],
					"logistics"=>$v['logistics'],
					"handle" => $uid,
					"ware_id"=>$sender,
					"send_status"=>1
				);
				$i++;
			}else{
				$error[] = $key;
			}
			//上门送货
			if($shipping_type==2 && $v['stores'] && $v['user'] && $v['mobile']){
				$data[$i] = array(
						"rec_ids"=>json_encode($v['rec']),
						"send_sn"=>$this->createCode(),
						"send_time"=>NOW_TIME,
						"order_id" => $key,
						"send_type" => $shipping_type,
						"send_remark" => '',
						"stores_id" => $v['stores'],
						"delivery" => $v['user'],
						"extends" => $v['mobile'],
						"handle" => $uid,
						"send_status"=>2
				);
				$i++;
			}else{
				$error[] = $key;
			} 
			foreach($rec_id as $val){
				$message_logistics[$j]['uid'] = $user_id;
				$message_logistics[$j]['order_id'] = $val['rec_id'];
				$message_logistics[$j]['addtime'] = time();
				$message_logistics[$j]['type'] = 1;
				$message_logistics[$j]['number'] = $v['number'];
				$message_logistics[$j]['fk_lc_code'] = $v['logistics'];
				$j++;
		   }
		}

		if(!empty($data)){
		   $this->startTrans();
		   //记录发货信息
		   $result = M("OrderSend")->addAll($data);
		   $orders = array_column($data, "order_id");
		   //更新订单状态
		   $where = array(
		   		"order_id"=>array("in",$orders)
		   );
		   /* if($shipping_type!=2){
			   $order_result = M("Order")->where($where)->save(
			   		[
			   		   'shipping_status' =>1,
			   		   'shipping_time' => NOW_TIME
			   		]
			   	);
			   if($order_result === false){
			   	$this->rollback();
			   } 
		   } */
		   $order_result = M("Order")->where($where)->save(
		   		[
		   		'shipping_status' =>1,

		   		'shipping_time' => NOW_TIME // 发货时间

		   		]
		   );
		   if($order_result === false){
		     	$this->rollback();
		    }
		   //记录发货日志
		   if($shipping_type==2){
			   $record_result = $this->sendRecord($orders, $uid,$shipping_type);
			   if($result === false || $record_result === false){
			   	 $this->rollback();
			   }
		   }
		   $this->commit();
		   //记录物流推送消息
		   
		   $result = M('message_logistics') ->addAll($message_logistics); 
		   return $this->result()->success();
		}	
		return $this->result()->error();
	    
	}
	/**
	 * 蜂蜜众筹发货
	 */
	function sendGoods($data,$shipping_type,$orderGoodId){
		$data['send_sn'] = $this->createCode();
		$m = M("OrderSend");
		if($m->where(['rec_ids' => $data['rec_ids'],'order_id' => $data['order_id']])->count()){
			return $this->result()->error('不能重复发货');
		}
		if($data['send_num']){ //有单号则默认已开单
			$data['send_is_signed'] = 1; 
		}
		$this->startTrans();
		//记录发货信息
		$result = $m->add($data);
		$orders = $data['order_id'];
		//更新商品状态
		$where = array(
				"cog_id"=>array("in",(array)$orderGoodId)
		);
		$order_result = M("crowdfunding_order_goods")->where($where)->save(['cog_shipping_status' =>2,'cog_shipping_time' => NOW_TIME]);
		if($order_result === false){
			$this->rollback();
		}
		//记录发货日志
		if($shipping_type==2){
			$record_result = $this->sendRecord($orders, $data['handle'],$shipping_type);
			if($result === false || $record_result === false){
				$this->rollback();
			}
		}
		$this->commit();
		
		//记录物流推送消息
		
		$message_logistics['uid'] = M('crowdfunding_order_goods')->join('LEFT JOIN __CROWDFUNDING_ORDER__ ON __CROWDFUNDING_ORDER_GOODS__.fk_cor_order_id =__CROWDFUNDING_ORDER__.cor_order_id')->where(['cog_id'=>$orderGoodId]) ->getfield('cor_uid');
		$message_logistics['order_id'] = $orderGoodId;
		$message_logistics['addtime'] = time();
		$message_logistics['type'] = 2;
		$message_logistics['number'] = $data['send_num'];
		$message_logistics['fk_lc_code'] = $data['logistics'];
		M('message_logistics') ->add($message_logistics);

		/* //订阅物流信息
		$KuaiDi = new KuaiDi();
		$lc_code = M('logistics_company') -> where(['lc_id'=>$data['logistics']]) -> getField('lc_code');
		$KuaiDi -> submit_data($lc_code,$data['send_num']); */
		return $this->result()->success();
	}
	
	/**
	 * 快递100数据接口
	 */
	public function logisticsSub(){
		
	}
	
	/**
	 * 商品发货生成发货编号 
	 */
	public function createCode(){
		$code = date ( 'yHis', time () ) . rand_string ( 3, 1 );
		$where = array (
				'send_sn' => $code
		);
		if ($this->where ( $where )->find ()) {
			$this->createCode();
		}
		return $code;
	}
	/**
	 * 自提发货记录信息
	 * @param  $order_id 订单id
	 * @param  $uid  用户id
	 */
	public function recSend($order_id,$uid){
		$rec_id = $this->getSendGoods ( $order_id );
		//发货信息
		$arr = array (
				"send_sn"=>$this->createCode(),
				"order_id" => $order_id,
				"send_time" => NOW_TIME,
				"rec_ids" => json_encode ( $rec_id ),
				"handle" => $uid,
				"stores_id"=>M("Order")->where("order_id='{$order_id}'")->getField("stores_id"),
				"send_type"=>0,
				"send_status"=>1
		);
		$result = $this->addData ( $arr );
		$message = array();
		if($result->isSuccess()){
			//订单日志
			$this->sendRecord($order_id, $uid);
			//$this->sendMessage($order_id); //发送短信
			
			//添加物流消息
			for($i=0;$i<count($rec_id);$i++){
				$data['uid'] = $uid;
				$data['order_id'] = $rec_id[$i];
				$data['addtime'] = time();
				$data['type'] = 1;
				$message[$i] = $data;
			}
			
			M('message_logistics') ->addAll($message);
		}
	}
	/**
	 * 订单发货日志
	 * @param  $order_id
	 * @param  $uid
	 */
	public function sendRecord($order_id,$uid,$shipping_type=0){
		if(!is_array($order_id)){
			$order_id = array($order_id);
		}
		if($shipping_type>0){
			$data = $this->getSendInfo($order_id,$shipping_type);
		}
		foreach($order_id as $key=>$v){
			$action[$key] = array(
					"order_id"=>$v,
					"action" => json_encode(array("shipping_status" => 1)),
					"is_seller"=>1,
					"handle"=>$uid,
					"remark"=>"商家已发货",
					"front_remark"=>"您已在指定门店完成包裹提货，感谢您的光临",
					"type"=>0,
					"add_time"=>NOW_TIME
			);
			if($shipping_type==2){
				foreach($data as $vo){
					if($v==$vo['order_id']){
						$action[$key]['front_remark'] = "您的订单已出库，由".$vo['name']."负责配送，配送员：".$vo['username']."，联系电话：".$vo['mobile']."，请保持电话畅通哦！";
					}
				}
			}
			if($shipping_type==1){
				$action[$key]['front_remark'] = "您的订单已发货";
			}
		}
		return  M("OrderAction")->addAll($action);
	}
	
	/**
	 * 发货完成发送短信
	 * @param  $order_id
	 */
	public function sendMessage($order_id){
		$where = array(
				"order_id"=>$order_id
		);
		$mobile = M("OrderReceipt")->where($where)->getField("mobile");
		$stores_id = M("Order")->where($where)->getField("stores_id");
		$stores_name = current(D("Stores/Stores")->getStoresById($stores_id,"name"));
		$template = getTempContent ( "send_order_message", 1, array (
				"date" => date('Y-m-d'),
				"stores_name" => $stores_name
		));
		$mobileTemplate = html_entity_decode ( strip_tags ( $template ) );
		// 发送短信
		$messageObj = new \Common\Org\Util\MobileMessage ();
		$mobileResult = $messageObj->mobileSend ( $mobile, $mobileTemplate,0 );
	}
	
	/**
	 * 通过订单id获取发货详细信息
	 * @param $order_id
	 * @param 发货类型
	 */
	public function getSendInfo($order_id,$shipping_type=0){
		$where = array(
				"order_id"=>array('in',$order_id),
				"send_type"=>$shipping_type
		);
		$data = $this->where($where)->select();
		if($shipping_type==2){
			//获取配送员信息
			if($data){
				$uid = array_column($data, "delivery");
				$stores_ids = array_column($data, "stores_id");
				$stores = D("Stores/Stores")->getStoresByIds($stores_ids,"stores_id,name");
				$user = D("Admin/Admin")->getUserInfo($uid,'uid,nickname,mobile');
				foreach($data as &$v){
					if($user){
						foreach($user as $vo){
							if($v['delivery'] == $vo['uid']){
								$v['username'] = $vo['nickname'];
								$v['mobile'] = $vo['mobile'];
							}
						}
					}
					if($stores){
						 foreach($stores as $vs){
						 	if($v['stores_id'] == $vs['stores_id']){
						 		$v['name'] = $vs['name'];
						 	}
						 }
					}
				}
			}
		}
		return $data;
	}
	
	/**
	 * 发货销售单
	 * @param $order_id 订单id
	 */
	public function getSales($order_id){
		$where = array(
				"order_id"=>array('in',$order_id),
				//"Orders.shipping_status"=>array('in',"1,2")
		);
		$data = $this->saleView()->where($where)->select();
		//获取订单商品
		$recData = $this->getRecData($order_id);
	    foreach($data as $key=>&$v){
					foreach ($recData as $vo){
						if($v['order_id'] == $vo['order_id']){
							if($vo['norms_value']){
								$vo['norms_value'] = json_decode($vo['norms_value'],true);
							}
							$v['rec'][] = $vo;
						}
					}
				}
		return $data;
	}
	
	/**
	 * 发货销售单试图
	 */
	public function saleView(){
		$viewFields = array(
				'Order' => array (
						'order_id',
						'shipping_type',
						'postscript',
						'_type' => 'LEFT',
						'_as' => 'Orders',
				),
				 'OrderSend' => array (
						'send_num',
						'send_time',
						'_on' => 'Orders.order_id=OrderSend.order_id',
						'_type' => 'LEFT'
				),
				'User' => array(
						'username',
						'_on' => 'Orders.uid=User.uid',
						'_type'=>'LEFT'
				),
				'OrderReceipt'=>array(
						'mobile',
						'name',
						'address',
						'_on' => 'Orders.order_id = OrderReceipt.order_id',
						'_type' => 'LEFT'
		        ),
				'LogisticsCompany'=>array(
				     'lc_name',
					 '_on'=>'OrderSend.logistics = LogisticsCompany.lc_id'  		
		         )   
		);
		return $this->dynamicView($viewFields);
	}
	
	/**
	 * 获取快递单号
	 * @param $order_id 订单id
	 * @param $ware_id 发件人id
	 */
	public function getSendCode($order_id,$ware_id){
		$return_array = array();
		$where = array(
				'order_id'=>array('in',(array)$order_id)  
		);
		$ware = D("Admin/WareHouse")->getWareById($ware_id); //发件人信息
		$order = M("Order")->field('order_id,order_sn')->where($where)->select();//订单编号
		//收货人信息
		$recept = M("OrderReceipt")->where($where)->select();
		foreach ($order as $key=>$v){
			$return_array[$key] = array(
					'order_sn'=>$v['order_sn'],
					'senderName'=>$ware['ware_username'], //发货人姓名
					'senderMobile'=>$ware['ware_mobile'], //发货人手机
					'senderProvinceName'=>$ware['provice'],//行政地区
					'senderCityName'=>$ware['citys'],   //城市
					'senderAddress'=>$ware['ware_address'],//发货地址
					'payType'=>1        //0:发货人付款（现付）1:收货人付款（到付）2：发货人付款（月结）	
			);
			foreach($recept as $vs){
				if($v['order_id'] == $vs['order_id']){
					$return_array[$key]['receiverName'] = $vs['name'];//收货人姓名
					$return_array[$key]['receiverMobile']=$vs['mobile'];//收货人手机
					$return_array[$key]['receiverProvince']=$vs['province']; //省份
					$return_array[$key]['receiverCity']=$vs['city'];
					$return_array[$key]['receiverAddress']=$vs['localtion'].$vs['address'];//收货人详细地址
					$return_array[$key]['cargoName']='h';//货物名称
				}
			}
		}
		$code = array();
		if(!empty($return_array)){
			$deBang = new \Common\Org\Util\DeBangKuaidi();
			foreach ($return_array as $v){
				$result = $deBang->createOrder($v);
				$result = json_decode($result,true);
				if($result['result']){
					$code[] = $result['logisticID'];
				}
			}
		}
		return $code;
	}
}