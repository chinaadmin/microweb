<?php
/**
 * 退款/退货管理
 * @author xiongzw
 * @date 2015-05-27
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\ThinkPay\RefundVo;
use Common\Org\ThinkPay\ThinkPay;

class RefundController extends AdminbaseController {
	protected $refund_model;
	protected $curent_menu = 'Refund/index';
	public function _initialize() {
		parent::_initialize ();
		$this->refund_model = D ( 'Admin/Refund' );
	}
	/**
	 * 退款列表
	 */
	public function index() {
		$model = $this->refund_model->viewModel ();
		$where = $this->_where ();
		$lists = $this->lists ( $model, $where, "refund_time desc" );
		$attrs = array_column ( $lists, "voucher" );
		$attrs = $this->refund_model->getAttr ( $attrs );
		$goods = array_column ( $lists, "goods_id" );
		$orders = array_column ( $lists, "order_id" );

		$refundList = M('Refund')->field('refund_status')->select();

		//统计数量
		$orderNum = array();
		$orderNum['notPass']  = 0;	//审核未通过
		$orderNum['notAudit'] = 0;	//待审核
		$orderNum['toRefund'] = 0;	//待退款
		$orderNum['toReturn'] = 0;	//待退货
		$orderNum['Refunded'] = 0;	//已退款
		foreach($refundList as $key=>$val){
			if($val['refund_status']==-1){
				$orderNum['notPass']+=1;
			}elseif($val['refund_status']==0){
				$orderNum['notAudit']+=1;
			}elseif($val['refund_status']==1){
				$orderNum['toRefund']+=1;
			}elseif($val['refund_status']==3){
				$orderNum['Refunded']+=1;
			}elseif($val['refund_status']==5){
				$orderNum['toReturn']+=1;
			}
		}
		$orderNum['all'] = count($refundList);

		foreach ($lists as &$v) {
			foreach ( $attrs as $vo ) {
				if (in_array ( $vo ['att_id'], json_decode ( $v ['voucher'], true ) )) {
					$v [pic] [] = $vo ['path'];
				}
			}
			if (! empty ( $v ['norms_value'] )) {
				$v ['norms'] = json_decode ( $v ['norms_value'], true );
			}
		}
		
		$this->assign ( "lists", $lists );
		$this->assign ( "orderNum", $orderNum );
		$this->assign ( "refund_status", $this->refund_model->refund_status );
		$this->assign ( "refund_show", $this->refund_model->formatStatus () );
		$this->display ();
	}
	/**
	 * 查询条件
	 */
	private function _where() {
		$where = "Refund.delete_time=0 AND"; // 没有删除的退货单
		/*
		 * $stores_id = D('Stores/StoresUser')->storesUser($this->user['uid']); if(!empty($stores_id) && !$this->checkRule("Refund/checkStores")){ $stores_id = $stores_id['stores_id']; $where .= " Orders.stores_id=".$stores_id." AND"; }
		 */
		if ($this->stores_id) {
			$where .= " Orders.stores_id=" . $this->stores_id . " AND"; // 所属门店id
		}

		// 订单状态
		$refund_status = I ( 'request.refund_status', 0, 'intval' );

		// $keywords = I ( 'request.refund_keywords', '' ); // 关键字

		if ($refund_status >= - 1 && isset ( $_REQUEST ['refund_status'] )) {
			$where .= " Refund.refund_status=" . $refund_status . " AND";
		}

		//申请退货时间
		$start_time = I ( 'request.start_time', '', "strtotime" );

		$end_time = I ( 'request.end_time', '', 'strtotime' );

		if ($start_time && ! $end_time) {
			$where .= " Refund.refund_time>=" . $start_time . " AND";
		}
		if (! $start_time && $end_time) {
			$where .= " Refund.refund_time<=" . $end_time . " AND";
		}
		if ($start_time && $end_time) {
			if ($start_time == $end_time) {
				$where .= " FROM_UNIXTIME(Refund.refund_time, '%Y-%m-%d')='" . date ( 'Y-m-d', $start_time ) . "' AND";
			} else {
				$where .= " Refund.refund_time>=" . $start_time . " AND Refund.refund_time<=" . $end_time . " AND";
			}
		}

		// 关键字
		// if (! empty ( $keywords )) {
		// 	$where .= " Refund.refund_sn LIKE '%" . $keywords . "%' OR AdminUser.username LIKE '%" . $keywords . "%' ";
		// }

		//售后单号
		$refundSn = I('request.refund_sn', '');
		if( ! empty($refundSn)){
			$where .= " Refund.refund_sn = '" . $refundSn . "' AND";
		}

		//手机号
		$mobile = I('request.mobile', '');
		if( ! empty($mobile)){
			$where .= " User.mobile = '" . $mobile . "' AND";
		}

		//订单号
		$orderSn = I('request.order_sn', '');
		if(! empty($orderSn)){
			$where .= " Orders.order_sn = '" . $orderSn . "' AND";
		}

		//用户名
		$username = I('request.username', '');
		if( ! empty($username)){
			$where .= " User.username = '" . $username . "' AND";
		}

		//支付时间
		$payStartTime = I ( 'request.pay_start_time', '', "strtotime" );

		$payEndTime = I ( 'request.pay_end_time', '', 'strtotime' );

		if ($payStartTime && ! $payEndTime) {
			$where .= " Orders.pay_time >= " . $payStartTime . " AND";
		}
		if (! $payStartTime && $payEndTime) {
			$where .= " Orders.pay_time <= " . $payEndTime . " AND";
		}
		if ($payStartTime && $payEndTime) {
			if ($payStartTime == $payEndTime) {
				$where .= " FROM_UNIXTIME(Orders.pay_time, '%Y-%m-%d')='" . date ( 'Y-m-d', $payStartTime ) . "' AND";
			} else {
				$where .= " Orders.pay_time >= " . $payStartTime . " AND Orders.pay_time <= " . $payEndTime . " AND";
			}
		}

		$where = rtrim ( $where, "AND" );

		$this->assign ( "select", I ( 'request.' ) );
		return $where;
	}

	/**
	 * 门店人员是否有权限查看所有退款列表
	 */
	public function checkStores() {
	}
	
	/**
	 * 退款详情页
	 */
	public function info() {
		$refund_id = I ( 'request.refund_id', '' );
		$where = array (
				"refund_id" => $refund_id 
		);
		$info = $this->refund_model->viewModel ()->where ( $where )->find ();
// 		print_r($info);exit;
		//商品图片
		$info['rec_id']=explode(",",$info['rec_id']);
// 		print_r($info['rec_id']);exit;
		foreach($info['rec_id'] as $key=>$v){
			if($v){
				$swhere = array (
						"rec_id" => $v
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
				$info['goodspic'][$key] = array(
						"name"=>$g ['name'],
						"goods_price"=>$sgoods ['goods_price'],
						"number"=>$sgoods ['number'],
						"pic"=>fullPath($infos),
						"norms_value"=>json_decode ( $sgoods ['norms_value'], true )
				);
			}
		}
		$info ['refund_reasons'] = $this->refund_model->refund_reasons [$info ['refund_reasons']];
// 		if ($info ['norms_value'])
// 			$info ['norms_value'] = json_decode ( $info ['norms_value'], true );
// 		$attrs = $this->refund_model->getAttr ( json_decode ( $info ['voucher'], true ) );
		//凭证图片
		$info ['voucher'] = json_decode ( $info ['voucher'], true );
// 		print_r($info ['voucher']);
		foreach($info['voucher'] as $key=>$v){
			if($v){
				$pic = D ( 'Upload/AttachMent' )->getAttach ( $v );
// 				print_r($pic);exit;
				$pic = $pic [0] ['path'];
				$info['voucher'][$key] = array(
						"pic"=>fullPath($pic)
				);
			}
		
		}
		$this->assign ( 'info', $info );
// 		$this->assign ( "attrs", $attrs );
		$this->assign ( "refund_show", $this->refund_model->formatStatus () );

        $order_info = M ( 'Order' )->field ( true )->where ( [
            'order_id' => $info ['order_id']
        ] )->find ();
        
        
        $this->assign ( "order_info",$order_info);
        
//         print_r($order_info);
//         print_r($info);exit;
        

		$this->display ();
	}
	
	/**
	 * 收货
	 */
	public function receive() {
		$refund_id = I ( 'request.refund_id', '' ); // 退货id
		if ($refund_id) {
			$where = array (
					'refund_id' => $refund_id 
			);
			$data = array (
					"is_receive" => I ( 'post.is_receive', 0, 'intval' ), // 退货商品收货状态
					"refund_mark" => I ( 'post.refund_mark', '' ) // 退货备注
			);
			if ($data ['is_receive'] != 0) { // 如果退货商品已收到
				$data ['receive_user'] = $this->user ['uid']; // 收货人
				$data ['receive_time'] = NOW_TIME; // 收货时间
				if ($data ['is_receive'] < 0) { // 拒收
					$data ['examine'] = $this->user ['uid']; // 审核人
					$datap ['examine_time'] = NOW_TIME; // 审核时间
					$data ['refund_status'] = - 1; // 退货状态改为 审核未通过
				} else {
					$data ["refund_status"] = 1; // 退货状态改为待退款
				}
			}
			if ($data ['refund_status'] < 0) { // 审核未通过
				if (empty ( $data ['refund_mark'] )) { // 备注为空时
					$this->ajaxReturn ( $this->result->error ( "请填写拒收货的原因" )->toArray () );
				}
			}
			$result = $this->refund_model->setData ( $where, $data ); // 写入修改数据
			if ($result->isSuccess ()) {
				if ($data ["refund_status"] != 0) { // 写入审核信息
					$this->refund_model->record ( $refund_id, $this->user ['uid'], $data ['refund_status'], $data ['refund_mark'] );
				}
				if ($data ['is_receive']) {
					$msg = $data ['is_receive'] == 1 ? "商家已收货" : "商家拒绝收货"; // 收货状态
					$message = "修改退款id：" . $refund_id . "&nbsp;收货状态：" . $msg;
				}
				// 修改商品发货状态
				$order_id = current ( $this->refund_model->getRefundById ( $refund_id, "order_id" ) ); // 获取订单id
				if ($this->refund_model->allRefund ( $order_id,2 ) && $data ['is_receive'] > 0) {
					M ( "Order" )->where ( "order_id='{$order_id}'" )->save ( array (
							"shipping_status" => 3 
					) );
				}
				// 后台日志
				D ( 'AdminLog' )->addAdminLog ( $message, 2, $where );
			}
			$this->ajaxReturn ( $result->toArray () );
		} else {
			$this->ajaxReturn ( $this->result->error ( "未知错误" )->toArray () );
		}
	}
	/**
	 * 删除退款单
	 */
	public function del() {
		$refund_ids = I ( 'post.refund_id', '' );
		if ($refund_ids) {
			$where = [ 
					'refund_id' => [ 
							'in',
							$refund_ids 
					],
					'refund_status' => 6 
			];
			$refund_ids = $this->refund_model->where($where)->getField("refund_id",true);
			if(empty($refund_ids)){
				$this->ajaxReturn ( $this->result->error ( "请选择符合条件的值！" )->toArray () );
			}
			$data = array ();
			foreach ( $refund_ids as $v ) {
				$data [$v] = NOW_TIME;
			}
			$sql = array2UpdateSql ( $data, C ( 'DB_PREFIX' ) . "refund", 'delete_time', 'refund_id' );
			if ($this->refund_model->execute ( $sql )) {
				$message = "删除退款id:" . implode ( ",", $refund_ids );
				D ( 'AdminLog' )->addAdminLog ( $message, 3, "", $sql );
			}
			$this->ajaxReturn ( $this->result->success ()->toArray () );
		} else {
			$this->ajaxReturn ( $this->result->error ( "请选择要删除的值！" )->toArray () );
		}
	}
	/**
	 * 批量审核
	 */
	public function batchAudit() {
		$ids = html_entity_decode ( I ( 'post.ids' ) );
		$ids = array_column ( json_decode ( $ids, true ), "value" );
		if (empty ( $ids )) {
			$refund_id = I ( "request.refund_id", '' );
			$ids = $refund_id;
			if ($ids && ! is_array ( $ids )) {
				$ids = array (
						$ids 
				);
			}
		}
		$count = count ( $ids ); // 审核总id数
		$ids = $this->refund_model->getNoVerifyId ( $ids );
		$ids = array_column ( $ids, 'refund_id' );
		$success_count = count ( $ids ); // 审核成功数
		$refund_mark = I ( 'post.refund_mark', '' );
		$refund_status = I ( 'post.refund_status', 0, 'intval' );
		if ($refund_status < 0 && empty ( $refund_mark )) {
			$this->ajaxReturn ( $this->result->error ( '请填写不同意原因！' )->toArray () );
		}
		if (! empty ( $ids )) {
			$refund = $this->refund_model->getRefundByship ( $ids );	
			$data = array (
					"refund_mark" => $refund_mark,
					"refund_status" => $refund_status,
					'examine' => $this->user ['uid'],
					'examine_time' => NOW_TIME 
			);
			// 退货审核
			if (! empty ( $refund ['shipping'] )) {
				$where = array (
						'refund_id' => array (
								'in',
								$refund ['shipping'] 
						) 
				);
				$result = $this->refund_model->setData ( $where, $data );
				if ($result->isSuccess ()) {
					$this->_refundAction ( $refund ['shipping'], $refund_status, $where, $refund_mark );
				}
			}
			// 退款审核
			if (! empty ( $refund ['no_shipping'] )) {
				$where = array (
						'refund_id' => array (
								'in',
								$refund ['no_shipping'] 
						) 
				);
				$data ["refund_status"] = $refund_status>-1?1:-1;
				$result = $this->refund_model->setData ( $where, $data );
				if ($result->isSuccess ()) {
					$this->_refundAction ( $refund ['no_shipping'], $data ["refund_status"], $where, $refund_mark );
				}
			}
		}
		$fail_count = $count - $success_count;
		if (! empty ( $refund_id )) {
			$msg = "审核成功";
		} else {
			$msg = $success_count . "条退款单审核成功，" . $fail_count . "条退款单审核失败!";
		}
		$this->ajaxReturn ( $this->result->success ( $msg )->toArray () );
	}
	
	/**
	 * 订单操作
	 */
	private function _refundAction($ids, $status, $where, $refund_mark) {
		$this->refund_model->record ( $ids, $this->user ['uid'], $status,$refund_mark );
		// 日志
		$message = "审核了退款id：" . implode ( ",", $ids ) . "退款状态：" . $this->refund_model->refund_status ["{$status}"] . "备注：" . $refund_mark;
		D ( 'AdminLog' )->addAdminLog ( $message, 2, $where );
	}
	
	/**
	 * 第三方平台退款
	 */
	public function platform() {
		$id = I ( 'get.id' );
		$refund_info = M ( 'Refund' )->field ( true )->where ( [ 
				'refund_id' => $id 
		] )->find ();
		$order_info = M ( 'Order' )->field ( true )->where ( [ 
				'order_id' => $refund_info ['order_id'] 
		] )->find ();
		$pay_type = explode("#", $order_info['pay_type']);
		print_r($id);exit;
        switch($pay_type[0]){
            case 'WEIXIN'://微信退款
                $pay = new \Common\Org\Util\WechatPay();
                $data = array(
                    "out_trade_no" => $order_info['order_sn'],
                    "total_fee" => $order_info['money_paid'],
                    "refund_fee" => $refund_info['refund_money'],
                    "refund_no" => $refund_info['refund_sn'],
                	"pay_type" => $order_info['pay_type']
                );
                $pay = $pay->refund($data);
                if($pay["result_code"] == "SUCCESS"){
                    $result = D('Admin/Refund')->refundOnce($refund_info['refund_id'],true);
                    $this->success('退款成功！');
                }else{
                    $this->error($pay['err_code_des']);
                }
                break;
            case 'ACCOUNT'://余额支付退款
                $result = D('Admin/Refund')->refundOnce($refund_info['refund_id'],true);
                if($result->isSuccess()){
                    $this->success('退款成功');
                }else{
                    $this->error('退款失败');
                }
                break;
            default://支付宝退款
                $thinkpay = ThinkPay::getInstance ( $order_info ['pay_type'] );
                $thinkpay->setProcess ( ThinkPay::PROCESS_REFUND );
                $refund_vo = new RefundVo ();
                $refund_vo->setBatchNo ()->addData ( $id );
                echo $thinkpay->submit ( $refund_vo );
        }
	}
	
	
	/**
	 * 后台申请退款退货操作
	 */
	public function doRefund(){
		$rec_id = I("post.recId","");
		$refund_money = I("post.refund_money");
		$max_refund_money = I("post.max_refund_money");
		if($refund_money>$max_refund_money){	//退款金额大于最大退款金额，取最大退款金额
			$refundMoney = $max_refund_money;
		}else{
			$refundMoney = $refund_money;
		}
		$rec = D("Home/Refund")->recInfo($rec_id);

		//查看是否已申请退货、退款
		$refundData = M('Refund')->where(['rec_id'=>$rec_id])->find();
		if(!empty($refundData)){	//已申请过的订单判断该订单是否是用户取消的，是则修改订单，否则退出
			if($refundData['refund_status']==6){
				$dat = array();
				$dat['refund_status'] = I("post.refund_status",0,'intval');
				$dat['is_receive'] = I("post.is_receive",0,'intval');
				$dat['refund_num'] = $rec['number'];
				$dat['refund_money'] = $refundMoney;
				$dat['description'] = I("post.mark","");
				$dat['refund_time'] = NOW_TIME;
				$dat['examine'] = I('post.refund_status')?$this->user['uid']:"";
				$dat['examine_time'] = I('post.refund_status')?NOW_TIME:0;
				$dat['refund_reasons'] = I("post.reasons",0,'intval');

				$resSave = D("Home/Refund")->saveRefundRecord($dat,$refundData['refund_id'],$rec_id);
				if($resSave){
					$this->ajaxReturn($this->result->success("操作成功")->toArray());
				}else{
					$this->ajaxReturn($this->result->error("操作失败")->toArray());
				}
			}else{
				$this->ajaxReturn($this->result->error("该订单已申请退款，不能重复申请")->toArray());
			}
			exit;
		}

		//$refund_money = $rec['goods_price']*$number+D("Home/Refund")->getRefundMoney($rec['order_id'],$rec_id,$number);
		$data = array(
				"refund_id"=>md5(uniqid().microtime().mt_rand(111111,999999)),
				"order_id"=>I("post.order_id",""),
				"rec_id" => $rec_id,
				"refund_status"=>I("post.refund_status",0,'intval'),
				"is_receive"=>I("post.receipt",'0','intval'),
				"goods_id"=>I("post.goods_id",0,'intval'),
				"refund_money"=>$refundMoney,
				"refund_sn"=>D("Home/Refund")->refund_sn(),
				"refund_num"=>$rec['number'],
				"description"=>I("post.mark",""),
				"refund_time"=>NOW_TIME,
				"refund_uid"=>I("post.uid"),
				"examine"=>I('post.refund_status')?$this->user['uid']:"",
				"examine_time"=>I('post.refund_status')?NOW_TIME:0,
				"receive_user"=>I("post.receipt")?$this->user['uid']:"",
				"receive_time"=>I("post.receipt")?NOW_TIME:0,
				"refund_reasons"=>I("post.reasons",0,'intval'),
				"hope_refund_money"=>$refundMoney
		);
		// dump($data);die;
		$result = D("Home/Refund")->addRefund($data,false,1,false);
		$this->ajaxReturn($result->toArray());
	}


	/**
	 * 更改退款单状态
	 */
	public function changeRefundStatus(){
		$refundStatus = I('post.refundStatus'); // 退款状态
		$refundMoney = I('post.refundMoney'); // 退款金额
		$refundMark = I('post.refundMark'); // 备注
		$editRefundMoney = I('post.editRefundMoney'); // 审核后修改的退款金额
		$refundSn = I('post.refundSn'); // 退款编号
		$orderStatus = I('post.orderStatus'); // 订单发货状态
		$confirmDelivery = I('post.confirmDelivery'); // 确认收货时的退款状态
		$refundHandle = I('post.refundHandle'); // 处理退款单后的退款状态

		$orderSn = I('post.orderSn'); // 订单编号

		// 退货单审核状态
		if( ! empty($refundStatus)){
			if( 0 == $orderStatus && -1 != $refundStatus){ // 订单未发货并且通过审核
				$data['refund_status'] = 1;
			}elseif( 0 != $orderStatus && -1 != $refundStatus){ // 订单已发货并通过审核
				$data['refund_status'] = 5;
			}else{	//	没通过审核
				$data['refund_status'] = -1;
			}	
			
			$data['examine'] = $this->user ['uid']; // 审核人
			$data['examine_time'] = NOW_TIME; // 审核时间
		}

		//	退款金额
		if( ! empty($refundMoney)){
			$data['refund_money'] = $refundMoney;
		}

		//	备注
		if( ! empty($refundMark)){
			$data['refund_mark'] = $refundMark;
		}

		//	修改退款金额
		if( ! empty($editRefundMoney)){
			$data['refund_money'] = $editRefundMoney;
		}

		// 确认收货时的退款状态
		if( ! empty($confirmDelivery)){
			$data['refund_status'] = $confirmDelivery; // 退货单状态
			$data['is_receive'] = 1; // 退货商品收货状态：1 已收到
			$data['receive_user'] = $this->user ['uid']; // 收货人
			$data['receive_time'] = NOW_TIME; // 收货时间
		}

		// 处理退款单后的退款状态
		if( ! empty($refundHandle)){
			$data['refund_status'] = $refundHandle;
			$data['completion_time'] = NOW_TIME; // 订单完成时间
		}
			
		if(M('Refund')->where(['refund_sn' => $refundSn])->setField($data)){
			// 确认收货
			if( ! empty($orderSn)){
				M('Order')->where(['order_sn' => $orderSn])->setField('shipping_status', 3); // 将订单配送状态改为 退货
				D ( 'AdminLog' )->addAdminLog ( '确认收货成功！', 1 ); // 写后台日志
			}
			if($refundHandle==3){
				$refund_info = M ( 'Refund' )->field ( true )->where ( [
						'refund_sn' => $refundSn
				] )->find ();
				$order_info = M ( 'Order' )->field ( true )->where ( [
						'order_id' => $refund_info ['order_id']
				] )->find ();
				$pay_type = explode("#", $order_info['pay_type']);
				// 		print_r($refund_info);exit;
				switch($pay_type[0]){
					case 'WEIXIN'://微信退款
						$pay = new \Common\Org\Util\WechatPay();
						$data = array(
								"out_trade_no" => $order_info['order_sn'],
								"total_fee" => $order_info['money_paid'],
								"refund_fee" => $refund_info['refund_money'],
								"refund_no" => $refund_info['refund_sn'],
								"pay_type" => $order_info['pay_type']
						);
						$pay = $pay->refund($data);
						
						break;
					case 'ACCOUNT'://余额支付退款
						//返还余额
						
							$credits_model = D('User/Credits');
							$credits_model->setOperateType(5,'ACCOUNT');
							$credits_result = $credits_model->setCredits($order_info['uid'],$refund_info['refund_money'],'订单'.$order_info['order_sn'].'退款,余额返还',0,0);
						break;
					default://支付宝退款
						break;
				}
// 				header('Location:'.U('Admin/Refund/platform2',array('id'=>$refundSn)));
// 				$this->redirect('Admin/Refund/platform2', array('id'=>$refundSn));
// 				$this->platform2($refundSn);
			}

			$this->ajaxReturn(['code' => 1, 'msg' => '审核成功！']);
		}else{
			$this->ajaxReturn(['code' => 0, 'msg' => '审核失败！']);
		}
	}
	
	
}