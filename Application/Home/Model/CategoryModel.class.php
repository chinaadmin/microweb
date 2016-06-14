<?php
/**
 * 商品分类模型
 * @author xiongzw
 * @date 2015-04-29
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class CategoryModel extends HomebaseModel{
	protected $tableName = "category";
	protected $_scope = [ 
			'category' => [ 
					'where' => [ 
							'status' => 1,
					] 
			] 
	];
	/**
	 * 获取每个分类下商品总数
	 * @param  $catId 分类id
	 */
	public function coutByCat(Array $catId) {
		$catModel = D ( 'Admin/Category' );
		$topId = $catModel->getTopCats ( $catId );
		$cats = array_unique(array_merge($catId,$topId));
		$where = array (
				"cat_id" => array (
						'in',
						$cats 
				) 
		);
		$cat = $this->field ( "cat_id,name,pid" )->where ( $where )->select ();
		$data = M ( 'Goods' )->field ( "count(*) as count,cat_id" )->scope ( "goods" )->where ( $where )->group ( "cat_id" )->select ();
		if ($data) {
			foreach ( $cat as &$v ) {
				foreach ( $data as $vs ) {
					if ($v ['cat_id'] == $vs ['cat_id']) {
						$v ['count'] = $vs ['count'];
					}
				}
			}
		}
		$cat = list_to_tree ( $cat, 'cat_id', 'pid', 'child' );
		return $cat;
	}
	
	/**
	 * 获取分类
	 * @param  $type 1:获取所有分类  2:获取指定分类
	 * @param  $cat_id 分类id
	 */
	public function getCats($type=1,$cat_id=0){
		$where = array();
		if($type==2 && $cat_id){
			 $cat_model = D("Admin/Category");
			 $cat_id = current($cat_model->getTopCats ( array($cat_id) ));
			 $childs = $cat_model->getChilds ( $cat_id );
			 $childs [] = $cat_id;
			 $childs = array_unique($childs);
			 $where = array(
			 		"cat_id" => array('in',$childs)
			 );
		}
		$cats = $this->field("cat_id,name,pid,icon")->scope("category")->where($where)->select();
		return list_to_tree ( $cats, 'cat_id', 'pid', 'child' );
	}
	/**
	 * 获取分类id获取一级二级分类
	 * @param array $catId
	 * @return Ambigous <\Think\mixed, boolean, mixed, multitype:, unknown, object>
	 */
	public function getCatsLevel(Array $catId){
		$catModel = D ( 'Admin/Category' );
		$topId = $catModel->getTopCats ( $catId );  //获取顶级分类
		$where = array(
				"pid"=>array('in',$topId)
		);
		$pcatId = $this->where($where)->getField("cat_id",true);
		$ids = array_unique(array_merge((array)$topId,(array)$pcatId));
		$where = array(
				"cat_id"=>array('in',$ids)
		);
		$cats = $this->field("cat_id,name,pid,icon")->scope("category")->where($where)->select();
		return list_to_tree ( $cats, 'cat_id', 'pid', 'child' );
	}
	/**
	 * 获取所有顶级分类
	 * @param string $field 字段
	 * @return Ambigous <\Think\mixed, boolean, mixed, multitype:, unknown, object>
	 */
	public function getTopCats($field=true){
		$where = array(
				"pid"=>0
		);
		return $this->field($field)->scope("category")->where($where)->select();
	}
}