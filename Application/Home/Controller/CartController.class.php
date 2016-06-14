<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use User\Org\Util\Integral;

class CartController extends HomeBaseController {

    public function index(){
        $this->assign('web_top_cart',false);
        if(empty($this->user)){
            $this->redirect('passport/login');
        }
        $cart_lists = D('Home/Cart')->getLists();
        $this->assign('cart_lists',$cart_lists);
        $this->display();
    }

    public function listJson(){
        if(empty($this->user)){
            $this->ajaxReturn($this->result->set('NOT_LOGGED_IN','未登录')->toArray());
        }
        $cart_lists = D('Home/Cart')->getListsDetail();
        $this->ajaxReturn($this->result->content($cart_lists)->success()->toArray());
    }

    public function add(){
        if(empty($this->user)){
            $this->ajaxReturn($this->result->set('NOT_LOGGED_IN','未登录')->toArray());
        }
        $id = I('request.id',0,'intval');
        $num = I('request.num',1,'intval');
        $norms = I('request.norms');
        $result = D('Home/Cart')->addCart($id,$num,$norms);
        $this->ajaxReturn($result->content($id)->toArray());
    }
 
    public function success(){
    	$goods_id = I("request.id",0,'intval');
    	$data = D('Home/Cart')->recommGoods($goods_id);
        $this->assign("data",$data);
    	$this->display();
    }

    public function update(){
        $id = I('request.id',0,'intval');
        $num = I('request.num',0,'intval');
        $cart_model = D('Home/Cart');
        $result = $cart_model->updateCart($id,$num);
        $this->ajaxReturn($result->toArray());
    }

    public function del(){
        $id = I('request.id',0,'intval');
        $cart_model = D('Home/Cart');
        $where = [
            'cart_id' => $id
        ];
        $result = $cart_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    public function selectAllItem(){
        $result = D('Home/Cart')->selectAllItem();
        $this->ajaxReturn($result->toArray());
    }

    public function cancelAllItem(){
        $result = D('Home/Cart')->cancelAllItem();
        $this->ajaxReturn($result->toArray());
    }

    public function selectItem(){
        $id = I('request.id',0,'intval');
        $result = D('Home/Cart')->selectItem($id);
        $this->ajaxReturn($result->toArray());
    }

    public function cancelItem(){
        $id = I('request.id',0,'intval');
        $result = D('Home/Cart')->cancelItem($id);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 立即购买
     */
    public function buynow(){
        if(empty($this->user)){
            $this->ajaxReturn($this->result->set('NOT_LOGGED_IN','未登录')->toArray());
        }
        $id = I('request.id',0,'intval');
        $num = I('request.num',1,'intval');
        $norms = I('request.norms');
        $result = D('Home/Cart')->addCart($id,$num,$norms,false);
        if($result->isSuccess()){
            $cart_id = $result->getResult()['cart_id'];
        }else{
            $this->ajaxReturn($result->toArray());
        }
        $cart_model = D('Home/Cart');
        $cart_model->cancelAllItem();
        $cart_model->selectItem($cart_id);
        $this->ajaxReturn($this->result->success()->toArray());
    }

    public function shopping(){
        //配送方式
        $delivery_way = M('DeliveryWay')->where(['dw_status'=>1])->field(true)->select();
        $this->assign('delivery_way',$delivery_way);

        $cart_model = D('Home/Cart');
        $cart_id = $cart_model->cacheSelectItem();
        if(empty($cart_id)){//没有选中商品跳回购物车页面
            $this->redirect('Cart/index');
        }
        $cart_lists = $cart_model->getLists(['cart_id'=>['in',$cart_id]]);

        if(empty($cart_lists)){//没有选中商品跳回购物车页面
            $this->redirect('Cart/index');
        }
        $this->assign('cart_lists',$cart_lists);

        /*$cart_all = [];
        array_map(function($info) use (&$cart_all,&$brand_ids){
            $cart_all['price'] += $info['price'];
            $cart_all['number'] += $info['number'];
            $thrift_price = ($info['market_price']*$info['number']) - $info['price'];
            $cart_all['thrift_price'] += $thrift_price;
        },$cart_lists);

        $cart_all['thrift_price'] = $cart_all['thrift_price']<0?0:$cart_all['thrift_price'];
        $user = D("User/UserGrade")->getGradeByUser($this->user['uid'],"grade_discount");
        $discount = $user['grade_discount'];
        $discount_price = $cart_all['price']*($discount/10);
        $cart_all['discount'] = $discount;//打折
        $cart_all['discount_price'] = $discount_price-$cart_all['price'];
        $cart_all['price'] = $discount_price;
        $this->assign('cart_all',$cart_all);*/

        //支付方式
        $this->assign('payment_lists',D('Admin/Payment')->getLists());

        //积分抵现
        $integral = Integral::getInstance();
        $integral_cash = $integral->getIntegral('integral_cash',$this->uid);
        $this->assign('integral_cash',(int)$integral_cash);

        $this->display('shopping1.1');
    }

    /**
     * 获取金额
     */
    public function getMoney(){
        $coupon_id = I('request.coupon',0,'intval');
        $credits = I('request.credits',0,'intval');
        $address_id = I('request.address_id',0,'intval');
        $shipping_type = I('request.shipping_type','');

        $cart_model = D('Home/Cart');
        $cart_id = $cart_model->cacheSelectItem();
        if(empty($cart_id)){//没有选中商品跳回购物车页面
            $this->ajaxReturn($this->result->error('没有选中任何商品')->toArray());
        }
        $cart_lists = $cart_model->getLists(['cart_id'=>['in',$cart_id]]);
        $cart_all = [];
        $goodsArr = [];
        array_map(function($info) use (&$cart_all,&$brand_ids,&$goodsArr){
            $cart_all['price'] += $info['price'];
            $cart_all['number'] += $info['number'];
            $thrift_price = ($info['market_price']*$info['number']) - $info['price'];
            $cart_all['thrift_price'] += $thrift_price;
            $goodsArr[] = $info['goods_id'];
        },$cart_lists);
        $cart_all['thrift_price'] = $cart_all['thrift_price']<0?0:$cart_all['thrift_price'];

        $amount_data = [
            'uid'=>$this->user['uid'],
            'goods_amount'=>$cart_all['price'],
            'integral'=>$credits,
            'coupon_id'=>$coupon_id,
            'address_id'=>$address_id,
            'shipping_type'=>$shipping_type,
            'balance'=>0,
            'goods_ids'=>$goodsArr
        ];
        $amount_result = D('Home/Order')->calculationAmount($amount_data);
        if($amount_result->isSuccess()){
            $amount_data = $amount_result->getResult();
        }else{
            $this->ajaxReturn($amount_result->toArray());
        }

        $cart_all['discount'] = $amount_data['order_discount'];
        $cart_all['discount_price'] = $amount_data['discount_price'];
        $cart_all['integral_price'] = $amount_data['integral_price'];
        $cart_all['coupon_price'] = $amount_data['coupon_price'];
        $cart_all['shipment_price'] = $amount_data['shipment_price'];
        $cart_all['pay_price'] = $amount_data['order_amount'];

        $order_price = $amount_data['goods_amount']-$cart_all['discount_price']- $cart_all['integral_price'];

        /*$user = D("User/UserGrade")->getGradeByUser($this->user['uid'],"grade_discount");
        $discount = $user['grade_discount'];
        $discount_price = $cart_all['price']*($discount/10);
        $cart_all['discount'] = $discount;//打折
        $cart_all['discount_price'] = $cart_all['price']-$discount_price;//折扣优惠
        $credits_param = 10;
        $cart_all['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现
        $order_price = $discount_price - $cart_all['integral_price'];
        $coupon_model  = D('User/Coupon');

        $coupon = [];
        if(!empty($coupon_id)) {
            $coupon = $coupon_model->verifyHasUse($coupon_id, $order_price);
            if ($coupon === false) {
                $this->ajaxReturn($this->result->error('优惠劵不能使用')->toArray());
            }
        }

        //积分
        $user_credits = D('User/Credits')->getCredits($this->user['uid'],1);
        $cart_all['coupon_price'] = $coupon['money'];//优惠劵优惠

        if(!empty($coupon) && $coupon['rule'] == 2 && $coupon['order_money'] > 0){
            $can_use_credits = ($discount_price - $coupon['order_money'])*$credits_param;
            $can_use_credits = $can_use_credits>$user_credits?$user_credits:$can_use_credits;
        }else{
            $can_use_credits = $user_credits;
        }

        $no_credits_money = ($discount_price - $coupon['order_money'] - $coupon['money'])*$credits_param;
        if($can_use_credits > $no_credits_money){
            $can_use_credits = $no_credits_money > 0?$no_credits_money:0;
        }

        //可使用好货币大于已选择好货币值
        if($credits > $can_use_credits){
            $credits = $can_use_credits;
            $cart_all['integral_price'] = ((int)$credits)/$credits_param;//好货币抵现
            $order_price = $discount_price - $cart_all['integral_price'];
        }

        $cart_all['pay_price'] = $order_price - $cart_all['coupon_price'];
        $cart_all['pay_price'] = $cart_all['pay_price']<0?0:$cart_all['pay_price'];*/

        //优惠劵
        $coupon_lists = D('User/Coupon')->getUseLists($this->user['uid'],$order_price,$goodsArr);

        $result = [
            'money'=>$cart_all,
            'coupon'=>$coupon_lists,
            'credits'=>[
                'count'=>$amount_data['user_credits'],
                'use'=>$amount_data['can_use_credits']
            ],
            'sel'=>[
                'coupon'=>$coupon_id,
                'credits'=>$amount_data['integral']
            ]
        ];

        $this->ajaxReturn($this->result->content($result)->success()->toArray());
    }

    /**
     * 支付成功
     */
    public function paysuccess(){
        $id = I('request.id');
        $order_info = M('Order')->field(true)->find($id);
        if($order_info['pay_status'] != 2){
            $this->redirect('Cart/payfail',['id'=>$id]);
        }
        $this->assign('order_info',$order_info);
        $this->display();
    }

    /**
     * 支付失败
     */
    public function payfail(){
        $id = I('request.id');
        $order_info = M('Order')->field(true)->find($id);
        $this->assign('order_info',$order_info);
        $this->display();
    }
}