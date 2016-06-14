<?php
namespace Api\Controller;
class AffiliatedController extends ApiBaseController {

    public function _initialize(){
        parent::_initialize();
        $this->authToken();
    }

    /**
     * 提货地址列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function getdelivery(){
        $delivery_lists = D('Home/Delivery')->getLists($this->user_id);
        $delivery_lists = array_map(function($info){
            return D('Api/Affiliated')->formatDeliveryLists($info);
        },$delivery_lists);
        $this->ajaxReturn($this->result->content(['deliveryList'=>$delivery_lists])->success());
    }

    /**
     * 新增提货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         name	 姓名
     *         mobile 手机号码
     *         storesId 门店id
     *         </code>
     */
    public function adddelivery(){
        $delivery_model = D('Home/Delivery');
        $data = [
            'uid' => $this->user_id,
            'stores_id'=>I('post.storesId',0),
            'name' => I('post.name'),
            'mobile' => I('post.mobile')
        ];
        $result = $delivery_model->addData($data);
        $this->ajaxReturn($result);
    }

    /**
     * 修改提货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         deliveryId 提货地址id
     *         name	 姓名
     *         mobile 手机号码
     *         storesId 门店id
     *         </code>
     */
    public function updatedelivery(){
        $id = I('request.deliveryId');
        $delivery_model = D('Home/Delivery');
        $data = [
            'uid' => $this->user_id,
            'stores_id'=>I('post.storesId',0),
            'name' => I('post.name'),
            'mobile' => I('post.mobile')
        ];
        $where = [
            'delivery_id' => $id
        ];
        $result = $delivery_model->setData($where,$data);
        $this->ajaxReturn($result);
    }

    /**
     * 删除提货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         deliveryId 提货地址id
     *         </code>
     */
    public function deldelivery(){
        $id = I('request.deliveryId');
        $delivery_model = D('Home/Delivery');
        $where = [
            'delivery_id' => $id
        ];
        $result = $delivery_model->delData($where);
        $this->ajaxReturn($result);
    }

    /**
     * 获取发票列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function getinvoice(){
        $invoice_lists = D('Home/Invoice')->where(['uid' => $this->user_id])->select();
        $invoice_lists = array_map(function ($info) {
            return D('Api/Affiliated')->formatInvoiceLists($info);
        }, $invoice_lists);
        $this->ajaxReturn($this->result->content(['invoiceList' => $invoice_lists])->success());
    }

    /**
     * 新增发票地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         type	 类型：0为普通发票
     *         invoiceType 发票类型
     *         invoicePayee 发票抬头
     *         </code>
     */
    public function addinvoice(){
        $invoice_model = D('Home/Invoice');
        $data = [
            'uid' => $this->user_id,
            'type'=>I('post.type'),
            'invoice_payee' => I('post.invoicePayee'),
            'invoice_type' => I('post.invoiceType')
        ];
        $result = $invoice_model->addData($data);
        $this->ajaxReturn($result);
    }

    /**
     * 更新发票地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         invoiceId 发票id
     *         type	 类型：0为普通发票
     *         invoiceType 发票类型
     *         invoicePayee 发票抬头
     *         </code>
     */
    public function updateinvoice(){
        $id = I('request.invoiceId');
        $invoice_model = D('Home/Invoice');
        $data = [
            'uid' => $this->user_id,
            'type'=>I('post.type'),
            'invoice_payee' => I('post.invoicePayee'),
            'invoice_type' => I('post.invoiceType')
        ];
        $where = [
            'invoice_id' => $id
        ];
        $result = $invoice_model->setData($where,$data);
        $this->ajaxReturn($result);
    }

    /**
     * 删除发票信息
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         invoiceId 发票id
     *         </code>
     */
    public function delinvoice(){
        $id = I('request.invoiceId');
        $invoice_model = D('Home/Invoice');
        $where = [
            'invoice_id' => $id
        ];
        $result = $invoice_model->delData($where);
        $this->ajaxReturn($result);
    }

    /**
     * 收货地址列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function getrecaddress(){
        $order = [];
        $order['is_default'] = 'desc';
        $order['add_time'] = 'desc';
        $recaddress_lists = D('Home/ShippingAddress')->where(['uid'=>$this->user_id])->order($order)->select();
        $recaddress_lists = array_map(function($info){
            return D('Api/Affiliated')->formatRecaddressLists($info);
        },$recaddress_lists);
        $this->ajaxReturn($this->result->content(['recaddressList'=>$recaddress_lists])->success());
    }

    /**
     * 新增收货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         name	 姓名
     *         mobile 手机号码
     *         provice 省id
     *         city 市id
     *         county 区id
     *         address 详细地址
     *         </code>
     */
    public function addrecaddress(){
        $recaddress_model = D('Home/ShippingAddress');
        $provice_id = I('request.provice');
        $city_id = I('request.city');
        $county_id = I('request.county');
        $localtion = D('Api/Affiliated')->getLocaltion($provice_id,$city_id,$county_id);
        $LatLon = $recaddress_model->getLatLonByAddress($localtion.I('request.address'));
        if($LatLon){
        	$LatLon = implode(',', $LatLon);
        }else{
        	$LatLon = '';
        }
        $data = [
            'uid' => $this->user_id,
            'name' => I('request.name'),
            'mobile' => I('request.mobile'),
            'user_provice' => $provice_id,
            'user_city' => $city_id,
            'user_county' => $county_id,
            'user_localtion' => $localtion,
            'user_detail_address' => I('request.address'),
        	'user_lat_lon' => $LatLon
        ];
        $result = $recaddress_model->addData($data);
        if($result->isSuccess()) {
            $is_default = I('request.isDefault');
            $address_id = $result->getResult();
            if($is_default == 1){
                $recaddress_model->setDefault($this->user_id,$address_id);
            }
            $result->content(['addressId'=>$address_id]);
        }
        $this->ajaxReturn($result);
    }

    /**
     * 修改收货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         addressId 收货地址id
     *         name	 姓名
     *         mobile 手机号码
     *         provice 省id
     *         city 市id
     *         county 区id
     *         address 详细地址
     *         </code>
     */
    public function updaterecaddress(){
        $id = I('request.addressId');
        $recaddress_model = D('Home/ShippingAddress');
        $provice_id = I('request.provice');
        $city_id = I('request.city');
        $county_id = I('request.county');
        $localtion = D('Api/Affiliated')->getLocaltion($provice_id,$city_id,$county_id);
        $LatLon = $recaddress_model->getLatLonByAddress($localtion.I('request.address'));
        if($LatLon){
        	$LatLon = implode(',', $LatLon);
        }else{
        	$LatLon = '';
        }
        $data = [
            'uid' => $this->user_id,
            'name' => I('request.name'),
            'mobile' => I('request.mobile'),
            'user_provice' => $provice_id,
            'user_city' => $city_id,
            'user_county' => $county_id,
            'user_localtion' => $localtion,
            'user_detail_address' => I('request.address'),
        	'user_lat_lon' => $LatLon	
        ];
        $where = [
            'address_id' => $id
        ];
        $result = $recaddress_model->setData($where,$data);
        if($result->isSuccess()) {
            $is_default = I('request.isDefault');
            if($is_default == 1){
                $recaddress_model->setDefault($this->user_id,$id);
            }
        }
        $this->ajaxReturn($result);
    }

    /**
     * 删除收货地址
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         addressId 收货地址id
     *         </code>
     */
    public function delrecaddress(){
        $id = I('request.addressId');
        $recaddress_model = D('Home/ShippingAddress');
        $where = [
            'address_id' => $id
        ];
        $result = $recaddress_model->delData($where);
        $this->ajaxReturn($result);
    }

    /**
     * 设置默认收货地址
     */
    public function setDefaultRecaddress(){
        $id = I('request.addressId');
        $address_model = D('Home/ShippingAddress');
        $result = $address_model->setDefault($this->user_id,$id);
        $this->ajaxReturn($result);
    }

    /**
     * 获取门店列表
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function getStores(){
        $stores_model = D('Stores/Stores');
        //查询深圳地区的门店
        $city_id = '440300000000';
        $stores_lists = $stores_model->scope()->where(['city'=>$city_id])->field(true)->select();
        $county_ids = array_column($stores_lists,'county');
        $stores_lists = array_map(function($info){
            return [
                'storesId'=>$info['stores_id'],
                'name'=>$info['name'],
                'countyId'=>$info['county'],
                'localtion'=>$info['localtion'],
                'address'=>$info['address'],
                'phone'=>$info['phone'],
                'amStart'=>$info['am_start_time'],
                'amEnd'=>$info['am_end_time'],
                'pmStart'=>$info['pm_start_time'],
                'pmEnd'=>$info['pm_end_time'],
            ];
        },$stores_lists);
        $countys = M('PositionCounty')->where([
            'city_id'=>$city_id,
            'county_id'=>['in',$county_ids]
        ])->field([
            'county_id',
            'county_name'
        ])->select();
        $countys = array_map(function($info){
            return [
                'countyId'=>$info['county_id'],
                'countyName'=>$info['county_name']
            ];
        },$countys);
        $this->ajaxReturn($this->result->content(['stores'=>$stores_lists,'county'=>$countys])->success());
    }

    /**
     * 获取送货上门
     * @author cwh
     *         传入参数:
     *         <code>
     *         addressId 收货地址id
     *         token token值
     *         </code>
     */
    public function getStoresDistance(){
        $address_id = I('request.addressId');
		$orderWeight = I('request.orderWeight',0,'floatval');
        $address_model = D('Home/ShippingAddress');
        $goodsIds = I('request.goodsId','','trim');
        if($goodsIds){
        	$orderWeight = D('Admin/Goods')->getGoodsWeight($goodsIds);
        }
        $result = $address_model->getStoresDistance($address_id,$orderWeight);
        $this->ajaxReturn($result);
    }
}