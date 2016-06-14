<?php
/**
 * 分类模型
 * @author xiongzw
 * @date 2015-04-09
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Util\Tree;
class CategoryModel extends AdminbaseModel{
	protected $tableName = "category";
	public $_validate = [ 
			[ 
					'name',
					'require',
					'商品分类不能为空' 
			],
			[ 
					'name',
					"1,10",
					'分类名称不能超过10个字',
					0,
					'length' 
			],
			[ 
					'name',
					'',
					'分类名称已存在',
					0,
					'unique',
					self::MODEL_BOTH 
			],
	];
	
	/**
	 * 获取分类
	 * @param $field 字段
	 * @return array
	 */
	public function categorys($field=true){
	   $data =  $this->field($field)->order("sort DESC,add_time Desc,cat_id DESC")->select();	
	   return $data;
	}
	/**
	 * 通过id获取分类
	 * @param $id 分类id
	 * @param $field 字段
	 * @return array
	 */
	public function getById($id,$field=true){
		return $this->field($field)->find($id);
	}
	
	/**
	 * 根据id获取分类下所有子级别
	 * @param $id 分类id
	 * @return array 分类下的子级
	 */
	public function getChilds($id,$isFirst = false) {
		static $childs = array ();
		if($isFirst){
			$childs = array ();
		}
		$where = array (
				'pid' => $id 
		);
		if(strpos($id, ',') !== false){
			$where['pid'] = ['in',$id];
		}
		$child = $this->where ( $where )->getField ( 'cat_id', true );
		if ($child) {
			foreach ( $child as $v ) {
				$childs [] = $v;
				$this->getChilds ( $v );
			}
		}
		return $childs;
	}
	
	/**
	 * 根据id获取顶级分类
	 * @param $id 分类id
	 * @return array 分类的顶级父类
	 */
	public function getTopCats(Array $id) {
		static $top = array ();
		$where = array (
				'cat_id' => array (
						'in',
						$id 
				) 
		);
		$data = $this->field ( "pid,cat_id" )->where ( $where )->select ();
		$notTop = array ();
		foreach ( $data as $v ) {
			if ($v ['pid'] == 0) {
				$top [] = $v ['cat_id'];
			} else {
				$notTop [] = $v ['pid'];
			}
		}
		if (! empty ( $notTop )) {
			$this->getTopCats ( $notTop);
		}
		return $top;
	}
	
	/**
	 * 查询分类下的商品
	 * @param $cid 分类id
	 * @return array 
	 */
	public function goods($cid) {
		if (is_array ( $cid )) {
			$child = $cid;
		} else {
			$child = $this->getChilds ( $cid );
			$child [] = $cid;
		}
		$where = array (
				'cat_id' => array (
						'in',
						$child 
				) 
		);
		return M ( 'Goods' )->where ( $where )->select ();
	}
	/**
	 * 删除分类下的属性
	 *
	 * @param $cat_id 分类id        	
	 */
	public function delAttr($cat_id) {
		$where = array (
				"cat_id" => $cat_id 
		);
		return M ( 'FilterCategoryAttribute' )->where ( $where )->delete ();
	}
	
	/**
	 * 通过id获取属性
	 * @param $cat_id 分类id
	 * @param $field 字段
	 * @return array
	 */
	public function getByCat($cat_id, $field = true) {
		$where = array (
				"cat_id" => $cat_id 
		);
		return M ( 'FilterCategoryAttribute' )->field ( $field )->where ( $where )->select ();
	}
	/**
	 * 添加/编辑分类属性
	 * @param $addId 添加时分类id
	 * @return lastInsertId
	 */
	public function addAttr($addId=0){
		$editId = I ( 'post.id', 0, 'intval' );
		if($addId) $cat_id = $addId;
		if($editId) $cat_id = $editId;
		$attr = I ( 'post.attr', '' );
		$data = array();
		if (! empty ( $attr )) {
			array_walk ($attr,function (&$v,$key) use($cat_id,&$data) {
				$data [$key]['attr_id'] = $v;
				$data [$key]['cat_id'] = $cat_id;
			});
			$model = M ( 'FilterCategoryAttribute' );
			// 编辑属性
			if ($editId) {
				$model->where ( array (
						'cat_id' => $editId 
				) )->delete ();
			}
			if($data){
			 return $model->addAll ( $data );
			}
		}
	}
	
	/**
	 * 获取树形分类
	 * @param $field 字段
	 * @return array
	 */
	public function getTree($field=true,$icon = ''){
		$data = $this->categorys ( $field );
		$tree = new Tree ( $data, array (
				'cat_id',
				'pid',
				'name'
		) );
		if(!$icon){
			$tree->icon = array (
					'&nbsp;&nbsp;&nbsp;│ ',
					'&nbsp;&nbsp;&nbsp;├─ ',
					'&nbsp;&nbsp;&nbsp;└─ '
			);
		}else{
			$tree->icon = $icon;
		}
		$data = $tree->getArray ();
		return $data;
	}
	/**
	 * 添加编辑商品规格
	 * 
	 * @param
	 *        	$data
	 * @param $cat_id 分类id        	
	 * @param $type 0:添加
	 *        	1：编辑
	 */
	public function setNorms(Array $norms, $cat_id, $type = 0) {
		$model = M ( 'CatNorms' );
		if ($type) {
			$model->where ( "cat_id={$cat_id}" )->delete ();
		}
		$data = array ();
		if (! empty ( $norms )) {
			foreach ( $norms as $k => $v ) {
				$data [$k] ['norms_id'] = $v;
				$data [$k] ['cat_id'] = $cat_id;
			}
			return $model->addAll($data);
		}
	}
	/**
	 * 通过分类id获取规格
	 * @param  $cat_id 分类id
	 */
	public function getNormsById($cat_id,$field=true){
		return M('CatNorms')->field($field)->where("cat_id={$cat_id}")->select();
	}
	/**
	 * 通过分类id获取规格值
	 */
	public function getNormsValueById($cat_id, $field = true) {
		$norms_id = $this->getNormsById ( $cat_id, 'norms_id' );
		$norms_id = array_column($norms_id, "norms_id");
		$norms = array ();
		if ($norms_id) {
			$where = array (
					'norms_id' => array (
							'in',
							$norms_id 
					) 
			);
			$norms = D ( 'Admin/Norms' )->getNormsByIds ( $norms_id );
			$data = M ( 'NormsValue' )->field ( $field )->where ( $where )->order("norms_value_sort desc")->select ();
			/* foreach ( $data as $k => &$v ) {
				if ($v ['norms_attr']) {
					$attrs = current ( D ( 'Upload/AttachMent' )->getAttach ( $v ['norms_attr'] ) );
					$v ['pic'] = $attrs ['path'];
				}
				foreach($norms as $vo){
					if($v['norms_id'] == $vo['norms_id']){
						$v ['norms_name'] = $vo['norms_name'];
					}
				}
			} */
		foreach($norms as &$v){
			foreach($data as &$vo){
				if ($vo ['norms_attr']) {
					$attrs = current ( D ( 'Upload/AttachMent' )->getAttach ( $vo ['norms_attr'] ) );
					$vo ['pic'] = $attrs ['path'];
				}
				if($v['norms_id'] == $vo['norms_id']){
					$v ['norms_values'][] = $vo;
				}
			}
		}
		}
		return $norms;
	}
}