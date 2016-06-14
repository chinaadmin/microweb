<?php
/**
 * 退款模型
 * @author xiongzw
 * @date 2015-05-27
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class RefundModel extends AdminbaseModel{
	public $refund_reasons = array(
			"1"=>"商品有问题 ",
			"2"=>"商品实物与网站描述不符",
			"3"=>"错发",
			"4"=>"未收到商品",
			"5"=>"7天无理由退货"
	);
	/**
	 * 退款状态
	 * @var array
	 */
	public $refund_status = array(
			"-1"=>'未通过',
			"0" => '待审核',
			"1" =>"待退款",
			"2"=>'退款中',
			"3"=>"已退款",
			"4"=>"退款失败",
			"5"=>"待退货",
			"6"=>"取消退款"
	);
	/**
	 * 格式化退款状态
	 */
	public function formatStatus(){
		return D("Admin/Order")->formatStatus($this->refund_status);
	}
	/**
	 * 退款退货列表模型
	 * @return Ambigous <\Common\Model\mixed, \Think\Model\ViewModel, \Think\Model\RelationModel>
	 */
	public function viewModel(){
		$viewFields = array(
				"Refund"=>array(
					"refund_id",
					"refund_sn",
					"refund_num",
					"refund_money",
					"refund_status",
					"goods_id",
					"refund_time",
					"voucher",
					"description",
					"refund_reasons",
					"order_id",
					"rec_id",
					"refund_mark",
					"is_receive",
                    'examine', // 审核人
                    'hope_refund_money', // 用户期望退款金额
                    'examine_time', // 审核时间
                    'completion_time', // 订单完成时间
					"_type"=>"LEFT",
		        ),
				 "Order" =>array( 
					"order_sn",
					"pay_type",
					// "order_sn",
					"pay_time",
				 	"shipping_status",

                    'money_paid', // 订单实际付款金额
                    'coupon_price', // 优惠券金额
                    'shipping_type', // 配送方式
                    'shipment_price', // 运费
                    'order_id',

					"_as"=>"Orders",
					"_on"=>"Refund.order_id=Orders.order_id",
				    "_type"=>"LEFT"
				), 
				"Goods"=>array(
					"name",
					"_on"=>"Refund.goods_id=Goods.goods_id",
					"_type"=>"LEFT"
		        ),
				"OrderGoods"=>array(
					"number",
					"norms_value",
					"_on"=>"Refund.rec_id=OrderGoods.rec_id",
					"_type"=>"LEFT"	
		         ),
				"AdminUser"=>array(
				    "username",
					"_on"=>"Refund.examine=AdminUser.uid",		
		            "_type"=>"LEFT"
				),
				'User'=>array(
			        "username"=>"user",

                    'mobile', // 手机号码

				    "_on"=>"Refund.refund_uid=User.uid"
	         	)
		);
		return $this->dynamicView($viewFields);
	}
	/**
	 * 获取退货凭证
	 * @param $att_id
	 */
	public function getAttr(Array $att_id){
		$attrs = array();
		foreach($att_id as $v){
			if(is_string($v)){
				$v= json_decode($v,true);
			}
			if(is_array($v)){
			 $attrs = array_merge($attrs,$v);
			}else{
				$attrs[] = $v;
			}
		}
		$data = D('Upload/AttachMent')->getAttach($attrs);
		return $data;
	}
	/**
	 * 查找没处理的id
	 */
	public function getNoVerifyId($ids,$field=true){
		$where = array(
				'refund_id'=>array('in',$ids),
				"refund_status"=>0
		);
		$data = $this->field($field)->where($where)->select();
		return $data;
	}

    /**
     * 批量审核日志
     * @param array $refund_id 退款id
     * @param string $uid 处理人id
     * @param int $refund_status 审核状态
     * @param int $is_seller 是否卖家
     * @return string
     */
	public function record($refund_id,$uid,$refund_status,$message="",$is_seller=1){
		$viewFields = array(
				"Refund"=>array(
					"refund_id",
				    "order_id",
					"_type"=>"LEFT"		
		        ),
				"Goods"=>array(
						"name",
						"_on"=>"Refund.goods_id=Goods.goods_id"
		        ),
				"Order"=>array(
					   "shipping_status",
					   "stores_id",
				       "_as" =>"Orders",
						"_on" => "Refund.order_id=Orders.order_id",
						"_type"=>"LEFT"		
		        ),
				"Stores"=>array(
				     "name"=>"stores_name",
					 "_on" => "Orders.stores_id=Stores.stores_id"		
		        )
		);
		$where = array(
				"refund_id"=>array('in',$refund_id)
		);
		$data = $this->dynamicView($viewFields)->where($where)->select();
		foreach($data as $key=>&$v){
			$v['action'] = json_encode(array('refund_status'=>$refund_status));
			$v['is_seller']=$is_seller;
			$v['handle']=$uid;
			$v['add_time'] = NOW_TIME;
			$v['type'] = 1;
			$v['extend'] = $v['refund_id'];
			//$mark = "卖家审核商品".$v['name'];
			$mark = "商家审核";
			$front_mark = "";
			switch($refund_status){
				case -1:
					$mark.="不通过";
					$front_mark ="您的退款退货申请被驳回，原因是：".$message;
					break;
				case 1:
					if($v['shipping_status']){
						$mark.="已收货";
						$front_mark ="您的商品已退货，请等待退款";
					}else{
						$mark.="审核通过";
						$front_mark ="您的商品已审核通过，请等待退款";
					}
					break;
				case 2://退款中
					$mark="商家已付款";
					$front_mark = "";
					break;
				case 3:
					$mark="商家确认退款成功";
					$front_mark ="您的订单已退款成功，请注意查收！";
					break;
                case 4:
                    $mark="退款失败";
                    break;
                case 5:
                     $mark="审核通过，待退货";
                     $front_mark = "您的退款退货申请已审核通过，由".$v['stores_name']."为您提供售后服务，请带着退货商品前往";
                    break;
			}
			$v['remark'] = $mark;
			$v['front_remark'] = $front_mark; 
			unset($data[$key]['name']);
		}
		return M("OrderAction")->addAll($data);
	}

    /**
     * 退款
     * @param $data
     * @return \Common\Org\Util\Results
     */
    public function refund($data){
        $this->startTrans();
        foreach($data as $v){
            $result = $this->refundOnce($v['refund_id'],$v['status'],false);
            if(!$result->isSuccess()){
                $this->rollback();
                return $result;
            }
        }
        $this->commit();
        return $result;
    }

    /**
     * 单条退款信息
     * @param string $refund_id 退款id
     * @param bool $status 状态 true为成功，false为失败
     * @param bool $is_trans 是否开启事务
     * @return \Common\Org\Util\Results
     */
    public function refundOnce($refund_id,$status,$is_trans = true){
        if($is_trans) {
            $this->startTrans();
        }
        if($status) {
            $status_code = 3;
        }else{
            $status_code = 4;
        }
        $refund_info = $this->where(['refund_id' => $refund_id])->field(true)->find();
        $uid = $refund_info['refund_uid'];
        $result = $this->where(['refund_id' => $refund_id])->data(['refund_status'=>$status_code])->save();
        if($result === false){
            if($is_trans) {
                $this->rollback();
            }
            return $this->result()->error('退款失败');
        }

        if($status){
            //设置订单退款状态
            $result = $this->setOrderRefundStatus($refund_info['order_id']);
            if($result === false){
                if($is_trans) {
                    $this->rollback();
                }
                return $this->result()->error('退款失败');
            }

            //退款记录
            $order_info = D('Home/order')->where(['order_id'=>$refund_info['order_id']])->find();
            $credits_model = D('User/Credits');
            $credits_model->setOperateType(5,$order_info['pay_type']);
            $credits_result = $credits_model->setCredits($uid,$refund_info['refund_money'],'订单'.$order_info['order_sn'].'退款',0,0);
            if(!$credits_result->isSuccess()){
                if($is_trans) {
                    $this->rollback();
                }
                return $credits_result;
            }

            //减少商品销售统计
            M("GoodsStatistics")->where(['goods_id' => $refund_info['goods_id']])->setDec('sales', $refund_info['refund_num']);

            //用户完成订单总数
            $user_order_count = M('Order')->where(['uid'=>$uid])->count('order_id');

            //用户数据统计
            $analysis_data = [
                'refund_money'=>['exp','refund_money + '.$refund_info['refund_money']]
            ];
            if($user_order_count <= 0){
                $analysis_data['first_single'] = 0;
            }
            //$uid = M('Order')->where(['order_id'=>$refund_info['order_id']])->getField('uid');
            if(M('UserAnalysis')->where([
                    'uid'=>$uid
                ])->data($analysis_data)->save() === false){
                $this->rollback();
                return $result->error('退款失败');
            }
        }

        //记录日志
        $result = $this->record($refund_id,'',$status_code,2);
        if($result === false){
            if($is_trans) {
                $this->rollback();
            }
            return $this->result()->error('退款失败');
        }

        if($is_trans) {
            $this->commit();
        }
        return $this->result()->success('退款成功');
    }

    /**
     * 通过id获取退款信息
     * @param $refund_id
     * @param bool|string $field
     * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
     */
    public function getRefundById($refund_id,$field=true){
    	$where = array(
    			"refund_id" => $refund_id
    	);
    	return $this->field($field)->where($where)->find();
    }
    /**
     * 退款退货信息
     * @param  $order_id 订单id
     * @param string $field
     * @return Ambigous <\Think\mixed, boolean, mixed, multitype:, unknown, object>
     */
    public function getRefundByOrder($order_id,$field=true){
    	$where = array(
    			"order_id" => $order_id
    	);
    	return $this->field($field)->where($where)->select();
    }

    /**
     * 判断商品是否全部退货
     * @param string $order_id 订单id
     * @return bool
     */
    public function allRefund($order_id,$type=1){
    	$goods = M('OrderGoods')->where(['order_id'=>$order_id])->select();
    	$rec_id = [];
    	foreach($goods as $v){
    		if(empty($v['refund_status'])){
    			return false;
    		}
    		$rec_id[] = $v['rec_id'];
    	}
    	$refund_model = M('Refund');
    	$refund_sql = $refund_model->where(['order_id'=>$order_id,'rec_id'=>['in',$rec_id]])->order('refund_time desc')->buildSql();
    	$refund = $refund_model->table($refund_sql.' refund')->group('rec_id')->select();
    	foreach($refund as $v){
    		if($type==1){
	    		if($v['refund_status'] != 3){
	    			return false;
	    		}
    		}else{
    			if($v['is_receive'] < 1){
    				return false;
    			}	
    		}
    	}
    	return true;
    }

    /**
     * 设置订单退款状态
     * @param string $order_id 订单id
     * @return bool
     */
    public function setOrderRefundStatus($order_id){
        if(!$this->allRefund($order_id)){
        	return true;
        }
        $order_result = M('Order')->where(['order_id'=>$order_id])->data(['status'=>4,'pay_status'=>3])->save();
        if($order_result === false){
            return false;
        }

        //是否有优惠劵货积分退款
        $order_info = M('Order')->field(true)->where(['order_id'=>$order_id])->find();
        //返还优惠劵
        /*if(!empty($order_info['coupon_id'])){
            if(M('CouponCode')->where(['id'=>$order_info['coupon_id']])->data(['order_id'=>'','use_time'=>0])->save()===false){
                return false;
            }
        }*/

        //返还积分
        if(!empty($order_info['integral'])){
            $credits_model = D('User/Credits');
            $credits_model->setOperateType(5,'ACCOUNT');
            $credits_result = $credits_model->setCredits($order_info['uid'],$order_info['integral'],$order_info['order_sn'],0,1);
            if(!$credits_result->isSuccess()){
                return false;
            }
        }

        //返还余额
        if($order_info['balance'] > 0){
            $credits_model = D('User/Credits');
            $credits_model->setOperateType(5,'ACCOUNT');
            $credits_result = $credits_model->setCredits($order_info['uid'],$order_info['balance'],'订单'.$order_info['order_sn'].'退款,余额返还',0,0);
            if(!$credits_result->isSuccess()){
                return false;
            }
        }

        //删除促销活动
        /*$viewFields = [
            "PromotionsX" =>[
                "id",
                "promotions_id",
                "type",
                "associate_id",
                "_type" => "LEFT"
            ],
            "Promotions"=>[
                "param",
                "_on" =>"PromotionsX.promotions_id=Promotions.id",
                "_type"=>"LEFT"
            ]
        ];
        $promotions_lists = $this->dynamicView($viewFields)->where(['PromotionsX.type'=>1,'PromotionsX.status'=>1,'associate_id'=>$order_info['order_id']])->select();
        if(!empty($promotions_lists)) {
            foreach ($promotions_lists as $promotions_v) {
                $param_arr = json_decode($promotions_v['param'], true)[0];
                if ($param_arr['content']['coupon']['is_sel']) {
                    M('CouponCode')->where(['source' => 1, 'source_associate' => $promotions_v['id']])->delete();
                }
            }
            M('PromotionsX')->where(['type' => 1, 'associate_id' => $order_info['order_id']])->data(['status' => 0])->save();
        }*/

        return true;
    }

    /**
     * 已发货和未发货退货单
     * @param array $refund
     * @return multitype:multitype: Ambigous <\Think\mixed, NULL, mixed, unknown, multitype:Ambigous <unknown, string> unknown , object>
     */
    public function getRefundByship(array $refund_id){
    	$viewFields = array(
    			"Refund"=>array(
    					"refund_id",
    					"_type"=>"LEFT"
    			),
    			"Order" =>array(
    					"order_id",
    					"shipping_status",
    					"_as"=>"Orders",
    					"_on"=>"Refund.order_id=Orders.order_id",
    					"_type"=>"LEFT"
    			)
       );
       $where = array(
       		"_string" =>"Orders.shipping_status=1 OR Orders.shipping_status=2",
       		"Refund.refund_id" => array('in',$refund_id)
       );
       $data = array();
       //已发货
       $data['shipping'] = $this->dynamicView($viewFields)->where($where)->getField("refund_id",true);
       //未发货
       if(!empty($data['shipping'])){
        $data['no_shipping'] = array_diff($refund_id, $data['shipping']);
       }else{
       	$data['no_shipping'] = $refund_id;
       }
       return $data;
    }
    /**
     * 通过订单商品id获取退款订单
     * @param  $rec_id
     * @param  $field
     */
    public function getByRec($rec_id,$field=true){
    	$where = array(
    			'rec_id' => array('in',$rec_id),
    			'refund_status'=>array("NEQ",6),
    			'delete_time'=>0
    	);
    	return $this->field($field)->where($where)->select();
    }
}