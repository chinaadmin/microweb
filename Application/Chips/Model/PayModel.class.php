<?php
/**
 * 订单支付模型
 * @author xiongzw
 * @date 2015-12-11
 */
namespace Chips\Model;
use Api\Model\ApiBaseModel;
class PayModel extends ApiBaseModel{
	protected $autoCheckFields = false;
    /**
     * 账户付款
     * @param string $order_id 订单id
     * @return \Common\Org\Util\Results
     */
    public function accountPay($order_id){
        $result = $this->result();
        $this->startTrans();
        $order_model = D('Home/Order');
        $order_info = M('CrowdfundingOrder')->where(['cor_order_id'=>$order_id])->find();
        //使用余额
        $credits_model = D('User/Credits');
        $credits_model->setOperateType(4,'ACCOUNT');
        $credits_result = $credits_model->setCredits($order_info['cor_uid'], $order_info['cor_should_pay'], '支付订单'.$order_info['cor_order_sn'], 1, 0);
        if (!$credits_result->isSuccess()) {
            $this->rollback();

            $code = $credits_result->getCode();
            switch($code){
                case 'CREDITS_INADEQUATE':
                    $credits_result->error('余额不足',$code);
                    break;
                case 'SET_CREDITS_FAIL':
                    $credits_result->error('扣除余额失败',$code);
                    break;
            }

            return $credits_result;
        }

        $pay_result = $this->paying($order_id,'ACCOUNT',false);
        if(!$pay_result->isSuccess()){
            $this->rollback();
            return $pay_result;
        }

        $this->commit();
		
		if($order_info['cor_delivery_type'] == 0){
			$order_code = rand_string ( 6, 1 );
			$res = M("crowdfundingOrder") -> where(array('cor_order_id'=>$order_id)) -> save(array('order_code'=>$order_code));
			$phone = M("stores") -> where(array('stores_id'=>$order_info['cor_store_id'])) -> field('name,phone') -> find();
			// 发送短信
			$where = array(
					"uid"=>$order_info['cor_uid']
			);
			$mobile = M("user")->where($where)->getField("mobile");
			$messageObj = new \Common\Org\Util\MobileMessage ();
			$arr = array(
				'trade_name' =>empty($phone['name'])?"系统":$phone['name'],
				'code' =>$order_code,
				'stores_tel' =>empty($phone['phone'])?"4007777927":$phone['phone'],
			);
			$mobileResult = $messageObj->sendMessByTel($mobile,$arr,'pk_up_code');
			 
		}
        return $this->result()->success($mobileResult);
    }
    
    /**
     * 付款
     * @param string $order_id 订单id
     * @param bool $is_start_trans 是否开启事务
     * @return \Common\Org\Util\Results
     */
    public function paying($order_id,$pay_type='',$is_start_trans = true){
    	$result = $this->result();
    	$order_model = D('Home/Order');
    	$m = M('CrowdfundingOrder');
    	$order_info = $m->where(['cor_order_id'=>$order_id])->find();
    	$data = [
    	'cor_pay_amount'=>$order_info['cor_should_pay'],
    	'cor_pay_status'=>2,
    	'cor_pay_time'=>time(),
    	'cor_order_status'=>1 //订单变为已确认
    	];
    	if($pay_type){
    		$data['cor_pay_type'] = $pay_type;
    	}
    	if($is_start_trans) {
    		$this->startTrans();
    	}
    	$order_result = $m->where(['cor_order_id'=>$order_id])->data($data)->save();


        //后台订单提醒
        if($order_result){
            $order_message = array(
                'order_sn' => $order_info['fk_com_ordersn'],
                'order_type' => 1,
                'message_add_time' => time()
            );
            $order_message_result = M('OrderMessage')->add($order_message); //添加订单提醒消息
        }

    	$other_result = false;
    	$other_result = $this->otherProcess($order_info);
    	if($order_result === false || $other_result === false){
    		if($is_start_trans) {
    			$this->rollback();
    		}
    		return $result->error('付款失败');
    	}else{
    		//订单提交操作记录
    		if($order_model->orderAction($order_id,[ 'cor_pay_status'=>2],'订单付款','您的订单已支付完成，请等待审核确认') === false){
    			if($is_start_trans) {
    				$this->rollback();
    			}
    			return $result->error('付款失败');
    		}
    
    		//添加商品销售统计
//     		$order_goods_lists = M('OrderGoods')->where(['cor_order_id'=>$order_id])->field(true)->select();
//     		foreach($order_goods_lists as $v) {
//     			M("GoodsStatistics")->where(['goods_id' => $v['goods_id']])->setInc('sales', $v['number']);
//     		}
    
    		//订单支付记录(余额支付 后台操作 不记录)
    		//避免重复扣款
    		if($order_info['cor_pay_amount'] > 0 && !in_array($order_info['cor_pay_type'], ['ACCOUNT','ADMINPAY'])) {
    			$credits_model = D('User/Credits');
    			$credits_model->setOperateType(4, $order_info['cor_pay_type']);
    			$credits_result = $credits_model->setCredits($order_info['cor_uid'], $order_info['cor_pay_amount'], '支付订单' . $order_info['cor_order_sn'], 1, 0);
    			if (!$credits_result->isSuccess()) {
    				if($is_start_trans) {
    					$this->rollback();
    				}
    				return $credits_result;
    			}
    		}
    
    		//用户数据统计
    		$analysis_data = [//付款金额
    		'pay_count'=>['exp','pay_count + 1'],
    		'consume_money'=>['exp','consume_money + '.$order_info['cor_pay_amount']],
    		];
    		if(M('UserAnalysis')->where(['uid'=>$order_info['cor_uid']])->data($analysis_data)->save() === false){
    			if($is_start_trans) {
    				$this->rollback();
    			}
    			return $result->error('付款失败');
    		}
    		//增加众筹报名人数
    		if(!D('Chips/Chips')->addApply(M('crowdfunding_detail')->where([ 'cd_id' => $order_info['fk_cd_id']])->getField('fk_cr_id'),1)){
    			//失败处理
    		};
    		//增加内部员工使用折扣次数
    		$user_inside_discount = M('user_inside_discount');
    		if($ud_id = $user_inside_discount->where(['ud_is_used' => 0,'u_uid' => $order_info['cor_uid'],'ud_inside_discount_order_sn' => $order_info['cor_order_sn']])->getField('ud_id')){
    			$user_inside_discount->where(['ud_id' => $ud_id])->setField(['ud_is_used' => 1]);
    			M("User")->where(['uid'=>$order_info['cor_uid'],'is_inside_user' => 1])->setInc('inside_discount_use');
    		}
    		if($is_start_trans) {
    			$this->commit();
    		}
    		//设置子订单有效期
    		$this->setUseTime($order_id);
			
			if($order_info['shipping_type'] == 0){
				$order_code = rand_string ( 6, 1 );
				$res = M("order") -> where(array('order_id'=>$order_info['order_id'])) -> save(array('order_code'=>$order_code));
				$phone = M("stores") -> where(array('stores_id'=>$order_info['stores_id'])) -> getfield('phone,name');
				// 发送短信
				$where = array(
						"uid"=>$order_info['uid']
				);
				$mobile = M("user")->where($where)->getField("mobile");
				$messageObj = new \Common\Org\Util\MobileMessage ();
				$arr = array(
					'trade_name' =>empty($phone['name'])?"系统":$phone['name'],
					'code' =>$order_code,
					'stores_tel' =>empty($phone['phone'])?"4007777927":$phone['phone'],
				);
			}
			$mobileResult = $messageObj->sendMessByTel($mobile,$arr,'pk_up_code');
    		return $result->success('付款成功');
    	}
    }
    
    /**
     * 设置子订单有效期
     * @param $order_id 订单id
     */
    public function setUseTime($order_id){
    	$info = M("CrowdfundingOrder")->field("cor_term_index,fk_com_ordersn,fk_cd_id")->where(['cor_order_id'=>$order_id])->find();
    	$orders = M("CrowdfundingOrder")->where(['fk_com_ordersn'=>$info['fk_com_ordersn']])->order("cor_term_index asc")->select();
        $unit = M("CrowdfundingDetail")->where(['cd_id'=>$info['fk_cd_id']])->getField("cd_period_unit");
    	$data = array();
    	$i=1;
    	//设置子订单有效期 从第二期开始
    	if($info['cor_term_index']==$orders[0]['cor_term_index']){
	        foreach($orders as $key=>$v){
	        	if($key==0){
	        		continue;
	        	}
	        	switch($unit){
	        		//年
	        		case 1:
	        			  $start_time = strtotime(date('Y',strtotime("+".$i." year"))."-01-01 00:00:00");
	        			  $end_time = strtotime(date('Y',strtotime("+".$i." year"))."-12-31 23:59:59");
	        			  break;
	                //季度
	        		case 2:
	        			  $season = ceil((date('n'))/3)+$i;//当月是第几季度
	        			  $start_time =  mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
	        			  $end_time = mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));
	        			  break;
	        		default:
	        			  $start_time = strtotime(date('Y-m',strtotime("+".$i." month")."-01 00:00:00")); 
	        			  $end_time = strtotime(date('Y-m-d', strtotime("$start_time +1 month -1 day"))."  23:59:59");
	        	}
	        	$data[$v['cor_order_id']] = array(
	        			'start_time'=>$start_time,
	        			'end_time'=>$end_time
	        	);
	        	$i++;
	        }
			if ($data) {
				foreach ( $data as $key => $vo ) {
					M ( "CrowdfundingOrder" )->where ( [ 
							'cor_order_id' => $key 
					] )->save ( [ 
							'cor_start_time' => $vo ['start_time'],
							'cor_end_time' => $vo ['end_time'] 
					] );
				}
			}
    	}
    }
    
    /**
     * 判断订单是否可以支付
     */
    public function judgeOrder($order_id){
    	$result = $this->result();
    	$info = M("CrowdfundingOrder")->field("cor_pay_status,cor_term_index,fk_com_ordersn,fk_cd_id")->where(['cor_order_id'=>$order_id])->find();
    	if($info['cor_pay_status']!=0){
    		return $result->error("改订单已支付，请不要重复支付！");
    	}
    	$orders = M("CrowdfundingOrder")->where(['fk_com_ordersn'=>$info['fk_com_ordersn']])->order("cor_term_index asc")->select();
    	foreach ($orders as $v){
    		if($v['cor_term_index']<$info['cor_term_index'] && $v['cor_pay_status']==0){
    			return $result->error("请先支付上一期订单！");
    		}
    	}
    	return $result->success();
    }
    /**
     * 支付订单金额为零的订单
     * @param string $order_info  子订单单条信息
     * @param int $goodCount  份数
     * @return boolean
     */
     function payZero($order_info,$goodCount){
    	$data = [
    			'cor_pay_status'=>2,//订单变为已支付
    			'cor_pay_time'=>time(),
    			'cor_order_status'=>1 //订单变为已确认
    	];
    	$m = M('CrowdfundingOrder');
    	$res = $m->where(['fk_com_ordersn' => $order_info['fk_com_ordersn'],'cor_pay_amount' => 0])->save($data);
    	$res = $res === false ?  false : true;
    	$count = $m->where(['fk_com_ordersn' => $order_info['fk_com_ordersn'],'cor_pay_amount' => 0])->count();
    	$count = $goodCount * $count;
    	if($count && false){ //有为零的其他子订单   关闭后续单抽奖机会增加@date 2015/01/29
    		$awardModel = D('Admin/Award');
    		if($order_info['cor_recommend_uid']){//给推荐人增加抽奖机会
	    		$addRecommendAwardChance = $awardModel->increaseAwardChance($order_info['cor_recommend_uid'],$count);
    		}
    		//给下单人增加抽奖机会
    		$addAwardChance = $awardModel->increaseAwardChance($order_info['cor_uid'],$count);
    	}
    	return $res;
    }
	/**
	 * 订单处理 扩展
	 * @param array $order_info 首单子订单
	 * @return boolean
	 */
    private function otherProcess($order_info){
    	$res = [];
    	$goodCount = M('crowdfunding_order_goods')->where(['fk_cor_order_id' => $order_info['cor_order_id']])->sum('cog_count');
    	$res[] = $this->payZero($order_info,$goodCount);
    	$awardModel = D('Admin/Award');
    	if($order_info['cor_recommend_uid']){
    		//给推荐人增加抽奖机会
    		$res[] = $addRecommendAwardChance = $awardModel->increaseAwardChance($order_info['cor_recommend_uid'],$goodCount);
    	}
    	 //给下单人增加抽奖机会 add by youngt 2016-03-16
    	 $cd_id = C('WONG_CONFIG.cd_id');
    	 $s_time = strtotime(C('WONG_CONFIG.zc_start_time'));
    	 $e_time = strtotime(C('WONG_CONFIG.zc_end_time'));
    	 $status = (NOW_TIME > $s_time && NOW_TIME < $e_time) ? true : false;
    	 if ($status && $cd_id == intval($order_info['fk_cd_id'])){
        	 //wongwongwong
        	 $addAwardChance = $awardModel->increaseAwardChance($order_info['cor_uid'],$goodCount,1);
        	 //十周年
        	 $addAwardChance = $awardModel->increaseAwardChance($order_info['cor_uid'],$goodCount,2);
        	 $res[] = $addAwardChance;
    	 }
    	 else{
    	     $addAwardChance = $awardModel->increaseAwardChance($order_info['cor_uid'],$goodCount);
    	     $res[] = $addAwardChance;
    	 }
    	if(count($res) == count(array_filter($res))){
    		return true;
    	}
    	return false;
    }
}