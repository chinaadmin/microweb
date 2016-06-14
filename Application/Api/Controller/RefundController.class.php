<?php
/**
 * 退款退货接口
 * @author xiongzw
 * @date 2015-07-02
 */
namespace Api\Controller;
class RefundController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
	}
	/**
	 * 退款退货列表
	 */
	public function refundList(){
		$where = array(
				'Refund.delete_time'=>0,
				'Refund.refund_uid'=>$this->user_id
		);
		$model = D('Home/Refund')->viewModel();
		$lists = $this->_lists($model,$where,"Refund.refund_time desc");
		D("Home/List")->getThumb($lists['data'],0);
// 		print_r($lists);
		$lists['data'] = D('Api/Refund')->formatRefund($lists['data']);
// 		print_r($lists);exit;
		$this->ajaxReturn($this->result->success()->content($lists));
	}
	/**
	 * 退款退货详情
	 *       <code>
	 *       refundId 退款id
	 *       </code>
	 */
	public function refundInfo(){
		$refund_id = I('post.refundId','');
		if($refund_id){
			$info = D("Home/Refund")->getInfo($refund_id);
			// $action = D("Admin/Order")->getOrderAction ( $info ['order_id'],1,true,['extend' => $refund_id] ); // 原退货记录

			switch ($info['refund_status']) {
				case 0 : 
					$action[0]['front_remark'] = '您已经提交了退货/退款申请，请等候审核！';
					$action[0]['add_time'] = $info['refund_time'];
					break;
				case -1 :
					$action[0]['front_remark'] = '抱歉，您的退货/退款申请没有通过审核，如有问题，请联系客服！';
					$action[0]['add_time'] = $info['examine_time'];
					$action[1]['front_remark'] = '您已经提交了退货/退款申请，请等候审核！';
					$action[1]['add_time'] = $info['refund_time'];
					break;
				case 3 :
					$action[0]['front_remark'] = '您的订单已退款成功，请注意查收！';
					$action[0]['add_time'] = $info['completion_time'];
					$action[1]['front_remark'] = '您的退货/退款申请已通过审核！';
					$action[1]['add_time'] = $info['examine_time'];
					$action[2]['front_remark'] = '您已经提交了退货/退款申请，请等候审核！';
					$action[2]['add_time'] = $info['refund_time'];
					break;
				case 6 :
					$action[0]['front_remark'] = '您已经取消退款！';
					$action[0]['add_time'] = $info['completion_time'];
					$action[1]['front_remark'] = '您已经提交了退货/退款申请，请等候审核！';
					$action[1]['add_time'] = $info['refund_time'];
					break;
				default:
					$action[0]['front_remark'] = '您的退货/退款申请已通过审核！';
					$action[0]['add_time'] = $info['examine_time'];
					$action[1]['front_remark'] = '您已经提交了退货/退款申请，请等候审核！';
					$action[1]['add_time'] = $info['refund_time'];
			}

			$info['action'] = $action;
			$info = D("Api/Refund")->formatInfo($info);
			$this->ajaxReturn($this->result->success()->content(['info'=>$info]));
		}else{
			$this->ajaxReturn($this->result->error("退款id不能为空",'REFUNDID_REQUIRE'));
		}
		
	}
	/**
	 * 退款退货页面
	 *        <code>
	 *        rec_id 退款商品id
	 *        </code>
	 */
	public function refundShow(){
		$rec_id = I('post.recId','');
		//$result = D("Api/Refund")->recDate($rec_id,"rec_id,number,goods_price");
		$data = D("Admin/Order")->getRefundInfo($rec_id);
		// $data['goods_price'] = number_format($data['goods_price']*($data['money_paid']/$data['goods_amount']),2); // 原最多退款金额

		// 最多退款金额 = 退货商品总价 - 退货商品总价 / 订单总价 * 优惠券金额
		$data['goods_price'] = number_format(($data['goods_price'] - $data['goods_price'] / $data['goods_amount'] * $data['coupon_price']), 2);
		$data['goods_price'] = $data['goods_price'] < 0 ? number_format(0, 2) : $data['goods_price'];
		$data = D("Api/Refund")->formatShow($data);
		
		$data['maxRefundMoney'] = number_format($data['price'] * $data['maxNum'], 2); // 最多退款金额

		$this->ajaxReturn($this->result->content(['data'=>$data]));
	}

	/**
	 * 整单退款/退货页面
	 * @param string $orderId 订单id
	 */
	public function refundShowAll(){
		$orderId = I('post.orderId', '');
		$recidList = M('OrderGoods')->where(['order_id' => $orderId])->getField('rec_id', true);

		$data = array();
		foreach($recidList as $key => $value){
			$data[$key] = D("Admin/Order")->getRefundInfo($value);
			// 单个商品最多退款金额 = 退货商品总价 - 退货商品总价 / 订单总价 * 优惠券金额
			// $data[$key]['goods_price'] = number_format(($data[$key]['goods_price'] - $data[$key]['goods_price'] / $data[$key]['goods_amount'] * $data[$key]['coupon_price']), 2);
			$data[$key] = D("Api/Refund")->formatShow($data[$key]);
		}
		$maxRefundMoney = D("Api/Refund")->maxRefundMoney($orderId); // 最多退款金额
		$maxRefundMoney = $maxRefundMoney < 0 ? number_format(0, 2) : $maxRefundMoney;
		$this->ajaxReturn($this->result->content(['maxRefundMoney' => $maxRefundMoney, 'data' => $data]));
	}

	/**
	 * 提交退款退货操作
	 *          <code>
	 *           recId 退款商品id
	 *           number  退款数量
	 *           reason  退款理由
	 *           explain 退款说明
	 *           attrId 退款凭证id
	 *          </code>
	 */
	public function refundAction(){
		$refund_model = D("Api/Refund");
		$home_refund = D("Home/Refund");
		// $home_base = D("Common/Homebase");
		$rec_id = I('post.recId','');
		// $num = I('post.number',0,'intval'); // 默认退全部，去掉退货数量

		$hopeMoney = I('post.hopeRefundMoney', ''); // 期待退款金额

		//先查询该商品是否已申请退款退货
		$refundInfo = M('Refund')->where(['rec_id'=>$rec_id])->find();
		if(!empty($refundInfo)){
			$this->ajaxReturn($home_refund->result()->error("该商品已申请退货,请不要重复提交！","REFUND_ALREADY"));
			exit;
		}
		unset($refundInfo);

		//$rec = $home_refund->recInfo($rec_id);
		$rec = $home_refund->getRefundInfo($rec_id);
		$post = array(
				"refund_id"=>md5(uniqid().rand_string(6,1)),
				"rec_id" =>$rec_id,
				"order_id" =>$rec['order_id'],
				"goods_id" => $rec['goods_id'],
				// "refund_money" => $rec['goods_price']*$num+$home_refund->getRefundMoney($rec['order_id'],$rec_id,$num),
				"refund_money" => $rec['goods_price'] * $rec['number'] + $home_refund->getRefundMoney($rec['order_id'], $rec_id, $rec['number']),

				"refund_status"=>0,
				"refund_sn"=>$home_refund->refund_sn(),
				// "refund_num" => $num, // 默认退全部，去掉退货数量
				"refund_reasons" =>I('post.reason',0,'intval'),
				"description" => I('post.explain',''),
				"voucher" => json_encode(explode(",", I('post.attrId',''))),
				"refund_uid" => $this->user_id,
				"refund_time"=>NOW_TIME,

				'hope_refund_money' => $hopeMoney // 期待退款金额
		);
		$result = $home_refund->addRefund($post,false,0,false);

		//用户申请退款/退货时后台弹出消息提醒
		if($result){
			D('Admin/OrderMessage')->where(['order_id' => $rec['order_id']])->setField(['message_add_time' => time(), 'message_status' => 0, 'order_status' => 1]);
		}
		$this->ajaxReturn($result);
	}

	/**
	 * 整单退
	 * <code>
	 *    orderId 订单id
	 *    reason  退款理由
	 *    explain 退款说明
	 *    attrId  退款凭证id
	 * </code>
	 */
	public function allRefundAction(){
		$home_refund = D("Home/Refund");
		$orderId = I('post.orderId');
		$hopeMoney = I('post.hopeRefundMoney', ''); // 期待退款金额

		//先查询该订单是否已申请退款退货
		$refundInfo = M('Refund')->where(['order_id'=> $orderId])->find();
		if( ! empty($refundInfo) ){
			$this->ajaxReturn($home_refund->result()->error("该订单已申请退货,请不要重复提交！","ORDER_REFUND_ALREADY"));
			exit;
		}
		unset($refundInfo);

		$goodsInfo = M('OrderGoods')->where(['order_id' => $orderId])->select();
// 		print_r($goodsInfo);
		foreach($goodsInfo as $key => $value){
			$rec = $home_refund->getRefundInfo($value['rec_id']);
			$goods_ids[]=$rec['goods_id'];
			$rec_ids[]=$rec['rec_id'];
			if($key==0){
				$post = array(
						"refund_id"=>md5(uniqid().rand_string(6,1)), // 退款单 id
// 						"rec_id" =>$rec['rec_id'], // 订单商品 id
						"order_id" =>$rec['order_id'], // 订单号
// 						"goods_id" => "111", // 商品id
						// "refund_money" => $rec['goods_price'] * $num + $home_refund->getRefundMoney($rec['order_id'],$rec['rec_id'],$num), // 退款金额
						// "refund_money" => $rec['goods_price'] * $rec['number'] + $home_refund->getRefundMoney($rec['order_id'], $rec['rec_id'], $rec['number']), // 退款金额
						// 'number' =>  $rec['number'],
						"refund_status"=>0, // 退款状态 0:未审核
						"refund_sn"=>$home_refund->refund_sn(), // 退款编号
						"refund_reasons" =>I('post.reason',0,'intval'), // 退款理由
						"description" => I('post.explain',''), // 退款说明
						"voucher" => json_encode(explode(",", I('post.attrId',''))), // 退款凭证
						"refund_uid" => $this->user_id, //  退款/退货人
						"refund_time"=>NOW_TIME, // 退款申请时间
				
						'hope_refund_money' => $hopeMoney // 期待退款金额
				
				);
			}
			
		}
		$goods_ids= implode(",",$goods_ids);
		$goods_ids = rtrim($goods_ids, ",\n");
		$rec_ids= implode(",",$rec_ids);
		$rec_ids = rtrim($rec_ids, ",\n");
// 		print_r($rec_ids);
// 		print_r($goods_ids);exit;
		$post['rec_id'] = $rec_ids;
		$post['goods_id'] = $goods_ids;

		//退款金额
		$orderMoney = M('Order')->field('money_paid, shipment_price')->find($orderId);
		$post['refund_money'] = number_format($orderMoney['money_paid'] - $orderMoney['shipment_price'], 2);

		// print_r($post);exit;
		$result = $home_refund->addRefund($post);

		$this->ajaxReturn($result);
	}
	
	/**
	 * 上传凭证
	 * key file
	 *   code 
	 *   type int 1:表单上传  2：base64上传
	 */
	public function uploadVoucher(){
		$type = I('post.type',1,'intval');
		$config = array(
				'thumb'=>false
		);
		if($type==2){
			$baseImg = I('post.baseData','');
			if(!$baseImg){ 
				$this->ajaxReturn($this->result->set("PHOTO_REQUIRE"));
			}
			$ext = I ( "post.ext", '', 'trim' );
			if(!$ext){ 
				$this->ajaxReturn($this->result->set("PHOTO_EXT_REQUIRE"));
			}
			$data = $this->_base64Upload($baseImg,$ext);
			
			$data = array(
					"attId"=>$data['attId'],
					"photo"=>fullPath($data['path'])
			);
// 			print_r($data);exit;
			$this->ajaxReturn($this->result->success()->content(['result'=>$data]));exit;
			
		}
		if($type==1){
			$result = $this->uploadPic($config);
		}
		if($result['success']){
			$data = array(
					"attId"=>$result['id'],
					"photo"=>fullPath($result['path'])
			);
			$this->ajaxReturn($this->result->success()->content(['result'=>$data]));
		}else{
			$this->ajaxReturn($this->result->error($result['error'],"UPLOAD_ERROR"));
		}
	}
	
	/**
	 * 取消退款
	 */
	public function cancelRefund(){
		$refund_id  = I('post.refundId','','trim');
		if(!$refund_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
		}
		$refund_model = D ( 'Home/Refund' );
		$res = $refund_model->cancelRefund($refund_id);
		$this->ajaxReturn($res);
	}


	/**
	 * base64上传
	 */
	private function _base64Upload($baseImg,$ext) {
		
		$baseImg = preg_replace("/^data:image\/\w+;base64,/","",$baseImg);
		if(empty($baseImg)){
			$this->ajaxReturn($this->result->set("UPLOAD_ERROR"));
		}
		$baseImg = base64_decode ( $baseImg );
		$config = array(
				'thumb'=>false
		);
		$info = array(
				'is_admin' => 0,
				'uid' => $this->user_id,
				'model' => 'image'
		);
		$result = D ( "Upload/UploadImage" )->base64Upload ( $baseImg, $ext, $config, $info );
		if ($result) {
			$result['attId'] = $result ['att_id'];
			$result['path'] = fullPath ( $result ['path'] );
			return $result;
		} else {
			$this->ajaxReturn ( $this->result->error () );
		}
	}
	
}