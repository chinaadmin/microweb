<?php
/**
 * 有赞api逻辑类
 * @author cwh
 * @date 2015-05-05
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\SharedModel;

class YouzanApiController extends AdminbaseController {

    public  $no_auth_actions = ['index','user'];

    /**
     * 获取有赞api客户端
     * @return KdtApiClient
     */
    private function getClient(){
        Vendor('Youzan.KdtApiClient');
        //$appId = '3aebe40aed163b0db7';
        //$appSecret = '9efda3ca564b4f36b79391a69e0cde3f';
//         $appId = 'dd52e8b78741a7df6c';
//         $appSecret = '3ae6d46675df986129ffb5f32726abc8';
        $appId = '2dcce858ca9812748c';
        $appSecret = '321cfbe50a99a12a11e53781ae646601';
        return new \KdtApiClient($appId, $appSecret);
    }

    public function index() {
        $this->redirect('user');
    }

    public function user(){
        $page_no = I('request.page_no',1);
        $page_size = 10;
        $client = $this->getClient();
        $method = 'kdt.users.weixin.followers.get';
        $params = [
            'page_no'=>$page_no,
            'page_size'=>$page_size
        ];
        $result = $client->post($method, $params,[]);
        if(!empty($result['error_response'])){
            $this->ajaxReturn('导入出错，错误编码'.$result['error_response']['code'], 'EVAL');
        }
        $result = $result['response'];
        $users = $result['users'];
        foreach($users as $user){
            $this->addUser($user);
        }
        $total_results = $result['total_results'];
        if($total_results - $page_size*$page_no>0){
            $this->success('导入用户数据第'.$page_no.'页成功',U('',['page_no'=>$page_no+1]));
        }else {
            $this->ajaxReturn('用户数据已经全部导完', 'EVAL');
        }
    }

    /**
     * 添加第三方用户
     * @param $data
     */
    private function addUser($data){
        $oauth_model = D('Home/Oauth');
        $where = [
            'type'=>'wechat',
            'openid'=>$data['weixin_openid']
        ];
        $oauth_info = $oauth_model->where($where)->field(true)->find();
        if(!empty($oauth_info)){
            if(!empty($oauth_info['yzid'])){
                return ;
            }
            $oauth_model->where($where)->data(['yzid'=>$data['user_id']])->save();
            return;
        }

        //生成账号
        $user_data = [
            'pass'=>'123456',
            'status'=>1,
            'grade_id'=>D("User/UserGrade")->getDefaultGrade(),//用户等级
            'account_status'=>1,
            'aliasname'=> $data['nick']//昵称
        ];
        if($data['avatar']){   //下载第三方头像
            $pic = D("Home/Oauth")->userPic($data['avatar']);
            $user_data['headAttr'] = $pic['att_id'];
        }

        $user_data['aliasname'] = empty($user_data['aliasname'])?"BHH".rand_string(8,1):$user_data['aliasname'];
        $user_data['come_from'] = SharedModel::SOURCE_WEIXIN;//来源
        $result = D('User/User')->addData($user_data);
        $uid = $result->getResult();

        $oauth_model->add([
            'uid'=>$uid,
            'nick'=>$data['nick'],
            'pic'=>$data['avatar'],
            'openid'=>$data['weixin_openid'],
            'add_time'=>NOW_TIME,
            'type'=>'wechat',
            'yzid'=>$data['user_id']
        ]);
        return;
    }

    public function order(){
        $page_no = I('request.page_no',1);
        $page_size = 20;
        $client = $this->getClient();
        $method = 'kdt.trades.sold.get';
        $params = [
            'page_no'=>$page_no,
            'page_size'=>$page_size
        ];
        $result = $client->post($method, $params,[]);
        if(!empty($result['error_response'])){
            $this->ajaxReturn('导入出错，错误编码'.$result['error_response']['code'], 'EVAL');
        }

        $result = $result['response'];
        //header("Content-type:text/html;charset=utf-8");
        $trades = $result['trades'];
        foreach($trades as $order){
            $this->addOrder($order);
        }
        $total_results = $result['total_results'];
        if($total_results - $page_size*$page_no>0){
            $this->success('导入订单数据第'.$page_no.'页成功',U('',['page_no'=>$page_no+1]));
        }else {
            $this->ajaxReturn('用户订单已经全部导完', 'EVAL');
        }
    }

    /**
     * 添加订单
     * @param $order
     * @return bool
     */
    public function addOrder($order){
        $order_model = D('Home/Order');
        $order_info = $order_model->where(['yzid'=>$order['tid']])->find();
        if(!empty($order_info)){
            return true;
        }

        $uid = D('Home/Oauth')->where(['yzid'=>$order['weixin_user_id']])->getField('uid');
        if(empty($uid)){
            return false;
        }

        $order_model->startTrans();
        $order_id = $order_model->orderid();
        $order_sn = $order_model->ordersn();

        //物流方式
        $shipping_type = 0;
        switch($order['shipping_type']){
            case 'express'://快递
                $shipping_type = 1;
                break;
            case 'fetch'://到店自提
                $shipping_type = 0;
                break;
        }

        $add_time = empty($order['created'])?0:tomktime($order['created'],true);
        $order_data = [
            'order_id'=>$order_id,//订单id
            'order_sn'=>$order_sn,//订单号
            'uid'=>$uid,//用户id
            'shipping_type'=>$shipping_type,//配送方式：0为自提，1为快递，2门店发货
            'postscript'=>$order['buyer_message'],//订单附言
            'seller_postscript'=>$order['trade_memo'],//卖家附言
            'money_paid'=>$order['payment'],//已付款金额，即实际付款金额
            'goods_amount'=>$order['total_fee'],//商品的总金额
            'order_amount'=>$order['payment'],//应付款金额~~#######???????????
            'credits'=>0,//获得积分
            'add_time'=>$add_time,//添加时间
            'confirm_time'=>'',//订单确认时间
            'pay_time'=>empty($order['pay_time'])?0:tomktime($order['pay_time'],true),//订单支付时间
            'shipping_time'=>empty($order['consign_time'])?0:tomktime($order['consign_time'],true),//订单配送时间
            'pay_type'=>$order['pay_type'],//支付方式
            'needs_invoice'=>0,//是否需要发票:0为否，1为是
            'invoice_id'=>0,//发票id
            'order_discount'=>10,//会员折扣
            'discount_price'=>0,//折扣优惠
            'receiving_time'=>empty($order['sign_time'])?0:tomktime($order['sign_time'],true),//收货时间(用户收货时间)
            //'coupon_id'=>0,//优惠劵id~~
            //'coupon_price'=>0,//优惠卷金额~~
            'integral'=>0,//使用积分
            'integral_price'=>0,//积分抵现
            'balance'=>0,//余额
            'shipment_price'=>$order['post_fee'],//运费
            'source'=>SharedModel::SOURCE_WEIXIN,//订单来源
            'yzid'=>$order['tid'],//有赞关联id
        ];

        //优惠劵
        $coupon_list = $order['coupon_details'];
        $coupon_price = 0;
        foreach ($coupon_list as $v) {
            $coupon_price += $v['discount_fee'];
        }
        $order_data['coupon_price'] = $coupon_price;

        //状态
        switch($order['status']){
            case 'TRADE_NO_CREATE_PAY'://没有创建支付交易
            case 'WAIT_BUYER_PAY'://等待买家付款
                $order_data['shipping_status'] = 0;//商品配送状态;0未发货,1已发货,2已收货,3退货
                $order_data['pay_status'] = 0;//支付状态;0未付款;1付款中;2已付款;3已退款
                $order_data['status'] = 0;//订单的状态;0未确认,1确认,2已取消,3无效,4退货,5已过期,6已完成
                break;
            case 'WAIT_SELLER_SEND_GOODS'://等待卖家发货，即：买家已付款
                $order_data['shipping_status'] = 0;
                $order_data['pay_status'] = 2;
                $order_data['status'] = 0;
                break;
            case 'WAIT_BUYER_CONFIRM_GOODS'://等待买家确认收货，即：卖家已发货
                $order_data['shipping_status'] = 1;
                $order_data['pay_status'] = 2;
                $order_data['status'] = 1;
                break;
            case 'TRADE_BUYER_SIGNED'://买家已签收
                $order_data['shipping_status'] = 2;
                $order_data['pay_status'] = 2;
                $order_data['status'] = 6;
                break;
            case 'TRADE_CLOSED'://付款以后用户退款成功，交易自动关闭
                $order_data['shipping_status'] = 3;
                $order_data['pay_status'] = 3;
                $order_data['status'] = 4;
                break;
            case 'TRADE_CLOSED_BY_USER'://付款以前，卖家或买家主动关闭交易
                $order_data['shipping_status'] = 0;
                $order_data['pay_status'] = 0;
                $order_data['status'] = 2;
                break;
        }

        //退款
        switch($order['refund_state']){
            case 'NO_REFUND'://无退款
                break;
            case 'PARTIAL_REFUNDING'://部分退款中
                break;
            case 'PARTIAL_REFUNDED'://已部分退款
                break;
            case 'PARTIAL_REFUND_FAILED'://部分退款失败
                break;
            case 'FULL_REFUNDING'://全额退款中
                break;
            case 'FULL_REFUNDED'://已全额退款
                break;
            case 'FULL_REFUND_FAILED'://全额退款失败
                break;
        }

        //门店自取
        if($shipping_type == 0){
            $stores_id = M('Stores')->where(['name'=>$order['fetch_detail']['shop_name']])->getField('stores_id');
            if(empty($stores_id)) {
                E('缺少门店:' . $order['fetch_detail']['shop_name']);
                return false;
            }
            $order_data['stores_id'] = $stores_id;
            $order_data['stores_time'] = empty($order['fetch_detail']['fetch_time'])?0:tomktime($order['fetch_detail']['fetch_time'],true);
        }

        $order_result = M('Order')->add($order_data);
        if($order_result===false){
            $order_model->rollback();
            return false;
        }

        $provice = M('PositionProvice')->where([
            'provice_name'=>['like','%'.$order['receiver_state'].'%']
        ])->field(true)->find();
        $city = M('PositionCity')->where([
            'city_name'=>['like','%'.$order['receiver_city'].'%'],
            'province_id'=>$provice['provice_id']
        ])->field(true)->find();
        $county = M('PositionCounty')->where([
            'county_name'=>['like','%'.$order['receiver_district'].'%'],
            'city_id'=>$city['city_id']
        ])->field(true)->find();

        //收货信息
        $order_receipt_data = [
            'order_id'=>$order_id,//订单id
            'province'=>empty($provice['provice_id'])?0:$provice['provice_id'],//收货人省份
            'city'=>empty($city['city_id'])?0:$city['city_id'],//收货人城市
            'county'=>empty($county['county_id'])?0:$county['county_id'],//收货人地区
            'localtion'=>$provice['provice_name'].' '.$city['city_name'].' '.$county['county_name'],//省市区
            'address'=>$order['receiver_address'],//收货人详细地址
            'zipcode'=>$order['receiver_zip'],//收货人邮编
            'mobile'=>$order['receiver_mobile'],//收货人手机
            'name'=>$order['receiver_name'],//收货人姓名
        ];
        $order_receipt_result = M('OrderReceipt')->add($order_receipt_data);
        if($order_receipt_result===false){
            $order_model->rollback();
            return false;
        }

        //用户收货地址添加
        $shipping_address_data = [
            'uid'=>$uid,
            'name'=>$order_receipt_data['name'],
            'mobile'=>$order_receipt_data['mobile'],
            'user_provice'=>$order_receipt_data['province'],
            'user_city'=>$order_receipt_data['city'],
            'user_county'=>$order_receipt_data['county'],
            'user_localtion'=>$order_receipt_data['localtion'],
            'user_detail_address'=>$order_receipt_data['address'],
        ];
        $shipping_address_model = M('UserShippingAddress');
        $shipping_address_result = $shipping_address_model->where($shipping_address_data)->find();
        if(empty($shipping_address_result)){
            if($shipping_address_model->count() == 0){
                $shipping_address_data['is_default'] = 1;
            }
            $shipping_address_data['add_time'] = NOW_TIME;
            $shipping_address_model->add($shipping_address_data);
        }

        //订单商品信息
        $order_goods_all_data = [];
        $all_number = 0;
        $goods_model = D('Admin/Goods');
        foreach($order['orders'] as $v) {
            $goods_info = $goods_model->where(['yzid'=>$v['num_iid']])->field(true)->find();
            if(empty($goods_info)){
                $order_model->rollback();
                if($v['title']=='七夕首单返现活动奖励，再接再励哦噢~'){
                    return true;
                }
                E('缺少有赞商品:'.$v['num_iid'].'['.$v['title'].']'.'价格:'.$v['price']);
                return false;
            }
            //$goods_info = $goods_model->where(['goods_id'=>56])->field(true)->find();

            $norms_return = $goods_model->getSpecificNorms($goods_info['goods_id']);
            if(!$norms_return->isSuccess()){
                $order_model->rollback();
                return false;
            }

            $norms_info = $norms_return->getResult();
            $norms_value = [];
            $norms = '';
            if(empty($norms_info)) {
                $norms_value = $norms_info['norms'];
                $norms_attr = 0;
                $norms_ids = [];
                foreach($norms_value as $v){
                    $norms_ids[] = $v['id'];
                    if(!empty($v['photo'])) {
                        $norms_attr = $v['photo'];
                    }
                }
                $norms = implode('_',$norms_ids);
            }

            $order_goods_data = [
                'rec_id'=>$order_model->orderid(),
                'order_id'=>$order_id,
                'goods_id'=>$goods_info['goods_id'],
                'goods_code'=>$goods_info['code'],
                'market_price'=>$goods_info['old_price'],
                'goods_price'=>$v['price'],
                'goods_credits'=>$goods_info['integral'],
                'number'=>$v['num'],
                'norms'=>$norms,
                'norms_attr'=>empty($norms_attr)?0:$norms_attr,
                'norms_value'=>json_encode($norms_value)
            ];
            $order_goods_all_data[] = $order_goods_data;
            $all_number += $v['num'];

            //库存变更
            /*$chengs_stock = D('Admin/Goods')->changeStock($order_goods_data['goods_id'],$order_goods_data['norms'],-($order_goods_data['number']));
            if(!$chengs_stock->isSuccess()){
                $this->rollback();
                return $result->error('库存不足');
            }*/

            //统计商品下订单数
            M("GoodsStatistics")->where(['goods_id'=>$goods_info['goods_id']])->setInc('orders',$v['num']);
        }

        if(M('OrderGoods')->addAll($order_goods_all_data) === false){
            $order_model->rollback();
            return false;
        }

        //用户统计数据
        $user_analysis_model = M('UserAnalysis');
        $analysis_where = [
            'uid'=>$uid
        ];
        $user_analysis = $user_analysis_model->where($analysis_where)->field(true)->find();

        $analysis_data = [];
        if(empty($user_analysis['order_count'])){
            $analysis_data['first_buy_time'] = $add_time;//第一次下单时间
            $analysis_data['last_buy_time'] = $add_time;//最后一次下单时间
        }
        $analysis_data['order_count'] = (int)$user_analysis['order_count']+1;//订单总数
        $analysis_data['order_goods_count'] = (int)$user_analysis['order_goods_count'] + $all_number;//商品总数

        if($user_analysis_model->where($analysis_where)->data($analysis_data)->save() === false){
            $order_model->rollback();
            return false;
        }

        $order_model->commit();
        return true;
    }

}