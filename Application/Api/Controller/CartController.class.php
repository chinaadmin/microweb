<?php
namespace Api\Controller;
use User\Org\Util\Integral;
use User\Org\Util\User;
use Common\Model\SharedModel;
class CartController extends ApiBaseController {
    public function _initialize(){
        parent::_initialize();
        if(ACTION_NAME !='lists'){
	       $this->verify();
        }else{
        	if(I("request.token","")){
        		$this->verify();
        	}
        }
    }
    //验证token
    private function  verify(){
    	$this->authToken();
    	$user = User::getInstance ();
    	$user->loginUsingId($this->user_id);
    }

    /**
     * 购物车列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function lists(){
    	$carts = I("post.carts","");
        $cart = array();
    	if($this->user_id){
    		$this->addAll();
    		$cart = D('Home/Cart')->getLists();
    		$cart = array_map(function($info){
    			return D('Api/Cart')->formatLists($info);
    		},$cart);
    	}else{
    		if($carts){
    			$carts = json_decode(html_entity_decode($carts),true);
    			foreach($carts  as &$v){
    				if(empty($v['goodsId']) || empty($v['num'])){
    					unset($v);
    				}
    			}
    			if(empty($carts)){
    				$this->ajaxReturn($this->result->error());
    			}
    			$cart = D('Api/Cart')->cartList($carts);
    		}
    	}
    	foreach($cart as &$v){
    		$v['goods']['price'] = discountAmount($v['price']/$v['number']);
    	}
        $this->ajaxReturn($this->result->content(['cartList'=>$cart])->success());
    }
    
    /**
     * 批量加入购物车
     */
    public function addAll(){
    	$carts = I("post.carts","");
    	if($carts){
    		$carts = json_decode(htmlspecialchars_decode($carts),true);
    		foreach ($carts as $v){
    			D('Home/Cart')->addCart($v['goodsId'],$v['num'],$v['norms'],false);
    		} 
    	}
    }

    /**
     * 立即购买
     * @author cwh
     *         传入参数:
     *         <code>
     *         goodsId 商品id
     *         num 数量
     *         norms 规格
     *         token token值
     *         </code>
     */
    public function buynow(){
        $goodsId = I('request.goodsId','');
        $num = I('request.num',1,'intval');
        $norms = I('request.norms','');
        $result = D('Home/Cart')->addCart($goodsId,$num,$norms,false);
        if($result->isSuccess()){
            $cart_id = $result->getResult()['cart_id'];
            $result->content(['cartId'=>$cart_id]);
        }
        $this->ajaxReturn($result);
    }
	
	/**
	 * 加入购物车
	 * 
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         goodsId 商品id
	 *         num 数量
	 *         norms 规格
	 *         token token值
	 *         </code>
	 */
	public function add() {
		$goodsId = I ( 'request.goodsId', '' );
		$num = I ( 'request.num', 1, 'intval' );
		$norms = I ( 'request.norms', '' );
		$result = D ( 'Home/Cart' )->addCart ( $goodsId, $num, $norms );
		$this->ajaxReturn ( $result->content ( '' ) );
	}

    /**
     * 更新购物车
     * @author cwh
     *         传入参数:
     *         <code>
     *         cartId 购物车id
     *         num 数量
     *         token token值
     *         </code>
     */
    public function update(){
        $cartId = I('request.cartId',0,'intval');
        $num = I('request.num',1,'intval');
        $cart_model = D('Home/Cart');
        $result = $cart_model->updateCart($cartId,$num);
        $this->ajaxReturn($result->content(''));
    }

    /**
     * 删除购物车
     * @author cwh
     *         传入参数:
     *         <code>
     *         cartId 购物车id
     *         token token值
     *         </code>
     */
    public function del(){
        $cartId = I('request.cartId', 0, 'intval');
        $cart_model = D('Home/Cart');
        $where = [
            'cart_id' => $cartId
        ];
        $result = $cart_model->delData($where);
        $this->ajaxReturn($result);
    }

    /**
     * 批量删除购物车
     * @author qrong
     * @param  cartId 购物车id集合
     * @param  token token值
     */
    public function delAll(){
        $cartId = I('request.cartId',0);
        $cart_model = D('Home/Cart');
        $result = $cart_model->delAll(['cart_id' => ['in',$cartId]]);
        
        $this->ajaxReturn($result);
    }

    /**
     * 结算
     * @author cwh
     *         传入参数:
     *         <code>
     *         cartId 购物车id
     *         token token值
     *         </code>
     */
    public function goShopping(){
        $cart_id = I('request.cartId', 0);
        $cart_model = D('Home/Cart');
        $cart_lists = $cart_model->getLists(['cart_id' => ['in', (string)$cart_id]]);
        //判断是限购
        $result = $cart_model->isPurchase($cart_lists,$this->user_id);
        if(!$result->isSuccess()){
        	$this->ajaxReturn($result);
        }
        if (empty($cart_lists)) {//没有选中商品跳回购物车页面
            $this->ajaxReturn($this->result->set('GOODS_REQUIRE'));
        }
        $d = D('Admin/Goods');
        $cart_all = [];
        $cart_all['goodsList'] = array_map(function($info) use (&$cart_all,$d){
            unset($info['cart_id']);
            $cart_all['goodsPrice'] += $info['price'];
            $cart_all['goodsNum'] += $info['number'];
            $cart_all['weight'] += $d->where(['goods_id' => $info['goods_id']])->getField('weight')*$info['number'];
            //$thrift_price = ($info['market_price'] * $info['number']) - $info['price'];
            //$cart_all['thrift_price'] += $thrift_price;
            return D('Api/Cart')->formatLists($info);
        },$cart_lists);
        //$cart_all['thrift_price'] = $cart_all['thrift_price'] < 0 ? 0 : $cart_all['thrift_price'];
        //用户折扣
        $user = D("User/UserGrade")->getGradeByUser($this->user_id, "grade_discount");
        $integral = M('AccountCredits')->where(array('uid'=>$this->user_id,'type'=>1))->getField("credits");

        $discount = $user['grade_discount'];
        $discount_price = $cart_all['price'] * ($discount / 10);
        //$cart_all['discount'] = $discount;//打折
        //$cart_all['discount_price'] = $discount_price - $cart_all['goodsPrice'];
        $cart_all['payPrice'] = $cart_all['goodsPrice'] - $discount_price;

        //用户余额
        $cart_all['money'] = D('User/Credits')->getCredits($this->user_id,0);

        //配送方式
        $delivery_way = array_flip(D('Home/Order')->delivery_way);
        $delivery_way_lists = M('DeliveryWay')->where(['dw_status'=>1])->field(true)->select();
        $delivery_way_lists = array_map(function($info) use($delivery_way){
            $data = [];
            $data['id'] = $delivery_way[$info['dw_code']];
            $data['name'] = $info['dw_delivery_way'];
            $data['remark'] = $info['dw_remark'];
            return $data;
        },$delivery_way_lists);
        $cart_all['deliveryWay'] = $delivery_way_lists;

        $user_analysis = M('UserAnalysis')->where(['uid'=>$this->user_id])->field('delivery_stores,pay_way,delivery_way')->find();
        $cart_all['selPayWay'] = $user_analysis['pay_way'];
        $cart_all['selDeliveryWay'] = $user_analysis['delivery_way'];
        $cart_all['selDeliveryStores'] = $user_analysis['delivery_stores'];


        //积分抵现
        $integralModel = Integral::getInstance();
        $integral_cash = $integralModel->getIntegral('integral_cash',$this->user_id);
        $integral_cash = $integral_cash?$integral_cash:1;
        $credits_param = intval($integral_cash);
        $cart_all['credits_param']=$credits_param;
        if($integral>=$cart_all['goodsPrice']){     //当用户积分大于等于订单总额时，用户可用积分为订单总额
            $cart_all['integral']=floor($cart_all['goodsPrice'])*$credits_param;
            $cart_all['integralPrice']=floor($cart_all['goodsPrice']);
        }else{     //当用户积分小于订单总额时，用户可用积分为用户积分
            $cart_all['integral']=$integral*$credits_param;
            $cart_all['integralPrice']=$integral;
        }
        

        $this->ajaxReturn($this->result->content($cart_all)->success());
    }
    
    /**
     * 获取购物车总量
     */
	function getMyCartCount(){
		$count = $this->getCartCount();		
		$this->ajaxReturn($this->result->content(['cartTotal' => $count])->success());
	}
}