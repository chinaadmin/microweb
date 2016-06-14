<?php
namespace Api\Model;
class AffiliatedModel extends ApiBaseModel{

    protected $autoCheckFields  =   false;

    /**
     * 获取省市区名称
     * @param int $provice_id 省id
     * @param int $city_id 市id
     * @param int $county_id 区id
     * @return string
     */
    public function getLocaltion($provice_id,$city_id,$county_id){
        $provice_name = M('PositionProvice')->where(['provice_id'=>$provice_id])->getField('provice_name');
        $city_name = M('PositionCity')->where(['city_id'=>$city_id])->getField('city_name');
        $county_name = M('PositionCounty')->where(['county_id'=>$county_id])->getField('county_name');
        return $provice_name.' '.$city_name.' '.$county_name;
    }

    /**
     * 格式化提货列表信息
     * @param array $data 列表数据
     * @return array
     */
    public function formatDeliveryLists($data){
        $return_data = [];
        $return_data['id'] = $data['delivery_id'];//提货地址id
        $return_data['username'] = $data['username'];//用户名
        $return_data['mobile'] = $data['mobile'];//手机号
        $return_data['storesId'] = $data['stores_id'];//门店id
        $return_data['storesName'] = $data['name'];//店名
        $return_data['localtion'] = $data['localtion'];//省市区
        $return_data['address'] = $data['address'];//店铺详细地址
        return $return_data;
    }

    /**
     * 格式化收货列表信息
     * @param array $data 列表数据
     * @return array
     */
    public function formatRecaddressLists($data){
        $return_data = [];
        $return_data['id'] = $data['address_id'];//收货地址id
        $return_data['name'] = $data['name'];//用户名
        $return_data['mobile'] = $data['mobile'];//手机号
        $return_data['isDefault'] = $data['is_default'];//是否默认地址
        $return_data['provice'] = $data['user_provice'];//省id
        $return_data['city'] = $data['user_city'];//市id
        $return_data['county'] = $data['user_county'];//区id
        $return_data['localtion'] = $data['user_localtion'];//省市区
        $return_data['address'] = $data['user_detail_address'];//店铺详细地址
        return $return_data;
    }

    /**
     * 格式化发票列表信息
     * @param array $data 列表数据
     * @return array
     */
    public function formatInvoiceLists($data){
        $return_data = [];
        $return_data['id'] = $data['invoice_id'];//发票id
        $return_data['type'] = $data['type'];//类型：0为普通发票
        $return_data['invoicePayee'] = $data['invoice_payee'];//发票抬头
        $return_data['invoiceType'] = $data['invoice_type'];//发票类型
        return $return_data;
    }

}