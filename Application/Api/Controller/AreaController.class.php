<?php
/**
 * 行政地区数据接口
 * @author xiongzw
 * @date 2015-07-07
 */
namespace Api\Controller;
use Common\Org\Util;
use Common\Org\Util\BaiduMap;
class AreaController extends ApiBaseController{
	/**
	 * 请求地区数据
	 *       <code>
	 *       pid 省份id
	 *       cid 城市id
	 *       countyId 县、镇id
	 *       </code>
	 */
	public function getArea(){
		$pid = I('request.pid',0);
		$cid = I('request.cid',0);
		$county_id = I('request.countyId',0);
		if(empty($pid)){
			$return_array = D("Home/Area")->getProvice();
		}else{
			$data = D('Home/Area')->getAreaData();
			$data = $data[$pid]['child'];
			if($cid){
				$data = $data[$cid]['child'];
			}
			if($county_id){
				$data = $data[$county_id]['child'];
			}
			$return_array = array();
			foreach($data as $k=>$v){
				unset($v['child']);
				$return_array[]=$v;
			}
		}
		$this->ajaxReturn($this->result->success()->content(['data'=>$return_array]));
	}

    /**
     * 门店列表
     * @author cwh
     *         传入参数:
     *         <code>
     *            无
     *         </code>
     */
    public function getStoresData(){
        $data = D('Stores/Stores')->getAreaApi();
        $this->ajaxReturn($this->result->success()->content(['data'=>$data]));
    }
    function getLocationByLngLat(){
    	$lng = I('lng','','trim');
    	$lat = I('lat','','trim');
    	if(!$lng){
    		$this->ajaxReturn($this->result->set('LOCATION_LNG_REQUIRE'));
    	}
    	if(!$lat){
    		$this->ajaxReturn($this->result->set('LOCATION_LAT_REQUIRE'));
    	}
 		$baiduMap = new BaiduMap();
 		$location = $baiduMap->getAddressByLngLat($lng,$lat);
 		//获取省市区id
 		$provice_id = M('position_provice')->where(['provice_name' => $location['province']])->getField('provice_id');
 		$city_id = M('position_city')->where(['city_name' => $location['city'],'provice_id' => $provice_id])->getField('city_id');
		$district_id = M('position_county')->where(['county_name' => $location['district'],'city_id' => $city_id])->getField('county_id');
		$data = [
				'provice' => [
						'id' => $provice_id,
						'name' => $location['province']
				],
				'city' => [
						'id' => $city_id,
						'name' => $location['city']
				],
				'district' => [
						'id' => $district_id,
						'name' => $location['district']
				]
		];
		$this->ajaxReturn($this->result->success()->content(['data'=>$data]));
    }
    function getCountyDistrictGroup(){
    	$data = D('Home/Area')->getCountyDistrictGroup();
    	$this->ajaxReturn($this->result->success()->content(['data'=>$data]));
    }
    //获取热门市    
    function getHotCity(){
    	$data = D('Home/Area')->getHotCity();
    	$this->ajaxReturn($this->result->success()->content(['data'=>$data]));
    }
}