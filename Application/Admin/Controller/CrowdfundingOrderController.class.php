<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CrowdfundingOrderController extends AdminbaseController{
	protected $curent_menu = 'CrowdfundingOrder/index';
	private $kuidiObj;
 	public function _initialize() {
 		$this->model = D('CrowdfundingOrderMakefile');
 		$this->kuidiObj = new \Common\Org\Util\ExpressDelivery();
		parent::_initialize ();
	} 
	function index(){
		$this->title = '众筹订单';
		$this->crLists = M('Crowdfunding')->field('cr_id, cr_name')->where(['cr_delete_time' => 0])->select(); // 众筹列表
		$makeFileModel = D('CrowdfundingOrderMakefile');
		$model = $makeFileModel->viewModel();
		$cor_shipping_status = I('cor_shipping_status','','int');

		$where = [];
		$where['jt_com_delete_time'] = 0;

		// 众筹项目名
		// if($jt_com_crowdfunding_name = I('jt_com_crowdfunding_name','','trim')){
		// 	$where['jt_com_crowdfunding_name'] = ['like','%'.$jt_com_crowdfunding_name.'%'];
		// }

		if($userName = I('userName','','trim')){
			$where['username|aliasname'] = ['like','%'.$userName.'%'];
		}

		if($detail_order_sn = I('orderSn','','trim')){
			$where['cor_order_sn|jt_com_ordersn'] = $detail_order_sn;
		}
		$jt_com_type = 0;
		if($jt_com_type = I('jt_com_type',0,'int')){
			$where['jt_com_type'] = $jt_com_type;
		}
		if(($jt_offline_check = I('jt_offline_check','','trim')) && $jt_com_type == 2){
			$where['jt_offline_check'] = $jt_offline_check;
		}

		//支付状态
		$cor_pay_status = I('cor_pay_status','','intval');
		if($cor_pay_status!=0){
			$where['cor_pay_status'] = $cor_pay_status-1;
		}
		if($cor_store_id = I('cor_store_id',0,'int')){
			$where['cor_store_id'] = $cor_store_id;
		}
		if($uid = I('uid','','trim')){
			$where['cor_uid'] = $uid;
		}

		$orderModel = D('Admin/CrowdfundingOrder');
		// 查询商品发货状态
		$cor_shipping_status = I('cor_shipping_status');
		if( is_numeric($cor_shipping_status) ){
			$cor_order_id_arr = $orderModel->getOrderIdByShippingStatus($cor_shipping_status);
			if(is_array($cor_order_id_arr)){
				$where['cor_order_id'] = ['in',$cor_order_id_arr];
			}else{
				$where['cor_order_id'] = '';
			}
		}

		// 查询订单配送方式
		$cor_delivery_type = I('cor_delivery_type','','intval');
		if($cor_delivery_type!=0){
			$where['cor_delivery_type'] = $cor_delivery_type-1;
		}

		// 查询下单时间
		$startTime = I('start_time', '', ['urldecode', 'strtotime']); // 起始时间 
		$endTime = I('end_time', '', ['urldecode', 'strtotime']); // 结束时间
		if( ! empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = [['gt', $startTime], ['lt', $endTime]];
		}elseif( ! empty($startTime) && empty($endTime)){
			$where['jt_com_add_time'] = ['gt', $startTime];
		}elseif(empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = ['lt', $endTime];
		}

		// 查询众筹方案
		$cdId = I('jt_com_cd_id', '', 'intval');
		if($cdId){
			$where['jt_com_cd_id'] = $cdId;
		}

		// 查询是否内部员工
		$userType = I('is_inside_user', '');
		// dump($userType);die;
		if(is_numeric($userType)){
			$where['is_inside_user'] = intval($userType);
		}

		// 收货人
		$receiptName = I('receipt_name', '');
		if($receiptName){
			$where['receipt_name'] = ['like','%'.$receiptName.'%'];
		}

		// 收货电话
		$mobile = I('receipt_mobile', '');
		if($mobile){
			$where['receipt_mobile'] = ['like','%'.$mobile.'%'];
		}
		// dump($where);die;

		$total = count($makeFileModel->viewModel()->where($where)->select());
		$list =  $this->lists($model,$where,'jt_com_add_time desc',true,$total);
		$userModel = D('User/User');
		$this->list = $list;

		// 获取众筹方案列表
		$crId = I('fk_cr_id', '', 'intval');
		$this->cdLists = M('CrowdfundingDetail')->field('cd_id as id, cd_name as name')->where(['fk_cr_id' => $crId])->select(); // 众筹方案列表
		
		//获取门店名称
		$stores = M('stores') -> select();
		foreach($stores as $val){
			$stores_name[$val['stores_id']] = $val['name'];
		}

		foreach ($list as $k => &$v){
			$v['user_name'] = $userModel->showUserName($v['jt_com_uid']);
			$v['recommend_name'] =  $userModel->showUserName($v['jt_com_recommend_uid']);
			$v['orderDetail'] = $this->getOrderDetail($v['jt_com_ordersn']);
			$v['store']      = $v['cor_store_id']>0?$stores_name[$v['cor_store_id']]:"";

			//支付状态
			foreach ($v['orderDetail'] as $value) {
				$v['number'] = count($value['goodsList']);
				if(2 == $value['cor_pay_status']){
					$v['pay_status'] = 2;
					break;
				}
			}
		}
		$this->list = $list;
		$this->assign("stores_name",$stores_name);
		$this->assign("companys",M('logistics_company')->select());
		$this->assign("list",$list);

		$m = D('CrowdfundingOrder');
		// $this->shippingStatusName = $m->shippingStatusName; //原发货状态
		$shippingStatusName = $m->shippingStatusName; //发货状态
		unset($shippingStatusName[1]);
		$this->shippingStatusName = $shippingStatusName;

		// $this->deliveryName = $m->deliveryName; // 原配送方式
		// $this->payStatusName = $m->payStatusName;//原支付状态

		$deliveryName = [	// 配送方式
			'1' => '门店自提',
			'2' => '普通快递',
			'3' => '快兔配送'
		];
		$payStatusName = [ //支付状态
			'1' => '待支付',
			// '2' => '支付中',
			'3' => '已支付',
			'4' => '申请退款',
			'5' => '已退款'
		];
		$this->assign("deliveryName",$deliveryName);
		$this->assign("payStatusName",$payStatusName);

		$this->assign ( 'stores', D ( 'Stores/Stores' )->getStores () );
		$this->logisticsCompany = M('express_template')->select();
		$this->display();
	}
	//获取子订单详情
	protected function getOrderDetail($fk_com_ordersn){
		static $tmpModel = null;
		if(!$tmpModel){
			$tmpModel = D('CrowdfundingOrder');
		}
		$list =  $tmpModel->where(['fk_com_ordersn' => $fk_com_ordersn])->order('cor_term_index')->select();
		$orderModel = D("Admin/Order");
		$payStatusName = $tmpModel->formatStatus ( $tmpModel->payStatusName );
		foreach ($list as &$v){
			// $v['payStatusName'] = getStatus($v['cor_pay_status'],$payStatusName);
			$v['payStatusName'] = getSpanStatus($v['cor_pay_status'],$payStatusName);

			$v['goodsList'] = $tmpModel->orderGoodList($v['cor_order_id']);
		}
		return $list;
	}
	function orderdetail(){
		//$this->getExpressBill(889,'b0fc429a52983d5ae1b4f22740ae7e29');
		$id = I('id','','trim');
		$m = D('CrowdfundingOrder');
		$info = $m->getOrderWithGoods([ 'cor_order_id' => $id]);
		$info = $info[0];
		$info['deliveryName'] = $m->deliveryName[$info['cor_delivery_type']];
		$this->extendsInfo($info);
		$this->info = $info;
		$this->cog_shipping_status = $m->shippingStatusName;
		$orderInfo = M("CrowdfundingOrderMakefile")->where(['jt_com_ordersn'=>$info['fk_com_ordersn']])->find();
		$this->orderInfo = $orderInfo;
		$receipt = M("OrderReceipt")->where(['order_id'=>$orderInfo['jt_com_id']])->find();
		$this->assign("receipt",$receipt);
		$this->assign ( 'order_record', D( 'Admin/Order' )->order_record ( $id ) );
		$this->display();
	}
	//增加物流跟踪信息
	private function extendsInfo(&$info){
		$kuidiObj = $this->kuidiObj; 
		$fields = [
				'ltr_mail_no_status' => 'status',
				'ltr_accept_time' => 'accept_time',
				'ltr_remark' => 'content',
		];
		foreach ($info['goodsList'] as &$v) {
			$v['logisticsRecord'] = $kuidiObj->getRecord($v['send_num'],$fields);
		}
	}
	function edit(){
		$id = I('id');
		$m = D('Admin/CrowdfundingOrder');
		$info = $m->viewModel()->where(['cor_order_id' => $id])->find();
		$count = M('crowdfunding_order_goods')->where(['fk_cor_order_id' => $info['cor_order_id'],'cog_shipping_status' => ['in',[2,3]]])->count();
		$info['isSend'] = $count ? 1 : 0;
		$info['goodsList'] = $m->orderGoodList($id);
		$tmpModel = D('CrowdfundingOrder');
		// $info['payStatusName'] = getStatus($info['cor_pay_status'],$tmpModel->formatStatus ());
		$info['payStatusName'] = getSpanStatus($info['cor_pay_status'],$tmpModel->formatStatus ()); // 修改支付状态为文字样式

		$info['deliveryName'] = $m->deliveryName[$info['cor_delivery_type']];
		$this->info = $info;
		$this->cog_shipping_status = $m->shippingStatusName;
		$orderInfo = M("CrowdfundingOrderMakefile")->where(['jt_com_ordersn'=>$info['fk_com_ordersn']])->find();
		$receipt = M("OrderReceipt")->where(['order_id'=>$orderInfo['jt_com_id']])->find();
		$this->orderInfo = $orderInfo;
		$this->logistics_company = M('logistics_company')->select(); 
		$this->assign("receipt",$receipt);
		$this->assign ( 'stores', D ( 'Stores/Stores' )->getStores () );
		$this->display('edit_add');
	}
	function update(){
		$cor_delivery_type = I('cor_delivery_type');
		if($cor_delivery_type==0){
			$receipt_name = I('receipt_names','','trim');
			$receipt_mobile = I('receipt_mobiles','','trim');
		}else{
			$receipt_name = I('receipt_name','','trim');
			$receipt_mobile = I('receipt_mobile','','trim');
		}
		
		$order_id = I('order_id','','trim');
		$data['mobile'] = $receipt_mobile;
		$data['name'] = $receipt_name;
		$jt_com_ordersn = M('crowdfunding_order')->where(['cor_order_id' => $order_id])->getField('fk_com_ordersn');
		if(M('order_receipt')->where(['order_id' => $order_id])->find()){
			M('order_receipt')->where(['order_id' => $order_id])->save($data);
		}else{
			$data['order_id'] = $order_id;
			M('order_receipt')->add($data);
		}
		M('crowdfunding_order_makefile')->where(['jt_com_ordersn' => $jt_com_ordersn])->save(['jt_com_receipt_name' => $receipt_name]);
		if($cor_store_id = I('cor_store_id',0,'int')){ //如果有门店信息
			M('crowdfunding_order')->save(['cor_store_id' => $cor_store_id,'cor_order_id' => $order_id]);
		}
		return $this->ajaxReturn($this->result->success()->toArray());
	}
	function changeStauts(){
		$id = I('id');	

		// $comOrderSn = I("comOrderSn"); //总括订单号，用于设置订单提醒

		$dataStr = I('dataStr');		
		parse_str($dataStr,$data);
		if($data['amp;mod'] == 2){
			unset($data['mod']);
			$data['cog_id'] = $id;
			$res = M('crowdfunding_order_goods')->save($data);
		}else{
			$data['cor_order_id'] = $id;
			$res = D('Admin/CrowdfundingOrder')->save($data);

			//改变订单提醒中的状态
			// if($res){
			// 	D('Admin/OrderMessage')->where(['order_sn' => $comOrderSn])->setField(['message_add_time' => time(), 'message_status' => 0, 'order_status' => 1]);
			// }
		}
		return $this->ajaxReturn($this->result->success()->toArray());
	}
	
	/**
	 * 后台下单
	 * @author xiongzw
	 */
	public function addOrder(){
		$step = I("request.step",1,'intval');
		$validate = I("request.validate",0,'intval');
		if(IS_POST){
			switch($step){
				case 1:
					 $username = I("post.username","");
					 $result = D("User/User")->getUserByName($username);
					 if($result->isSuccess()){
					 	$return = $result->toArray(); 
					 	$uid = $return['result']['uid'];
					 	$this->assign('uid',$uid);
					 	$step  = 2;
					 	if(IS_AJAX){
		                 $this->ajaxReturn($this->result->success()->toArray());
					 	}
					 }else{
					 	$this->ajaxReturn($this->result->error("用户不存在！")->toArray());
					 }
					 break;
				case 2:
					 $uid = I('post.uid','');
					 $address_id = I('post.addressId',0,'intval');
					 $this->assign('uid',$uid);
					 $this->assign("address_id",$address_id);
					 $step=3;
					 break;
				default:
					   //新增订单
					  $data = array(
					      'cdId'=>I("post.scheme",''),
					      'cgIds'=>I("post.cgIds",""),

					      //增加备选商品
					      'bgIds'=>I("post.bgIds",""),

					      'uid'=>I("post.uid",''),
					      'recommendUid'=>'',
					      'mark'=>'',
					      'store_id'=>I("post.shipping_type")==0?I('post.stores_id'):0,
					      'addressId'=>I('post.addressId',''),
					      'shipping_type'=>I("post.shipping_type",''),
					      'staff_uid'=>I("post.staff",""),
					      'jt_com_type'=>2,
					      'pay_type'=>I('post.pay_type',''),
					      'cor_sign_time' => I("post.cor_sign_time"), //合同签订时间
					      'cor_remark' => I("post.cor_remark")		//备注
					  );
					  $refereeMobile = I("post.refereeMobile",'');
					  $cgIds = array();
					  foreach($data['cgIds'] as $key=>$v){
					  	 if($key && $v){
					  	 	$cgIds[]=array(
					  	 			'cgId'=>$key,
					  	 			'num'=>$v
					  	 	);
					  	 }
					  }

					  //添加备选商品
					  foreach($data['bgIds'] as $k => $v){
					  	$bg[] = explode("_", $k);
					  }
					  for($i = 0; $i < count($cgIds) ; $i ++){
					  	$cid = $cgIds[$i]['cgId'];
					  	$bid = $bg[$i][1];
					  	if(null != $bid && $cid == $bid){
					  		$cgIds[$i]['back_up_goods'] = $bg[$i][0];
					  	}
					  }

					  if(empty($data['uid'])){
					  	$this->ajaxReturn($this->result->error("请选择用户！")->toArray());
					  }
					  if(empty($data['addressId'])){
					  	$this->ajaxReturn($this->result->error("请选择收货地址！")->toArray());
					  }
					  if(empty($data['cdId'])){
					  	$this->ajaxReturn($this->result->error("请选择方案！")->toArray());
					  }
					  if($refereeMobile){
					  	$where = array(
					  			'username|mobile'=>$refereeMobile
					  	);
					  	$recommendUid =D('User/User')->scope('normal,default')->where($where)->getField("uid");
					  	if(empty($recommendUid)){
					  		$this->ajaxReturn($this->result->error("推荐人不存在！")->toArray());
					  	}
					  	if($recommendUid==$data['uid']){
					  		$this->ajaxReturn($this->result->error("推荐人不能是本人！")->toArray());
					  	}
					  	$data['recommendUid'] = $recommendUid;
					  }
					  if(empty($cgIds)){
					  	$this->ajaxReturn($this->result->error("请选择商品！")->toArray());
					  }
					  if($data['shipping_type']==''){
					  	$this->ajaxReturn($this->result->error("请选择配送方式")->toArray());
					  }
					  if($data['shipping_type'] == 0  && empty($data['store_id'])){
					  	$this->ajaxReturn($this->result->error("请选择自提门店")->toArray());
					  }
					  $result = D("Chips/Order")->creatOrder($data['cdId'],$cgIds,$data,true);
					  $this->ajaxReturn($result->toArray());
			}
		}
		if($step==2){
			if(empty($uid)){
				$this->redirect(U("addOrder",['step'=>1],''));
			}
			//获取用户收货地址
			$recaddress_lists = $this->getAddress($uid);
			$this->assign("recaddress",$recaddress_lists);
		}
		if($step==3){
			//增加确认收货地址
			$recaddress_lists = $this->getAddress($uid, $address_id);
			$this->assign("recaddress",$recaddress_lists);

			$this->assign('chips',D("Admin/Crowdfunding")->scope("using")->select());
			$where= ['delete_time'=>0,'status'=>1];
			$where['role_id'] = M('admin_role')->where(['type' => 2,'code' => 'dtry'])->getField('role_id');
			$this->assign('staff',D('Admin/Admin')->where($where)->select());
			$this->assign('stores',$this->getStores());
		}
		$this->assign('step',$step);
		$this->display("addOrder");
	}

	/**
	* 编辑审核不通过的线下下单
	* @author qrong
	*/
	public function editOrders(){
		$step = I("request.step",2,'intval');
		$id = I('id');
		$validate = I("request.validate",0,'intval');

		$orderInfo = M('CrowdfundingOrderMakefile')->join('LEFT JOIN __CROWDFUNDING_ORDER__ ON __CROWDFUNDING_ORDER_MAKEFILE__.jt_com_ordersn=__CROWDFUNDING_ORDER__.fk_com_ordersn')->where(['jt_com_id'=>$id])->select();

		if($step==2){
			//获取用户收货地址
			$recaddress_lists = $this->getAddress($orderInfo[0]['jt_com_uid']);
			$address_id = I('post.addressId',0,'intval');
			$this->assign('address_id',$address_id);
			$this->assign("recaddress",$recaddress_lists);
		}
		if($step==3){
			//增加确认收货地址
			$uid = I('uid');
			$address_id = I('address_id');
			$recaddress_lists = $this->getAddress($uid,$address_id);
			$this->assign("recaddress",$recaddress_lists);

			$this->assign('chips',D("Admin/Crowdfunding")->scope("using")->select());
			$where= ['delete_time'=>0,'status'=>1];
			$where['role_id'] = M('admin_role')->where(['type' => 2,'code' => 'dtry'])->getField('role_id');
			$this->assign('staff',D('Admin/Admin')->where($where)->select());
			$this->assign('stores',$this->getStores());
			$this->assign('orderInfo',$orderInfo[0]);
		}
		$this->assign('step',$step);
		$this->assign("uid",$orderInfo[0]['jt_com_uid']);
		$this->assign('id',$id);

		$this->display('edit_order');
	}

	public function doEdits(){
		$id = I('id');
		$orderList = M('CrowdfundingOrderMakefile')->join('LEFT JOIN __CROWDFUNDING_ORDER__ ON __CROWDFUNDING_ORDER_MAKEFILE__.jt_com_ordersn=__CROWDFUNDING_ORDER__.fk_com_ordersn')->where(['jt_com_id'=>$id])->select();
		if(IS_POST){
			//新增订单
			$data = array(
				'cdId'=>I("post.scheme",''),
				'cgIds'=>I("post.cgIds",""),

				//增加备选商品
				'bgIds'=>I("post.bgIds",""),

				'uid'=>I("post.uid",''),
				'recommendUid'=>'',
				'mark'=>'',
				'store_id'=>I("post.shipping_type")==0?I('post.stores_id'):0,
				'addressId'=>I('post.addressId',''),
				'shipping_type'=>I("post.shipping_type",''),
				'staff_uid'=>I("post.staff",""),
				'jt_com_type'=>2,
				'pay_type'=>I('post.pay_type',''),
				'cor_sign_time' => I("post.cor_sign_time"), //合同签订时间
				'cor_remark' => I("post.cor_remark")		//备注
			);
			$refereeMobile = I("post.refereeMobile",'');
			$cgIds = array();
			foreach($data['cgIds'] as $key=>$v){
				if($key && $v){
					$cgIds[]=array(
						'cgId'=>$key,
						'num'=>$v
					);
				}
			}

			//添加备选商品
			foreach($data['bgIds'] as $k => $v){
				$bg[] = explode("_", $k);
			}
			for($i = 0; $i < count($cgIds) ; $i ++){
				$cid = $cgIds[$i]['cgId'];
				$bid = $bg[$i][1];
				if(null != $bid && $cid == $bid){
					$cgIds[$i]['back_up_goods'] = $bg[$i][0];
				}
			}

			if(empty($data['uid'])){
				$this->ajaxReturn($this->result->error("请选择用户！")->toArray());
			}
			if(empty($data['addressId'])){
				$this->ajaxReturn($this->result->error("请选择收货地址！")->toArray());
			}
			if(empty($data['cdId'])){
				$this->ajaxReturn($this->result->error("请选择方案！")->toArray());
			}
			if($refereeMobile){
				$where = array(
						'username|mobile'=>$refereeMobile
				);
				$recommendUid =D('User/User')->scope('normal,default')->where($where)->getField("uid");
				if(empty($recommendUid)){
					$this->ajaxReturn($this->result->error("推荐人不存在！")->toArray());
				}
				if($recommendUid==$data['uid']){
					$this->ajaxReturn($this->result->error("推荐人不能是本人！")->toArray());
				}
				$data['recommendUid'] = $recommendUid;
			}
			if(empty($cgIds)){
				$this->ajaxReturn($this->result->error("请选择商品！")->toArray());
			}
			if($data['shipping_type']==''){
				$this->ajaxReturn($this->result->error("请选择配送方式")->toArray());
			}
			if($data['shipping_type'] == 0  && empty($data['store_id'])){
				$this->ajaxReturn($this->result->error("请选择自提门店")->toArray());
			}
			$result = D("Chips/Order")->saveOrders($orderList,$data['cdId'],$cgIds,$data,true);
			$this->ajaxReturn($result->toArray());
		}
	}

	/**
	 * 获取门店
	 * @return multitype:unknown
	 */
	public function getStores(){
		$stores_model = D('Stores/Stores');
		//查询深圳地区的门店
		$city_id = '440300000000';
		$stores_lists = $stores_model->scope()->where(['city'=>$city_id])->field('stores_id,name,county,localtion,address,phone')->select();
		$county_ids = array_column($stores_lists,'county');
		$stores_lists = array_map(function($info){
			return [
			'storesId'=>$info['stores_id'],
			'name'=>$info['name'],
			'countyId'=>$info['county'],
			'localtion'=>$info['localtion'],
			'address'=>$info['address'],
			'phone'=>$info['phone'],
			];
		},$stores_lists);
		/* $countys = M('PositionCounty')->where([
				'city_id'=>$city_id,
				'county_id'=>['in',$county_ids]
				])->field([
						'county_id',
						'county_name'
						])->select();
		$countys = array_map(function($info){
			return [
			'countyId'=>$info['county_id'],
			'countyName'=>$info['county_name']
			];
		},$countys); */
		return $stores_lists;
	}
	
	/**
	 * 获取收货地址列表
	 * @param  $uid
	 */
	private function getAddress($uid,$address_id=0){
		$order['is_default'] = 'desc';
		$order['add_time'] = 'desc';
		$recaddress_lists = D('Home/ShippingAddress')->where(['uid'=>$uid])->order($order)->select();
		$recaddress_lists = array_map(function($info){
			return D('Api/Affiliated')->formatRecaddressLists($info);
		},$recaddress_lists);
		if($address_id){
			foreach ($recaddress_lists as &$v){
				if($address_id == $v['id']){
					$v['active'] =1;
				}else{
					$v['active'] = 0;
				}
			}
		}
		return $recaddress_lists;
	}
	
	/**
	 * ajax根据项目获取方案
	 */
	public function getScheme(){
		$cr_id = I("request.crId",0,'intval');
		$scheme = D("Chips/Chips")->getScheme($cr_id);
		$this->ajaxReturn($this->result->content($scheme)->success()->toArray());
	}
	/**
	 * ajax获取方案商品
	 */
	public function chipsGoods(){
		$id = I('request.cdId',0,'intval');
		$goods =D("Chips/Chips")->getSchemeGoods($id);

		//获取方案和备选方案
		$alternativegoods = D("Chips/Chips")->getAlternativeGoods($goods, $id);

		D("Home/List")->getThumb($alternativegoods,false,'cg_att_id');
		$return_data = array();
		foreach ($alternativegoods as $key=>$v){
			$return_data[$key] = array(
					'cgId'=>$v['cg_id'],
					'name'=>$v['cg_goods_name'],
					'price'=>$v['cg_market_price'],
					'photo' =>fullPath($v['thumb']),
					'subhead' =>$v['cg_goods_subhead'], //商品子标题

					//增加备选方案
					'alternativegoods' => $v['alternativegoods'] // 备选方案

			);
		}
		$this->ajaxReturn($this->result->content(['goods'=>$return_data])->success()->toArray());
	}
	
	/**
	 * ajax请求
	 * 更新用户收货地址
	 */
	public function updateAddress(){
		$address_id = I("post.address_id",0,'intval');
		$recaddress_model = D('Home/ShippingAddress');
		$provice_id = I('request.provice_id');
		$city_id = I('request.city_id');
		$county_id = I('request.county_id');
		$localtion = D('Api/Affiliated')->getLocaltion($provice_id,$city_id,$county_id);
		$LatLon = $recaddress_model->getLatLonByAddress($localtion.I('request.user_detail_address'));
		$data = [
            'uid' => I("request.user_id",''),
            'name' => I('request.name'),
            'mobile' => I('request.mobile'),
            'user_provice' => $provice_id,
            'user_city' => $city_id,
            'user_county' => $county_id,
            'user_localtion' => $localtion,
            'user_detail_address' => I('request.user_detail_address'),
        	'user_lat_lon' => $LatLon
        ];
		if($address_id){
			$result = $recaddress_model->setData(['address_id'=>$address_id],$data);
		}else{
			$result = $recaddress_model->addData($data);
			if($result->isSuccess()){
				$return  = $result->toArray();
				$address_id = $return['result'];
			}
		}
		$recaddress_lists = $this->getAddress($data['uid'],$address_id);
		$this->ajaxReturn($result->content($recaddress_lists)->toArray());
	}
	
	/**
	 * 删除收货地址
	 */
	public function delrecaddress(){
		$id = I('request.address_id');
		$recaddress_model = D('Home/ShippingAddress');
		$where = [
		'address_id' => $id
		];
		$result = $recaddress_model->delData($where);
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 配送单
	 */
	public function picking(){
		$order_id = I("request.id","");
		$goodId = I("request.goodId",0,'int');
		$order_model = D('Admin/CrowdfundingOrder');
		$data['order'] = $order_model->where(['cor_order_id' => $order_id])->find();
		$data['user'] = M("User")->where(['uid'=>$data['order']['cor_uid']])->find();
		$data['goods'] = $order_model->orderGoodList($order_id,['cog_id' => $goodId]);
		$order_count_id = M("CrowdfundingOrderMakefile")->where(['jt_com_ordersn'=>$data['order']['fk_com_ordersn']])->getField("jt_com_id");
		$data['receipt'] = M("OrderReceipt")->where(['order_id'=>$order_count_id])->find();
		$this->assign($data);
		$this->display();
	}
	//审核订单
	function checkOrder(){
		$id = I('id');
		$jt_offline_check = I('jt_offline_check');
		$res = D('Admin/CrowdfundingOrderMakefile')->setData(['jt_com_id' => $id],['jt_offline_check' => $jt_offline_check]);
		$this->ajaxReturn($res->toArray());
	}
	/**
	 * 后台现金支付
	 * @author wxb
	 * @date 2015/1/5
	 */
	function adminPay(){
		$order_id = I('order_id','','trim');
		if(!$order_id){
			$this->ajaxReturn($this->result->setCode('DATA_ERROR')->toArray());
		}
		$pay_type = 'CASH';
		$res = D('Chips/Pay')->paying($order_id,$pay_type);
		$this->ajaxReturn($res->toArray());
	}
	//发货
	function deliveryGood(){
		$cor_delivery_type = I('cor_delivery_type',0,'int');
		$logistics = I('logistics',0,'int');
		$send_num = I('send_num','','trim');
		$nickname = I('nickname','','trim');//发件人姓名
		$goodId = I('goodId',0,'int');
		$order_id = I('order_id','','trim');
		$orderGoodId = I('orderGoodId','','trim');//订单商品id
		$data = [
				"rec_ids"=>json_encode([$orderGoodId]),
				"send_time"=>NOW_TIME,
				"order_id" => $order_id,
				"send_type" => $cor_delivery_type,
				"send_remark" => '蜂蜜众筹发货',
				"send_num" => $send_num,
				"logistics"=>$logistics,
				"handle" => $this->user['uid'],
				"send_status"=>2,
				"ware_name"=>$nickname
		];		
		$result = D('Admin/SendGoods')->sendGoods($data,$cor_delivery_type,$orderGoodId);
		$this->ajaxReturn($result->toArray());
	}

	/**
	 * ajax 获取地推人员
	 */
	public function getStaff(){
		$where= ['delete_time'=>0,'status'=>1];
		$where['role_id'] = M('admin_role')->where(['type' => 2,'code' => 'dtry'])->getField('role_id');
		$this->ajaxReturn($this->result->content(['staff'=> D('Admin/Admin')->where($where)->select()])->success()->toArray());
	}

	//查询地推人员
	public function searchStaff(){
		$data = array();
		$where= ['delete_time'=>0,'status'=>1];
		$where['role_id'] = M('admin_role')->where(['type' => 2,'code' => 'dtry'])->getField('role_id');
		if($mix_keywords = I('mix_keywords','','trim')){
			$this->mix_keywords = $mix_keywords;
			$where['username|nickname'] = ['like','%'.$mix_keywords.'%'];
		}
		$data['staff'] = D('Admin/Admin')->where($where)->select();
		if(empty($data['staff'])){
			$data['msg'] = 0;
		}else{
			$data['msg'] = 1;
		}
		echo json_encode($data);
		exit;
	}
	function getExpressBill($goodId,$order_id){
		//889 	b0fc429a52983d5ae1b4f22740ae7e29
		$data = D('Admin/CrowdfundingOrder')->getExpressBillData($goodId,$order_id);
		$res = D('Admin/ExpressTemplate')->getDyTemplate('',[$data]);
		return $res[0];
	}

	//验证推荐人
	public function checkRefereeMobile(){
		$rMobile = I('request.refereeMobile');
		if(empty($rMobile)){
			$this->ajaxReturn(array('code' => 0, 'msg' => '请输入推荐人手机号码！'));
		}
		$where['mobile'] = $rMobile;
		$where['status'] = 1;
		$where['delete_time'] = 0;
		$user = M('User')->where($where)->find();
		if($user){
			$this->ajaxReturn(array('code' => 1, 'msg' => '推荐人已存在！'));
		}else{
			$this->ajaxReturn(array('code' => 2, 'msg' => '推荐人不存在，请先添加推荐人！'));
		}
	}

	//验证用户是否存在
	public function checkUser(){
		$username = I('request.username');
		if(empty($username)){
			$this->ajaxReturn(array('code' => 0, 'msg' => '请输入用户名！'));
		}
		$where['username'] = $username;
		$where['status'] = 1;
		$where['delete_time'] = 0;
		$user = M('User')->where($where)->find();
		if($user){
			$this->ajaxReturn(array('code' => 1, 'msg' => '用户已存在！'));
		}else{
			$this->ajaxReturn(array('code' => 2, 'msg' => '用户不存在，请先添加用户！'));
		}
	}

	//批量生成快递单打印内容
	function express_print(){
		$express_print_type = I('express_print_type',0,'int');
		$logisticsCompanyId = I('logisticsCompanyId',0,'int');
		//获取模板数据
		$data = [];
		$crowdfundingOrder =  D('Admin/CrowdfundingOrder');
		$sql = "SELECT DISTINCT cog.cog_id,co.cor_order_id FROM jt_crowdfunding_order_goods cog 
LEFT JOIN
`jt_crowdfunding_order` co on cog.fk_cor_order_id = co.cor_order_id 
LEFT JOIN
 jt_crowdfunding_order_makefile com on com.jt_com_ordersn = co.fk_com_ordersn  
";
		if($express_print_type == 1){//未发货订单
			$where = 'where jt_com_delete_time != 0 and cor_shipping_status = 0';			
			$sql .= $where;
		}else{ //按时间过滤
			$express_print_start =  I('express_print_start','','trim');
			$express_print_end =  I('express_print_end','','trim');
			$express_print_start = strtotime($express_print_start);
			$express_print_end = strtotime($express_print_end);
			$where = "where jt_com_delete_time != 0 and ((cor_sign_time != 0 and cor_sign_time between {$express_print_start} and {$express_print_end} ) or (cor_sign_time = 0 and cor_add_time  between {$express_print_start} and {$express_print_end} ))";
			$sql .= $where;
		}
		$res = M()->query($sql);
		if(!$res){
			$this->ajaxReturn($this->result->error('该时间段无订单')->toArray());
		}
		foreach ($res as $k=>$v){
			$data[] = $crowdfundingOrder->getExpressBillData($v['cog_id'],$v['cor_order_id']);
			if($k == 3 && APP_DEBUG){//test
				break;
			}
		}
		//获取模板
		$res = D('Admin/ExpressTemplate')->getDyTemplate($logisticsCompanyId,$data,2);
		$this->ajaxReturn($this->result->success()->content(['print_content' => $res])->toArray());
	}


	/**
	 * 根据众筹id获取众筹方案
	 * @param int parentId 父栏目id
	 */
    public function getPlanByCrowdId(){
        $cid = I("parentId", "", "intval"); // 众筹id
        if(! $cid){
            $this->ajaxReturn(['code' => 0, 'msg' => '众筹id不能为空！']);
        }
        $plans = M("CrowdfundingDetail")->field('cd_id as id, cd_name as name')->where(['fk_cr_id' => $cid])->select();
        if($plans){
            $this->ajaxReturn(['code' => 1, 'data' => $plans]);
        }else{
            $this->ajaxReturn(['code' => 2, 'msg' => '众筹方案不存在！']);
        }
    }

    /**
	 * 获取自提门店
	 * @param int parentId 父栏目id
	 */
    public function getStore(){
    	$sType = I("parentId", "", "intval"); // 配送方式
        if(1 == $sType){
        	$stores = M('Stores')->field('stores_id as id, name')->select();
       		$this->ajaxReturn(['code' => 1, 'data' => $stores]);
        }else{
        	$this->ajaxReturn(['code' => 0, 'msg' => '不是自提方式！']);
        }
    }

    /**
	 * 获取导出订单数据条数
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
    public function getExportNum(){
    	$model = D('Admin/CrowdfundingOrderMakefile'); // 总括订单
		$orderSn = rtrim(I("request.ordersn",''),",");
		$orderModel = D('Admin/CrowdfundingOrder');
		
		/*if (empty ( $order_sn )) {
			$this->error ( "请选择要导出的订单" );
		}*/
		$where = array();
		$where['jt_com_delete_time'] = 0;

		$startTime = I('startTime', '', ['urldecode', 'strtotime']); // 起始时间 
		$endTime = I('endTime', '', ['urldecode', 'strtotime']); // 结束时间
		if( ! empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = [['gt', $startTime], ['lt', $endTime]];
		}elseif( ! empty($startTime) && empty($endTime)){
			$where['jt_com_add_time'] = ['gt', $startTime];
		}elseif(empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = ['lt', $endTime];
		}

		$userType = I('insideUser', '', 'intval');
		// echo $userType;
		if($userType){
			$where['is_inside_user'] = $userType;
		}

		if($userName = I('uName','','trim')){
			$where['username|aliasname'] = ['like','%'.$userName.'%'];
		}
		//收货人
		$receiptName = I('reName', '');
		if($receiptName){
			$where['receipt_name'] = ['like','%'.$receiptName.'%'];
		}
		// 收货电话
		$mobile = I('reMobile', '');
		if($mobile){
			$where['receipt_mobile'] = ['like','%'.$mobile.'%'];
		}
		if($detail_order_sn = I('order_sn','','trim')){
			$where['cor_order_sn|jt_com_ordersn'] = $detail_order_sn;
		}

		if(!empty(I('payStatus'))){
			$where['cor_pay_status'] = I('payStatus')-1;
		}
		$shipping_status = I('shippingStatus','','intval');
		if($shipping_status){
			$cor_order_id_arr = $orderModel->getOrderIdByShippingStatus($shipping_status);
			if(is_array($cor_order_id_arr)){
				$where['cor_order_id'] = ['in',$cor_order_id_arr];
			}
		}

		if(!empty(I('crId'))){
			$where['com.fk_cr_id'] = I('crId');
		}
		if(!empty(I('cdId','','intval'))){
			$where['jt_com_cd_id'] = I('cdId','','intval');
		}
		$comType=I('comType','','intval');
		if(!empty($comType)){
			$where['jt_com_type'] = $comType;
		}
		//审核状态
		if(($jt_offline_check = I('offlineCheck','','trim')) && $comType == 2){
			$where['jt_offline_check'] = $jt_offline_check;
		}
		if(!empty(I('deliveryType','','intval'))){
			$where['cor_delivery_type'] = I('deliveryType')-1;
		}
		if(!empty(I('storeId','','intval'))){
			$where['cor_store_id'] = I('storeId','','intval');
		}

		$modelView = $model->viewModel(); //总括订单视图
		$coModel = D('Admin/CrowdfundingOrder'); //众筹子订单
		if(!empty($orderSn)){
			$order_sn = explode( ",",$orderSn);		//获取总订单号
			$where["jt_com_ordersn"] = array('in',$order_sn);
		}

		$num = $modelView->where($where)->order('jt_com_add_time desc')->limit(1005)->select();
		$this->ajaxReturn(count($num));
    }


    /**
	 * 导出订单excel
	 * <code>
	 * order_id 订单id
	 * </code>
	 */
	public function export() {
		$model = D('Admin/CrowdfundingOrderMakefile'); // 总括订单
		$orderSn = rtrim(I("request.ordersn",''),",");
		$orderModel = D('Admin/CrowdfundingOrder');
		
		/*if (empty ( $order_sn )) {
			$this->error ( "请选择要导出的订单" );
		}*/
		$where = array();
		$where['jt_com_delete_time'] = 0;

		$startTime = I('startTime', '', ['urldecode', 'strtotime']); // 起始时间 
		$endTime = I('endTime', '', ['urldecode', 'strtotime']); // 结束时间
		if( ! empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = [['gt', $startTime], ['lt', $endTime]];
		}elseif( ! empty($startTime) && empty($endTime)){
			$where['jt_com_add_time'] = ['gt', $startTime];
		}elseif(empty($startTime) && ! empty($endTime)){
			$where['jt_com_add_time'] = ['lt', $endTime];
		}

		$userType = I('insideUser', '', 'intval');
		// echo $userType;
		if($userType){
			$where['is_inside_user'] = $userType;
		}

		if($userName = I('uName','','trim')){
			$where['username|aliasname'] = ['like','%'.$userName.'%'];
		}
		//收货人
		$receiptName = I('reName', '');
		if($receiptName){
			$where['receipt_name'] = ['like','%'.$receiptName.'%'];
		}
		// 收货电话
		$mobile = I('reMobile', '');
		if($mobile){
			$where['receipt_mobile'] = ['like','%'.$mobile.'%'];
		}

		if(!empty(I('payStatus'))){
			$where['cor_pay_status'] = I('payStatus')-1;
		}
		$shipping_status = I('shippingStatus','','intval');
		if($shipping_status){
			$cor_order_id_arr = $orderModel->getOrderIdByShippingStatus($shipping_status);
			if(is_array($cor_order_id_arr)){
				$where['cor_order_id'] = ['in',$cor_order_id_arr];
			}
		}

		if(!empty(I('crId'))){
			$where['com.fk_cr_id'] = I('crId');
		}
		if(!empty(I('cdId','','intval'))){
			$where['jt_com_cd_id'] = I('cdId','','intval');
		}
		$comType=I('comType','','intval');
		if(!empty($comType)){
			$where['jt_com_type'] = $comType;
		}
		if($detail_order_sn = I('order_sn','','trim')){
			$where['cor_order_sn|jt_com_ordersn'] = $detail_order_sn;
		}
		//审核状态
		if(($jt_offline_check = I('offlineCheck','','trim')) && $comType == 2){
			$where['jt_offline_check'] = $jt_offline_check;
		}
		if(!empty(I('deliveryType','','intval'))){
			$where['cor_delivery_type'] = I('deliveryType')-1;
		}
		if(!empty(I('storeId','','intval'))){
			$where['cor_store_id'] = I('storeId','','intval');
		}

		$modelView = $model->viewModel(); //总括订单视图
		$coModel = D('Admin/CrowdfundingOrder'); //众筹子订单
		if(!empty($orderSn)){
			$order_sn = explode( ",",$orderSn);		//获取总订单号
			$where["jt_com_ordersn"] = array('in',$order_sn);
		}

		$lists = $modelView->where($where)->order('jt_com_add_time desc')->limit(1000)->select();
		/*echo $modelView->getLastSql();
		dump($_GET);
		dump($where);
		dump($lists);die;*/
		foreach ($lists as $k => &$v){
			$v['subOrder_sn'] = $coModel->getSubOrderSn($v['jt_com_ordersn']);
			$v['goods_total'] = $this->countGoods($v['jt_com_ordersn']);
		}
		
		$model->exportExcel($lists);
	}

	/**
	 * 统计总订单的商品总数
	 * @param string $orderSn 总订单号
	 * @return int $total 订单所有商品总数
	 */
	public function countGoods($orderSn){
		$orderIdList = M('CrowdfundingOrder')->where(['fk_com_ordersn'  => $orderSn])->getField('cor_order_id', true); // 获取订单对应的子订单id
		$total = 0; // 商品总数
		foreach($orderIdList as $key => $val){
			$goodsCount = M('CrowdfundingOrderGoods')->where(['fk_cor_order_id' => $val])->sum('cog_count');
			$total += $goodsCount;
		}
		return $total;
	}


}