<?php
namespace Api\Controller;
use Common\Model\SharedModel;
use Common\Org\ThinkPay\PayVo;
use Common\Org\ThinkPay\ThinkPay;
use User\Org\Util\User;
class OrderController extends ApiBaseController {

    public function _initialize(){
        parent::_initialize();
        $this->authToken();
        $user = User::getInstance ();
        $user->loginUsingId($this->user_id);
    }

    /**
     * 获取订单运费
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         shippingType 配送方式：0为自提，1为快递，2门店发货
     *         addressId 收货地址
     *         orderMoney 订单金额
     *         </code>
     */
    public function shipmentPrice(){
        $shipping_type = I('request.shippingType','');
        $address_id = I('request.addressId','');
        $orderMoney = I('request.orderMoney','');
        $orderWeight = I('request.orderWeight','')/1000;//订单商品重量
        $shipping_type = D('Home/Order')->delivery_way[$shipping_type];
        //获取运费
        $shipment_price = 0;
        if(!empty($address_id) && !empty($shipping_type)){
            switch($shipping_type){
                case 'express_delivery'://普通快递
                    $recaddress_info = D('Home/ShippingAddress')->where(['address_id'=>$address_id])->find();
                    $shipment_price = D('Admin/FreightTemplate')->freight($recaddress_info,$orderMoney,$orderWeight);
                    break;
                case 'visit_delivery'://送货上门
                    $shipping_result = D('Home/ShippingAddress')->getStoresDistance($address_id,$orderWeight);
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
        $this->ajaxReturn($this->result->content(['shipmentPrice'=>$shipment_price])->success());
    }

    /**
     * 提交订单
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         shippingType 配送方式：0为自提，1为快递，2门店发货
     *         addressId 收货地址
     *         storesId 门店id
     *         invoiceId 发票id
     *         couponId 优惠劵id
     *         integral 积分
     *         cartIds 购物车id，多个用”,“相隔
     *         </code>
     */
    public function single(){
        $cart_id = I('request.cartIds','');
        $cart_id = empty($cart_id)?[]:explode(',',$cart_id);
        $data = [];
        $data['shipping_type'] = I('request.shippingType',0);//配送方式
        $data['address_id'] = I('request.addressId','');//收货地址
        $data['stores_id'] = I('request.storesId','');//门店id
        // $data['integral'] = I('request.integral',0,'intval');//用户抵扣积分   //原先逻辑
        $data['use_integral'] = I('request.use_integral',0,'intval');//用户是否开启抵扣积分
        $stores_time = I('request.storesTime','');
		$stores_time = !empty($stores_time)?strtotime($stores_time):0;
        $data['stores_time'] = $stores_time;//预定提货时间
        //$data['pay_type'] = 'ALIPAY';//支付方式
        $data['pay_type'] = I('request.payType','ALIPAY');//支付方式
        $data['is_auto_pay'] = true;//自动支付
        $data['postscript'] = I('request.postscript','');//买家附言
        $invoice_payee = I('request.invoicePayee','');//发票抬头
        $invoice_id = I('request.invoiceId', 0);//发票id
		
		$uid = I('uid');
		// $this->ajaxReturn($this->result->content($data));exit;
				
        if(!empty($invoice_id)){
            $data['invoice_id'] = $invoice_id;//发票id
        }else {
            if (!empty($invoice_payee)) {
                $invoice_data = [
                    'invoice_payee' => $invoice_payee,
                    'type' => 0,
                    'uid' => $this->user_id
                ];
                $result = D('Home/Invoice')->data($invoice_data)->add();
                if ($result === false) {
                    $this->ajaxReturn($this->result->error('添加发票失败'));
                    exit;
                }
                $data['invoice_id'] = $result;
            }
        }
        $data['coupon_id'] = I('request.couponId',0);//优惠劵id
        // $data['integral'] = I('request.integral',0);//积分
        $data['source'] = I('request.source',1);//来源
        $result = D('Home/Order')->addOrder($data,$cart_id);
		
		$userName = D('Home/Order')->getUserName($uid);
		$arr = $userName->toArray();
        if($result->isSuccess()) {
            D('Home/Cart')->where(['cart_id'=>['in',$cart_id]])->delete();
            $result_content = $result->getResult();
            $is_pay = $result_content['order_amount']>0?1:0;
			
			if($arr['result']['toBind']=='toBind'){	//如果该用户用户名和手机号均不存在（老用户）,则去绑定页面
				$result->content(['orderId'=>$result_content['order_id'],'isPay'=>$is_pay,'toBind'=>'toBind']);
			}else{
				$result->content(['orderId'=>$result_content['order_id'],'isPay'=>$is_pay]);
			}
        }else{
			if($arr['result']['toBind']=='toBind'){	//如果该用户用户名和手机号均不存在（老用户）,则去绑定页面
				$result->content(['toBind'=>'toBind']);
			}
		}
        $this->ajaxReturn($result);
    }

    /**
     * 订单列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         keyword 关键字
     *         type 类型：0、全部；1、未支付；2、待收货；3、已完成；4、已取消；5、待发货 6:待评论
     *         currentPage 当前页
     *         count 获取当前页数据个数
     *         </code>
     */
    public function lists(){
        $keyword = I('post.keyword');
        $type = I('post.type',0);
        $where = [
            'uid'=>$this->user_id,
            'delete_time'=>['eq',0]
        ];
        $order_model = D('Admin/Order');
        if(!empty($keyword)){
            $viewFields = array (
                'OrderGoods' => array (
                    '_type' => 'LEFT'
                ),
                'Order' => array (
                    "order_sn",
                    'order_id',
                    'status',
                    '_as'=>'AsOrder',
                    '_on' => 'OrderGoods.order_id=AsOrder.order_id'
                ),
                'Goods' => array (
                    '_on' => 'OrderGoods.goods_id=Goods.goods_id'
                )
            );
            $order_sn = $order_model->dynamicView ( $viewFields )->where([
                'name'=>['like','%'.$keyword.'%']
            ])->group('order_id')->getField('order_sn',true);
            if(empty($order_sn)) {
                $where['order_sn'] = ['like', '%' . $keyword . '%'];
            }else {
                $map['order_sn'] = ['like', '%' . $keyword . '%'];
                $map['order_sn'] = ['in', $order_sn];
                $map['_logic'] = 'or';
                $where['_complex'] = $map;
            }
        }

        switch($type){
            case 0://全部
                break;
            case 1://未支付
                $where['pay_status'] = 0;
                $where['status'] = ['in',[0,1]];
                break;
            case 2://待收货
                $where['status'] = 1;
                $where['shipping_status'] = ['in',[4,1]];;
                break;
            case 3://已完成
                $where['status'] = 6;
                break;
            case 4://已取消
                $where['status'] = 2;
                break;
            case 5://待发货
                $where['pay_status'] = 2;
                $where['status'] = ['in',[0,1]];
                $where['shipping_status'] = 0;
                break;
            case 6:
            	$where['status'] = 6;
            	break;
        }
        $page_lists = $this->_lists ($order_model, $where ,'add_time desc');
        $result_data = [
            'lastPage'=>$page_lists['lastPage']
        ];
        $order_lists = $page_lists['data'] ;
        if($order_lists){
            $order_lists = $order_model->formatList($order_lists);
            $order_lists = array_filter(array_map(function($info) use($type) {
            	if($type == 6 && !$this->ifNotCommented($info['order_id'])){ //待评论时  至少某一商品为未评论
            		return null;
            	}elseif ($type == 6){ //除去已经被评论的商品
            		$tmp = [];
            		foreach ($info['goods'] as $k => $v){
            			if($v['goods_comment_status'] == 2){
            			}else{
            				$tmp[] = $v;
            			}
            		}
            		$info['goods'] = $tmp;
            	}
                $info = D('Home/Order')->formatStatus($info);
                return D('Api/Order')->formatLists($info);
            },$order_lists));
        }
        if(!$order_lists[0]){
        	$order_lists = array_combine (range(0, count($order_lists)-1),$order_lists);
        }
        if(!$order_lists){
        	$order_lists = [];
        }
		foreach($order_lists as $k => $v){
			$order_lists[$k]['recId'] = $v['goods'][0]['recId'];
		}
        $result_data['orderList'] = $order_lists;
        $this->ajaxReturn($this->result->content($result_data)->success());
    }
    //是否所有商品已评论过
	private function ifNotCommented($order_id){
		return M('order_goods')->where(['order_id' => $order_id,'goods_comment_status' =>1 ])->count();
	}
    /**
     * 账户余额支付
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         </code>
     */
    public function accountPay(){
        $order_id = I('post.orderId');
        M('Order')->where(['order_id'=>$order_id])->data(['pay_type'=>'ACCOUNT'])->save();
        $result = D('Home/Pay')->accountPay($order_id);
        $this->ajaxReturn($result);
    }

    /**
     * 移动支付
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         type 类型 0为订单，1为充值
     *         orderId 订单id
     *         amount 充值金额
     *         </code>
     */
    public function mobilePay(){
        $type = I('post.type',0,'intval');
        $pay_type = 'ALIPAY';
        switch($type){
            case 0:
                $order_id = I('post.orderId');
                $order_info = M('Order')->field(true)->where(['order_id'=>$order_id])->find();
                if(empty($order_info)){
                    $this->ajaxReturn($this->result->error('该订单不存在'));
                    exit;
                }

                //$pay_type = $order_info['pay_type'];
                if($order_info['pay_type'] != 'ALIPAY'){
                   // $this->ajaxReturn($this->result->error('支付方式有误'));
                   M('Order')->where(['order_id'=>$order_id])->save(['pay_type'=>'ALIPAY']);
                    //exit;
                }

                $order_id = $order_info['order_id'];
                $order_sn = $order_info['order_sn'];
                $amount = $order_info['order_amount'];
                break;
            case 1:
                $amount = I('post.amount');
                $balance_model = D('Home/Recharge');
                $balance_result = $balance_model->addRecord($amount,$this->user_id,SharedModel::SOURCE_MOBILE);
                if(!$balance_result->isSuccess()){
                    $this->ajaxReturn($this->result->error('支付出错'));
                    return;
                }
                $balance_info = $balance_result->getResult();
                $order_id = $balance_info['order_id'];
                $order_sn = $balance_info['order_sn'];
                break;
            default:
                $this->ajaxReturn($this->result->error('类型有误'));
                return;
        }

        $thinkpay_config = C('THINK_PAY.common');
        $thinkpay_config['sign_type'] = 'RSA';
        C('THINK_PAY.common',$thinkpay_config);
        $thinkpay = ThinkPay::getInstance($pay_type);
        $thinkpay->setProcess(ThinkPay::PROCESS_PAY)
            ->setMode(ThinkPay::MODE_STRING)
            ->setService('mobile');
        $pay_vo = new PayVo();
        $pay_vo->setFee($amount)
            ->setOrderNo($order_sn)
            ->setOrderId($order_id)
            //->setCallback('Cart/paysuccess?id='.$order_info['order_id'])
            ->setOrderType($type);//订单类型
        $pay_param = $thinkpay->submit($pay_vo);
        $this->ajaxReturn($this->result->content(['data'=>$pay_param])->success());
    }


    /**
     * 微信支付
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         openid openid
     *         </code>
     */
    public function wechatPay(){
        $order_id = I('post.orderId');
        $openid = I('post.openid');
        $trade_type = I('post.tradeType','JSAPI');
        $order_info = M('Order')->field(true)->where(['order_id'=>$order_id])->find();
        if(empty($order_info)){
            $this->ajaxReturn($this->result->error('该订单不存在'));
            exit;
        }
        $wechat_type = 'WEIXIN';
        if(strtoupper($trade_type)!='JSAPI'){
        	$wechat_type .= "#".$trade_type;
        }
        M('Order')->where(['order_id'=>$order_id])->data(['pay_type'=>$wechat_type])->save();
        /*$payment_info = D('Admin/Payment')->field(true)->where(['code'=>$order_info['pay_type']])->find();
        if(empty($payment_info)){
            $this->error('没有找到该支付方式');
            return;
        }*/
        $pay_vo = new PayVo();
        $result = $pay_vo->setFee($order_info['order_amount'])
            ->setOrderNo($order_info['order_sn'])
            ->setOrderId($order_info['order_id'])
            ->setOrderType(0)->record();//订单类型
        if($result === false){
            $this->ajaxReturn($this->result->error('添加订单记录失败'));
            exit;
        }

        $subject = $pay_vo->getTitle();
        if(empty($subject)){
            $subject = $pay_vo->getOrderNo();
        }

        $pay = new \Common\Org\Util\WechatPay ();
        $pay->unifiedParam = array (
            "body" =>$subject,
            "out_trade_no" => $pay_vo->getOrderNo(),
            "total_fee" => $pay_vo->getFee(),
            "notify_url" => U('Pay/wechatPaynotify',[],true,true),
            "trade_type" => $trade_type,
            "openid" => $openid
        );
        $returnData = $pay->unifiedOrder();
        if(is_string($returnData)){
        	$returnData = json_decode($pay->unifiedOrder(),true);
        }
        $returnData = $this->makeSign($trade_type, $returnData);
        $this->ajaxReturn($this->result->content(['data'=>$returnData])->success());
    }

    /**
     * 微信充值
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         amount 金额
     *         openid openid
     *         </code>
     */
    public function wechatRecharge(){
        $amount = I('post.amount');
        $openid = I('post.openid');
        $trade_type = I('post.tradeType','JSAPI');
        $balance_model = D('Home/Recharge');
        $balance_result = $balance_model->addRecord($amount,$this->user_id,SharedModel::SOURCE_WEIXIN);
        if(!$balance_result->isSuccess()){
            $this->ajaxReturn($this->result->error('支付出错'));
            return;
        }
        $balance_info = $balance_result->getResult();
        $order_id = $balance_info['order_id'];
        $order_sn = $balance_info['order_sn'];
        /*$payment_info = D('Admin/Payment')->field(true)->where(['code'=>$pay_type])->find();
        if(empty($payment_info)){
            $this->error('没有找到该支付方式');
            return;
        }*/
        $pay_vo = new PayVo();
        $result = $pay_vo->setFee($amount)
            ->setOrderNo($order_sn)
            ->setOrderId($order_id)
            ->setOrderType(1)->record();//订单类型
        if($result === false){
            $this->ajaxReturn($this->result->error('添加订单记录失败'));
            exit;
        }

        $subject = $pay_vo->getTitle();
        if(empty($subject)){
            $subject = $pay_vo->getOrderNo();
        }
        $pay = new \Common\Org\Util\WechatPay ();
        $pay->unifiedParam = array (
            "body" =>$subject,
            "out_trade_no" => $pay_vo->getOrderNo(),
            "total_fee" => $pay_vo->getFee(),
            "notify_url" => U('Pay/wechatPaynotify',[],true,true),
            "trade_type" => $trade_type,
            "openid" => $openid
        );
        $returnData = $pay->unifiedOrder();
        if(is_string($returnData)){
        	$returnData = json_decode($pay->unifiedOrder(),true);
        }
        $returnData = $this->makeSign($trade_type, $returnData);
        $this->ajaxReturn($this->result->content(['data'=>$returnData])->success());
    }
    
    
    public function makeSign($type,$return){
    	if(strtoupper($type)=='APP'){
    		require_once (VENDOR_PATH . "WechatPay/WxPay.JsApiPay.php");
    		$key = \WxPayConfig::$config['app']['KEY'];
// 			$sign = "appid=" . $return ['appid'] . "&noncestr=" . $return ['nonce_str'] . "&package=Sign=WXPay&partnerid=" . $return ['mch_id'] . "&prepayid=" . $return ['prepay_id'];
// 			$sign .= "&timestamp=" . time ()."&key=".$key;
// 			$sign = strtoupper(md5($sign));
// 			$return['sign'] = $sign;
            $return['key'] = base64_encode('jitujituan'.$key);
			return $return;
    	}
    	return $return;
    }
    
    /**
     * 取消订单
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         </code>
     */
    public function cancel(){
        $id = I('post.orderId');
        $result = D('Home/Order')->cancel($id);
        $this->ajaxReturn($result);
    }

    /**
     * 确认收货
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         </code>
     */
    public function receipt(){
        $id = I('post.orderId');
        $result = D('Home/Order')->receipt($id);
        $this->ajaxReturn($result);
    }

    /**
     * 删除订单
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         </code>
     */
    public function del(){
        $id = I('post.orderId');
        $result = D('Home/Order')->del($id);
        $this->ajaxReturn($result);
    }

    /**
     * 订单详情
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         orderId 订单id
     *         </code>
     */
    public function detail(){
        $order_id = I('post.orderId');
        $order_model = D('Admin/Order');
        $where = [
            'order_id' => $order_id,
            'uid' => $this->user_id
        ];
        $info = $order_model->viewModel ()->where ( $where )->find ();
        if(!empty($info)) {
            $info = D('Home/Order')->formatStatus($info);
            $info['goods'] = $order_model->getGoodsById ( $info['order_id'] );
            //配送方式
            switch($info['shipping_type']){
                case 0://自提
                case 2://送货上门
                    $info['stores'] = D('Stores/Stores')->where(['stores_id'=>$info['stores_id']])->find();
                    break;
                case 1://快递
                default:
                    break;
            }
            $info = D('Api/Order')->formatLists($info);

            $info['refundStatus'] = M('Refund')->where(['order_id' => $order_id])->getField('refund_status'); // 退货状态
        }
        $this->ajaxReturn($this->result->content($info)->success());
    }

}