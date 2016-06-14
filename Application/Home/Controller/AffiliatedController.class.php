<?php
/**
 * 附属信息
 * @author cwh
 * @date 2015-05-27
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;

class AffiliatedController extends HomeBaseController{

    /**
     * 获取提货信息列表
     */
    public function getdelivery(){
        $delivery_lists = D('Home/Delivery')->getLists($this->uid);
        $this->ajaxReturn($delivery_lists);
    }

    /**
     * 更新提货地址
     */
    public function updatedelivery(){
        $uid = $this->user_instance->isLogin();
        if(empty($uid)){
            $this->ajaxReturn($this->result->error('请先登录')->toArray());
        }
        $id = I('request.delivery_id');
        $delivery_model = D('Home/Delivery');
        $data = [
            'uid' => $uid,
            'stores_id'=>I('post.stores_id',0),
            'name' => I('post.name'),
            'mobile' => I('post.telephone')
        ];
        if(!empty($id)) {
            $where = [
                'delivery_id' => $id
            ];
            $result = $delivery_model->setData($where,$data);
        }else{
            $result = $delivery_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除提货信息
     */
    public function deldelivery(){
        $id = I('request.id');
        $delivery_model = D('Home/Delivery');
        $where = [
            'delivery_id' => $id
        ];
        $result = $delivery_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }


    /**
     * 获取发票列表
     */
    public function getinvoice(){
        $invoice_lists = D('Home/Invoice')->where(['uid'=>$this->uid])->select();
        $this->ajaxReturn($invoice_lists);
    }

    /**
     * 更新发票地址
     */
    public function updateinvoice(){
        $uid = $this->user_instance->isLogin();
        if(empty($uid)){
            $this->ajaxReturn($this->result->error('请先登录')->toArray());
        }
        $id = I('request.invoice_id');
        $invoice_model = D('Home/Invoice');
        $data = [
            'uid' => $uid,
            'type'=>I('post.type'),
            'invoice_payee' => I('post.invoice_payee'),
            'invoice_type' => I('post.invoice_type')
        ];
        if(!empty($id)) {
            $where = [
                'invoice_id' => $id
            ];
            $result = $invoice_model->setData($where,$data);
        }else{
            $result = $invoice_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 批量更新发票
     */
    public function batchupdateinvoice(){
        $invoice = I('post.invoice');
        $invoice_model = D('Home/Invoice');
        $invoice_lists = $invoice_model->where(['uid'=>$this->uid])->select();
        $exist_invoice_id = array_column($invoice_lists,'invoice_id');
        $invoice_lists = array_column($invoice_lists,'invoice_payee','invoice_id');

        $invoice_model->startTrans();
        $invoice_id = [];
        $sel_id = 0;
        foreach($invoice as $v){
            $id = $v['id'];
            if(!empty($id)) {
                $invoice_id[] = $id;
                if($invoice_lists[$id] != $v['val']){
                    $result = $invoice_model->where(['invoice_id'=>$invoice_id])->data(['invoice_payee'=>$v['val']])->save();
                    if($result=== false){
                        $invoice_model->rollback();
                        $this->ajaxReturn($this->result->error('保存发票失败')->toArray());
                    }
                }
            }else{
                $data = [
                    'invoice_payee'=>$v['val'],
                    'type'=>0,
                    'uid'=>$this->uid
                ];
                $result = $invoice_model->data($data)->add();
                if($result === false){
                    $invoice_model->rollback();
                    $this->ajaxReturn($this->result->error('保存发票失败')->toArray());
                }
                $id = $result;
            }

            if($v['sel'] == 1){
                $sel_id = $id;
            }
        }

        $del_invoice_id = array_diff($exist_invoice_id,$invoice_id);
        if(!empty($del_invoice_id)) {
            $result = $invoice_model->where(['invoice_id'=>['in',$del_invoice_id]])->delete();
            if($result === false){
                $invoice_model->rollback();
                $this->ajaxReturn($this->result->error('保存发票失败')->toArray());
            }
        }

        $invoice_model->commit();
        $this->ajaxReturn($this->result->content($sel_id)->success('保存发票成功')->toArray());
    }

    /**
     * 删除发票信息
     */
    public function delinvoice(){
        $id = I('request.id');
        $invoice_model = D('Home/Invoice');
        $where = [
            'invoice_id' => $id
        ];
        $result = $invoice_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 收货地址
     */
    public function getRecaddress(){
        $is_default = I('post.is_default',1);
        $order_money = I('post.order_money',0);//订单金额
        $order_weight = I('post.order_weight',0);//订单重量
        $order = [];
        if($is_default == 1){
            $order['is_default'] = 'desc';
        }
        $order['add_time'] = 'desc';
        $recaddress_lists = D('Home/ShippingAddress')->where(['uid'=>$this->uid])->order($order)->select();
        $freight_model = D('Admin/FreightTemplate');
        $freight_template = $freight_model->relation(true)->where(['ft_default_status'=>1])->find();
        foreach($recaddress_lists as $k=>$recaddress_v) {
            $recaddress_lists[$k]['shipment_price'] = $freight_model->freight($recaddress_v,$order_money,$order_weight,$freight_template);
        }
        $this->ajaxReturn($recaddress_lists);
    }

    /**
     * 更新收货地址
     */
    public function updateRecaddress(){
        $uid = $this->user_instance->isLogin();
        if(empty($uid)){
            $this->ajaxReturn($this->result->error('请先登录')->toArray());
        }
        $id = I('request.address_id');
        $address_model = D('Home/ShippingAddress');
        $localtion = I('request.provice').' '.I('request.city').' '.I('request.county');
        $data = [
            'uid' => $uid,
            'name' => I('request.name'),
            'mobile' => I('request.mobile'),
            'user_provice' => I('request.provice_id'),
            'user_city' => I('request.city_id'),
            'user_county' => I('request.county_id'),
            'user_localtion' => $localtion,
            'user_detail_address' => I('request.user_detail_address')
        ];
        if(!empty($id)) {
            $where = [
                'address_id' => $id
            ];
            $result = $address_model->setData($where,$data);
        }else{
            //验证地址不能超过12个
            $count = $address_model->where(['uid'=>$uid])->count();
            if($count>=12){
                $result = $this->result->error('收货地址最多只能添加12个');
            }else {
                $result = $address_model->addData($data);
            }
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除收货地址
     */
    public function delRecaddress(){
        $id = I('request.id');
        $address_model = D('Home/ShippingAddress');
        $where = [
            'address_id' => $id
        ];
        $result = $address_model->delData($where);
        $address_model->verifyDefault($this->uid);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 设置默认收货地址
     */
    public function setDefaultRecaddress(){
        $id = I('request.id');
        $address_model = D('Home/ShippingAddress');
        $result = $address_model->setDefault($this->uid,$id);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 获取与门店距离
     */
    public function getStoresDistance(){
        $address_id = I('request.address_id');
        $orderWeight = I('request.orderWeight',0);
        $address_model = D('Home/ShippingAddress');
        $result = $address_model->getStoresDistance($address_id,$orderWeight);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 获取门店列表
     */
    public function getStores(){
        $stores_model = D('Stores/Stores');
        //查询深圳地区的门店
        $city_id = '440300000000';
        $stores_lists = $stores_model->scope()->where(['city'=>$city_id])->field(true)->select();
        $county_ids = array_column($stores_lists,'county');
        $countys = M('PositionCounty')->where([
            'city_id'=>$city_id,
            'county_id'=>['in',$county_ids]
        ])->field('county_id,county_name')->select();
        $this->ajaxReturn($this->result->content(['stores'=>$stores_lists,'county'=>$countys])->success()->toArray());
    }

    /**
     * 获取门店自提地址时间
     */
    public function getStoresTime(){
        $date_week = [];
        $week_array = ["日","一","二","三","四","五","六"];
        for($i=0;$i<=6;$i++){
            $date = [];
            $strtotime = strtotime('+'.$i.' day');
            $date['strtotime'] = strtotime(date('Y-m-d',$strtotime));
            $date['week'] = $week_array[date("w",$strtotime)];
            $date['day'] = date('m-d',$strtotime);
            $date['year_day'] = date('Y-m-d',$strtotime);
            $date_week[] = $date;
        }
        $this->ajaxReturn($this->result->content($date_week)->success()->toArray());
    }

}