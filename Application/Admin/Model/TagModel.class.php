<?php
/**
 * 商品标签模型
 * @author xiongzw
 * @date 2015-07-16
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class TagModel extends AdminbaseModel{
	protected $tableName = "goods_tag";
	protected $_validate = array(    
			 array('name','require','标签名称必须填写'),
			 array('name','','标签名称已存在',self::EXISTS_VALIDATE,'unique',self::MODEL_BOTH)
	);
	/**
	 * 根据标签id获取标签数据
	 * @param  $tag_id 标签id
	 * @param string $field
	 * @return array
	 */
	public function getById($tag_id,$field=true){
		$where = array(
				'tag_id'=>$tag_id
		);
		return $this->field($field)->where($where)->find();
	}
	
	/**
	 * 删除标签
	 * @param $tag_id 商品标签
	 */
	public function delTag($tag_id){
		if(empty($tag_id)){
			return $this->result()->error("请选择要删除的标签！");
		}
		$where = array(
				"tag_id"=>array('in',$tag_id)
		);
		$this->startTrans();
		//删除标签商品关联
		$return_link = M("GoodsTagLink")->where($where)->delete();
		$return_tag = $this->where($where)->delete();
		if($return_tag!==false && $return_link!==false){
			$this->commit();
			return $this->result()->success();
		}else{
			$this->rollback();
			return $this->result()->error();
		}
	}
	
	public function getTags($field=true){
		$where = array(
				'tag_status'=>1
		);
	   	return $this->field($field)->where($where)->order("tag_sort desc,update_time desc,add_time desc")->select();
	}
}