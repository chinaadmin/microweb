<?php
/**
 * 
 * @author wxb
 * @date 2015-07-24
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class FreightDetailModel extends AdminbaseModel {
	function _after_select(&$resultSet,$options) {
		//将省名解析成中文字窜
		foreach ($resultSet as &$v){
			if($v['fd_county_id']){
				$nameStr = M('position_city')->where(['city_id'=>['in',$v['fd_county_id']]])->getField('city_name',true);//只显示市区
				$v['fd_county_name'] = implode(',',$nameStr);
				$v['fd_county_name_formate'] = D('Home/Area')->getCityFormat($v['fd_county_id']);
			}
		}
	}
}