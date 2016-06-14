<?php
/**
 * 收藏模型
 */
namespace Api\Model;
class CollectModel extends ApiBaseModel{
	/**
	 * 格式化数据
	 */
	public function fomatList($lists){
		$return_array = array();
		foreach($lists as $key=>$v){
			$return_array[$key] = array(
					'addTime' => date('Y-m-d H:i:s',$v['add_time']),
					'goodsId' => $v['goods_id'],
					'normsValue'=>empty($v['norms_value'])?"":json_decode($v['norms_value'],true),
			        'name' => $v['name'],
					'price'=>$v['price'],
					'photo'=>fullPath($v['thumb'])	
			);
		}
		return $return_array;
	}
}