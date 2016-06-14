<?php
/**
 * 用户收货地址模型
 * @author cwh
 * @date 2015-07-29
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
use Common\Org\Util\BaiduMap;

class ShippingAddressModel extends HomebaseModel{

    protected $tableName = 'user_shipping_address';

    public $_validate = [
        ['name', 'require', '发收货人姓名不能为空'],
        ['mobile', 'require', '手机号码不能为空']
    ];

    public $_auto = [
        ['add_time', 'time', self::MODEL_INSERT, 'function']
    ];

    /**
     * 设置默认
     * @param string $uid 用户id
     * @param null $id 用户收货地址id
     * @return bool
     */
    public function setDefault($uid, $id = null){
        $address_info = $this->where(['address_id' => $id])->field(true)->find();
        if(!empty($address_info) && $address_info['is_default'] != 1) {
            $this->where(['uid' => $uid])->data(['is_default' => 0])->save();
            $this->where(['address_id' => $id])->data(['is_default' => 1])->save();
        }
        return $this->result()->success('设为默认成功');
    }

    /**
     * 验证默认值
     * @param string $uid 用户id
     * @return bool
     */
    public function verifyDefault($uid){
        $count = $this->where(['uid' => $uid, 'is_default' => 1])->count();
        if(empty($count)){
            $this->where(['uid' => $uid])->data(['is_default' => 1])->order('add_time desc')->limit(1)->save();
        }
        return $this->result()->success();
    }

    /**
     * 获取指定范围内的门店id
     * @param int $address_id 收货地址id
     * @param int $goods_weight 订单商品重量g
     * @return $this
     */
    public function getStoresDistance($address_id,$goods_weight = 0){
    	if($goods_weight){
    		$goods_weight = $goods_weight;
    	}
        $freight_info = D('Admin/FreightTemplate')->getDefaultInfo();
        if(empty($freight_info) ||empty($freight_info['ft_home_delivery_status'])  //是否开启送货上门
        ){
            return $this->result()->content(['stores_id'=>0,'shipment_price'=>0])->success();
        }
        $delivery_elt_distance = $freight_info['ft_home_delivery_elt_distance'];//送货上门多少距离以内（km）包邮

        $stores_model = D('Stores/Stores');
        $stores_lists = $stores_model->field(true)->scope()->select();
        $shipping_address = D('Home/ShippingAddress')->field(true)->where(['address_id'=>$address_id])->find();
        $baidu_map = new BaiduMap();
        $allowStores = [];
        $freeAllowStores = [];//500m 免费配送
        $free_stores_id = 0;
        foreach($stores_lists as $v){
            //不在同一个区的地址先排除
            if($shipping_address['user_county'] !== $v['county']){
                continue;
            }
            $origins = $shipping_address['user_localtion'].' '.$shipping_address['user_detail_address'];
            $destinations = $v['localtion'].' '.$v['address'];
            if($origins === $destinations){
                $free_stores_id = $v['stores_id'];
                break;
            }
            //获取最近的店start
            $this->getInnerStore($baidu_map,$address_id,$shipping_address,$v,0.5,$freeAllowStores);//500m
            $this->getInnerStore($baidu_map,$address_id,$shipping_address,$v,$delivery_elt_distance,$allowStores);
            //获取最近的店end
        }
        //门店与运费计算start
        if(!$free_stores_id){
        	$stores_id = array_search(min($allowStores), $freeAllowStores);
        	$shipment_price = 0;//500m免费配送
        	if(!$stores_id){
	        	$stores_id = array_search(min($allowStores), $allowStores);
	        	$shipment_price = $this->innerLongShipmentPrice($goods_weight,$freight_info);//超过500m 门店运费计算
        	}
        }else{
        	$shipment_price = 0;//同一地址
        	$stores_id = $free_stores_id;
        }
        //门店与运费计算end        
        return $this->result()->content(['stores_id'=>$stores_id ? $stores_id : 0 ,'shipment_price'=>$shipment_price])->success();
    }
    //获取符合范围的门店
    private function getInnerStore($baidu_map,$address_id,$originsV,$destinationsV,$maxDistance,&$allowStores){
    	if(!$originsV['user_lat_lon']){ //没有用户经纬信息重新查询一次
    		$address = $originsV['user_localtion'].' '.$originsV['user_detail_address'];
    		$originsLonLat = $baidu_map->getLngLatByAddress($address);
    		if(!$originsLonLat){ //查询不到经纬
    			$originsLonLat = $baidu_map->getLngLatByAddress($originsV['user_detail_address']);
    			if(!$originsLonLat){
    				return;
    			}
    		}
    		$originsLonLatStr = implode(',', $originsLonLat);
    		D('Home/ShippingAddress')->save(['address_id' => $address_id,'user_lat_lon' => $originsLonLatStr]);
    	}
    	if(!$destinationsV['lat_lon']){ //没有门店经纬信息重新再查询一次
    		$address = $destinationsV['localtion'].' '.$destinationsV['address'];
    		$destinationsLonLat = $baidu_map->getLngLatByAddress($address);
    		if(!$destinationsLonLat){
    			$destinationsLonLat = $baidu_map->getLngLatByAddress($destinationsV['address']);
    			if(!$destinationsLonLat){
	    			return;
    			}
    		}
    		$destinationsLonLatStr = implode(',', $destinationsLonLat);
    		D('Stores/Stores')->save(['stores_id' => $destinationsV['stores_id'],'lat_lon' => $destinationsLonLatStr]);
    	}
    	//比较两者距离
    	$startLon = $originsV['user_lat_lon'] ? explode(',', $originsV['user_lat_lon'])[0] : array_shift($originsLonLat);
    	$startLat = $originsV['user_lat_lon'] ? explode(',', $originsV['user_lat_lon'])[1] : array_shift($originsLonLat);
    	$endLon = $destinationsV['lat_lon'] ? explode(',', $destinationsV['lat_lon'])[0] : array_shift($destinationsLonLat);
    	$endLat = $destinationsV['lat_lon'] ? explode(',', $destinationsV['lat_lon'])[1] : array_shift($destinationsLonLat);
    	$origins = $baidu_map->getDistanceBetweenPointsNew($startLon,$startLat,$endLon,$endLat);
    	if($origins['meters'] <= $maxDistance*1000){
    		$allowStores[$destinationsV['stores_id']] = $origins['meters'];
    	}
    }
    /**
     * 通过详情地址获取位置的经纬
     * @param string $address
     * @return array || boolean $res
     */
    function getLatLonByAddress($address){
    	$baiduMap = new BaiduMap();
    	$res = $baiduMap->getLngLatByAddress($address);
    	return $res;
    }
    /**
     * 有效距离内计算门店运费
     * @param float $weight 订单商品重量
     * @param array $freight_info 运费模板信息
     * @param float $shipment_price 订单运费
     */
    private function innerLongShipmentPrice($weight,$freight_info){
    	if($weight <= $freight_info['ft_home_delivery_weight_one']){
    		$shipment_price = $freight_info['ft_home_delivery_price_one'];
    	}else{
    		$shipment_price = $freight_info['ft_home_delivery_price_two'];
    	}
    	return $shipment_price;
    }
}