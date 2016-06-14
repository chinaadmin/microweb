<?php

/**
 * 记录
 * @author cwh
 * @date 2015-08-19
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
class RecordController extends ApiBaseController {
	
	/**
	 * 充值历史记录
	 *
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         token token值
	 *         </code>
	 * @edit xiaohuakang 2016-03-18
	 */
	public function recharge() {
		$this->authToken ();

		$startTime = strtotime(I('request.startTime', date('Y-m', time()))); //	查询起始时间
		$endTime = strtotime(I('request.endTime', date('Y-m-d', time())));	//	查询结束时间
		
		$where = [ 
				// 'type' => 1,
				'type' => [1, 6, 'or'], //查询类型为充值或活动中奖的记录
				'uid' => $this->user_id,
				'credits_type' => 0,


				'add_time' => [['egt', $startTime], ['lt', $endTime]]  // 查询订单起始时间和结束时间


		];
		$page_lists = $this->_lists ( M ( 'AccountLog' ), $where, 'add_time desc' );
		$result_data = [ 
				'lastPage' => $page_lists ['lastPage'] 
		];
		$recharge_lists = $page_lists ['data'];
		if ($recharge_lists) {
			$recharge_lists = array_map ( function ($info) {
				return [ 
						'money' => $info ['credits'],
						'addTime' => date ( 'Y-m-d H:i', $info ['add_time'] ),
						'type' => $info['type'] // 金额来源：1、充值 6、抽奖
				];
			}, $recharge_lists );
		}
		$result_data ['rechargeList'] = $recharge_lists;
		$this->ajaxReturn ( $this->result->content ( $result_data )->success () );
	}
	
	
	/**
	 * 消费记录
	 *
	 * @author xiongzw
	 *         传入参数
	 *         <code>
	 *         token token值
	 *         </code>
	 * @edit xiaohuakang 2016-03-25
	 */
	public function payRecord() {
		$this->authToken ();

		$startTime = strtotime(I('request.startTime', date('Y-m', time()))); // 查询起始时间
		$endTime = strtotime(I('request.endTime', date('Y-m-d', time()))); // 查询结束时间

		$where = array (
				'pay_status' => 2,
				// 'pay_type' => 'ACCOUNT',

				'pay_type' => [["eq", "ACCOUNT"], ["eq", "WEIXIN"], ["eq", "WEIXIN#APP"], ["eq", "ALIPAY"], "or"], // 修改为查询通过所有支付方式产生的消费记录（包括账户余额、微信 网页/APP、支付宝 网页/APP 等方式）
				'add_time' => [['egt', $startTime], ['lt', $endTime]], // 查询订单起始时间和结束时间

				'uid' => $this->user_id 
		);
		// $pay_lists = $this->_lists ( M ( "Order" ), $where, 'add_time desc' );


		$pay_lists = $this->_lists ( M ( "v_all_order" ), $where, 'add_time desc' ); // 修改为查询所有消费记录（包括普通订单消费和众筹消费）

		$result_data = [ 
				'lastPage' => $pay_lists ['lastPage'] 
		];
		
		$pay_lists = $pay_lists ['data'];
		if ($pay_lists) {
			$pay_lists = array_map ( function ($vo) {
				return array (
						// 'money' => $vo ['money_paid'],
						// 'payTime' => date ( 'Y-m-d H:i', $vo ['pay_time'] ) 

						// 修改为查询所有消费记录（包括众筹）
						'money' => $vo['order_amount'],
						'payTime' => date ( 'Y-m-d H:i', $vo['add_time'] ),
						'payType' => $vo['pay_type'] // 增加返回支付方式字段

				);
			}, $pay_lists );
		}
		$result_data ['payList'] = $pay_lists;
		$this->ajaxReturn ( $this->result->content ( $result_data )->success () );
	}

	
	/**
	 * 全部明细
	 *
	 * @author xiongzw
	 *         传入参数
	 *         <code>
	 *         token token值
	 *         </code>
	 */
	public function allRecord() {
		$this->authToken ();
		$where = [ 
				'type' => 1,
				'uid' => $this->user_id,
				'credits_type' => 0 
		];
		$recharge_lists = M ( 'AccountLog' )->field ( "credits,add_time as addTime" )->where ( $where )->order ( 'add_time desc' )->select ();
		$where = array (
				'pay_status' => 2,
				'pay_type' => 'ACCOUNT',
				'uid' => $this->user_id 
		);
		$pay_lists = M ( "Order" )->field ( "money_paid,pay_time as addTime" )->where ( $where )->order ( 'pay_time desc' )->select ();
		$data = array ();
		$recharge_lists = array ();
		$data = array_merge ( $recharge_lists, $pay_lists );
		if ($data) {
			foreach ( $data as $key => &$v ) {
				for($i = $key + 1; $i < count ( $data ); $i ++) {
					if ($data [$key] ['add_time'] < $data [$i] ['add_time']) {
						$str = $data [$key];
						$data [$key] = $data [$i];
						$data [$i] = $str;
					}
				}
				$v ["addTime"] = date ( 'Y-m-d h:i', $v ['addtime'] );
				unset ( $v ['addtime'] );
				if (isset ( $v ['credits'] )) {
					$v ['money'] = $v ['credits'];
					$v ['type'] = 1;
					unset ( $v ['credits'] );
				}
				if (isset ( $v ['money_paid'] )) {
					$v ['money'] = $v ['money_paid'];
					$v ['type'] = 2;
					unset ( $v ['money_paid'] );
				}
			}
		}
		$this->ajaxReturn ( $this->result->content ( [ 
				'result_data' => $data 
		] )->success () );
	}
}