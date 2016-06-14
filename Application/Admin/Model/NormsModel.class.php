<?php
/**
 * 商品规格模型
 * @author xiongzw
 * @date 2015-05-14
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class NormsModel extends AdminbaseModel{
	protected $tableName="norms";
	 public $_validate = [
        ['norms_name','require','规格名称不能为空',self::EXISTS_VALIDATE],
        ['norms_name','','规格名称已存在',self::EXISTS_VALIDATE,'unique']
     ];
	 /**
	  * 添加商品规格值
	  * @param $data 规格值
	  * @param $where where条件
	  */
	 public function addNormsValue($data,$where=array()){
	 	$addData = array();
	 	$value_model = M('NormsValue');
	 	if(!empty($where)){
	 		$value_model->where($where)->delete();
	 	}
	    foreach($data['norms_value'] as $key=>$v){
	    	//过滤空值附件
	    	if($data['type'] == 2){
	    		if(!empty($v) && empty($data['noems_attr'][$key])){
	    			unset($data['norms_value'][$key]);
	    		}
	    	}
	    	if($v){
	    	 $addData[$key]['norms_value'] = $v;
	    	 $addData[$key]['norms_value_sort'] = empty($data['norms_value_sort'][$key])?0:$data['norms_value_sort'][$key];
	    	 $addData[$key]['norms_id'] = $data['norms_id'];
	    	 $addData[$key]['norms_attr'] = empty($data['norms_attr'][$key])?0:$data['norms_attr'][$key];
	    	}
	    }
	   return  $value_model->addAll($addData);
	 }
	 
	 /**
	  * 通过norms_id获取规格值
	  * @param $norms_ids 规格id 
	  * @param $field 字段
	  */
	 public function getNormsValue(Array $norms_ids,$field=true){
	 	$where = array(
	 			'norms_id'=>array('in',$norms_ids)
	 	);
	 	return M('NormsValue')->field($field)->where($where)->select();
	 }
	 /**
	  * 通过id获取值
	  * @param $norms_id 规格id
	  * @param $type 1:取规格  2：取规格值
	  */
	 public function getById($norms_id,$type=1,$field=true){
	 	$data = $this->field($field)->find($norms_id);
	 	//获取规格值
	 	if($type==2){
	 		$data['norms_values'] = $this->getNormsValue($data['norms_id']);
	 	}
	 	return $data;
	 }
	 /**
	  * 通过规格id查找分类
	  * @param  $norms_id
	  */
	 public function getCats($norms_id){
	 	return M('CatNorms')->where("norms_id={$norms_id}")->select();
	 }
	 /**
	  * 通过规格id获取关联商品
	  * @param  $norms_id
	  */
	 public function getGoods($norms_id){
	 	$ids = M('NormsValue')->where("norms_id={$norms_id}")->getField('norms_value_id',true);
	 	$where = array(
	 			'norms_value_id'=>array('in',$ids)
	 	);
	 	return M('GoodsNormsValue')->where($where)->select();
	 }
	 
	 /**
	  * 获取所有规格值
	  */
	 public function getNorms($field=true){
	 	return $this->field($field)->select();
	 }
	 /**
	  * 批量获取规格
	  * @param array $norms_ids 
	  */
	 public function getNormsByIds(Array $norms_ids,$field=true){
	 	$where = array(
	 			'norms_id'=>array('in',$norms_ids)
	 	);
	 	return $this->field($field)->where($where)->order("norms_sort desc")->select();
	 }
}