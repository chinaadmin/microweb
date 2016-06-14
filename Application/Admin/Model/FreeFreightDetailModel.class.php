<?php
/**
 * 包邮详情模板
 * @author wxb
 * @date 2015-07-24
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;

class FreeFreightDetailModel extends AdminbaseModel {
	function _after_select(&$resultSet,$options) {
		//将省名解析成中文字窜
		foreach ($resultSet as &$v){
			if($v['fsd_county_id']){
				$nameStr = M('position_city')->where(['city_id'=>['in',$v['fsd_county_id']]])->getField('city_name',true);
				$v['fsd_county_name'] = implode(',',$nameStr);
				$v['fsd_county_name_formate'] = D('Home/Area')->getCityFormat($v['fsd_county_id']);
			}
		}
	}
}