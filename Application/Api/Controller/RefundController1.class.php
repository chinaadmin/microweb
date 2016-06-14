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
		$lists['data'] = D('Api/Refund')->formatRefund($lists['data']);
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
			$action = D("Admin/Order")->getOrderAction ( $info ['order_id'],1,true,['extend' => $refund_id] );
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
		$data['goods_price'] = number_format($data['goods_price']*($data['money_paid']/$data['goods_amount']),2);
		$data = D("Api/Refund")->formatShow($data);
		$this->ajaxReturn($this->result->content(['data'=>$data]));
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
		$rec_id = I('post.recId',''); // 订单商品id
		$num = I('post.number',0,'intval'); // 退货数量
		$rec = $home_refund->getRefundInfo($rec_id); // 获取退款详情
		$post = array(
				"refund_id"=>md5(uniqid().rand_string(6,1)), // 退款单 id
				"rec_id" =>$rec_id, // 订单商品 id
				"order_id" =>$rec['order_id'], // 订单号
				"goods_id" => $rec['goods_id'], // 商品id
				"refund_money" => $rec['goods_price']*$num+$home_refund->getRefundMoney($rec['order_id'],$rec_id,$num), // 退款金额
				"refund_status"=>0, // 退款状态 0:未审核
				"refund_sn"=>$home_refund->refund_sn(), // 退款编号
				"refund_num" => $num, // 退货数量
				"refund_reasons" =>I('post.reason',0,'intval'), // 退款理由
				"description" => I('post.explain',''), // 问题描述
				"voucher" => json_encode(explode(",", I('post.attrId',''))), // 退款凭证
				"refund_uid" => $this->user_id, //  退款/退货人
				"refund_time"=>NOW_TIME // 退款申请时间
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
		$goodsInfo = M('OrderGoods')->where(['order_id' => $orderId])->select();
		foreach($goodsInfo as $key => $value){
			$rec = $home_refund->getRefundInfo($value['rec_id']);
			$post = array(
				"refund_id"=>md5(uniqid().rand_string(6,1)), // 退款单 id
				"rec_id" =>$rec['rec_id'], // 订单商品 id
				"order_id" =>$rec['order_id'], // 订单号
				"goods_id" => $rec['goods_id'], // 商品id
				// "refund_money" => $rec['goods_price']*$num+$home_refund->getRefundMoney($rec['order_id'],$rec['rec_id'],$num), // 退款金额
				"refund_status"=>0, // 退款状态 0:未审核
				"refund_sn"=>$home_refund->refund_sn(), // 退款编号
				"refund_reasons" =>I('post.reason',0,'intval'), // 退款理由
				"description" => I('post.explain',''), // 退款说明
				"voucher" => json_encode(explode(",", I('post.attrId',''))), // 退款凭证
				"refund_uid" => $this->user_id, //  退款/退货人
				"refund_time"=>NOW_TIME // 退款申请时间
			);
			$result = $home_refund->addRefund($post);
		}

		if($result){
			D('Admin/OrderMessage')->where(['order_id' => $orderId])->setField(['message_add_time' => time(), 'message_status' => 0, 'order_status' => 1]);
		}

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
			$baseData = base64_decode(I('post.baseData'));
			$ext = I('post.ext','png');
			$this->base64Upload($baseData,$ext,$config);
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
}