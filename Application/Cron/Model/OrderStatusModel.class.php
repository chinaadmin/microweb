<?php

/**
 * 订单状态模型
 */
namespace Cron\Model;

use Common\Model\BaseModel;

class OrderStatusModel extends BaseModel {
	protected $autoCheckFields = false;
	
	/**
	 * 付款过期时间按小时算
	 * 
	 * @var int
	 */
	public $pay_timeout = 48;
	
	/**
	 * 收货过期时间按天算
	 * 
	 * @var int
	 */
	public $receipt_timeout = 7;
	
	/**
	 * 获取付款过期时间
	 * 
	 * @return int
	 */
	public function getPayTimeout() {
		return $this->pay_timeout * 60 * 60;
	}
	
	/**
	 * 获取收货过期时间
	 * 
	 * @return int
	 */
	public function getReceiptTimeout() {
		return $this->receipt_timeout * 24 * 60 * 60;
	}
	
	/**
	 * 获取是否有超时未支付订单
	 * 
	 * @return bool
	 */
	public function isTimeoutUnpaid() {
		$order_id = M ( 'Order' )->where ( [ 
				'pay_status' => 0,
				'status' => [
						// 'in',[0,1]
						'eq',
						0 
				],
				'add_time' => [ 
						'lt',
						time () - $this->getPayTimeout () 
				] 
		] )->getField ( 'order_id' );
		return empty ( $order_id ) ? false : true;
	}
	
	/**
	 * 取消超时未支付的订单
	 * 
	 * @param int $limit
	 *        	数量
	 * @return bool
	 */
	public function cancelUnpaidOrder($limit = 4) {
		$order_lists = M ( 'Order' )->field ( true )->where ( [ 
				'pay_status' => 0,
				'status' => [
						// 'in',[0,1]
						'eq',
						0 
				],
				'add_time' => [ 
						'lt',
						time () - $this->getPayTimeout () 
				] 
		] )->limit ( $limit )->select ();
		
		$order_ids = array_column ( $order_lists, 'order_id' );
		
		$this->startTrans ();
		// 修改订单状态为已支付
		if (M ( 'Order' )->where ( [ 
				'order_id' => [ 
						'in',
						$order_ids 
				] 
		] )->data ( [ 
				'status' => 2 
		] )->save () === false) {
			$this->rollback ();
			return false;
		}
		
		// 添加库存
		$order_goods_lists = M ( 'OrderGoods' )->where ( [ 
				'order_id' => [ 
						'in',
						$order_ids 
				] 
		] )->field ( true )->select ();
		foreach ( $order_goods_lists as $v ) {
			$chengs_stock = D ( 'Admin/Goods' )->changeStock ( $v ['goods_id'], $v ['norms'], $v ['number'] );
			if (! $chengs_stock->isSuccess ()) {
				$this->rollback ();
				return false;
			}
		}
		
		// 订单提交操作记录
		if ($this->orderAction ( $order_ids, [ 
				'status' => 2 
		], $this->pay_timeout . '小时内未进行支付，订单已自动取消', '您在' . $this->pay_timeout . '小时内未进行支付，订单已自动取消' ) === false) {
			$this->rollback ();
			return false;
		}
		
		// 用户数据统计
		$user_ids = [ ];
		foreach ( $order_lists as $v ) {
			if (empty ( $user_ids [$v ['uid']] )) {
				$user_ids [$v ['uid']] = 1;
			} else {
				$user_ids [$v ['uid']] += 1;
			}
		}
		$user_analysis_model = M ( 'UserAnalysis' );
		foreach ( $user_ids as $k => $v ) {
			if ($user_analysis_model->where ( [ 
					'uid' => $k 
			] )->data ( [  // 交易关闭次数
					'order_close_count' => [ 
							'exp',
							'order_close_count + ' . $v 
					] 
			] )->save () === false) {
				$this->rollback ();
				return false;
			}
		}
		
		$this->commit ();
		return true;
	}
	
	/**
	 * 获取是否有超时未收货订单
	 * 
	 * @return bool
	 */
	public function isTimeoutNotReceipt() {
		$order_id = M ( 'Order' )->where ( [ 
				'pay_status' => 2,
				'status' => 1,
				
				'shipping_status'=>1, 
				
				'shipping_time' => array(array('lt',time () - $this->getReceiptTimeout ()))
				
		] )->getField ( 'order_id' );
		
		return empty ( $order_id ) ? false : true;
	}
	/**
	 * 获取是否有超时已收货订单
	 * 
	 * @return bool
	 */
	public function isTimeoutHasReceipt() {
		$order_id = M ( 'Order' )->where ( [ 
				'pay_status' => 2,
				'status' => 6,
				'not_refund' => 0,
				
				// 'shipping_status'=>1, //解除门店自提订单限制
				// 'money_paid'=>[
				// 'egt',20
				// ],
				'receiving_time' => [ 
						'lt',
						time () - 7 * 24 * 60 * 60 
				] 
		] )->getField ( 'order_id' );
		
		return empty ( $order_id ) ? false : true;
	}
	
	/**
	 * 确认有超时未收货的订单收货
	 * 
	 * @param int $limit
	 *        	数量
	 * @return bool
	 */
	public function confirmReceiptOrder($limit = 4) {
		$order_lists = M ( 'Order' )->field ( true )->where ( [ 
				'pay_status' => 2,
				'status' => 1,
				
				'shipping_status'=>1,
				'shipping_time' => array(array('lt',time () - $this->getReceiptTimeout ()))
				
		] )->limit($limit)->select (); // 
		
		$order_ids = array_column ( $order_lists, 'order_id' );
		
		$this->startTrans ();
		// 修改订单状态为已完成
		$map = [ ];
		$map [] = '1 = 1'; // tp bug 自动加空格
		$map ['order_id'] = array (
				'in',
				$order_ids 
		);
		if (M ( 'Order' )->where ( $map )->data ( [ 
				'status' => 6,
				'shipping_status' => 2,
				'receiving_time' => time () 
		] )->save () === false) {
			$this->rollback ();
			return false;
		}
		// 订单完成处理
// 		foreach ( $order_ids as $v ) {
// 			D ( 'Home/Order' )->orderFinishAction ( $v );
// 		}
		
		// 订单提交操作记录
		if ($this->orderAction ( $order_ids, [ 
				'status' => 6,
				'shipping_status' => 2,
				'comment_status' => 1 
		], $this->receipt_timeout . '天内未进行收货，订单已自动确认收货', '您在' . $this->receipt_timeout . '天内未进行收货，订单已自动确认收货' ) === false) {
			$this->rollback ();
			return false;
		}
		
		$this->commit ();
		return true;
	}
	/**
	 * 确认有积分清空
	 *
	 * @param int $limit
	 *        	数量
	 * @return bool
	 */
	public function confirmjfqk($limit = 4) {
		$flag_time=NOW_TIME-365 * 2 * 24 * 60 * 60;
		$user_lists = M ( 'User' )->field ( true )->where ( [
				'delete_time' => 0,
        		'last_empty_integral' => [
        		'lt',$flag_time
        		],
        		
        		'add_time' => [
        				'lt',
        				time () - 365 * 2 * 24 * 60 * 60
        		]
		] )->limit($limit)->select (); //
	
				$user_ids = array_column ( $user_lists, 'uid' );
	
				$this->startTrans ();
				// 清空积分
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = array (
						'in',
						$user_ids
				);
				$map ['type'] = 1;
				if (M ( 'AccountCredits' )->where ( $map )->data ( [
						'credits' => 0
						
				] )->save () === false) {
					$this->rollback ();
					return false;
				}
				// 增加清空时间记录
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = array (
						'in',
						$user_ids
				);
				
				if (M ( 'User' )->where ( $map )->data ( [
						'last_empty_integral' => NOW_TIME
				
				] )->save () === false) {
					$this->rollback ();
					return false;
				}
				
				//日志记录
				foreach ( $user_lists as $k => $v ) {
					//前积分
					$ojf = M ( 'AccountCredits' )->where ( [
							'uid' => $v ['uid'],
							'type'=>1
								
					] )->getField ( 'credits' );
					$data = array(
							'uid'=>$v ['uid'],
							'credits_type'=>1,
							'type' =>0,
							'credits' =>-$ojf,
							'remark' =>"积分清空",
							'editor' =>"系统",
							'add_time'=>NOW_TIME
					);
					if (M ( 'AccountLog' )->add ($data) === false) {
						$this->rollback ();
						return false;
					}
				}
				
	
		$this->commit ();
		return true;
	}
	/**
	 * 确认有成长值清空
	 *
	 * @param int $limit
	 *        	数量
	 * @return bool
	 */
	public function confirmczzqk($limit = 4) {
		$flag_time=NOW_TIME-365 * 1 * 24 * 60 * 60;
		$user_lists = M ( 'User' )->field ( true )->where ( [
				'delete_time' => 0,
				'last_empty_growth' => [
						'lt',$flag_time
				],
	
				'add_time' => [
						'lt',
						time () - 365 * 1 * 24 * 60 * 60
				]
		] )->limit($limit)->select (); //
	
				$user_ids = array_column ( $user_lists, 'uid' );
	
				$this->startTrans ();
				// 清空成长值
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = array (
						'in',
						$user_ids
				);
				$map ['type'] = 3;
				if (M ( 'AccountCredits' )->where ( $map )->data ( [
						'credits' => 0
	
				] )->save () === false) {
					$this->rollback ();
					return false;
				}
				// 增加清空时间记录
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = array (
						'in',
						$user_ids
				);
	
				if (M ( 'User' )->where ( $map )->data ( [
						'last_empty_growth' => NOW_TIME
	
				] )->save () === false) {
					$this->rollback ();
					return false;
				}
	
				//日志记录
				foreach ( $user_lists as $k => $v ) {
					//前成长值
					$oczz = M ( 'AccountCredits' )->where ( [
							'uid' => $v ['uid'],
							'type'=>3
							
					] )->getField ( 'credits' );
					$data = array(
							'uid'=>$v ['uid'],
							'credits_type'=>3,
							'type' =>0,
							'credits' =>-$oczz,
							'remark' =>"成长值清空",
							'editor' =>"系统",
							'add_time'=>NOW_TIME
					);
					if (M ( 'AccountLog' )->add ($data) === false) {
						$this->rollback ();
						return false;
					}
				}
	
	
				$this->commit ();
				return true;
	}
	/**
	 * 确认有超时已收货的订单收货
	 * 
	 * @param int $limit
	 *        	数量
	 * @return bool
	 */
	public function confirmhasReceiptOrder($limit = 4) {
		
		$order_lists = M ( 'Order' )->field ( true )->where ( [ 
				'pay_status' => 2,
				'status' => 6,
				'not_refund' => 0,
				
				// 'order_sn'=>"20151111843354",
				// 'shipping_status'=>1,
				// 'money_paid'=>[
				// 'egt',20
				// ],
				'receiving_time' => [ 
						'lt',
						time () - 7 * 24 * 60 * 60 
				] 
		] )->limit ( $limit )->select (); //
		
		$order_ids = array_column ( $order_lists, 'order_id' );
		
		$this->startTrans ();
		foreach ( $order_lists as $k => $v ) {
			$czz = floor ( $v ['money_paid']-$v ['shipment_price'] ); // 向下取整
			$jf = floor ( ($v ['money_paid']-$v ['shipment_price']) / 20 );
// 			if($czz<0||$jf<0){
				
// 				return false;
// 			}
			// 订单处理
			$map = [ ];
			$map [] = '1 = 1'; // tp bug 自动加空格
			$map ['order_id'] = $v ['order_id'];
			if (M ( 'Order' )->where ( $map )->data ( [ 
					'not_refund' => 1,
					'credits' => $jf 
			] )->save () === false) {
				$this->rollback ();
				return false;
			}
			//会员成长值
			if($czz>0){
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = $v ['uid'];
				$map ['type'] = 3;
				if (M ( 'AccountCredits' )->where ( $map )->setInc('credits',$czz)=== false) {
					$this->rollback ();
					return false;
				}
			}
			// 会员积分处理
			if($jf>0){
				$map = [ ];
				$map [] = '1 = 1'; // tp bug 自动加空格
				$map ['uid'] = $v ['uid'];
				$map ['type'] = 1;
				
				if (M ( 'AccountCredits' )->where ( $map )->setInc('credits',$jf)=== false) {
					$this->rollback ();
					return false;
				}
				
				//日志
				$data = array(
			    		'uid'=>$v ['uid'],
			    		'credits'=>$jf,
			    		'credits_type'=>1,
			    		'type' =>8,
						'remark' =>$v ['order_sn'],
						'editor' =>"系统",
			    		'add_time'=>NOW_TIME
			    );
				if (M ( 'AccountLog' )->add ($data) === false) {
					$this->rollback ();
					return false;
				}
				//推荐人+积分
				$tjr = M ( 'UserRecommend' )->where ( [
						'uid' => $v ['uid']
				] )->getField ( 'recommend' );
				if(!empty($tjr)){
					$map = [ ];
					$map [] = '1 = 1'; // tp bug 自动加空格
					$map ['uid'] = $tjr;
					$map ['type'] = 1;
					
					if (M ( 'AccountCredits' )->where ( $map )->setInc('credits',$jf)=== false) {
						$this->rollback ();
						return false;
					}
					
					//日志
					$data = array(
							'uid'=>$tjr,
							'credits'=>$jf,
							'credits_type'=>1,
							'type' =>10,
							'remark' =>$v ['order_sn'],
							'editor' =>"系统",
							'add_time'=>NOW_TIME
					);
					if (M ( 'AccountLog' )->add ($data) === false) {
						$this->rollback ();
						return false;
					}
					
				}
				
				
				
				
				
				
/* 			    //消息推送   暂时屏蔽
				$da=array(
						'push_name'=>3,
						'push_title'=>'尊敬的必好货用户：您好！',
						'push_brief'=>"您的订单".$v ['order_sn']."已完成，赠送".$jf."积分！",
						'push_url'=>'http://'.C('JT_CONFIG_WEB_MOBILE_WAP_URL').'/html/memcenter.html',  //会员中心查看积分详情
						'push_is_app'=>2,
						'push_addtime'=>NOW_TIME,
						'is_bind'=>1,
					);
				$push_id=M('MessagePush')->add($da);
				if($push_id){
					$dat=array(
						'user_id'=>$v ['uid'],
						'fk_push_id'=>$push_id,
						'addtime'=>NOW_TIME,
						'fk_push_name'=>3,
					);
					if (M ( 'MessageUser' )->add ($dat) === false) {
						$this->rollback ();
						return false;
					}		
				} */
					
				
			}
			
		}
		
		$this->commit ();
		return true;
	}
	
	/**
	 * 订单处理
	 * 
	 * @param string $order_ids
	 *        	订单id
	 * @param array $action
	 *        	订单操作
	 * @param string $remark
	 *        	备注
	 * @param string $front_remark
	 *        	前端显示备注
	 * @return mixed
	 */
	public function orderAction($order_ids, $action, $remark, $front_remark) {
		$data_all = [ ];
		$data = [ 
				'action' => json_encode ( $action ),
				'is_seller' => 2,
				'handle' => '',
				'remark' => $remark,
				'front_remark' => $front_remark,
				'add_time' => time () 
		];
		foreach ( $order_ids as $v ) {
			$data ['order_id'] = $v;
			$data_all [] = $data;
		}
		return M ( 'OrderAction' )->addAll ( $data_all );
	}
}