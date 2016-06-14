<?php
/**
 * 图片投放模型
 * @author xiongzw
 * @date 2015-08-18
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class PutModel extends AdminbaseModel{
	protected $_validate = array (
			array (
					'put_title',
					'require',
					'标题不能为空' 
			),
			array (
					"put_attr",
					"number",
					"请选择图片上传" 
			) 
	);
	
	/**
	 * 通过id获取信息
	 * @param unknown $put_id
	 */
	public function getById($put_id,$field=true){
		return $this->field($field)->where(['put_id'=>$put_id])->find();
	}
}