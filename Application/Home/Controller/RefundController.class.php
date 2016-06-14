<?php
/**
 * 退款退货
 * @author xiongzw
 * @date 2015-06-01
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class RefundController extends HomeBaseController{
	private  $error;
    public function _initialize(){
        parent::_initialize();
        if(!$this->uid){
            redirect(U('Passport/login'));
        }
    }

    /**
     * 用户选中样式
     */
    private function userSelCurrent($select = 'refund'){
        $this->assign('userSelCurrent',$select);
    }

	/**
	 * 退款列表
	 */
	public function index(){
        $this->userSelCurrent();
	   $model = D('Home/Refund')->viewModel();
	   $where = $this->_where();
	   $lists = $this->lists($model,$where,"Refund.refund_time DESC");
	   $this->assign("lists",$lists);
	   $this->assign("refund_status",D("Admin/Refund")->formatStatus());
	   $this->display();	
	}
	/**
	 * 查询条件
	 * @return multitype:number NULL
	 */
	private function _where(){
		$where = array(
				"Refund.delete_time"=>0,
				"Refund.refund_uid"=>$this->user['uid']
		);
		$order_sn = I("request.sn",'');
		if($order_sn){
			$where['order_sn'] = $order_sn;
		}
		$status = I('request.status','0','intval');
		$keyword = I('request.keyword','');
		if($status>-2 && isset($_REQUEST['status'])){
			if($status == -1){
				$status = ['in',[-1,6]];
			}
			$where['refund_status'] = $status;
			$this->assign('status',$status);
		}
		if($keyword){
			$where["Orders.order_sn|Refund.refund_sn|Goods.name"] = array("like","%{$keyword}%");
			$this->assign('keyword',$keyword);
		}
		return $where;
	}
	
	/**
	 * 退款/退货
	 */
	public function refund(){
		$refund_model = D('Home/Refund');
		$rec_id = I('request.recId','');
		$refund_id = I('refundId','','trim');
		if($rec_id){
			//$rec = $refund_model->recInfo($rec_id);
			$rec = $refund_model->getRefundInfo($rec_id);
			$num = I('post.number',0,'intval');
			if(IS_POST){
				$update = false;
				$data = array(
						"refund_id"=>md5(uniqid().rand_string(6,1)),
						"rec_id" =>$rec_id,
						"order_id" =>$rec['order_id'],
						"goods_id" => $rec['goods_id'],
						"refund_money" => $rec['goods_price']*$num+$refund_model->getRefundMoney($rec['order_id'],$rec_id,$num),
						"refund_status"=>0,
						"refund_sn"=>$refund_model->refund_sn(),
						"refund_num" => $num,
						"refund_reasons" =>I('post.refund_rea',0,'intval'),
						"description" => I('post.mark',''),
						"voucher" => json_encode(I('post.attachId','')),
						"refund_uid" => $this->user['uid'],
						"refund_time"=>NOW_TIME
				);
				if($refund_id){
					$data['refund_id'] = $refund_id;
					$update = true;
				}
				$result = $refund_model->addRefund($data,$update,0,false);
				if($result->isSuccess()){
					$result = $result->success(U("success",array('refund_id'=>$data['refund_id'])));
				}
				$this->ajaxReturn($result->toArray());
			}else{
				if($refund_id){//重新返回修改申请内容
					$refundInfo = D('Home/Refund')->where(['refund_id' => $refund_id])->find();
					if($refundInfo['voucher']){
						$refundInfo['voucher'] = json_decode($refundInfo['voucher'],true);
						$refundInfo['voucher'] = D('Upload/AttachMent')->getAttach($refundInfo['voucher']);
					}
					$this->refundInfo = $refundInfo;
				}
				$this->assign($refund_model->assign($rec_id));
				$this->display();
			}
		}else{
			$this->_404();
		}
	}
	
	/**
	 * 退款详情
	 */
	public function info() {
		$refund_id = I ( 'request.refund_id', '' );
		if ($refund_id) {
			$refund_model = D ( 'Home/Refund' );
			$info = $refund_model->getInfo($refund_id);
			$this->assign ( 'info', $info );
			$this->assign ( 'orderAction', D("Admin/Order")->getOrderAction ( $info ['order_id'],1,true,['extend' => $refund_id] ) );
			$this->display ();
		} else {
			$this->_404 ();
		}
	}
	
	public function success(){
		$this->display();
	}
	//取消退款/退货
	function cancelRefund(){
		$refund_id  = I('request.ref_id','','trim');
		if(!$refund_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
		}
		$refund_model = D ( 'Home/Refund' );
		$res = $refund_model->cancelRefund($refund_id);
		$this->ajaxReturn($res->toArray());
	}
}