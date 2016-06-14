<?php
/**
 * 列表模型
 * @author xiongzw
 * @date 2015-06-30
 */
namespace Api\Model;
class ListModel extends ApiBaseModel{
	Protected $autoCheckFields = false;

	/**
	 * 获取所有子级分类id
	 * @param  $cat_id
	 * @return array
	 */
	public function getChildCarts($cat_id){
		$cat_model = D('Admin/Category');
		$cats = $cat_model->getChilds ( $cat_id ,true);
		$cats [] = $cat_id;
		return $cats;
	}
	/**
	 * 格式化列表数据
	 * @param  $data
	 * @return multitype:multitype:unknown Ambigous <string, string>
	 */
	public function formatData($data){
		$return_array = array();
		foreach($data as $k=>$v){
			$return_array[$k] = array(
					'goodsId'=>$v['goods_id'],
					'catId' => $v['cat_id'],
					'name' => $v['name'],
					'price'=> $v['price'],
					'sales'=> $v['sales'],
					'photo'=> fullPath($v['thumb']),
					'stockNumber'=>$v['stock_number'],
					'marketPrice'=>$v['old_price']
			);
		}
		return $return_array;
	}
}