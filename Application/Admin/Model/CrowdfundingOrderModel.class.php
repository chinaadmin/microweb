<?php
/**
 * 众筹订单模型
 * @author wxb 
 * @date 2015-12-18
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class CrowdfundingOrderModel extends  AdminbaseModel{
    public $statusStringArr = [ //订单状态名数组
			'0' => '待确认',
			'1' => '已确认',
			'2' => '已取消',
			'3' => '退货',
			'4' => '已完成',
			'5' =>'已过期',
			'6' =>'无效'
	];
    public $payStatusName = [ //支付状态
    		'0' => '待支付',
    		'1' => '支付中',
    		'2' => '已支付',
    		'3' => '申请退款',
    		'4' => '已退款'
    ];
    public $deliveryName = [
            '0' => '门店自提',
    		'1' => '普通快递',
    		'2' => '快兔配送'
    ];
    public $shippingStatusName = [
    		'0' => '待发货',
    		'1' => '发货中',
    		'2' => '已发货',
    		'3' => '已收货',
    		'4' => '退货'
    ];
    private $objContainer = null;
    public function formatStatus(Array $status){
    	if(!$status){
    		$status = $this->payStatusName;
    	}
    	$data = array ();
    	array_walk ( $status, function (&$item, $key) use(&$data) {
    		if (is_array ( $item )) {
    			foreach ( $item as $k => &$vs ) {
    				$item [$k] = array (
    						'text' => $vs['text'],
    						'style'=> $vs['style']
    				);
    			}
    			$data [$key] = $item;
    		} else {
    			$data [$key] = array (
    					'text' => $item
    			);
    			switch($key){
    				case 2:
    					 // $data[$key]['style'] = 'label-success';
    					 $data[$key]['style'] = 'text-success';
    					 break;
    				default:
    					// $data[$key]['style'] = 'label-important';
    					$data[$key]['style'] = 'text-danger';
    			}
    		}
    	} );
    	return $data;
    }
	/**
	 * 生成订单id
	 * @return string
	 */
	public function orderid() {
		return uniqueId();
	}
	/**
	 * 生成订单号
	 * @return string
	 */
	public function ordersn() {
		mt_srand((double) microtime() * 1000000);
		return date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
	}
		/**
		 * 订单试图模型
		 */
	public function viewModel() {
			$viewFields = array (
					'CrowdfundingOrder' => array (
							'*',
							'_type' => 'LEFT',
							'_as' => "Orders"
					),
					'User' => array (
							"username",
							"aliasname",
							"mobile",
							'_on' => 'Orders.cor_uid=User.uid',
							'_type' => 'LEFT'
					),
					"Payment" => array (
							"code",
							"name" => 'payment_name',
							"_on" => "Orders.cor_pay_type=Payment.code",
							"_type" => "LEFT"
					),
					'CrowdfundingOrderMakefile' => [
							'_as' => 'com',
							'jt_com_crowdfunding_plan',

							'jt_com_type', // 订单来源
							'jt_offline_check', // 审核状态

							'_on' => 'com.jt_com_ordersn = Orders.fk_com_ordersn',
							'_type' => 'LEFT'
					],
					'Crowdfunding' => [
							'_type' => 'LEFT',
							'_as' => "crw",
							'_on' => "crw.cr_id = com.fk_cr_id",
							'cr_name' => 'jt_com_crowdfunding_name'
					],
					'OrderReceipt' => array (
							"name" => 'receipt_name',
							'mobile' => 'receipt_mobile',
							"tel",
							"zipcode",
							"localtion",
							"address",
							'_on' => "OrderReceipt.order_id = cast(com.jt_com_id as char)",
							'_type' => 'LEFT'
					),
					'Stores' => array(
							'_as' => 'st',
							'name' => 'stores_name',
							'_on' => 'Orders.cor_store_id != 0 and st.stores_id = Orders.cor_store_id'
					)
			);
			return $this->dynamicView ( $viewFields );
		}
	/**
	 * 订单处理
	 * @param string $order_id 订单id
	 * @param array $action 订单操作
	 * @param string $remark 备注
	 * @param string $front_remark 前端显示备注
	 * @return mixed
	 */
	public function orderAction($order_id,$action,$remark,$front_remark,$uid){
		return M('OrderAction')->add([
				'order_id'=>$order_id,
				'action'=>json_encode($action),
				'is_seller'=>0,
				'handle'=>$uid,
				'remark'=>$remark,
				'front_remark'=>$front_remark,
				'add_time'=>time()
		]);
	}
	/**
	 * 将订单状态转化成状态名称
	 * @param int $statusInt 订单状态
	 */
	function statusToString($statusInt){
		if(!$this->statusStringArr[$statusInt]){
			return '状态码有误!';
		}
		return $this->statusStringArr[$statusInt];
	}
	function orderGoodList($order_id,$where = []){
		static $tmpModel = null,$m = null;
		if(!$tmpModel){
			$tmpModel = M('crowdfunding_order_goods');
		}
		if(!$m){
			$m = M('order_send');
		}
		$where['fk_cor_order_id'] = $order_id; 
		$res =  $tmpModel->where($where)->select();
		foreach ($res as &$v){			
			$where = [];
			$where['rec_ids'] = json_encode([$v['cog_id']]);
			$where['order_id'] = $v['fk_cor_order_id'];
			$tmp = $m->where($where)->find();
			if($tmp){
				$v = array_merge($v,$tmp);
			}
		}
		return $res;
	}
	/**
	 * 获取订单及其产品
	 * @param array $where 订单条件
	 */
	function getOrderWithGoods($where){
		$m = $this->viewModel();
		$list = $m->where($where)->select();
		$model = M('crowdfunding_order_goods');
		foreach ($list as &$v){
			$v['order_count'] = $model->where(['fk_cor_order_id' => $v['cor_order_id']])->sum('cog_count');
			$v['pay_status_name'] = $this->payStatusName[$v['cor_pay_status']];
			$v['status_name'] = $this->statusToString($v['cor_order_status']);
			$v['delivery_name'] = $this->deliveryName[$v['cor_delivery_type']];
			$v['goodsList'] = $this->orderGoodList($v['cor_order_id']);
			if(!$v['payment_name'] && $v['cor_pay_type'] == 'ACCOUNT'){
				$v['payment_name'] = '余额支付';
			}else if(!$v['payment_name'] && $v['cor_pay_type'] == 'CASH'){
				$v['payment_name'] = '现金支付';
			}
		}
		return $list;
	}
	//订单提成视图模型
	function percentageOrderView(){
		$viewFields = [
				'crowdfunding_order' => [
					'_as' => 'co',
					'_type' => 'left',
					'*'
				],
				'crowdfunding_detail' => [
					'_as' => 'cd',	
					'_on' => 'cd.cd_id = co.fk_cd_id',
					'*',
					'_type' => 'left',
				],
				'market_pushing_recommend' => [
						'_as' => 'mpr',
						'_type' => 'left',
						'_on' => 'mpr.mpc_uid = co.cor_uid',
				],
				'admin_user' => [
					'_as' => 'au',
					'_type' => 'left',
					'_on' => 'au.uid = mpr.mpc_recommend',	
					'*'
				],
				'admin_department' => [	//所属部门
					'_as' => 'ad',
					'_type' => 'left',
					'_on' => 'ad.de_id = au.fk_de_id',
					'*'
				]
		];
		return $this->dynamicView ( $viewFields );
	}	
	//员工提成统计视图模型
	function percentageStatisticsView(){
		$viewFields = [
			'admin_user' => [
				'_as' => 'au',
				'_type' => 'left',
				'*'
			],
			'admin_role' => [
					'_as' => 'ar',
					'_on' => 'ar.role_id = au.role_id',
					'_type' => 'left'
			],	
		];
		$where = [
				'au.status' => 1,//用户未禁用
				'au.delete_time' => 0,//后台用户未删除
				'code' => 'dtry', //为地推人员
				'ar.status' => 1,//地推角色可用
		];
		return $this->dynamicView ( $viewFields )->where($where);
	}
	//获取某地推的提成
	function getOneMoney($uid){
		static $tmp;
		if(!isset($tmp['market'])){
			$tmp['market'] = M('market_pushing_recommend');
		}
		$mpc_uid_arr = $tmp['market']->where(['mpc_recommend' => $uid])->getField('mpc_uid',true);
		if(!$mpc_uid_arr){
			return 0;
		}
		if(!isset($tmp['view'])){
			$viewFields = [
					'crowdfunding_order' => [
							'_as' => 'co',
							'cor_should_pay',
							'_type' => 'left',
					],
					'crowdfunding_detail' => [
							'_as' => 'cd',
							'_type' => 'left',
							'_on' => 'cd.cd_id = co.fk_cd_id',
							'cd_percentage'
					],
			];
			$tmp['view'] = $this->dynamicView ( $viewFields );
		}
		$where = [
				'cor_pay_status' => 2,//已支付
				//'cor_order_status' => 4, //已完成订单
				'cor_uid' => ['in',$mpc_uid_arr]
		];
		$list = $tmp['view']->where($where)->select();
		$total = 0;
		foreach ($list as $v){
			$total += $v['cor_should_pay']*($v['cd_percentage']/100);
		}
		return $total;
	}
	/**
	 * 获取快递单模板数据
	 * @param int 订单商品id $goodId
	 * @param int 订单id $order_id
	 */
	function getExpressBillData($goodId,$order_id){
		if(!$orderSend = $this->objContainer['orderSend']){
			$orderSend = $this->objContainer['orderSend'] = D('Admin/SendGoods');
		}
		if(!$orderReceipt = $this->objContainer['orderReceipt']){
			$orderReceipt = $this->objContainer['orderReceipt'] = M('order_receipt');
		}
		if(!$user = $this->objContainer['user']){
			$user = $this->objContainer['user'] = D('Admin/User');
		}
		$orderSendData = $orderSend->where(['order_id' => $order_id,'rec_ids' => json_encode([(string)$goodId])])->find();
		$senderName = '';
		$orderSendUser = [];
		$orderSendUser['mobile'] = '';
		if($orderSendData['send_type'] == 1){//普通快递
			$senderName = '深圳积土电子商务有限公司';
			$orderSendUser['mobile'] = '4007777927 ';
		}else if($orderSendData['send_type'] == 2){ //门店配送
			$senderName = $orderSendData['ware_name'];
			$orderSendUser['mobile'] = '4007777927 ';
		}else{// 自提
			
		}
		$orderInfo = $this->where(['cor_order_id' => $order_id])->find();
		$order_total_info = M('crowdfunding_order_makefile')->where(['jt_com_ordersn' => $orderInfo['fk_com_ordersn']])->find();
		$orderReceiptData = $orderReceipt->where(['order_id' => $order_total_info['jt_com_id']])->find();
		$data = [];
		$data['orNu'] = $orderInfo['cor_order_sn'];
	
		$data['senderName'] = $senderName;
		$data['senderTel'] = $orderSendUser['mobile'];
		$data['remark'] = $orderInfo['cor_remark'];
		$data['senderAdd'] = '';
		$data['senderPostcode'] = '';
		
		$data['recipientsName'] = $orderReceiptData['name'];
		$data['recipientsTel'] = $orderReceiptData['mobile'];
		$data['recipientsAddr'] = $orderReceiptData['localtion'].'  '.$orderReceiptData['address'];
		$data['recipientsPostcode'] = $orderReceiptData['zipcode'];
		$data['recipientsGoodsCount'] = '';
		return  $data;
	}

	/**
	 * @param  int $shippingStatus 众筹商品发货状态
	 * @return array 
	 */
	public function getOrderIdByShippingStatus($shippingStatus){
		return M('CrowdfundingOrderGoods')->where(['cog_shipping_status' => $shippingStatus])->getField('fk_cor_order_id', true);
	}

	/**
	 * 获取子订单号
	 * @param string $orderSn 总括订单号
	 * @return string
	 */
    public function getSubOrderSn($orderSn){
    	$res = $this->where(['fk_com_ordersn'  => $orderSn])->getField('cor_order_sn', true);
    	return implode(',', $res);	
    }

}