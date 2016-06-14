<?php
/**
 * 首页搜索模型
 * @author xiongzw
 * @date 2015-04-29
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class SearchModel extends HomebaseModel{
	//protected $tableName = "goods";
	  Protected $autoCheckFields = false;
	/**
	 * 分词搜索条件
	 * @param  $keywords
	 * @return string
	 */
	public function searchWord($keywords){
		$keys = pullWord($keywords);
		$keys[] = $keywords;
		$keys = array_unique($keys);
		$where = array();
		foreach($keys as $v){
			$v = trim($v);
			$where[] = array("like","%{$v}%");
		}
		$where[] = "OR";
		return $where;
	}
	
}