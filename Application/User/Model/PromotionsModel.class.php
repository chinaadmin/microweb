<?php
/**
 * 促销模型类
 * @author cwh
 * @date 2015-08-11
 */
namespace User\Model;
use Common\Model\BaseModel;

class PromotionsModel extends BaseModel{

    protected $tableName = 'promotions';

    public $_validate = [
        ['name','require','活动名称不能为空']
    ];

    public $_auto = [
        ['start_time','tomktime',self::MODEL_BOTH,'function'],
        ['end_time','tomkendtime',self::MODEL_BOTH,'function'],
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>[
                'status'=>1,
                'end_time'=>['gt',NOW_TIME],
                'start_time'=>['lt',NOW_TIME]
            ],
        ],
        'default'=>[

        ]
    ];

    private $type = [
        1=>'满减优惠',
        2=>'充值优惠',
        3=>'限时促销',
        4=>'免邮优惠',
        5=>'限量折扣',
        6=>'购物赠券',
        7=>'注册赠券'
    ];

    /**
     * 获取类型
     * @param int|null $type_id 类型id
     * @return array
     */
    public function getType($type_id = null){
        if(is_null($type_id)){
            return $this->type;
        }
        return $this->type[$type_id];
    }

    /**
     * 获取正常促销活动列表
     * @param null|int $type 类型
     * @param null $source 平台:0为全部,3:电脑 1:微商城 2:手机
     * @param null int $show_type 显示位置 1：首页板块
     * @return mixed
     */
    public function getNormalList($type = null,$source = null,$show_type=null){
        $where = [];
        if(!is_null($type)){
        	if(is_array($type)){
        		$where['type'] = array('in',$type);
        	}else{
        		$where['type'] = $type;
        	}
        }
        if(!is_null($show_type)){
        	$where['show_type'] = $show_type;
        }
        if(!empty($source)){
            if(!is_array($source)){
                $source = explode(',',$source);
            }
            $source[] = 0;
            $where['source'] = ['in',$source];
        }
        return $this->scope('normal')->where($where)->field(true)->select();
    }

    /**
     * 验证商品是否优惠
     * @param array $goods 商品id
     * @param null $source 平台:0为全部,3:电脑 1:微商城 2:手机
     * @return array
     */
    public function verifyGoods($goods = [],$source = null,$promotion_id=0){
    	$promotion_id = I("request.promotionId",$promotion_id,'intval');
    	$return_promtions = [];
    	if($promotion_id){
    		$promotion = $this->scope("normal")->where(['id'=>$promotion_id])->find();
    		$goods = current($goods);
    		if($promotion['type']==3){ //限时促销
    			$where = array(
    					'promotions_id'=>$promotion_id,
    					'goods_id'=>$goods,
    					'end_time'=>['gt',NOW_TIME],
    					'start_time'=>['lt',NOW_TIME]
    			);
    			$promotionGoods = M("PromotionsGoods")->where($where)->find();
    			if($promotionGoods){
    				$all_goods_promtions['limit'] = 0;//限购
    				$all_goods_promtions['discount'] = $promotionGoods['discount'];//折扣
    			}
    			
    		}
    		if($promotion['type']==4){
    			//限量促销
    			$param_arr = json_decode($promotion['param'], true)[0];
    			$all_goods_promtions['limit'] = $param_arr['content']['limit']['val'];//限购
    			$all_goods_promtions['discount'] = $param_arr['content']['discount']['val'];//折扣
    		}
    		$all_goods_promtions['id'] = $promotion['id'];//促销id
    		$return_promtions[$goods] = $all_goods_promtions;
    	}else{
    		/* $promotions_lists = $this->getNormalList(array(3,5),$source);
    		$ids = array_column($promotions_lists, 'id');
    		$where = array(
    				'promotions_id'=>array('in',$ids),
    				'goods_id'=>array('in',(array)$goods)
    		);
    		$promotionGoods = M("PromotionsGoods")->where($where)->select(); //选择的促销商品
    		$all_goods_promtions = [];
    		$other_goods_promtions = [];
    		foreach($promotions_lists as $promtions){
    			$param_arr = json_decode($promtions['param'], true)[0];
    			if($promtions['all_goods'] == 1){
    				$all_goods_promtions['limit'] = $param_arr['content']['limit']['val'];//限购
    				$all_goods_promtions['discount'] = $param_arr['content']['discount']['val'];//折扣
    				$all_goods_promtions['id'] = $promtions['id'];//促销id
    			}else{
    				
    			}
    		}
    		foreach($goods as $goods_v){
    			$return_promtions_info = [];
    			if(!empty($all_goods_promtions)){
    				$return_promtions_info = $all_goods_promtions;
    			}
    			if(!empty($other_goods_promtions[$goods_v])){
    				$return_promtions_info = $other_goods_promtions[$goods_v];
    			}
    		
    			$return_promtions[$goods_v] = $return_promtions_info;
    		}	 */
    		$return_promtions = $this->getDiscount($goods,$source);
    	}
        return $return_promtions;
    }
    
    /**
     * 商品折扣
     * @param unknown $goods
     * @param string $source
     * @return multitype:multitype:NULL Ambigous <>  Ambigous <multitype:unknown Ambigous <> >
     */
    private function getDiscount($goods = [],$source = null){
    	$promotions_lists = $this->getNormalList(array(3,5),$source);
    	$ids = array_column($promotions_lists, 'id');
    	$where = array(
    			'promotions_id'=>array('in',$ids),
    			'goods_id'=>array('in',(array)$goods)
    	);
    	if($ids){
    	 $promotionGoods = M("PromotionsGoods")->where($where)->select(); //选择的促销商品
    	}
    	$data[] = array();
    	$all_goods_promtions = array();
    	//全场折扣下最低折扣
    	if($promotions_lists){
	    	foreach($promotions_lists as $promtions){
	    	$param_arr = json_decode($promtions['param'], true)[0];
		    	if($promtions['all_goods'] == 1){
		    		if(empty($all_goods_promtions) || ($all_goods_promtions['discount']>$param_arr['content']['discount']['val'])){  //最低折扣
		    		 $all_goods_promtions['limit'] = $param_arr['content']['limit']['val'];//限购
		    		 $all_goods_promtions['discount'] = $param_arr['content']['discount']['val'];//折扣
		    		 $all_goods_promtions['id'] = $promtions['id'];//促销id
		    		}
		        }
		        foreach ($promotionGoods as $key=>&$progoods){
		        	if($promtions['id']==$progoods['promotions_id']){
		        		//删除过期的活动
		        		if(time()>($promtions['end_time']+3600*24)){
		        			unset($promotionGoods[$key]);
		        		}else{
		        			$progoods['limit'] = $param_arr['content']['limit']['val'];
		        			$progoods['time_type'] = $promtions['time_type'];
		        		}
		        	}
		        }
	    	}
    	}
    	$goodsPromotion = array();
    	//部分商品最低折扣
    	if($promotionGoods){
    		foreach($promotionGoods as $v){
    			if($v['type']==3 && $v['time_type']==1){
    				if(time()>($v['end_time']+3600*24)){
    					continue;
    				} 
    			}
    			if(empty($goodsPromotion[$v['goods_id']]) || $v['discount']<$goodsPromotion[$v['goods_id']]['discount']){
    				//和全场促销比对
    				if($all_goods_promtions['discount'] && $all_goods_promtions['discount']<$v['discount']){
    					$v['discount'] = $all_goods_promtions['discount'];
    					$v['promotions_id'] = $all_goods_promtions['id'];
    					$v['limit'] =  $all_goods_promtions['limit'];
    				}
    				$goodsPromotion[$v['goods_id']] = array(
    						'id'=>$v['promotions_id'],
    						'discount'=>$v['discount'],
    						'limit'=>$v['limit']
    				);
    			}
    		}
    	}
    	$return_promtions = array();
    	foreach($goods as $vo){
    		if($goodsPromotion[$vo]){
    			$return_promtions[$vo] = $goodsPromotion[$vo];
    		}else{
    			$return_promtions[$vo] = $all_goods_promtions;
    		}
    	}
    	return $return_promtions;
    }

    /**
     * 是否免邮
     * @param int $order_money 订单金额
     * @param array $goods 商品id
     * @param null $source 来源
     * @return boolean
     */
    public function hasFreeMail($order_money,$goods = [],$source = null,$position = []){
        $promotions_lists = $this->getNormalList(4,$source);
        foreach($promotions_lists as $promtions){
            //指定商品
            $is_designation_goods = true;
            if($promtions['all_goods'] != 1){

            }
            if(!$is_designation_goods){
                continue;
            }

            $param_arr = json_decode($promtions['param'], true)[0];

            if($param_arr['condition']['goods']['is_sel'] !== true ){//指定商品
                if($param_arr['condition']['money']['is_sel'] === true && $order_money < $param_arr['condition']['money']['val']){//指定商品加消费金额
                    continue;
                }
                continue;
            }

            //指定地区要运费
            $shipment = explode(',',$param_arr['condition']['shipment']['val']);
            //省
            if(in_array($position['provice'],$shipment)){
                continue;
            }
            //市
            if(in_array($position['city'],$shipment)){
                continue;
            }

            return true;
        }

        return false;
    }
    
    /**
     * 赠券
     * @param int $type 6：购物赠券  7：注册赠券
     * @param $goods_id 
     */
    public function giveCoupon($uid,$type,$order_id='',$source=null){
    	if($type==6 && empty($order_id)){
    		return;
    	}
    	$promotions = $this->getNormalList ( $type, $source );
		if ($promotions) {
			// 获取优惠劵
			$coupons = array ();
			if ($type == 6) {
				if ($order_id) {
					// 获取订单商品
					$where = array (
							"order_id" => $order_id,
							"refund_status" => 0 
					);
					$goods_id = M ( "OrderGoods" )->where ( $where )->getField ( "goods_id", true );
					$pay_time = M ( "Order" )->where ( [ 
							'order_id' => $order_id 
					] )->getField ( "pay_time" );
					//是否在有效期范围内下的单
					 foreach ( $promotions as $key => $vo ) {
						if ($vo ['start_time'] > $pay_time || $vo ['end_time'] < $pay_time) {
							unset ( $promotions [$key] );
						}
					} 
				}
				if (! empty ( $promotions )) {
					// 获取赠券商品
					$promotion_ids = array_column ( $promotions, 'id' );
					$where = array (
							'promotions_id' => array (
									'in',
									$promotion_ids 
							),
							'goods_id' => array (
									'in',
									( array ) $goods_id 
							) 
					);
					$promotion_goods = M ( "PromotionsGoods" )->where ( $where )->getField ( "promotions_id", true ); // 参加活动的商品
				}
			}
			if ($promotions) {
				$promotions_x_data = array ();
				foreach ( $promotions as $v ) {
					// 判断商品是否有优惠券
					if ($type == 6) {
						if ($v ['all_goods'] == 0) { // 部分商品情况下
							if (! in_array ( $v ['id'], $promotion_goods )) {
								continue;
							}
						}
					}
					$coupon = current ( json_decode ( $v ['param'], true ) );
					$coupon = explode ( ",", $coupon ['content'] ['coupon'] ['val'] );
					$coupons = array_merge ( $coupons, $coupon );
					$promotions_x_data [] = [ 
							'promotions_id' => $v ['id'],
							'type' => $type,
							'status' => 1,
							'add_time' => NOW_TIME,
							'uid' => $uid,
							'associate_id' => $order_id 
					];
				}
				// 发券
				if ($coupons) {
					$coupon_model = D ( "User/Coupon" );
					$data = array ();
					foreach ( $coupons as $vs ) {
						$data [] = array (
								'coupon_id' => $vs,
								'add_time' => NOW_TIME,
								'source' => 1,
								'source_associate' => '',
								'editor' => '系统',
								'code' => current ( $coupon_model->generateCode () ),
								'uid' => $uid 
						);
					}
					M ( "CouponCode" )->addAll ( $data );
					if ($promotions_x_data) {
						M ( "PromotionsX" )->addAll ( $promotions_x_data );
					}
				}
			}
		}
	}
}