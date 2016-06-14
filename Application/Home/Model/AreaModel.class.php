<?php
/**
 * 城市地区数据模型
 * @author xiongzw
 * @date 2015-05-08
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
use Common\Org\Util\BaiduMap;
class AreaModel extends HomebaseModel{
	protected $tableName = 'position_provice';
	/**
	 * 获取地区数据
	 * @update 是否强制更新
	 */
	public function getAreaData($update = false) {
		$data = F ( 'city_data' );
		if (empty ( $data ) || $update) {
			$provice = $this->select (); // 省份数据
			$ids = array ();
			$provice = $this->formatData ( $provice, $ids );
			$city = $this->selectData ( $ids, 'province_id', 'PositionCity' ); // 市级数据
			$city = $this->formatData ( $city, $ids, 'city_id' );
			$county = $this->selectData ( $ids, 'city_id', 'PositionCounty' ); // 县区数据
			$county = $this->formatData ( $county, $ids, "county_id" );
			$town = $this->selectData ( $ids, 'county_id', 'PositionTown' ); // 镇数据
			$data = $this->eachData ( $provice, $city, $county, $town );
			F ( 'city_data', $data );
		}
		return $data;
	}
	/**
	 * 获取省份数据
	 */
	public function getProvice($update=false){
		$provice = F('provice');
		if(empty($provice) || $update){
		 $provice = $this->select (); // 省份数据
		 F('provice',$provice);
		}
		return $provice;
	}
	/**
	 * 数据查询
	 * @param array $ids  关联的id
 	 * @param  $link_id  关联字段
	 * @param  $table    查询的表
	 * @return Ambigous <\Think\mixed, boolean, multitype:, unknown, mixed, object>
	 */
	public function selectData(Array $ids,$link_id,$table){
		$where = array(
				$link_id => array('in',$ids)
		);
		return M($table)->where($where)->select();
	}

	/**
	 * 格式化地区数据
	 * @param array $data
	 */
	public function formatData(Array $data,&$ids,$field="provice_id"){
		$ids = array();
		foreach($data as $key=>$v){
			$ids[] = $v[$field];
			$data[$v[$field]] = $v;
			unset($data[$key]);
		}
		return $data;
	}
	
	public function eachData($provice,$city,$county,$town){
		//县级数据
		foreach($town as $key=>$v){
			$county[$v['county_id']]['child'][$key] = $v;
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
	//格式化区域数据
	function getFormatArea(){
		$res = S( 'delivery_area' );
		if(!$res || APP_DEBUG){
			 $sql = "
			 		SELECT a.a_name areaName,GROUP_CONCAT(provice_id ORDER BY provice_id DESC SEPARATOR ','
					) proviceStr FROM `jt_position_provice` p
					LEFT JOIN jt_area a ON p.area_id = a.a_id
					GROUP BY area_id;
			 		";
			 $res['provice'] = $this->query($sql);
			 $tmp = M('position_city')->getField('city_id,city_name');
			 $res['data'] = M('position_provice')->getField('provice_id,provice_name');
			 $res['data'] = $tmp + $res['data'];
			 S( 'delivery_area',$res,30);
		}
		return $res;
	}
	/**
	 * 格式化市区数据
	 * @param string $idStr 用,隔开的省份或市区id字符窜 110,12011,120224
	 * @return [string,string]  例如:[省名(被选市区数),省名(被选市区数)]
	 */
	function getCityFormat($idStr){
		// [['provinceid' =>110,'provinceName' => 'test','positionCityCount' => 110]];
		$res = [];
		$idArr = explode(',', $idStr);
		$proviceIdCountArr = [];//[110=>'北京',120=>'上海']
		$countyIdArr = [];//[110=>'11023,110424']
		$m = M('position_city');
		$proviceModel = M('position_provice');
		foreach($idArr as $v){
			$proviceId = substr($v, 0,3);
			if(strlen($v) == 3){ //省id
				$proviceIdCountArr[$proviceId] = $m->cache(10)->where(['province_id' => $v])->count();
				$countyIdArr[$proviceId] = implode(',', $m->cache(10)->where(['province_id' => $v])->getField('city_id',true));
				continue;
			}
			if(in_array($proviceId, array_keys($proviceIdCountArr))){
				$proviceIdCountArr[$proviceId]++; 
				$countyIdArr[$proviceId] .= ','.$v;
			}else{
				$proviceIdCountArr[$proviceId] = 1;
				$countyIdArr[$proviceId] = $v;
			}
		}
		foreach($proviceIdCountArr as $k => $v){
			$res[] = [ 
					'provinceid' => $k,
					'countyIdStr' => $countyIdArr[$k],
					'provinceName' => $proviceModel->cache(10)->where(['provice_id' => $k])->getField('provice_name'),
					'positionCityCount' => $v 
			];
		}
		return $res;
	}
	//首字母分组市区信息
	function getCountyDistrictGroup(){
		if($abcArr = F('CountyDistrictGroup')){
			return $abcArr;
		}
		$city = M('position_city')->select();
		$county = M('position_county')->select();
		$abcArr = [];
		foreach($city as $cV){
			$abcArr[$cV['first_ping_ying']][] = ['id'=> $cV['city_id'],'name' => $cV['city_name'],'type' => 'city']; 
		}
		foreach ($county as $cyV){
			$abcArr[$cyV['county_first_ping_ying']][] = ['id' => $cyV['county_id'],'name' => $cyV['county_name'],'type' => 'county'];
		}
		ksort($abcArr);
		F('CountyDistrictGroup',$abcArr);
		return $abcArr;
	}
	//热门市
	function getHotCity(){
		$field = [
				'hl_loc_id' => 'city_id',
				'hl_name' => 'city_name',
				'hl_up_level_id' => 'provice_id'
		];
		$list = M('hot_location')->where(['hl_type' => 1])->field($field)->select();
		return $list;
	}
}