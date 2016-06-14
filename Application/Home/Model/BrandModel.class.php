<?php
/**
 * 前台品牌模型
 * @author xiongzw
 * @date 2015-04-28
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class BrandModel extends HomebaseModel{
	/**
	 * 获取品牌信息
	 * @param $brand_id
	 * @param string $field
	 */
	public function getById($brand_id,$field=true){
		return D('Admin/Brand')->getById($brand_id,$field);
	}
	/**
	 * 通过品牌获取商品
	 * @param  $brand_id 品牌id
	 */
	public function goodsByBrand($brand_id){
		$where = array(
				'brand_id'=>$brand_id,
		);
		$data = D('Home/List')->viewModel()->where($where)->select();
	    D('Home/list')->getThumb($data,true);
	    return $data;
	}
}