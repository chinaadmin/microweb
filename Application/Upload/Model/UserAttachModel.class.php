<?php
/**
 * 用户附件模型
 * @author xiongzw
 * @date 2015-06-11
 */
namespace Upload\Model;
class UserAttachModel extends AttachMentModel{
	protected $tableName = "user_attachment";
	protected $partition = array(
			'field' => 'id',
			'type' => 'mod',
			'num' => '10'
		
	);
	/**
	 * 添加数据
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Think\Model::add()
	 */
	public function add($data){
		$table = ltrim($this->getPartitionTableName($data),C("DB_PREFIX"));
		return M($table)->add($data);
	}
}