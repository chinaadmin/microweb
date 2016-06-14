<?php
namespace Api\Model;
class OrderModel extends ApiBaseModel{

    protected $autoCheckFields  =   false;

    /**
     * 格式化列表信息
     * @param array $data 列表数据
     * @return array
     */
    public function formatLists($data){
        $return_data = [];
        if(!empty($data['order_id'])) {//订单id
            $return_data['id'] = $data['order_id'];
        }
        $return_data['orderSn'] = $data['order_sn'];//订单号
        $return_data['statusName'] = $data['status_name'];//订单状态
        $return_data['status'] = $data['status'];//订单状态
        $return_data['btnPay'] = empty($data['btn_pay'])?0:1;//支付按钮
        $return_data['btnCancel'] = empty($data['btn_cancel'])?0:1;//取消按钮
        $return_data['btnReceipt'] = empty($data['btn_receipt'])?0:1;//确认收货按钮
        $return_data['btnDel'] = empty($data['btn_del'])?0:1;//删除订单按钮
        $return_data['btnShow'] = empty($data['btn_show'])?0:1;//查看详情按钮
        $return_data['btnReturns'] = empty($data['btn_returns'])?0:1;//退款退货按钮
        $return_data['addTime'] = date('Y-m-d H:i:s',$data['add_time']);//下单时间
        $return_data['price'] = $data['order_amount'];//总价
        $return_data['goods_amount'] = $data['goods_amount'];
        $return_data['couponPrice'] = $data['coupon_price'];//优惠劵价格
        $return_data['integralPrice'] = $data['integral_price'];//积分抵现
        $return_data['postscript'] = $data['postscript'];//买家附言

        $return_data['shipmentPrice'] = $data['shipment_price']; // 运费

        //配送方式
        if(isset($data['shipping_type'])){
            $return_data['shippingType'] = $data['shipping_type'];
            $return_data['receiving'] = $this->fomatReceiving($data);
            switch($data['shipping_type']){
                case 0://自提
                case 2://送货上门
                    if(!empty($data['stores'])){
                        //$return_data['delivery'] = $this->fomatDelivery($data);
                        $return_data['stores'] = $this->fomatStores($data);
                    }
                    break;
                case 1://快递
                default:
                    break;
            }
        }

        if(isset($data['needs_invoice'])){
            $return_data['needsInvoice'] = 0;
            if(!empty($data['needs_invoice'])) {
                $return_data['needsInvoice'] = 1;
                $return_data['invoice'] = $this->fomatInvoice($data);
            }
        }

        //商品信息
        if(!empty($data['goods'])){
            $return_data['goods'] = $this->formatGoods($data);
        }
        return $return_data;
    }

    /**
     * 格式化商品信息
     * @param array $order_info 订单数据
     * @return array
     */
    public function formatGoods($order_info){
        return array_map(function($info) use ($order_info){
            $goods_info = D('Api/Goods')->formatOrderGoods($info);
            $goods_info['recId'] = $info['rec_id'];
            $goods_info['number'] = $info['number'];
            $goods_info['isRefund'] = $info['refund_status'];//是否有退款
            if(isset($info['goods_comment_status'])){
            	$goods_info['comment_status'] = $info['goods_comment_status'];
            }
            if($info['refund_status']){ //已经申请了退款/退货
                $refund = M('refund')->where([
                    'order_id' => $order_info['order_id'],
                    'rec_id'=>$info['rec_id']
                ])->order('refund_time desc')->field('refund_id,refund_status')->find();
                $goods_info['refundStatus'] = $refund['refund_status'];
                $goods_info['refundId'] = $refund['refund_id'];
            }
            return $goods_info;
        },$order_info['goods']);
    }

    /**
     * 格式化门店信息
     * @param array $info 订单数据
     * @return array
     */
    public function fomatStores($info){
        $return_data = [];
        $return_data['name'] = $info['stores']['name'];//店名
        $return_data['mobile'] = $info['stores']['phone'];//门店联系电话
        $return_data['localtion'] = $info['stores']['localtion'];//省市区
        $return_data['address'] = $info['stores']['address'];//店铺详细地址
        if(!empty($info['stores_time'])) {
            $return_data['time'] = date('Y-m-d', $info['stores_time']);
        }
        return $return_data;
    }

    /**
     * 格式化订单收货信息
     * @param array $info 订单数据
     * @return array
     */
    public function fomatReceiving($info){
        $return_data = [];
        $return_data['username'] = $info['receipt_name'];//用户名
        $return_data['mobile'] = $info['receipt_mobile'];//手机号
        $return_data['localtion'] = $info['localtion'];//省市区
        $return_data['address'] = $info['address'];//详细地址
        return $return_data;
    }

    /**
     * 格式化订单提货信息
     * @param array $info 订单数据
     * @return array
     */
    public function fomatDelivery($info){
        $return_data = [];
        $return_data['username'] = $info['receipt_name'];//用户名
        $return_data['mobile'] = $info['receipt_mobile'];//手机号
        $return_data['storesName'] = $info['stores']['name'];//店名
        $return_data['localtion'] = $info['stores']['localtion'];//省市区
        $return_data['address'] = $info['stores']['address'];//店铺详细地址
        return $return_data;
    }

    /**
     * 格式化发票信息
     * @param array $info 订单数据
     * @return array
     */
    public function fomatInvoice($info){
        $return_data = [];
        $return_data['type'] = $info['invoice_type'];//类型：0为普通发票
        $return_data['invoicePayee'] = $info['invoice_payee'];//发票抬头
        return $return_data;
    }
}