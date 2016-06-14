<?php
/**
 * 订单模型
 * @author cwh
 * @date 2015-05-28
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
use User\Org\Util\Integral;
use User\Org\Util\User;

class OrderModel extends HomebaseModel{

    /**
     * 用户ID
     * @var int
     */
    public $user_id = 0;

    /**
     * 初始化
     * @see Model::_initialize()
     */
    public function _initialize(){
        parent::_initialize();
        $user = User::getInstance ();
        $user_id = $user->isLogin();
        $this->user_id = empty($user_id)?0:$user_id;
    }

    /**
     * 配送方式
     * @var array
     */
    public $delivery_way = [
        0=>'from_mentioning',//自提
        1=>'express_delivery',//快递
        2=>'visit_delivery'//送货上门
    ];

    /**
     * 生成订单id
     * @return string
     */
    public function orderid() {
        return uniqueId();
    }

    /**
     * 生成订单号
     * @return string
     */
    public function ordersn() {
      //  mt_srand((double) microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 格式化状态
     * @param array $info 订单信息
     * @return array
     */
    public function formatStatus(array $info){
        /**
         * 订单状态为：未支付，                 显示:查看详情、立即支付、取消订单
         * 订单状态为：已支付，待确认            显示：查看详情、退款退货
         * 订单状态为：已确认，待收货            显示：查看详情、退款退货，确认收货
         * 订单状态为：已提货，                 显示：查看详情、退款退货，确认收货  （若7天后用户没有进行售后申请，订单状态自动改为已完成）
         * 订单状态为：已取消                  显示：查看详情，删除
         * 订单状态为：已过期 （2天订单没有支付，系统自动取消订单）               显示：查看详情，删除
         *
         *
         * 交易状态就显示：待付款、
         *              已付款，待确认、
         *              已确认，待收货、
         *              已完成、
         *              已取消、
         *              已过期
         *
         * btn_pay 支付按钮
         * btn_cancel 取消按钮
         * btn_receipt 确认收货按钮
         * btn_del 删除订单按钮
         * btn_show 查看详情按钮
         * btn_returns 退款退货按钮
         * btn_logistics 查看物流按钮
         */
        $is_returns = false;//可否退款退货
        $status_name = '';
        $info['btn_show'] = true;
        //订单状态
        switch($info['status']){
            case 0://未确认
                switch($info['pay_status']) {
                    case 0://未付款
                        $status_name = '待付款';
                        $info['btn_pay'] = true;
                        $info['btn_cancel'] = true;
                        break;
                    default:
                        $status_name = '待发货';

                        $is_returns = true;
                        break;
                }
                break;
            case 1://1确认
                //支付状态
                switch($info['pay_status']){
                    case 0://未付款
                        $info['btn_pay'] = true;
                        $info['btn_cancel'] = true;
                        $status_name = '待付款';
                        break;
                    default:
                        $is_returns = true;
                        $status_name = '已发货';
                        $info['btn_logistics'] = true;
                        //配送状态
                        switch($info['shipping_status']){//0未发货,1已发货,2已收货,3退货',
                            case 0://0未发货
                                $info['btn_logistics'] = false;
                                $status_name = '待发货';
                                break;
                            case 1://1已发货
                                $info['btn_receipt'] = true;
                                break;
                            case 2://2已收货
                                $status_name = '已完成';
                                if($info['receiving_time']+7*24*60*60<time()) {//超过7天无法退款
                                    $is_returns = false;
                                }
                                break;
                            case 3://3退货
                                //$is_returns = false;
                                $status_name = '已退货';
                                break;
                            case 4://4发货中
                                $is_returns = false;
                                $status_name = '发货中';
                                break;
                            default:
                                break;
                        }
                        break;
                }
                break;
            case 2://2已取消
                $status_name = '已取消';
                $is_returns = false;
                $info['btn_del'] = true;
                break;
            case 3://3无效
                $status_name = '无效';
                $is_returns = false;
                $info['btn_del'] = true;
                break;
            case 4://4退货
                $status_name = '已退货';
                $is_returns = true;
                $info['btn_del'] = true;
                break;
            case 5://5已过期
                $status_name = '已过期';
                $is_returns = false;
                $info['btn_del'] = true;
                break;
            case 6://6已完成
                $status_name = '已完成';
                if($info['receiving_time']+7*24*60*60<time()) {//超过7天无法退款
                    $is_returns = false;
                }else{
                    $is_returns = true;
                }
                $info['btn_del'] = true;
                break;
        }

        //可否退款退货
        if($is_returns){
            $info['btn_returns'] = true;
        }else{
            $info['btn_returns'] = false;
        }

        $info['status_name'] = $status_name;
        return $info;
    }

    /**
     * 添加订单
     * @param array $data 订单信息
     *                  shipping_type 配送方式 0为自提，1为快递，2门店发货
     *                  address_id 收货地址id
     *                  stores_id  自提门店id
     *                  stores_time 自提时间
     *                  invoice_id 发票id
     *                  pay_type 支付方式
     *                  is_auto_pay 是否自动支付
     *                  coupon_id 优惠劵id
     *                  use_integral 用户是否开启积分抵扣       1为是，0为否
     *                  balance 余额
     *                  postscript 买家附言
     *                  source 来源：0为pc端，1为手机端
     * @param array $cart_id 购物车id
     * @return \Common\Org\Util\Results
     */
    public function addOrder($data = [],$cart_id = []){
        $result = $this->result();
        //用户id
        if(empty($data['uid'])){
            $data['uid'] = $this->user_id;
        }
        if(empty($data['uid'])){
            return $result->error('用户id不能为空');
        }
        //购物车id
        if(empty($cart_id)){
            return $result->error('商品不能为空');
        }else{
            $cart_id = is_array($cart_id)?$cart_id:(array)$cart_id;
        }
        //购物车列表
        $cart_lists = D('Home/Cart')->getLists(['cart_id'=>['in',$cart_id]]);
        if(empty($cart_lists)){
            return $result->error('商品不能为空');
        }

        $cart_all = [];
        array_map(function($info) use (&$cart_all){
            $cart_all['price'] += $info['price'];
            $cart_all['credits'] += $info['goods_credits']*$info['number'];
            $cart_all['goods_ids'] = array_merge($cart_all['goods_ids'] ? $cart_all['goods_ids'] : [],[$info['goods_id']]);
        },$cart_lists);
        //用户统计数据
        $user_analysis_model = M('UserAnalysis');
        $analysis_where = [
            'uid'=>$data['uid']
        ];
        $user_analysis = $user_analysis_model->where($analysis_where)->field(true)->find();
        $first_single_price = C('JT_CONFIG_WEB_FIRST_SINGLE_PRICE');
        if(empty($user_analysis['pay_count']) && !empty($first_single_price) && $cart_all['price'] < $first_single_price){ //首次下单金额限制
            return $result->error('首次下单金额不能小于'.$first_single_price.'元');
        }
        $this->startTrans();
        $order_id = $this->orderid();
        $order_sn = $this->ordersn();
        $order_data = [
            'order_id'=>$order_id,
            'order_sn'=>$order_sn,
            'uid'=>$data['uid'],
            'shipping_type'=>$data['shipping_type'],
            'postscript'=>$data['postscript'],
        ];

        if($data['shipping_type'] > 0){
			if(empty($data['address_id'])){
				return $result->error('收货地址不能为空'.$data['shipping_type']);
			}
			$shipping_address_model = D('Home/ShippingAddress');
			$shipping_address = $shipping_address_model->where(['address_id'=>$data['address_id']])->find();

		}
        //配送方式
        switch($data['shipping_type']){
            case 0://门店自提
                /*if(empty($data['delivery_id'])){
                    return $result->error('提货地址不能为空');
                }
                $delivery_info = D('Home/Delivery')->where(['delivery_id'=>$data['delivery_id']])->field(true)->find();
                if(empty($delivery_info)){
                    return $result->error('提货地址有误');
                }
                $order_data['stores_id'] = $delivery_info['stores_id'];*/
                if(empty($data['stores_id'])){
                    return $result->error('自提门店地址不能为空');
                }
                $order_data['stores_id'] = $data['stores_id'];
                $order_data['stores_time'] = empty($data['stores_time'])?0:$data['stores_time'];
                break;
            case 1://普通快递
                break;
            case 2://送货上门
                if(empty($data['stores_id'])){
                    return $result->error('门店地址不能为空');
                }
                $order_data['stores_id'] = $data['stores_id'];
                break;
        }
        //发票
        if(!empty($data['invoice_id'])){
            $order_data['needs_invoice'] = 1;
            $invoice = D('Home/Invoice')->field(true)->find($data['invoice_id']);//发票id
            unset($invoice['invoice_id']);
            $invoice['invoice_content'] = 1;//默认商品详情
            $invoice_id = M('Invoice')->add($invoice);
            if($invoice_id === false){
                return $result->error('添加订单失败');
            }
            $order_data['invoice_id'] = $invoice_id;
        }
        //计算金额
        $amout_data = [
            'uid'=>$data['uid'],
            'goods_amount'=>$cart_all['price'],
            'use_integral'=>$data['use_integral'],
            'coupon_id'=>$data['coupon_id'],
            'address_id'=>$data['address_id'],
            'shipping_type'=>$this->delivery_way[$data['shipping_type']],
            'balance'=>$data['balance'],
            'goods_ids'=>$cart_all['goods_ids'],
        ];
        $amount_result = $this->calculationAmount($amout_data);
        // return $amount_result;exit;
        if($amount_result->isSuccess()){
            $amount_data = $amount_result->getResult();
        }else{
            return $amount_result;
        }
        $order_data['goods_amount'] = $amount_data['goods_amount'];//商品的总金额
        $order_data['order_discount'] = $amount_data['order_discount'];//会员折扣
        $order_data['discount_price'] = $amount_data['discount_price'];//折扣优惠
        $order_data['coupon_id'] = $amount_data['coupon_id'];//优惠劵id
        $order_data['coupon_price'] = $amount_data['coupon_price'];//优惠劵优惠
        $order_data['integral'] = $amount_data['integral'];//积分
        $order_data['integral_price'] = $amount_data['integral_price'];//好货币抵现
        $order_data['balance'] = $amount_data['balance'];//余额
        $order_data['shipment_price'] = $amount_data['shipment_price'];//运费
        $order_data['order_amount'] = $amount_data['order_amount'];//应付款金额

        $order_data['pay_type'] = $data['pay_type'];//支付方式
        $order_data['pay_status'] = 0;
        $order_data['shipping_status'] = 0;
        $order_data['status'] = 0;
        $order_data['money_paid'] = 0;
        $order_data['credits'] = $cart_all['credits'];//积分
        $order_data['add_time'] = time();
        $order_data['source'] = empty($data['source'])?0:$data['source'];//来源
        if($this->add($order_data) === false){
            $this->rollback();
            return $result->error('添加订单失败');
        }

        //配送方式
        $order_receipt_data = [];
        $order_receipt_data['order_id'] = $order_id;
        $order_receipt_data['name'] = $shipping_address['name'];
        $order_receipt_data['mobile'] = $shipping_address['mobile'];
        switch($data['shipping_type']){
            case 0://门店自提
                //break;
            case 1://普通快递
            case 2://送货上门
                $order_receipt_data['province'] = $shipping_address['user_provice'];
                $order_receipt_data['city'] = $shipping_address['user_city'];
                $order_receipt_data['county'] = $shipping_address['user_county'];
                $order_receipt_data['localtion'] = $shipping_address['user_localtion'];
                $order_receipt_data['address'] = $shipping_address['user_detail_address'];
                break;
        }
		
		if($data['shipping_type'] > 0){
			if(M('OrderReceipt')->add($order_receipt_data) === false){
				$this->rollback();
				return $result->error('添加订单失败');
			 }
		}
        //订单商品信息
        $order_goods_all_data = [];
        $all_number = 0;
        foreach($cart_lists as $v) {
            $order_goods_data = [
                'rec_id'=>$this->orderid(),
                'order_id'=>$order_id,
                'goods_id'=>$v['goods_id'],
                'goods_code'=>$v['goods_code'],
                'market_price'=>$v['market_price'],
                'goods_price'=>$v['goods_price'],
                'goods_credits'=>$v['goods_credits'],
                'number'=>$v['number'],
                'norms'=>$v['norms'],
                'norms_attr'=>$v['norms_attr'],
                'norms_value'=>json_encode($v['norms_value']),
                'promotions_id'=>empty($v['promotions_id'])?0:$v['promotions_id'],
                'promotions_discount'=>empty($v['promotions_discount'])?0:$v['promotions_discount']
            ];
            $order_goods_all_data[] = $order_goods_data;
            $all_number += $v['number'];
            
            //库存变更
            $chengs_stock = D('Admin/Goods')->changeStock($order_goods_data['goods_id'],$order_goods_data['norms'],-($order_goods_data['number']));
            if(!$chengs_stock->isSuccess()){
                $this->rollback();
                return $result->error('商品:'.$v['name'].',库存不足');
            }

            //统计商品下订单数
            M("GoodsStatistics")->where(['goods_id'=>$v['goods_id']])->setInc('orders',$v['number']);
        }
        if(M('OrderGoods')->addAll($order_goods_all_data) === false){
            $this->rollback();
            return $result->error('添加订单失败');
        }
        //用户数据统计
        $analysis_data = [//最后一次下单时间
            'last_buy_time'=>time(),
        ];
        if(empty($user_analysis['order_count'])){//第一次下单时间
            $analysis_data['first_buy_time'] = time();
        }
        $analysis_data['order_count'] = (int)$user_analysis['order_count']+1;//订单总数
        $analysis_data['order_goods_count'] = (int)$user_analysis['order_goods_count'] + $all_number;//商品总数

        $analysis_data['pay_way'] = $data['pay_type'];//支付方式
        $analysis_data['delivery_way'] = $data['shipping_type'];//配送方式
        $analysis_data['delivery_stores'] = empty($order_data['stores_id'])?0:$order_data['stores_id'];//配送门店id
        if($user_analysis_model->where($analysis_where)->data($analysis_data)->save() === false){
            $this->rollback();
            return $result->error('添加订单失败');
        }

        //优惠劵使用
        if(!empty($amount_data['coupon_id'])){
            if(D('User/Coupon')->useCoupon($amount_data['coupon_id'],$order_id) === false){
                $this->rollback();
                return $result->error('使用优惠劵失败');
            }
        }

        //使用积分
        if(!empty($amount_data['integral']) && $amount_data['integral']!=0){
            $credits_model = D('User/Credits');
            $credits_model->setOperateType(7,'ACCOUNT');
            $credits_result = $credits_model->setCredits($data['uid'], $amount_data['integral'], $order_sn, 1, 1);
            if (!$credits_result->isSuccess()) {
                $this->rollback();
                return $credits_result;
            }
        }

        //使用余额
        /*if(!empty($amount_data['balance'])){
            $credits_model = D('User/Credits');
            $credits_model->setOperateType(4,'ACCOUNT');
            $credits_result = $credits_model->setCredits($data['uid'], $amount_data['balance'], '支付订单'.$order_sn, 1, 0);
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
        }*/

        //需要付款金额为0
        if(empty($amount_data['order_amount'])){
            $pay_result = D('Home/Pay')->paying($order_id);
            if(!$pay_result->isSuccess()){
                $this->rollback();
                return $pay_result;
            }
        }else {
            //余额并且是自动支付
            if($data['pay_type'] == 'ACCOUNT' && $data['is_auto_pay'] === true){
                
                $pay_time = M('Order')->where(['order_id'=>$amount_data['order_id']])->getField('pay_time');
                if(intval($pay_time) == 0){
                    $pay_result = D('Home/Pay')->accountPay($order_id);
                }
                
                if(!$pay_result->isSuccess()){
                    $this->rollback();
                    return $pay_result;
                }

                $amount_data['order_amount'] = 0;//支付完成设置应付款金额为0
            }

            //订单提交操作记录
            if ($this->orderAction($order_id, [
                    'pay_status' => 0,
                    'shipping_status' => 0,
                    'status' => 0
                ], '提交订单', '您的订单已提交，请尽快完成付款') === false
            ){
                $this->rollback();
                return $result->error('添加订单失败');
            }
        }

        $this->commit();
		/* //添加订单提醒消息
		$order_message = array(
		 		'order_sn' => $order_sn,
		 		'order_type' => 0,
		 		'message_add_time' => time()
		);
	  	 $order_message_result = M('OrderMessage')->add($order_message);  */
        return $result->content([
            'order_id'=>$order_id,//订单id
            'order_amount'=>$amount_data['order_amount']//应付款金额
        ])->success('添加订单成功');
    }

    /**
     * 计算金额
     * @param array $data
     *                  uid 用户id
     *                  goods_amount 总金额
     *                  integral 积分
     *                  coupon_id 优惠劵id
     *                  address_id 收货地址id
     *                  shipping_type 配送方式
     *                  balance 余额
     *                  goods_ids  //商品id数组
     * @return $this
     *                  uid 用户id
     *                  goods_amount 总金额
     *                  integral 积分
     *                  coupon_id 优惠劵id
     *                  balance 余额
     *                  shipment_price 运费
     *                  order_discount 折扣
     *                  discount_price 折扣优惠
     *                  integral_price 好货币抵现
     *                  coupon_price 优惠劵优惠
     *                  order_amount 应付款金额
     *                  user_credits 用户总积分
     *                  can_use_credits 当前可使用积分
     */
    public function calculationAmount($data){
        $data['use_integral'] = empty($data['use_integral'])?0:$data['use_integral'];
        $data['coupon_id'] = empty($data['coupon_id'])?0:$data['coupon_id'];
        $data['balance'] = empty($data['balance'])?0:$data['balance'];
        $data['address_id'] = empty($data['address_id'])?0:$data['address_id'];
        $data['shipping_type'] = empty($data['shipping_type'])?'':$data['shipping_type'];
		if(!$data['goods_ids']){ //商品id数组
			return $this->result()->set('ORDER_GOODSIDS_REQUIRE');
		}
        //获取运费
        $shipment_price = 0;
        $order_weight = $this->getGoodsWeight($data['goods_ids'])/1000;
        if(!empty($data['address_id']) && !empty($data['shipping_type'])){
            switch($data['shipping_type']){
                case 'express_delivery'://普通快递
                    $recaddress_info = D('Home/ShippingAddress')->where(['address_id'=>$data['address_id']])->find();
                    $shipment_price = D('Admin/FreightTemplate')->freight($recaddress_info,$data['goods_amount'],$order_weight);
                    break;
                case 'visit_delivery'://送货上门
                    $shipping_result = D('Home/ShippingAddress')->getStoresDistance($data['address_id'],$order_weight);
                    if($shipping_result->isSuccess()){
                        $result = $shipping_result->getResult();
                        $shipment_price = $result['shipment_price'];
                    }
                    break;
                case 'from_mentioning'://门店自提
                    $shipment_price = 0;
                    break;
            }
        }
        $data['shipment_price'] = $shipment_price;

        $user = D("User/UserGrade")->getGradeByUser($data['uid'],"grade_discount");
        $data['order_discount'] = empty($user['grade_discount'])?10:$user['grade_discount'];//折扣
        $discount_price = discountAmount($data['goods_amount'],$data['order_discount']);
        $data['discount_price'] = $data['goods_amount']-$discount_price;//折扣优惠

        // $order_price = $discount_price - $data['integral_price'];   //原订单价格先扣积分再扣优惠券
        $order_price = $discount_price;         //新订单价格先扣优惠券再扣积分

        //积分抵现
        $integralModel = Integral::getInstance();
        $integral_cash = $integralModel->getIntegral('integral_cash',$data['uid']);
        $integral_cash = $integral_cash?$integral_cash:1;
        $credits_param = (int)$integral_cash;

        $coupon_model  = D('User/Coupon');
	
        $coupon = [];
        if(!empty($data['coupon_id'])) {
            $coupon = $coupon_model->verifyHasUse($data['coupon_id'], $order_price,$data['goods_ids']);
            if ($coupon === false) {
                return $this->result()->error('优惠券不能使用');
            }
        }

        //积分  
        // $user_credits = D('User/Credits')->getCredits($data['uid'],1);  //用户可用积分
        $data['coupon_price'] = empty($coupon['money'])?0:$coupon['money'];//优惠劵优惠

        /* ################## 此处还需改动 ################### */
        /*if(!empty($coupon) && $coupon['rule'] == 2 && $coupon['order_money'] > 0){
            $can_use_credits = ($discount_price - $coupon['order_money'])*$credits_param;
            $can_use_credits = $can_use_credits>$user_credits?$user_credits:$can_use_credits;
        }else{
            $can_use_credits = $user_credits;
        }

        $no_credits_money = ($discount_price - $coupon['order_money'] - $coupon['money'])*$credits_param;
        if($can_use_credits > $no_credits_money){
            $can_use_credits = $no_credits_money > 0?$no_credits_money:0;
        }*/

        /* ################## 还需改动 ################### */

       /* $credits = $data['integral'];
        $data['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现*/

        //可使用好货币大于已选择好货币值
        /*if($credits > $can_use_credits){
            $credits = $can_use_credits;
            $data['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现
            $order_price = $discount_price - $data['integral_price'];
        }
        $data['integral'] = $credits;*/



        /*//原计算方法  当优惠券金额大于商品价格时，会抵扣运费
        $data['order_amount'] = $order_price - $data['coupon_price'] + $shipment_price;
        $data['order_amount'] = $data['order_amount']<0?0:$data['order_amount'];*/

        //新计算方法         $data['uid']
        $userIntegral = M('AccountCredits')->where(array('uid'=>$data['uid'],'type'=>1))->getField('credits');
        $userIntegral = intval($userIntegral);  //用户可用积分
        $order_price = $order_price - $data['coupon_price'];    //扣除优惠券
        if($order_price<=0 || $userIntegral<=0 || !$data['use_integral']){//用户可用积分小于等于0、用户未开启积分抵扣、订单扣除优惠券之后的金额小于等于零 都不可抵扣积分
            $credits = 0;
            $data['integral']=0;
            $data['integral_price']=0;
        }else{
            if($order_price>$userIntegral){
                $credits = $userIntegral;
                $data['integral'] = $credits;
                $data['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现
            }else{
                $credits = floor($order_price);
                $data['integral'] = $credits;
                $data['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现
            }
        }

        $order_amount = $order_price - $data['integral_price'] + $shipment_price;
        $data['order_amount'] = $order_price - $data['integral_price'];
        $data['order_amount'] = $data['order_amount']<0?$shipment_price:$order_amount;

        $data['user_credits'] = (int)$userIntegral;
        $data['can_use_credits'] = (int)$credits;

        return $this->result()->content($data)->success();
    }

    /**
     * 取消订单
     * @param string $order_id 订单id
     * @return string
     */
    public function cancel($order_id){
        $result = $this->result();
        $order_model = M('Order');
        $where = [
            'order_id'=>$order_id
        ];
        $order_info = $order_model->where($where)->field(true)->find();
        if(empty($order_info)){
            return $result->error('该订单不存在');
        }

        switch($order_info['status']){
            case 2:
                return $result->error('该订单已经取消');
            case 0:
                break;
            case 1:
                break;
            default:
                return $result->error('无法取消订单');
        }

        if($order_info['shipping_status']>0){
            return $result->error('该订单已发货，无法取消');
        }

        if($order_info['pay_status'] >= 1){
            return $result->error('该订单已支付，无法取消');
        }

        $this->startTrans();

        $data = [
            'status'=>2
        ];
        if($order_model->where($where)->data($data)->save()===false){
            $this->rollback();
            return $result->error('取消订单失败');
        }

        //添加库存
        $order_goods_lists = M('OrderGoods')->where(['order_id'=>$order_id])->field(true)->select();
        foreach($order_goods_lists as $v){
            $chengs_stock = D('Admin/Goods')->changeStock($v['goods_id'],$v['norms'],$v['number']);
            if(!$chengs_stock->isSuccess()){
                $this->rollback();
                return $result->error('库存出错');
            }
        }

        //订单提交操作记录
        if($this->orderAction($order_id,[
                'status'=>2
            ],'取消订单','您已取消订单') === false){
            $this->rollback();
            return $result->error('取消订单失败');
        }

        //用户数据统计
        $analysis_data = [//交易关闭次数
            'order_close_count'=>['exp','order_close_count + 1']
        ];
        if(M('UserAnalysis')->where([
                'uid'=>$order_info['uid']
            ])->data($analysis_data)->save() === false){
            $this->rollback();
            return $result->error('取消订单失败');
        }

        $this->commit();

        return $result->success('取消订单成功');
    }

    /**
     * 确认收货
     * @param string $order_id 订单id
     * @return string
     */
    public function receipt($order_id){
        $result = $this->result();
        $order_model = M('Order');
        $where = [
            'order_id'=>$order_id
        ];
        $order_info = $order_model->where($where)->field(true)->find();
        if(empty($order_info)){
            return $result->error('该订单不存在');
        }

        switch($order_info['status']){
            case 0:
                break;
            case 1:
                break;
            default:
                return $result->error('该订单无法确认收货');
        }

        if($order_info['shipping_status'] != 1){
            return $result->error('该订单无法确认收货');
        }

        $this->startTrans();
        $data = [
            'status'=>6,
            'shipping_status'=>2,
            //'shipping_time'=>time()
            'receiving_time'=>time(),
        ];
        if($order_model->where($where)->data($data)->save()===false || $this->readyToComment($order_id) == false){
            $this->rollback();
            return $result->error('确认收货失败');
        }
        //订单完成处理
        // $this->orderFinishAction($order_id);

        //订单提交操作记录
        if($this->orderAction($order_id,[
                'status'=>6,
                'shipping_status'=>2
            ],'确认收货','您已确认收货') === false){
            $this->rollback();
            return $result->error('确认收货失败');
        }
        $this->commit();
        
        return $result->success('确认收货成功');
    }

    /**
     * 订单完成处理
     * @param string $order_id 订单id
     */
    public function orderFinishAction($order_id){
        //订单信息
        $where = [];
        $where['order_id'] = $order_id;
        $order_info = M('Order')->where($where)->field(true)->find();
        $uid = $order_info['uid'];

        //用户等级
        D('User/UserGrade')->userGrade($uid);

       //用户积分
        D('User/UserGrade')->integral($uid,$order_info['goods_amount'] + $order_info['shipment_price']);
        $user_analysis_model = M('UserAnalysis');

        //获取促销活动
        $promotions_lists = D('User/Promotions')->getNormalList(1);
        $coupon_model = D('User/Coupon');

        foreach($promotions_lists as $promotions_v) {

            //来源
            if(!empty($promotions_v['source'])){
                if($order_info['source'] != $promotions_v['source']){
                    continue;
                }
            }

            //对象
            if(!empty($promotions_v['object'])){
                $object = json_decode($promotions_v['object'], true);
                if(!empty($object)) {
                    $grade_id = D('User/User')->where(['uid' => $uid])->getField('grade_id');
                    $group_id = M('UserGroupX')->where(['uid' => $uid])->getField('group_id', true);
                    $has_use = false;
                    foreach($object as $v){
                        if($v['name']=='grade'){//用户等级
                            if($grade_id == $v['id']){
                                $has_use = true;
                                break;
                            }
                        }

                        if($v['name']=='group'){//用户组
                            if(in_array($v['id'],$group_id)){
                                $has_use = true;
                                break;
                            }
                        }
                    }

                    if(!$has_use){
                        continue;
                    }
                }

            }


            $param_arr = json_decode($promotions_v['param'], true)[0];

            //需要满足首笔订单
            if ($param_arr['condition']['first_single']['is_sel']) {
                $is_first_single = $user_analysis_model->where(['uid' => $uid])->getField('first_single');
                if ($is_first_single == 1) {//已经完成首笔订单的返回
                    continue;
                }
            }

            $is_meets = false;
            //满足多少金额
            if ($param_arr['condition']['money']['is_sel']) {
                if ($order_info['order_amount'] >= $param_arr['condition']['money']['val']) {
                    $is_meets = true;
                }
            }

            //满足多少件
            if ($param_arr['condition']['num']['is_sel']) {
                $goods_count = M('OrderGoods')->where(['order_id' => $order_info['order_id']])->count('order_id');
                if ($goods_count >= $param_arr['condition']['num']['val']) {
                    $is_meets = true;
                }
            }

            if ($is_meets) {
                $promotions_x_data = [
                    'promotions_id' => $promotions_v['id'],
                    'type' => $promotions_v['type'],
                    'status' => 1,
                    'add_time' => NOW_TIME
                ];
                if ($param_arr['content']['coupon']['is_sel']) {
                    $promotions_x_data['associate_id'] = $order_info['order_id'];
                    $promotions_x_data['uid'] = $order_info['uid'];
                    $promotions_x_result = M('PromotionsX')->data($promotions_x_data)->add();

                    //发放优惠劵
                    $coupon_val = $param_arr['content']['coupon']['val'];
                    $coupon_arr = explode(',', $coupon_val);
                    foreach ($coupon_arr as $coupon) {
                        $coupon_model->issuing($coupon, $uid, 1,$promotions_x_result);
                    }
                }
            }
        }
         
        //指定商品赠券
        D("User/Promotions")->giveCoupon($uid,6,$order_id,$order_info['source']);
        //用户数据统计 设置为首次下单完成
        $analysis_data = [
            'first_single'=>1
        ];
        $user_analysis_model->where(['uid'=>$uid])->data($analysis_data)->save();
    }

    /**
     * 订单处理
     * @param string $order_id 订单id
     * @param array $action 订单操作
     * @param string $remark 备注
     * @param string $front_remark 前端显示备注
     * @return mixed
     */
    public function orderAction($order_id,$action,$remark,$front_remark){
        return M('OrderAction')->add([
            'order_id'=>$order_id,
            'action'=>json_encode($action),
            'is_seller'=>0,
            'handle'=>$this->user_id,
            'remark'=>$remark,
            'front_remark'=>$front_remark,
            'add_time'=>time()
        ]);
    }

    /**
     * 删除订单
     * @param string $order_id 订单id
     * @return string
     */
    public function del($order_id){
        $result = $this->result();
        $order_model = M('Order');
        $where = [
            'order_id'=>$order_id
        ];
        $order_info = $order_model->where($where)->field(true)->find();
        if(empty($order_info)){
            return $result->error('该订单不存在');
        }

        if($order_info['status'] < 2){
            return $result->error('该订单无法删除');
        }

        if($order_model->where($where)->data(['delete_time'=>time()])->save()===false){
            return $result->error('删除订单失败');
        }

        return $result->success('删除订单成功');
    }
    /**
     *修改商品为待评论 
     * @param array $order_id 订单id数组
     */
    function readyToComment($order_id){
    	$order_id = (array)$order_id;
    	return M('order_goods')->where(['order_id' => ['in',$order_id]])->setField('goods_comment_status',1);
    }
    /**
     * 修改商品为已评论
     * @param string $order_id 订单id
     * @param string $goods_id 商品id
     */
    function finishedComment($order_id,$goods_id){
    	return M('order_goods')->where(['order_id' => $order_id,'goods_id' => $goods_id])->setField('goods_comment_status',2);
    }
    /**
     * 商品重量
     * @param array $goodsArr
     */
    function getGoodsWeight($goodsArr){
    	$goodsArr = (array)$goodsArr;
    	$weight = D('Admin/Goods')->where(['goods_id' => ['in',$goodsArr]])->sum('weight');
    	return $weight;//单位g
    }
	
	
	/**
	 *	判断用户是否存在用户名
	 *
	 */
	 public function getUserName($uid){
		$username = M('User')->where(['uid'=>$uid])->getField('username');
		$mobile = M('User')->where(['uid'=>$uid])->getField('mobile');
		if(empty($username) && empty($mobile)){
			$return_data = array(
				'toBind'=>'toBind',
			);
			
			$res = $this->result()->success()->content($return_data);
			return $res;
		}
		$res = $this->result()->error();
		return $res;
	 }
}