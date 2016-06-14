<?php
/**
 * 商品类型模型
 * @author xiongzw
 * @date 2015-04-10
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class TypeModel extends AdminbaseModel{
	protected $tableName = "goods_type";
	public $_validate = [ 
			[ 
					'name',
					'require',
					'商品类型不能为空' 
			],
			[ 
					'name',
					"1,10",
					'类型名称不能超过10个字',
					0,
					'length' 
			],
			[ 
					'name',
					'',
					'商品类型已存在',
					0,
					'unique',
					self::MODEL_BOTH 
			] 
	];
	
	/**
	 * 添加属性分组
	 * @param $type 1:添加时 2：修改时添加
	 * @param $id 所属类型id
	 * @return \Common\Org\util\Results
	 */
	public function addAttgroup($id, $type = 1) {
		$attGroup = I ( 'post.attk', '' );
		$attGroup = array_unique ( $attGroup );
		$attGroup = array_filter ( $attGroup );
		$exits = array ();
		if ($attGroup) {
			$attGroup = array_map ( function (&$v) use($id, &$exits, $type) {
				$v = array (
						"name" => $v,
						'type_id' => $id 
				);
				if ($type == 2) {
					$name = M ( 'AttributeGroup' )->where ( $v )->getField ( 'name' );
					if ($name) {
						$exits [] = $name;
					}
				}
				return $v;
			}, $attGroup );
		}
		if (! empty ( $exits )) {
			$result = $this->result ()->error ("属性分组" . implode ( ",", $exits ) . "已存在" );
		} else {
			if ($attGroup) {
				M ( 'AttributeGroup' )->addAll ( $attGroup );
			}
			$result = $this->result ()->set ();
		}
		return $result;
	}
	
	/**
	 * 根据类型id查询属性分组
	 * @param  $type_id 所属类型id
	 * @param $type 1:获取属性分组名数组
	 * @return array
	 */
	public function getAttgroup($type_id,$type=1) {
		$att_group = M ( 'AttributeGroup' )->where ( "type_id={$type_id}" )->select();
		if($type==1){
			$att_group = array_column($att_group, "name");
		}
		return $att_group;
	}
	/**
	 * 通过类型id获取属性数量 
	 * @param  $type_id 类型id
	 * @return number 
	 */
	public function getAttcount($type_id) {
		return M ( 'Attribute' )->where ( "type_id={$type_id}" )->count ();
	}
	
	/**
	 * 获取所有类型
	 * @param $field 字段
	 * @return array
	 */
	public function getTypes($field=true) {
		return $this->field($field)->select();
	}
	/**
	 * 通过类型id获取类型信息
	 * @param $type_id 类型id
	 * @param $type 1：需要查询分组信息
	 *        	2：不需要查询分组信息
	 * @return array
	 */
	public function getById($id, $field = true, $type = 1) {
		$info = $this->field ( $field )->find ( $id );
		// 查询类型属性分组
		if ($type == 1) {
			$attGroup = M ( 'AttributeGroup' )->where ( "type_id={$id}" )->select ();
			if ($attGroup) {
				$info ['att_group'] = $attGroup;
			}
		}
		return $info;
	}
	
	/**
	 * 更新属性分组
	 * @param $type_id 所属类型id
	 */
	public function updateAttgroup($type_id) {
		$att = I ( 'post.att', '' );
		$data = M ( 'AttributeGroup' )->where ( "type_id={$type_id}" )->select ();
		$ids = array_column ( $data, "id" );
		$names = array_column ( $data, "name" );
		$data = array_combine ( $ids, $names );//关联data数组
		if(!empty($att)){
		 $empty = array_diff_key ( $data, $att );
		}else{
			$empty = $data;
		}
		$attGroup = array_diff ( $att, $data );
		$empty = array_keys ( $empty ); // 要删除的值
		$has = array (); // 已存在的值
		$update = array (); // 要更新的值
		$result = $result = $this->result ()->set ();
		if (! empty ( $attGroup )) {
			array_walk ( $attGroup, function ($v, $key) use(&$empty, &$has, &$update, $type_id) {
				if (empty ( $v )) {
					// 获取空值
					$empty [] = $key;
				} else {
					$where = array (
							'type_id' => $type_id,
							'name' => $v,
							'id' => array (
									'neq',
									$key 
							) 
					);
					$result = M ( 'AttributeGroup' )->where ( $where )->find ();
					// 判断是否存在
					if ($result) {
						$has [] = $v;
					} else {
						
						$update [$key] = $v;
					}
				}
				return $v;
			} );
		}
		if (! empty ( $has )) {
			$result = $this->result ()->error ( "属性分组" . implode ( ",", $has ) . "已存在" );
		} else {
		    //删除属性组
			if (! empty ( $empty )) {
				$this->delGroup ( $empty );
				$result = $this->result ()->set ();
			}
			//更新属性组
			if (! empty ( $update )) {
				$unqiue = array_unique ( $update );
				$diff = array_diff_assoc ( $update, $unqiue );
				if ($diff) {
					$result = $this->result ()->error ( "属性分组值：" . implode ( ",", $diff ) . "重复" );
				} else {
					$sql = array2UpdateSql ( $update, C ( 'DB_PREFIX' ) . "attribute_group", "name" );
					$this->execute ( $sql );
					$result = $this->result ()->set ();
				}
			}
		}
		return $result;
	}
	/**
	 * 删除属性分组
	 * 通过id删除属性分组
	 * @param $ids 属性分组id
	 */
	private function delGroup(Array $ids){
		$where = array(
				"id" => array('in',$ids)
		);
		//更改属性
		$att_where['attr_group'] = $where['id'];
		M('Attribute')->where($att_where)->save(array("att_group"=>0));
		return M("AttributeGroup")->where($where)->delete();
	}
	
	/**
	 * 通过类型id获取类型下商品
	 * @param $type_id 类型id
	 */
	public function goodsById($type_id,$filed=true){
		return M('Goods')->where("type_id={$type_id}")->select();
	}
	
	/**
	 * 删除类型
	 * @param  $type_id 类型id
	 */
	public function del($type_id){
		$where = array(
				'type_id' => $type_id
		);
		//删除属性分组
		M('AttributeGroup')->where($where)->delete();
		//删除属性
		M('Attribute')->where($where)->delete();
		//删除类型
		return $this->where($where)->delete();
	}
}