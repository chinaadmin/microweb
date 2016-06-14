<?php
/**
 * 门店模型类
 * @author cwh
 * @date 2015-05-08
 */
namespace Stores\Model;
class StoresModel extends StoresbaseModel{

    public $_auto = [
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    //命名范围
    protected $_scope = [
        'default'=>[
            'where'=>['status'=>1],
        ]
    ];

    protected $tableName = 'stores';

    /**
     * 获取所有门店
     * @param bool $field 字段
     * @return mixed
     */
    public function getStores($field=true){
    	return $this->field($field)->scope()->select();
    }
    /**
     * 通过门店id获取门店信息 
     * @param  $stores_id 门店id
     * @param string $field 字段
     * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
     */
    public function getStoresById($stores_id,$field=true){
    	$where = array(
    			"stores_id"=>$stores_id
    	);
    	return $this->field($field)->scope()->where($where)->find();
    }
    
    /**
     * 通过门店id批量获取门店信息
     * @param  $stores_id 门店id
     * @param string $field 字段
     * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
     */
    public function getStoresByIds($stores_id,$field=true){
    	$where = array(
    			"stores_id"=>array("in",(array)$stores_id)
    	);
    	return $this->field($field)->scope()->where($where)->select();
    }

    public function getAreaApi(){
        $stores_lists = $this->getField('stores_id,name,provice,city,county,localtion,address');
        $provice = [];
        $city = [];
        $county = [];
        $stores = [];
        foreach($stores_lists as $v){
            $provice[$v['provice']] = $v['provice'];
            $city[$v['city']] = $v['city'];
            $county[$v['county']] = $v['county'];
            $stores[$v['county']] = $v;
        }

        $area_model = D('Home/Area');
        $ids = [];
        $provice_lists = $area_model->selectData($provice,'provice_id','PositionProvice');
        $provice = $area_model->formatData ( $provice_lists, $ids );
        $city_lists = $area_model->selectData($city, 'city_id', 'PositionCity'); // 市级数据
        $city = $area_model->formatData ( $city_lists, $ids ,'city_id');
        $county_lists = $area_model->selectData($county, 'county_id', 'PositionCounty'); // 县区数据
        $county = $area_model->formatData ( $county_lists, $ids ,'county_id');

        //县级数据
        foreach($stores_lists as $key=>$v){
            unset($v['id']);
            $county[$v['county']]['child'][] = $v;
        }
        //市级数据
        foreach($county as $key=>$v){
            unset($v['id']);
            $city[$v['city_id']]['child'][] = $v;
        }
        //省级
        foreach($city as $key=>$v){
            unset($v['id']);
            $provice[$v['province_id']]['child'][] = $v;
        }

        $result = [];
        foreach($provice as $v){
            unset($v['id']);
            $result[] = $v;
        }

        return $result;
    }


    public function getArea(){
        $stores_lists = $this->getField('stores_id,name,provice,city,county,localtion,address');
        $provice = [];
        $city = [];
        $county = [];
        $stores = [];
        foreach($stores_lists as $v){
            $provice[$v['provice']] = $v['provice'];
            $city[$v['city']] = $v['city'];
            $county[$v['county']] = $v['county'];
            $stores[$v['county']] = $v;
        }

        $area_model = D('Home/Area');
        $ids = [];
        $provice_lists = $area_model->selectData($provice,'provice_id','PositionProvice');
        $provice = $area_model->formatData ( $provice_lists, $ids );
        $city_lists = $area_model->selectData($city, 'city_id', 'PositionCity'); // 市级数据
        $city = $area_model->formatData ( $city_lists, $ids ,'city_id');
        $county_lists = $area_model->selectData($county, 'county_id', 'PositionCounty'); // 县区数据
        $county = $area_model->formatData ( $county_lists, $ids ,'county_id');

        //县级数据
        foreach($stores_lists as $key=>$v){
            $county[$v['county']]['child'][$key] = $v;
        }
        //市级数据
        foreach($county as $key=>$v){
            $city[$v['city_id']]['child'][$key] = $v;
        }
        //省级
        foreach($city as $key=>$v){
            $provice[$v['province_id']]['child'][$key] = $v;
        }

        return $provice;
    }

    /**
     * 删除数据前
     * @param $options
     * @return bool|void
     */
    protected function _before_delete($options) {
        if(M('StoresUser')->where($options['where'])->delete()===false){
            return false;
        }
        return true;
    }
    
    /**
     * 获取门店及门店管理员信息
     */
    public function storesAndManager(){
    	   $viewFields = array(
    	   		"Stores" => array(
    	   		     "name",
    	   			 "address",
    	   			"_type" => "LEFT"
    	   		),
    	   		"StoresUser"=>array(
    	   			"uid",
    	   			"_on" =>"Stores.stores_id=StoresUser.stores_id",
    	   			"_type"=>"LEFT"	
    	        ),
    	   		"AdminUser"=>array(
    	   			"nickname",
    	   			"mobile",
    	   			"_on"=>"StoresUser.uid=AdminUser.uid"	
    	   		)
    	  );
    	  $where = array(
    	  		"StoresUser.manager"=>1
    	  );
    	  return $this->dynamicView($viewFields)->where($where)->select();
    }
    /**
     * 获取是否可编辑配送时间权限
     * @param int $present_stores_id
     * @param int $user_stores_id
     * @param boolean $is_admin
     * @return boolean
     */
    public function get_permission_delivery($present_stores_id,$user_stores_id,$is_admin = false){
    	if($present_stores_id == $user_stores_id || $is_admin){
    		return true;
    	}
    	return false;
    }
}