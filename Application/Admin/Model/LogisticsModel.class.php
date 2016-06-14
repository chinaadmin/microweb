<?php
/**
 * 物流管理模型
 * @author xiongzw
 * @date 2015-07-28
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class LogisticsModel extends AdminbaseModel{
	protected  $tableName = "logistics_company";
	public $_validate = [ 
			[ 
					'lc_name',
					'require',
					'物流公司不能为空' 
			],
			[ 
					'lc_name',
					'',
					'物流公司名称已存在',
					0,
					'unique',
					self::MODEL_BOTH 
			] ,
			[
			'lc_code',
			'uniqueCode',
			'物流公司代码已存在',
			0,
			'callback',
			self::MODEL_BOTH
			]
	];
	function uniqueCode($lc_code){
		$lc_id = I('lc_id',0,'int');	
		if($lc_id){ //更新  查看是否和其它纪录是一样的值
			$code = $this->where(['lc_id' =>['neq',$lc_id]])->getField('lc_code');
			return $code != $lc_code;
		}
		return !$this->where(['lc_code' => $lc_code])->count();
	}
	
	/**
	 * 获取物流公司
	 * @param string $field
	 */
	public function getLogistics($field=true){
		return $this->field($field)->select();
	}
	
	/**
	 * 通过物流id批量获取物流公司信息
	 * @param $id
	 * @param string $field
	 */
	public function getLogisticsById($id,$field=true){
		$where = array(
				"lc_id" => array('in',(array)$id)
		);
		return $this->field($field)->where($where)->select();
	}
	
	/**
	 * 通过物流id获取物流公司信息
	 * @param $id
	 * @param string $field
	 */
	public function getById($id,$field=true){
		return $this->field($field)->where(['lc_id'=>$id])->find();
	}
}