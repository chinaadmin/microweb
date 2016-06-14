<?php
/**
 * 不配送区域详情模板
 * @author xiaohuakang
 * @date 2016-05-13
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class NoDeliveryDetailModel extends AdminbaseModel {
	// 查询成功后格式化查询数据
	function _after_find(&$result,$options) {
		if($result['ndd_county_id']){
			$nameStr = M('position_city')->where(['city_id'=>['in', $result['ndd_county_id'] ]])->getField('city_name',true); // 只显示市区
			$result['ndd_county_name'] = implode(',', $nameStr);
			$result['ndd_county_name_formate'] = D('Home/Area')->getCityFormat($result['ndd_county_id']);
		}
	}
}