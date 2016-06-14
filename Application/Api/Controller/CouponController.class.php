<?php
/**
 * 优惠券
 * @author wxb
 * @date 2015-08-12
 */
namespace Api\Controller;
class CouponController extends ApiBaseController{
	public function _initialize() {
		parent::_initialize ();
		//检查是否登入
		$userInfo = $this->authToken();
	}
	 //优惠券列表
	  public function couponList(){
	        $coupon_model = D('User/Coupon');
	        $where = [
	            'uid'=>$this->user_id,
	        	'status'=>1
	        ];
	        $type = I('type', 1, 'intval');
	        switch($type){
	            case 2://已使用
	                $where['use_time'] = ['neq',0];
	                break;
	            case 3://已过期 未使用
	                $where['end_time']=['lt',NOW_TIME];
	                $where['use_time'] = 0;
	                break;
	            case 1: //正处于可使用未用状态(未使用 未过期)
	                $where['end_time'] = ['gt',NOW_TIME];
	                //$where['start_time'] = ['lt',NOW_TIME];
	                $where['use_time'] = 0;
	        }
	        $viewFields = [
	        		'Coupon' => [
	        				"name",
	        				"money",
	        				"order_money",
	        				"status",
	        				"start_time",
	        				"end_time",
	        				'_type' => 'LEFT'
	        		],
	        		'CouponCode' => [
	        				"id",
	        				'coupon_id',
	        				"code",
	        				'_on' => "Coupon.id=CouponCode.coupon_id",
	        				'_type' => 'LEFT'
	        		]
	        ];
	        $coupon_lists = $this->_lists ($coupon_model->viewModel($viewFields), $where ,'end_time asc');
	        foreach($coupon_lists['data'] as &$v){
	        	$v["start_time"] = date('Y-m-d H:i:s',$v["start_time"]);
	        	$v["end_time"] = date('Y-m-d H:i:s',$v["end_time"]);
                $v['money'] = formatAmount($v['money']);
	        }
	       /*  
	        $this->assign('type',$type);
	        $this->assign('coupon_type',[
	            1=>$this->coupon_count(1),
	            2=>$this->coupon_count(2),
	            3=>$this->coupon_count(3)
	        ]); */
	        $this->ajaxReturn($this->result->content($coupon_lists)->success());
	  }
	  /**
	   * 数量
	   * @param int $type 类型：1为未使用，2为已使用，3为已过期
	   */
	  public function coupon_count($type = 0){
	  	$type = $type ? $type : I('get.type',1);
	  	$where = [
	  			'uid'=>$this->uid
	  	];
	  	switch($type){
	  		case 2://已使用
	  			$where['use_time'] = ['neq',0];
	  			break;
	  		case 3://已过期
	  			$where['end_time']=['lt',NOW_TIME];
	  			$where['use_time'] = 0;
	  			break;
	  		case 1://未使用
	  		default:
	  			$where['end_time']=['gt',NOW_TIME];
	  			//$where['start_time']=['lt',NOW_TIME];
	  			$where['use_time'] = 0;
	  	}
	  	$count = D('User/Coupon')->viewModel()->where($where)->count();
	  	return $count;
	  }
	  //获取订单可用优惠券
	  public function getCouponListByOrderMount(){
	  	$order_amount = I('order_amount',0);

	  
	  	//如果订单金额不存在返回错误信息
	  	if(!$order_amount){
	  		$this->ajaxReturn($this->result->set('ORDER_AMOUNT_REQUIRE'));
	  	}
	  	$goodsArr = I('goodsIds','','trim');

	  
	  	//如果商品id不存在返回错误信息
	  	if(!$goodsArr){
	  		$this->ajaxReturn($this->result->set('ORDER_GOODSIDS_REQUIRE'));
	  	}

	  
	  	$goodsArr = explode(',', $goodsArr); // 将商品id组成数组

	  
	  	$where = [
	  			'uid' => $this->user_id, // 用户id
	  			'use_time'=>['eq',0], // 使用时间
	  			'status'=>1,
	  		 	'end_time'=>['gt',NOW_TIME], // 结束时间大于现在
	  			'start_time'=>['lt',NOW_TIME] // 开始时间小于现在
	  	];
/* 	  	$where['_complex'] = [
	  			'rule' => 1,
	  			'_complex' => [
	  					'rule' => 2,
	  					'order_money' => ['elt', $order_amount]
	  			],
	  			'_logic' => 'or'
	  	]; */
	  	$viewFields = [
	  			'Coupon' => [
	  					"name",
	  					"money",
	  					"order_money",
	  					"status",
	  					"start_time",
	  					"end_time",
	  					'rule',
	  					'goods_ids',
	  					'_type' => 'LEFT'
	  			],
	  			'CouponCode' => [
	  					"id",
	  					'coupon_id',
	  					"code",
	  					'_on' => "Coupon.id=CouponCode.coupon_id",
	  					'_type' => 'LEFT'
	  			]
	  	];
	  	$coupon_model = D('User/Coupon');
	  	$coupon_lists = $this->_lists ($coupon_model->viewModel($viewFields), $where ,'end_time asc'); // 查询符合条件的优惠券

        $coupon_lists['data'] = array_filter(array_map(function($info) use ($order_amount,$goodsArr){
	        	if ($info ['rule'] == 2 && $info ['order_money'] <= $order_amount) {
				// 该代金券可用
			} else if ($info ['rule'] == 3 && array_intersect ( explode ( ',', $info ['goods_ids'] ), $goodsArr )) {
				// 可用优惠券
			} else if ($info ['rule'] == 1) {
				// 可用优惠券
			} else {
				return null;
			}
			$info ['money'] = formatAmount ( $info ['money'] );
			$info["start_time"] = date('Y-m-d H:i:s',$info["start_time"]);
			$info["end_time"] = date('Y-m-d H:i:s',$info["end_time"]);
			return $info;
        },$coupon_lists['data']));
        	
// 		print_r(json_encode($coupon_lists['data']));echo 1223;
		$coupon_lists['data']=array_values($coupon_lists['data']);  //去key处理 
	  	$this->ajaxReturn($this->result->content($coupon_lists)->success());
	  }
	  //领取优惠券
	  function receiveCoupon(){
//  	 echo $a = path_encrypt('zhouhuodong|5'); //wzMA2G9uZ3aHVvZ6aG911hbGxSNAI2aml0d
	  	//一次领用优惠券
	  	$couponCode = I('couponCode','','trim'); //优惠id码
	  	if(!$couponCode){
	  		$this->ajaxReturn($this->result->set('COUPON_CODE_REQUIRE'));
	  	}
	  	$uid = $this->user_id; //用户id
	  	//解析优惠id
	  	preg_match('/\d+/i', path_decrypt($couponCode),$match);
	  	$coupon_id = $match[0];
	  	//领取优惠券
	  	$res = D('User/Coupon')->receive($coupon_id,$uid);
	 	if(!$res){
			//已领过或已经过期
			$this->ajaxReturn($this->result->set('COUPON_GET_ERROR'));
		}
	  	//获取可购买商品id
	  	$goodsIds = D('User/Coupon')->where(['id' => $coupon_id])->getField('goods_ids');
		$this->ajaxReturn($this->result->content(['goodsIds' => $goodsIds])->success());
	  }
	  private function findUid($uniqueKey){
	  	//微信id
	  	$resStr = M('user_connect')->where(['openid' => $uniqueKey])->getField('uid');
	  	if(!$resStr){
	  		$this->ajaxReturn($this->result->set('OPENID_NOT_CONNECT'));
	  	}
	  	return $resStr;
	  }
	  //获取优惠券详情
	  function getCouponDetail(){
	  	$couponCode = I('couponCode','','trim'); //优惠id码
	  	if(!$couponCode){
	  		$this->ajaxReturn($this->result->set('COUPON_CODE_REQUIRE'));
	  	}
	  	preg_match('/\d+/i', path_decrypt($couponCode),$match);
	  	$coupon_id = $match[0];
	  	$find = D('User/Coupon')->where(['id' => $coupon_id])->field('name,money,rule,limit_count,remark,start_time,end_time')->find();
	  	$find['startTime'] = date('Y-m-d H:i',$find['start_time']);
	  	$find['endTime'] = date('Y-m-d H:i',$find['end_time']);
	  	unset($find['start_time']);
	  	unset($find['end_time']);
	  	if($find['rule'] == 1){
	  		$find['rulStr'] = '无限制';
	  	}else if($find['rule'] == 2){
	  		$find['rulStr'] = '订单满减使用';
	  	}else{
	  		$find['rulStr'] = '指定商品使用';
	  	}
	  	unset($find['rule']);
	  	$find['limitStr'] = '每人限领'.$find['limit_count'].'张';
	  	unset($find['limit_count']);
	  	$this->ajaxReturn($this->result->content(['data' => $find])->success());
	  }

	  //获取优惠券备注（使用说明文档）
	  public function getCouponRemark(){
	  	$couponId = I('couponId', '', 'trim'); //优惠券id
	  	if( ! $couponId){
	  		$this->ajaxReturn($this->result->set('COUPON_ID_REQUIRE'));
	  	}
	  	$rdata = D('User/Coupon')->where(['id' => $couponId])->field('remark')->find();
	  	$this->ajaxReturn($this->result->content(['data' => $rdata])->success());
	  }


	  public function test(){

	  }



}