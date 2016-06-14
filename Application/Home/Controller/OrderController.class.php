<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;
use Common\Org\ThinkPay\PayVo;
use Common\Org\ThinkPay\ThinkPay;

class OrderController extends HomeBaseController {

    public function _initialize(){
        parent::_initialize();
        if(!$this->uid){
            redirect(U('Passport/login'));
        }
        $this->userSelCurrent();
    }

    /**
     * 用户选中样式
     */
    private function userSelCurrent($select = 'order'){
        $this->assign('userSelCurrent',$select);
    }

    /**
     * 订单列表
     */
    public function index(){
        $keyword = I('get.keyword');
        $this->assign('keyword',$keyword);
        $type = I('get.type',0);
        $this->assign('type',$type);
        $where = [
            'uid'=>$this->uid,
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
                break;
            case 3://已完成
                $where['status'] = 6;
                break;
            case 4://已取消
                $where['status'] = 2;
                break;
        }


        $order_lists = $this->lists ($order_model, $where ,'add_time desc');
        if($order_lists){
            $order_lists = $order_model->formatList($order_lists);
            $order_lists = array_map(function($info) {
            	foreach ($info['goods'] as &$v){
            		if($v['refund_status']){ //已经申请了退款/退货
            			$refund = M('refund')->where([
                            'order_id' => $info['order_id'],
                            'rec_id'=>$v['rec_id']
                        ])->order('refund_time desc')->field('refund_id,refund_status')->find();
                        $v['refund_real_status'] = $refund['refund_status'];
                        $v['refund_id'] = $refund['refund_id'];
            		}	
            	}
                return D('Home/Order')->formatStatus($info);
            },$order_lists);
        }
        //未支付  待收货数量
        $countArr = [];
        $countArr['noPayCount'] = $this->count(1);
        $countArr['waitingReceivingCount'] = $this->count(2);
        $this->countArr = $countArr;
        $this->assign('order_lists',$order_lists);
        $this->display();
    }

    /**
     * 数量
     */
    public function count($type = 0){
        $type = $type ? $type : I('get.type',0);
        $where = [
            'uid'=>$this->uid,
            'delete_time'=>['eq',0]
        ];
        switch($type){
            case 0://全部
                break;
            case 1://未支付
                $where['pay_status'] = 0;
                $where['status'] = ['in',[0,1]];
                break;
            case 2://待收货
                $where['status'] = 1;
                break;
            case 3://已完成
                $where['status'] = 6;
                break;
            case 4://已取消
                $where['status'] = 2;
                break;
        }
        $count = D('Admin/Order')->where($where)->count();
        if(!IS_AJAX){
        	return $count;
        }
        $result = $this->result->content($count)->success();
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 详细页面
     */
    public function view(){
        $order_id = I ( 'request.id');
        $order_model = D('Admin/Order');
        if ($order_id) {
            $where = array (
                'order_sn' => $order_id
            );
            $info = $order_model->viewModel ()->where ( $where )->find ();
            if($info['uid'] != $this->uid){
                $this->redirect('Order/index');
            }

            /*$status_name = $order_model->status[$info['status']];
            if($info['status']!=6){
                $status_name .= ' '.$order_model->pay_status[$info['pay_status']];
                $status_name .= ' '.$order_model->shipping_status[$info['shipping_status']];
            }
            $info['status_name'] = $status_name;*/
            $info = D('Home/Order')->formatStatus($info);
            $goods = $order_model->getGoodsById ( $info['order_id'] );
            $this->assign ( 'order_record', $order_model->getOrderAction ( $info['order_id'],0 ) );
            $this->assign ( "info", $info );
            $this->assign ( "goods", $goods );

            switch($info['shipping_type']){
                case 0://自提
                    $stores = D('Stores/Stores')->where(['stores_id'=>$info['stores_id']])->find();
                    $this->assign ( "stores", $stores );
                    break;
                case 1://快递

                    break;
                case 2://送货上门

                    break;
                default:
                    break;
            }
        }
        $this->display();
    }

    /**
     * 下单
     */
    public function single(){
        $cart_model = D('Home/Cart');
        $cart_id = $cart_model->cacheSelectItem();
        $data = I('post.');

        switch($data['shipping_type']){
            case 'express_delivery'://普通快递
                $data['shipping_type'] = 1;
                break;
            case 'visit_delivery'://送货上门
                $data['shipping_type'] = 2;
                break;
            case 'from_mentioning'://门店自提
                $data['shipping_type'] = 0;
                break;
        }
        $data['source'] = SharedModel::SOURCE_PC;//来源pc
        $result = D('Home/Order')->addOrder($data,$cart_id);
        if($result->isSuccess()) {
            D('Home/Cart')->where(['cart_id' => ['in', $cart_id]])->delete();
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 去支付
     */
    public function gopay(){
        $id = I('get.id');
        $order_info = M('Order')->field(true)->where(['order_id'=>$id])->find();
        $this->assign('info',$order_info);

        //支付方式
        $this->assign('payment_lists',D('Admin/Payment')->getLists());
        $this->display();
    }

    /**
     * 支付
     */
    public function pay(){
        $id = I('request.id');
        $pay_type = I('request.pay_type');
        $order_model = M('Order');
        if(!empty($pay_type)) {
            $order_model->where(['order_id' => $id])->data(['pay_type' => $pay_type])->save();
        }
        $order_info = $order_model->field(true)->where(['order_id'=>$id])->find();
        $pay_type = $order_info['pay_type'];
        $payment_info = D('Admin/Payment')->field(true)->where(['code' => $pay_type])->find();
        if (empty($payment_info)) {
            $this->error('没有找到该支付方式');
            return;
        }
        switch($payment_info['type']){
            case 0://支付平台
                $thinkpay = ThinkPay::getInstance($order_info['pay_type']);
                break;
            case 1://网银支付
                $pay_type = explode('#',$order_info['pay_type']);
                $thinkpay = ThinkPay::getInstance($pay_type[0]);
                $thinkpay->setBankPay($pay_type[1]);
                break;
            default:
                $this->error('支付出错');
                return;
        }
        $thinkpay->setProcess(ThinkPay::PROCESS_PAY);
        $pay_vo = new PayVo();
        $pay_vo->setFee($order_info['order_amount'])
                ->setOrderNo($order_info['order_sn'])
                ->setOrderId($order_info['order_id'])
                ->setCallback('Cart/paysuccess?id='.$order_info['order_id'])
                ->setOrderType(0);//订单类型
        echo $thinkpay->submit($pay_vo);
    }

    /**
     * 充值
     */
    public function recharge(){
        $pay_type = I('get.pay_type');
        $amount = I('get.amount');
        $balance_model = D('Home/Recharge');
        $balance_result = $balance_model->addRecord($amount);
        if(!$balance_result->isSuccess()){
            $this->error('支付出错');
            return;
        }
        $balance_info = $balance_result->getResult();
        $order_id = $balance_info['order_id'];
        $order_sn = $balance_info['order_sn'];
        $payment_info = D('Admin/Payment')->field(true)->where(['code'=>$pay_type])->find();
        if(empty($payment_info)){
            $this->error('没有找到该支付方式');
            return;
        }
        switch($payment_info['type']){
            case 0://支付平台
                $thinkpay = ThinkPay::getInstance($pay_type);
                break;
            case 1://网银支付
                $pay_type_arr = explode('#',$pay_type);
                $thinkpay = ThinkPay::getInstance($pay_type_arr[0]);
                $thinkpay->setBankPay($pay_type_arr[1]);
                break;
            default:
                $this->error('支付出错');
                return;
        }
        $thinkpay->setProcess(ThinkPay::PROCESS_PAY);
        $pay_vo = new PayVo();
        $pay_vo->setFee($amount)
            ->setOrderNo($order_id)
            ->setOrderId($order_sn)
            ->setCallback('Cart/paysuccess?id='.$order_id)
            ->setOrderType(1);//充值类型
        echo $thinkpay->submit($pay_vo);
    }

    /**
     * 取消订单
     */
    public function cancel(){
        $id = I('get.id');
        $result = D('Home/Order')->cancel($id);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 确认收货
     */
    public function receipt(){
        $id = I('get.id');
        $result = D('Home/Order')->receipt($id);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除订单
     */
    public function del(){
        $id = I('get.id');
        $result = D('Home/Order')->del($id);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 物流信息
     */
    public function logistics(){
        $id = I('request.id');
        $order_model = D ('Admin/Order');
        $order_info = $order_model->field(true)->where(['order_sn'=>$id])->find();
        $this->assign('info',$order_info);
        $order_id = $order_info['order_id'];

        $order_receipt = M('OrderReceipt')->where(['order_id'=>$order_id])->field(true)->find();
        $this->assign('order_receipt',$order_receipt);

        //配送方式
        switch($order_info['shipping_type']){
            case 0://0为自提
            case 2://2送货上门
                $stores = D('Stores/Stores')->getStoresById($order_info['stores_id']);
                $this->assign('stores',$stores);
                break;
            case 1://1为快递
                //发货记录
                if(!empty($order_info['shipping_status'])) {
                    $viewFields = [
                        "order_send" => array(
                            'send_sn', //发货编号
                            'send_num',
                            '_as' => 'orSe',
                            '_type' => 'LEFT'
                        ),
                        "logistics_company" => array(
                            'lc_name',
                            'lc_code',
                            'lc_tel',
                            '_as' => 'lc',
                            "_on" => "lc.lc_id = orSe.logistics"
                        )
                    ];
                    $where = ['order_id' => $order_id];
                    $delivery_record = $order_model->dynamicView($viewFields)->where($where)->find();
                    $this->assign('delivery_record',$delivery_record);
                }

                $where = [];
                $where['se_company'] = $delivery_record['lc_code'];
                $where['se_express_bill'] = $delivery_record['send_num'];
                $logistics_following['header'] = M('subscribe_express')->where($where)->find();
                $logistics_following['main'] = D('Logistics/SubscribeExpress')->getSubscribeView()->where($where)->select();
                $week_arr = ["周日","周一","周二","周三","周四","周五","周六"];
                foreach ($logistics_following['main'] as $key => &$v){
                    $v['add_time_formate'] = date('Y-m-d',$v['ses_add_time']);
                    $v['add_time_formate'] .= $week_arr[date('w',$v['ses_add_time'])];
                    if($key != 0){
                        $preArr = $logistics_following['main'][$key - 1];
                        $preTmp = date('Y-m-d',$preArr['ses_add_time']).$week_arr[date('w',$preArr['ses_add_time'])];
                        if($v['add_time_formate'] == $preTmp){
                            $v['add_time_formate'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                    }
                    //$v['add_time_formate'] .= '&nbsp;&nbsp;'.date('H:i:s',$v['ses_add_time']);
                    $v['add_time_formate_data'] = date('H:i:s',$v['ses_add_time']);
                }
                $this->assign('logistics_following',$logistics_following);
                break;
        }

        $this->display();
    }

}