<?php

/**
 * 百度地图接口类
 * 可参考http://developer.baidu.com/map/index.php?title=webapi/route-matrix-api
 * @author wxb
 * @date 2015/7/3
 */
namespace Common\Org\Util;

class BaiduMap {
	private $getWay; // 接口网关
	private $data = [ ];
	private $ak = '0CHht1XbhHWM4UTWNfrAUD6F';
	function __construct($type = 1) {
		$data = [ ];
		if ($type == 1) { // 计算两点距离
			$this->getWay = 'http://api.map.baidu.com/direction/v1/routematrix';
			$data = [  // 参数
					'mode' => 'walking',
					'output' => 'json' 
			];
			$data ['ak'] = $this->ak; // 百度应用密钥
		}
		$this->data = $data;
	}
	/**
	 * 计算两点之差的距离
	 * 
	 * @param string $origins        	
	 * @param string $destinations        	
	 * @return mixed 公里数 或者 错误提示
	 */
	function calcLong($origins, $destinations, $test) {
		$data = [ ];
		// 过滤掉空格
		$origins = preg_replace ( '/\s*?/', '', $origins );
		$destinations = preg_replace ( '/\s*?/', '', $destinations );
		$data ['origins'] = $origins;
		$data ['destinations'] = $destinations;
		$res = $this->_curl ( $data );
		if ($test) {
			myDump ( $res );
			exit ();
		}
		$results = new Results ();
		if ($res ['status'] == 0) {
			return $results->content ( $res ['result'] ['elements'] [0] ['distance'] ['value'] )->success ();
		} else {
			return $results->content ( $res ['message'] )->setCode ( 'BAIDU_MAP_ERROR' );
		}
	}
	/**
	 * 计算AB两经纬之间的距离
	 * 
	 * @param float $latitude1
	 *        	A点经度
	 * @param float $longitude1
	 *        	A点纬度
	 * @param float $latitude2
	 *        	B点经度
	 * @param float $longitude2
	 *        	B点纬度
	 */
	function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2) {
		$theta = $longitude1 - $longitude2;
		$miles = (sin ( deg2rad ( $latitude1 ) ) * sin ( deg2rad ( $latitude2 ) )) + (cos ( deg2rad ( $latitude1 ) ) * cos ( deg2rad ( $latitude2 ) ) * cos ( deg2rad ( $theta ) ));
		$miles = acos ( $miles );
		$miles = rad2deg ( $miles );
		$miles = $miles * 60 * 1.1515;
		$feet = $miles * 5280;
		$yards = $feet / 3;
		$kilometers = $miles * 1.609344;
		$meters = $kilometers * 1000;
		return compact ( 'miles', 'feet', 'yards', 'kilometers', 'meters' );
	}
	
	/**
	 * 求两个已知经纬度之间的距离,单位为米
	 * 
	 * @param
	 *        	lng1,lng2 经度
	 * @param
	 *        	lat1,lat2 纬度
	 * @return float 距离，单位米
	 * @author www.Alixixi.com
	 *        
	 */
	function getdistance($lng1, $lat1, $lng2, $lat2) {
		// 将角度转为狐度
		$radLat1 = deg2rad ( $lat1 ); // deg2rad()函数将角度转换为弧度
		$radLat2 = deg2rad ( $lat2 );
		$radLng1 = deg2rad ( $lng1 );
		$radLng2 = deg2rad ( $lng2 );
		$a = $radLat1 - $radLat2;
		$b = $radLng1 - $radLng2;
		$s = 2 * asin ( sqrt ( pow ( sin ( $a / 2 ), 2 ) + cos ( $radLat1 ) * cos ( $radLat2 ) * pow ( sin ( $b / 2 ), 2 ) ) ) * 6378.137 * 1000;
		return $s;
	}
	/**
	 * 通过ip获取位置信息  包括经纬度 省份城市(区)
	 * @param string $ip
	 */
	function getLocationByIp($getIp){
		$content = file_get_contents ( "http://api.map.baidu.com/location/ip?ak={$this->ak}&ip={$getIp}&coor=bd09ll" );
		$jsonArr = json_decode ( $content ,true);
		return $jsonArr;
	}
	/**
	 * 通过地址获取相应的经纬信息
	 * @param string $address 地址
	 * @url http://developer.baidu.com/map/index.php?title=webapi/guide/webservice-geocoding
	 * @return array ['lng' => 经度,'lat' => 纬度]
	 */
	function getLngLatByAddress($address){
		$address = urlencode($address);
		$content = file_get_contents ( "http://api.map.baidu.com/geocoder/v2/?address={$address}&output=json&ak={$this->ak}" );
		$jsonArr = json_decode ( $content ,true);
		if($jsonArr['status'] == 0 && $jsonArr['result']['confidence'] >= 30){ //返回结果并且可信度超过30
			return $jsonArr['result']['location'];
		}
		return false; //查询失败
	}
	/**
	 * 通过经纬获取相应的地址信息
	 * @param string $lng 经度
	 * @param string $lat 纬度
	 * @url http://developer.baidu.com/map/index.php?title=webapi/guide/webservice-geocoding
	 */
	function getAddressByLngLat($lng,$lat){
		$location = $lat.','.$lng;
		$content = file_get_contents ( "http://api.map.baidu.com/geocoder/v2/?ak={$this->ak}&location={$location}&output=json&pois=0" );
		$jsonArr = json_decode ( $content ,true);
		if($jsonArr['status'] == 0){
			return $jsonArr['result']['addressComponent'];
		}
		return false;
	}
	/**
	 *
	 * @param array $data
	 *        	参数数组
	 * @param string $type
	 *        	请求类型
	 * @return array $res 返回结果
	 */
	private function _curl($data, $type = 'get') {
		$curl = new Curl ();
		$type = 'st_' . strtolower ( $type );
		$data = array_merge ( $data, $this->data );
		$res = $curl->$type ( $this->getWay, $data );
		return json_decode ( $res, true );
	}
	//获取汉字拼音
	function getPinYing($str){
		if(!$str){
			return false;
		}
		$ch = curl_init();
		$str = urlencode($str);
		$url = "http://apis.baidu.com/xiaogg/changetopinyin/topinyin?str={$str}&type=json&traditional=0&accent=0&letter=0&only_chinese=0";
		$header = array(
				'apikey:45a56a04bf6b72b7cbe3656b6f4ef3a2',
		);
		// 添加apikey到header
		curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 执行HTTP请求
		curl_setopt($ch , CURLOPT_URL , $url);
		$res = curl_exec($ch);
		$res = json_decode($res,true);
		if($res['status'] == 1){
			return $res['pinyin'];
		}
		return false;
	}
}