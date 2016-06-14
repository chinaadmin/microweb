<?php
/**
 * 订单管理
 * @author xiongzw
 * @date 2015-05-20
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
header('Content-type:text/html;Charset=utf-8');
class OrderController extends AdminbaseController {
	protected $order_model;
	protected $curent_menu = 'order/index';
	protected $Week = array("周日","周一","周二","周三","周四","周五","周六");
	public function _initialize() {
		parent::_initialize ();
		$this->order_model = D ( 'Admin/Order' );
	}
	/**
	 * 订单列表 
	 */
	public function index() {
		$where = $this->_dowhere ();
		$model = $this->order_model->viewModel ();
		$lists = $this->lists ( $model, $where, 'Orders.add_time desc' );
		
		if ($lists) {
			$lists = $this->order_model->formatList ( $lists );
		}

		foreach($lists as $k=>$v){	//判断每个订单是否有商品已申请过退款，没有则显示整单退款入口
			$res = M('Refund')->field('rec_id,refund_status')->where(['order_id'=>$v['order_id']])->find();
			if(!empty($res) && strlen($res['rec_id'])>33){
				foreach($v['goods'] as $key=>$val){
					$lists[$k]['goods'][$key]['refundstatus']=$res['refund_status'];
				}
			}
			$lists[$k]['refundAllStatus']=empty($res)?1:0;
		}

		$this->assign ( "lists", $lists );
		$order_status = $this->order_model->order_status;
		$this->assign ( "sourceType", $this->order_model->order_status['source'] );
		$this->assign ( "shipType", $this->order_model->order_status['shipping_type'] );
		$order_status = $this->order_model->formatStatus ( $order_status );
		$this->assign ( "query_status", $this->order_model->order_status );
		$this->assign ( $order_status );
		$this->assign("stores",D("Stores/Stores")->getStores());//门店
		$this->assign("pays",D("Admin/Payment")->getPays());//获取支付方式
		$this->display ();
	}
	
	/**
	 * 导出订单excel
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function export() {
		$order_id = explode ( ",", rtrim ( I ( "request.order_id", '' ), "," ) );
		if (empty ( $order_id )) {
			$this->error ( "请选择要导出的订单" );
		}
		$where = array (
				"order_id" => array (
						'in',
						$order_id 
				),
				"Orders.delete_time" => 0 
		);
		$model = $this->order_model->viewModel ();
		$lists = $this->lists ( $model, $where, 'Orders.add_time desc' );
		if ($lists) {
			$lists = $this->order_model->formatList ( $lists );
		}
		$this->order_model->exportExcel ( $lists );
	}

	//导出全部订单excel
	public function allExport() {
		$where = $this->_dowhere();
		$model = $this->order_model->viewModel ();
		$lists = $model->where($where)->order('Orders.add_time desc')->select();
		if (count($lists)>1000) {	//超过1000条数据，将导出前1000条数据
			$lists = $this->order_model->formatList ( $lists );
			$listData = array();
			for($i=0;$i<1000;$i++){
				$listData[$i]=$lists[$i];
			}

			$this->order_model->exportExcel($listData);
		}else{
			$lists = $this->order_model->formatList($lists);
			$this->order_model->exportExcel ($lists);
		}
	}
	
	/**
	 * 查询条件
	 * <code>
	 * order_status 订单状态
	 * pay_status 支付状态
	 * receipt_status 发货状态
	 * order_keywords 关键字
	 * start_time 查询开始时间
	 * end_time 查询结束时间
	 * today 今日订单
	 * yesterday 昨日订单
	 * pay_today 今日付款
	 * pay_yesterday 昨日付款
	 * </code>
	 */
	private function _dowhere() {
		$where = "Orders.delete_time=0 AND";
		if ($this->stores_id) {
			$where .= " Orders.stores_id=" . $this->stores_id . " AND";
		}
		if($uid = I('uid','','trim')){
			$where .= "  Orders.uid = '$uid' ";
		}
		// 订单状态
		$order_status = I ( 'request.order_status', 0, 'intval' );
		// 支付状态
		$pay_status = I ( 'request.pay_status', 0, 'intval' );
		// 发货状态
		$receipt_status = I ( 'request.receipt_status', 0, 'intval' );
		// 关键字查询
		$keywords = trim(I ( 'request.order_keywords', '' ));
		if ($order_status >= 0 && isset ( $_REQUEST ['order_status'] )) {
			$where .= " Orders.status=" . $order_status . " AND";
			if($order_status==0){
				$where .= " Orders.pay_status=2 AND";
			}
		}
		if ($pay_status >= 0 && isset ( $_REQUEST ['pay_status'] )) {
			$where .= " Orders.pay_status=" . $pay_status . " AND";
		}
		if ($receipt_status >= 0 && isset ( $_REQUEST ['receipt_status'] )) {
			$where .= " Orders.shipping_status=" . $receipt_status . " AND";
		}
		// $start_time = I ( 'request.start_time', '', "strtotime" );
		// $end_time = I ( 'request.end_time', '', 'strtotime' );

		$start_time = I('request.start_time') ?  strtotime(I('request.start_time'). " 00:00:00") : ""; // 搜索时间改为搜索包含当天时间
		$end_time = I('request.end_time') ? strtotime(I('request.end_time') . " 23:59:59") : ""; // 搜索时间改为搜索包含当天时间

		if ($start_time && ! $end_time) {
			$where .= " Orders.add_time>=" . $start_time . " AND";
		}
		if (! $start_time && $end_time) {
			$where .= " Orders.add_time<=" . $end_time . " AND";
		}
		if ($start_time && $end_time) {
			if ($start_time == $end_time) {
				$where .= " FROM_UNIXTIME(Orders.add_time, '%Y-%m-%d')='" . date ( 'Y-m-d', $start_time ) . "' AND";
			} else {
				$where .= " Orders.add_time>=" . $start_time . " AND Orders.add_time<=" . $end_time . " AND";
			}
		}
		//订单金额查询
		$start_money = I ( 'request.start_money', '');
		$end_money = I ( 'request.end_money', '');
		if ($start_money && ! $end_money) {
			$where .= " Orders.order_amount>=" . $start_money . " AND";
		}
		if (! $start_money && $end_money) {
			$where .= " Orders.order_amount<=" . $end_money . " AND";
		}
		if ($start_money && $end_money) {
			 $where .= " Orders.order_amount>=" . $start_money . " AND Orders.order_amount<=" . $end_money . " AND";
		}
		
		if ($keywords) {
			$where .= " ( Orders.order_sn LIKE '%" . $keywords . "%' OR User.username LIKE '%" . $keywords . "%' OR Orders.order_code='" . $keywords . "' ) AND";
		}
		// 今日订单
		$today = I ( 'request.today', 0, 'intval' );
		if ($today == 1) {
			$field = "Orders.add_time";
			$time = date ( 'Y-m-d' );
		}
		// 昨日订单
		$yesterday = I ( 'request.yesterday', 0, 'intval' );
		if ($yesterday == 1) {
			$field = "Orders.add_time";
			$time = date ( 'Y-m-d', strtotime ( "-1 day" ) );
		}
		// 今日付款
		$pay_today = I ( "request.pay_today", 0, 'intval' );
		if ($pay_today == 1) {
			$field = "Orders.pay_time";
			$time = date ( 'Y-m-d' );
		}
		// 昨日付款
		$yesterday_pay = I ( 'request.pay_yesterday', 0, 'intval' );
		if ($yesterday_pay == 1) {
			$field = "Orders.pay_time";
			$time = date ( 'Y-m-d', strtotime ( "-1 day" ) );
		}
		if ($field && $time) {
			$where .= " FROM_UNIXTIME(" . $field . ", '%Y-%m-%d')='" . $time . "' AND";
		}
		//配送方式
		$send_type = I("request.send_type",'-1','intval');
		if($send_type>-1){
			$where .= " shipping_type=".$send_type." AND";
		}
		//订单来源
		$order_source = I("request.order_source","-1",'intval');
		if($order_source>-1){
			$where .= " source=".$order_source." AND";
		}
		//支付方式
		$pay_type = I("request.pay_type","");
		if($pay_type){
			$where .= " pay_type='".$pay_type."' AND";
		}
		//门店
		$stores = I("request.stores",'-1','intval');
		if($stores>0){
			$where .= " stores_id=".$stores." AND";
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
	 * 订单详情
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function info() {
		$order_id = I ( 'request.order_id' );
		if ($order_id) {
			$where = array (
					'order_id' => $order_id 
			);
			$info = $this->order_model->viewModel ()->where ( $where )->find ();
			if($info['stores_id']){
				$info['stores_name'] = current(D("Stores/Stores")->getStoresById($info['stores_id'],'name'));
			}
            $info['source_name'] = D('Common/Shared')->getSource($info['source']);
			$date = new \Org\Util\Date ();
			$info ['pay_time_long'] = rtrim ( $date->timeDiff ( date ( 'Y-m-d H:i:s', $info ['pay_time'] ) ), "前" );
			$goods = $this->order_model->getGoodsById ( $order_id );
			$this->assign ( 'order_record', $this->order_model->order_record ( $order_id ) );
			//配送信息获取
			$this->assign("send",D("Admin/Cartgo")->dispatching($info['order_id']));
			$this->assign ( "info", $info );
			$this->assign ( "goods", $goods );
			$refund_model = D ( "Admin/Refund" );
			$this->assign ( "refund", $refund_model->getRefundByOrder ( $order_id ) );
			$this->assign ( "refund_show", $refund_model->formatStatus () );
			$this->assign ( "refund_rea", $refund_model->refund_reasons );
			//发货纪录
			$viewFields = [
					'Order' => [
							'*',
							'_type' => 'LEFT',
							'_as' => "Orders"
					],
					'User' => [
							"username",
							"aliasname",
							'_type' => 'LEFT',
							'_on' => 'Orders.uid=User.uid'
				    ],
				    "Stores" => array (
							"name" => "stores_name",
							"_on" => "Stores.stores_id=Orders.stores_id",
							'_type' => 'LEFT'
					),
				    "order_send" => array (
							'send_sn', //发货编号
							'send_num',
				    		'_as' => 'orSe',
							"_on" => "orSe.order_id = Orders.order_id",
				    		'_type' => 'LEFT'
					),
				    "logistics_company" => array (
							'lc_name',
							'lc_code',
				    		'_as' => 'lc',
							"_on" => "lc.lc_id = orSe.logistics"
					)
			];
			$where = [];
			$where = ['order_id' => $order_id];
			$where['shipping_status'] = ['neq',0];
			$this->deliveryRecord = $this->order_model->dynamicView ( $viewFields )->where($where)->find();
			$where = [];
			//$where['ltr_mail_no'] = $this->deliveryRecord['send_num'];
 			$where['number'] = $this->deliveryRecord['send_num'];
			$logisticsFollowings = M('message_logistics')->where($where)->field('logistics_info')->order('logistics_info desc') ->find();
			$logisticsFollowing['main'] = unserialize($logisticsFollowings['logistics_info']);
			/* foreach ($logisticsFollowing['main'] as $key => &$v){
				$v['add_time_formate'] = date('Y-m-d',$v['ltr_accept_time']);
				$v['add_time_formate'] .= $this->Week[date('w',$v['ltr_accept_time'])];
				if($key != 0){
					$preArr = $logisticsFollowing['main'][$key - 1];
					$preTmp = date('Y-m-d',$preArr['ltr_accept_time']).$this->Week[date('w',$preArr['ltr_accept_time'])];
					if($v['add_time_formate'] == $preTmp){
						$v['add_time_formate'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				}
				$v['add_time_formate'] .= '&nbsp;&nbsp;'.date('H:i:s',$v['ltr_accept_time']);
			} */
			$this->logisticsFollowing = $logisticsFollowing;
		}
		$order_status = $this->order_model->order_status;
		//$order_status = $this->order_model->formatStatus ( $order_status );
		$this->assign ( $order_status );
		$this->display ();
	}
	/**
	 * 编辑订单 
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function edit() {
		$order_id = I ( 'request.order_id' );
		if ($order_id) {
			$this->_getInfo ( $order_id );
		}
		$this->assign ( 'stores', D ( 'Stores/Stores' )->getStores () );
		$this->display ();
	}
	
	/**
	 * 打印清单
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function printList() {
		$order_id = I ( 'request.order_id', '' );
		if ($order_id) {
			$this->_getInfo ( $order_id );
		}
		$this->display ( "printList" );
	}
	
	/**
	 * 通过id获取订单详情
	 *
	 * @param
	 *        	$order_id
	 */
	private function _getInfo($order_id) {
		$where = array (
				'order_id' => $order_id 
		);
		$info = $this->order_model->viewModel ()->where ( $where )->find ();
		$goods = $this->order_model->getGoodsById ( $where );
		$this->assign ( "info", $info );
		$this->assign ( "goods", $goods );
	}
	
	/**
	 * 删除订单
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function del() {
		$order_ids = I ( 'post.order_id', '' );
		if (! is_array ( $order_ids )) {
			$order_ids = array (
					$order_ids 
			);
		}
		if ($order_ids) {
			$data = array ();
			foreach ( $order_ids as $v ) {
				$data [$v] = NOW_TIME;
			}
			$sql = array2UpdateSql ( $data, C ( 'DB_PREFIX' ) . "order", 'delete_time', 'order_id' );
			if ($this->order_model->execute ( $sql )) {
				$message = "删除订单id:" . implode ( ",", $order_ids ) . "的订单";
				D ( 'AdminLog' )->addAdminLog ( $message, 3, "", $sql );
			}
			$this->ajaxReturn ( $this->result->success ()->toArray () );
		} else {
			$this->ajaxReturn ( $this->result->error ( "请选择要删除的值！" )->toArray () );
		}
	}
	
	/**
	 * 订单备注
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function orderMark() {
		if (IS_AJAX) {
			$order_id = I ( 'request.order_id', '' );
			if(empty($order_id)){
				$this->ajaxReturn($this->result->error()->toArray());
			}
			$where = array (
					'order_id' => $order_id 
			);
			$data = array (
					'seller_postscript' => I ( 'post.seller_postscript', '' ) 
			);
			$result = $this->order_model->setData ( $where, $data );
			if ($result->isSuccess ()) {
				$message = "修改了订单id：" . $order_id . "备注为：" . $data ['seller_postscript'];
				D ( 'AdminLog' )->addAdminLog ( $message, 2, $where );
			}
			$this->ajaxReturn ( $result->toArray () );
		}
	}
	
	/**
	 * 编辑订单
	 * <code>
	 * order_id 订单id
	 * stores_id 门店id
	 * need_invoice 是否需要发票
	 * invoice_id 发票id
	 * </code>
	 */
	public function update() {
		$order_id = I ( 'request.order_id','' );
		$stores_id = I ( 'request.stores_id', 0, 'intval' );
		$need_invoice = I ( 'post.need_invoice', 0, 'intval' );
		$invoice_id = I ( 'request.invoice_id', 0, 'intval' );
		if ($order_id)
			$message = "编辑订单id:" . $order_id;
		$i = 0;
		if ($need_invoice) {
			$where = array (
					'invoice_id' => $invoice_id 
			);
			$invoice_type = I ( 'post.invoice_type' );
			if (isset ( $_REQUEST ['invoice_type'] ) && $invoice_type == 0) { // 普通发票
			                                                                  // 发票信息
				$data = array (
						'invoice_payee' => I ( 'post.invoice_payee', '' ),
						'invoice_type' => I ( 'post.invoice_type', 0, 'intval' ),
						'invoice_content' => I ( 'post.invoice_content', 0, 'intval' ) 
				);
				if (empty ( $data ['invoice_payee'] )) {
					$this->ajaxReturn ( $this->result->error ( "请填写发票抬头！" )->toArray () );
				}
			}
			if (isset ( $_REQUEST ['invoice_type'] ) && $invoice_type == 1) {
			}
			if ($invoice_id) {
				$result = D ( 'Admin/Invoice' )->setData ( $where, $data );
				if ($result->isSuccess ()) {
					$message .= "发票信息";
					$i ++;
				}
			}
		}
		// 发票、提货门店
		if ($order_id) {
			$where = array (
					'order_id' => $order_id 
			);
			$order = array (
					"needs_invoice" => $need_invoice,
					'stores_id' => $stores_id 
			);
			if ($stores_id) {
				$result = $this->order_model->setData ( $where, $order );
				if ($result->isSuccess ()) {
					$message .= "&nbsp;门店";
					$i ++;
				}
			}
			// 收货人信息编辑
			if ($this->receipt ( $order_id )) {
				$message .= "&nbsp;收货人信息";
				$i ++;
			}
		}
		if (i > 0) {
			// 日志
			D ( 'AdminLog' )->addAdminLog ( $message, 2, "" );
		}
		$this->ajaxReturn ( $this->result->success ()->toArray () );
	}
	
	/**
	 * 编辑收货人信息
	 * <code>
	 * r_name 收货人姓名
	 * r_mobile 收货人手机
	 * </code>
	 */
	private function receipt($order_id) {
		$error = "";
		$receipt_name = I ( 'post.r_name', '' );
		$receipt_mobile = I ( 'post.r_mobile', '' );
		if (empty ( $receipt_name )) {
			$error = "收货人姓名不能为空！";
		}
		if (empty ( $receipt_mobile )) {
			$error = "收货人手机不能为空！";
		}
		if (! preg_match ( "/1[3458]{1}\d{9}$/", $receipt_mobile )) {
			$error = "请输入合法的手机号！";
		}
		if (! empty ( $error )) {
			$this->ajaxReturn ( $this->result->error ( $error )->toArray () );
		}
		$where = array (
				"order_id" => $order_id 
		);
		$data = array (
				"name" => $receipt_name,
				"mobile" => $receipt_mobile 
		);
		return M ( "OrderReceipt" )->where ( $where )->save ( $data );
	}
	
	/**
	 * 订单确认
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function order_confirm() {
		$order_id = I ( 'post.order_id', '' );
		if (! is_array ( $order_id )) {
			$order_id = Array (
					$order_id 
			);
		}
		$count = count ( $order_id );
		if ($order_id) {
			$order_id = $this->order_model->orderFilter ( $order_id );
		}
		$success_count = count ( $order_id );
		$fail_count = $count - $success_count;
		$data = array ();
		$arr = array ();
		if ($order_id) {
			$result = $this->order_model->orderConfirm($order_id,$this->user['uid']);
			if ($result->isSuccess ()) {
				// 操作日志
				$message = "确认了订单：" . implode ( ",", $order_id );
				D ( 'AdminLog' )->addAdminLog ( $message, 2 );
			}
		}
		$msg = $success_count . "条订单确认成功，" . $fail_count . "条订单确认失败!";
		$this->ajaxReturn ( $this->result->success ( $msg )->toArray () );
	}
	
	/**
	 * 收款管理
	 */
	public function collection() {
		$this->curent_menu = "order/collection";
		$where = $this->_dowhere ();
		$model = $this->order_model->viewModel ();
		$lists = $this->lists ( $model, $where, 'Orders.add_time desc' );
		$this->assign ( "lists", $lists );
		$this->display ();
	}
	
	/**
	 * 收款详情
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function collectionInfo() {
		$this->curent_menu = "order/collection";
		$order_id = I ( 'request.order_id' );
		if ($order_id) {
			$where = array (
					'order_id' => $order_id 
			);
			$info = $this->order_model->viewModel ()->where ( $where )->find ();
			$this->assign ( "info", $info );
		}
		$this->display ( "collectionInfo" );
	}
	
	/**
	 * 通过门店id获取门店成员
	 * <code>
	 * stores_id 门店id
	 * </code>
	 */
	public function getStoresUser() {
		$stores_id = I ( 'post.stores_id', 0, 'intval' );
		if ($stores_id) {
			$uid = D ( "Stores/StoresUser" )->getUserIds ( $stores_id );
		}
		$uid [] = $this->user ['uid'];
		$uid = array_unique ( $uid );
		$result = D ( 'Admin/Admin' )->getUserByIds ( $uid )->toArray ();
		foreach ( $result ['result'] as &$v ) {
			if ($v ['uid'] == $this->user ['uid']) {
				$v ['selected'] = "selected=selected";
			} else {
				$v ['selected'] = "";
			}
		}
		$this->ajaxReturn ( $result );
	}
	
	/**
	 * 发送提货码
	 */
	public function sendSort(){
		$order_id = I('post.order_id','');
		$result = $this->order_model->sendMessage(array($order_id));
		if($result){
			$this->ajaxReturn($this->result->success()->toArray());
		}else{
			$this->ajaxReturn($this->result->error()->toArray());
		}
	}
	
	/**
	 * 退款退貨
	 */
	public function refund(){
		$rec_id = I("request.rec_id",'');
		$data = $this->order_model->getRefundInfo($rec_id);
		$order = $this->order_model->getOrderInfo($data['order_id']);

		if($order['money_paid']>0){
			if($order['shipping_status']!=0){	//如果已发货，退款金额要扣除运费
				$discount = ($order['money_paid']-$order['shipment_price'])/$order['goods_amount'];
				$data['maxRefundMoney'] = round($data['goods_price']*$data['number']*$discount,2);
			}else{
				$discount = $order['money_paid']/$order['goods_amount'];
				$data['maxRefundMoney'] = round($data['goods_price']*$data['number']*$discount,2);
			}
		}else{
			$data['maxRefundMoney'] = 0;
		}
		
		/*dump($discount);
		dump($order);
		dump($data);die;*/

		$this->assign("data",$data);
		unset($data,$order,$discount);
		$this->assign("reasons",D("Admin/Refund")->refund_reasons);
		$this->display();
	}


	/**
	 * 整单退款/退货页面
	 * @param string $order_id 订单id
	 */
	public function refundShowAll(){
		$orderId = I('order_id');
		$orderInfo = M('Order')->where(['order_id'=>$orderId])->find();
		$recidList = M('OrderGoods')->where(['order_id' => $orderId])->getField('rec_id', true);

		$data = array();
		foreach($recidList as $key => $value){
			$data[$key] = D("Admin/Order")->getRefundInfo($value);
			$data[$key] = D("Api/Refund")->formatShow($data[$key]);
		}
		$maxRefundMoney = D("Api/Refund")->maxRefundMoney($orderId); //最大退款金额
		$maxRefundMoney = $maxRefundMoney < 0 ? number_format(0, 2) : $maxRefundMoney;

		$this->assign('maxRefundMoney',$maxRefundMoney);
		$this->assign('data',$data);
		$this->assign('orderInfo',$orderInfo);
		$this->assign('reason',$data[0]['reason']);
		unset($orderInfo,$recidList,$data);

		$this->display('refundAll');
	}

	/**
	 * 整单退款
	 * <code>
	 *    order_id 订单id
	 *    reasons  退款理由
	 *    mark 	   退款说明
	 *    uid 	   退款人id
	 * </code>
	 */
	public function doAllRefund(){
		$home_refund = D("Home/Refund");
		$orderId = I('post.order_id');

		$refundInfo = M('Refund')->where(['order_id' => $orderId])->select();
		if(!empty($refundInfo)){
			$is_all=0;
			foreach($refundInfo as $k=>$v){
				if($v['refund_status']==6 && (strlen($v['rec_id'])>33)){
					$refund_id = $v['refund_id'];
					$is_all=1;
				}
			}
			$dat = array();
			if($is_all){
				$dat['refund_status'] = 0;
				$dat['refund_reasons'] = I('post.reasons',0,'intval');
				$dat['description'] = I('post.mark','');

				$resSave = D("Home/Refund")->saveRefundAll($dat,$refund_id,$orderId);
				if($resSave){
					$this->ajaxReturn($this->result->success("操作成功")->toArray());
				}else{
					$this->ajaxReturn($this->result->error("操作失败")->toArray());
				}
			}else{
				$this->ajaxReturn($this->result->error("该订单已有商品申请退货,请不要重复提交！")->toArray());
			}
			exit;
		}

		$goods_ids = array();
		$rec_ids   = array();
		$goodsInfo = M('OrderGoods')->where(['order_id' => $orderId])->select();
		foreach($goodsInfo as $key => $value){
			$rec = $home_refund->getRefundInfo($value['rec_id']);
			$goods_ids[] = $rec['goods_id'];
			$rec_ids[]   = $rec['rec_id'];
			if($key==0){
				$post = array(
					"refund_id"=>md5(uniqid().rand_string(6,1)), 	// 退款单id
					"order_id" =>$rec['order_id'], 				 	// 订单号
					"refund_status"=>0, 						 	// 退款状态 0:未审核
					"refund_sn"=>$home_refund->refund_sn(), 	 	// 退款编号
					"refund_reasons"=>I('post.reasons',0,'intval'), // 退款理由
					"description" => I('post.mark',''), 		 	// 退款说明
					"voucher" => json_encode([""]), 			 	// 退款凭证
					"refund_uid" => I('post.uid',''), 			 	// 退款/退货人
					"refund_time"=>NOW_TIME, 					 	// 退款申请时间
				);
			}
		}
		$goods_ids = implode(",",$goods_ids);
		$goods_ids = rtrim($goods_ids, ",\n");
		$rec_ids   = implode(",",$rec_ids);
		$rec_ids   = rtrim($rec_ids, ",\n");
		$post['rec_id']   = $rec_ids;
		$post['goods_id'] = $goods_ids;

		//退款金额
		$orderMoney = M('Order')->field('money_paid, shipment_price,shipping_status')->find($orderId);
		if($orderMoney['shipping_status']==1){	//如果已发货，退款金额要扣除运费
			$post['refund_money'] = number_format($orderMoney['money_paid'] - $orderMoney['shipment_price'], 2);
		}else{
			$post['refund_money'] = number_format($orderMoney['money_paid'], 2);
		}
		$post['hope_refund_money'] = $post['refund_money'];
		$result = $home_refund->addRefund($post,false,1);
		unset($post,$goodsInfo,$refundInfo);

		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 门店自提列表
	 */
	public function since(){
		$this->curent_menu = "order/since";
		$where = "shipping_type=0 AND ";
		$where = $this->waitSend($where);
		$this->getList($where);
		$this->display();
	}
	
	/**
	 * 送货上门列表
	 */
	public function door(){
		$this->curent_menu = "order/door";
		$where = "shipping_type=2 AND ";
		$where = $this->waitSend($where);
		$this->getList($where);
		$this->display();
	}
	/**
	 * 普通物流列表列表
	 */
	public function logistics(){
		$this->curent_menu = "order/logistics";
		$where = "shipping_type=1 AND ";
		$where = $this->waitSend($where);
		$this->getList($where);
		$this->display();
	}
	/**
	 * 待发货/发货单
	 */
	private function waitSend($where=''){
		$delivery = I("request.wait_delivery",'0','intval');
		if($delivery){
			$where .= " Orders.status=1 AND pay_status=2 AND shipping_status=0 AND ";
		}
		$invoice = I("request.invoice",'0','intval');
		if($invoice){
			$where .= " Orders.shipping_status=1 OR Orders.shipping_status=2 AND ";
		}
		$where .= " yzid='' AND ";
		return $where;
	}
	/**
	 * 获取订单列表
	 */
	private function getList($where=""){
		$where .= $this->_dowhere ();
		$where = trim($where,"AND ");
		$model = $this->order_model->viewModel ();
		$lists = $this->lists ( $model, $where, 'Orders.add_time desc' );
		if ($lists) {
			$lists = $this->order_model->formatList ( $lists );
		}
		$this->assign ( "lists", $lists );
		$order_status = $this->order_model->order_status;
		$this->assign ( "sourceType", $this->order_model->order_status['source'] );
		$this->assign ( "shipType", $this->order_model->order_status['shipping_type'] );
		$order_status = $this->order_model->formatStatus ( $order_status );
		$this->assign ( "query_status", $this->order_model->order_status );
		$this->assign ( $order_status );
		$this->assign("pays",D("Admin/Payment")->getPays());//获取支付方式
	}
	
	
	/**
	 * 修改运单号
	*/
	public function up_number(){

		$send_num = I('post.send_num');
		$data['send_num'] =  I('post.number');
		if(!$send_num || !$data['send_num']){
			$this->ajaxReturn($this->result->error("运单号不能为空，或者运单号不存在")->toArray());
		}
		
		$logistics_state = M('message_logistics')->where(['number' => $send_num])->getField('logistics_state');
		if($logistics_state == 3){
			$this->ajaxReturn($this->result->error("该订单已经签收，不支持更改")->toArray());
		}
		
		
		
		$result = M('order_send') -> where(['order_id'=>I('post.order_id')])->save($data);
		if($result){
			$meg['number'] = $data['send_num'];
			$meg['poll'] = 0;
			M('message_logistics')->where(['number' => $send_num])-> save($meg);
			$this->ajaxReturn($this->result->success("更改成功")->toArray());
		}else{
			$this->ajaxReturn($this->result->error("更改失败")->toArray());
		}
		
	}
}