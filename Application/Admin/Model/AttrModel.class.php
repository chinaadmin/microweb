<?php
/**
 * 类型属性模型
 * @author xiongzw
 * @date 2015-04-13
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class AttrModel extends AdminbaseModel{
	protected $tableName = "attribute";
	public $_validate = [ 
			[ 
					'name',
					'require',
					'属性名称不能为空' 
			],
			[ 
					'type_id',
					'/[1-9]+/',
					'请选择类型' 
			],
			[ 
					'name',
					'uniqueName',
					'属性名称已存在',
					0,
					'callback' 
			]
	];
	
	public function uniqueName(){
	    $name = I('post.name','');
	    $id = I('post.id',0,'intval');
	    $type = I('post.type');
	    $where = array(
	    		'name'=>$name,
	    		'type_id'=>$type
	    );
	    if($id){
	    	$where['attr_id'] = array('NEQ',$id);
	    }
	    if($this->where($where)->find()){
	    	return false;
	    }else{
	    	return true;
	    }
	}
	/**
	 * 通过id获取属性信息
	 *
	 * @param $id 属性id        	
	 */
	public function getById($id, $field = true) {
		$data = $this->field ( $field )->find ($id);
		$data ['attr_groups'] = M ( 'AttributeGroup' )->where ( "type_id={$data['type_id']}" )->select ();
		return $data;
	}
	/**
	 * 通过类型id获取属性
	 *
	 * @param $type_id 类型id        	
	 */
	public function getByType($type_id, $field = true) {
		$where = array (
				'type_id' => $type_id 
		);
		return $this->field ( $field )->where ( $where )->select ();
	}
}