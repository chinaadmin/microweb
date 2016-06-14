<?php
/**
 * 订单状态
 * @author cwh
 */
class OrderStatusTask extends \Think\Controller {

	/**
	 * 返回信息
	 */
	private $message = '';

    /**
     * 任务主体
     * @param int $cronId 任务ID
     * @return bool
     */
    public function run($cronId) {
    	
    	$configs_model = D('Admin/Configs');
    	$configs_model->SysConfigs();
    	$data = $configs_model->Configs();
    	if ($data) {
    		$configs = [];
    		foreach ($data as $key => $val) {
    			$configs['JT_CONFIG_'.strtoupper($key)] = $val;
    		}
    		C($configs);
    	}
//     	$data = [];
//     	$data['goods_id'] = 998;
//     	$data['add_time'] = time();
//     	M('Collect')->data($data)->add();
        $order_model = D('OrderStatus');
        
        //积分    2年清空
        $jf_time=NOW_TIME-365 * 2 * 24 * 60 * 60;
        $uid = M ( 'User' )->where ( [
        		'delete_time' => 0,
        		'last_empty_integral' => [
        				'lt',$jf_time
        		],
        
        		'add_time' => [
        				'lt',
        				time () - 365 * 2 * 24 * 60 * 60
        		]
        ] )->getField ( 'uid' );
        		//          		echo $uid;exit;
        		if($uid){
        			if($order_model->confirmjfqk()===false){
        				$this->message = "确认有积分清空,出现失败状况";
        				return false;
        			}
        		}
        //积分 最后三天、一天提醒 ->会员中心处理
        
        
        //成长值    1年清空
        $czz_time=NOW_TIME-365 * 1 * 24 * 60 * 60;
        $uid2 = M ( 'User' )->where ( [
        				'delete_time' => 0,
        				'last_empty_growth' => [
        						'lt',$czz_time
        				],
        
        				'add_time' => [
        						'lt',
        						time () - 365 * 1 * 24 * 60 * 60
        				]
        		] )->getField ( 'uid' );
        				//         		echo $uid2;exit;
        if($uid2){
        					if($order_model->confirmczzqk()===false){
        						$this->message = "确认有成长值清空,出现失败状况";
        						return false;
        					}
        }
        
        //是否有超时未支付
        $has_unpaid = $order_model->isTimeoutUnpaid();

        //是否有超时未收货
        $has_not_receipt = $order_model->isTimeoutNotReceipt();
        
        //是否有超时已收货
        $has_has_receipt = $order_model->isTimeoutHasReceipt();
        
        //有超时已收货
        if ($has_has_receipt){
        	if($order_model->confirmhasReceiptOrder()===false){
        		$this->message = "确认有超时已收货的订单收货,出现失败状况";
        		return false;
        	}
        }
        if (!$has_unpaid && !$has_not_receipt){
            $this->message = "执行了订单状态变更计划任务";
            return false;
        }

        //有超时未支付
        if ($has_unpaid){
            if($order_model->cancelUnpaidOrder()===false){
                $this->message = "取消超时未支付的订单,出现失败状况";
                return false;
            }
        }

        //有超时未收货
        if ($has_not_receipt){
            if($order_model->confirmReceiptOrder()===false){
                $this->message = "确认有超时未收货的订单收货,出现失败状况";
                return false;
            }
        } 
        
        		
        		
        G('order_status_end');
        //超过20秒停止运行同步接口（等待下次计划任务执行）
        if(G('cron_begin','order_status_end',2) > 20){
            $this->message = "执行了订单状态变更计划任务";
            return false;
        }

        //递归执行
        $this->run($cronId);
    }

    /**
     * 返回信息
     * @return string
     */
    public function getMessage(){
    	return $this->message;
    }
    
}