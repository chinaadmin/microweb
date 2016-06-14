<?php
/**
 * 发件仓库模型
 * @author xiongzw
 * @date 2015-08-08
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class WareHouseModel extends AdminbaseModel{
	public $_validate = [ 
			[ 
					'ware_name',
					'require',
					'发货点名称不能为空' 
			],
			[ 
					'ware_username',
					"require",
					'联系人不能为空' 
			],
			[ 
					'provice',
					"require",
					'请选择省份' 
			],
			[ 
					'city',
					"require",
					'请选择市' 
			],
			[ 
					'county',
					"require",
					'请选择区' 
			],
			[ 
					'ware_address',
					"require",
					'详细地址不能未空' 
			],
			[ 
					'ware_zipcode',
					'require',
					'邮编不能为空' 
			],
			[ 
					'ware_mobile',
					'require',
					'手机号不能为空' 
			],
			[ 
					'ware_mobile',
					'/^1[3458]{1}\d{9}$/',
					'请填写正确的手机号' 
			]
	];
	
	protected $_scope = array(
           "default"=>array(
           		"where"=>array(
           		   "ware_status"=>1		
                )
	       )
	);
	
	/**
	 * 获取所有仓库
	 * @param $field 字段
	 */
	public function getWares($field=true){
		return $this->scope()->select();
	}
	
	/**
	 * 通过id获取仓库信息
	 */
	public function getWareById($ware_id,$field=true){
		return $this->field($field)->where(['ware_id'=>$ware_id])->find();
	}
	
	/**
	 * 批量获取发件人信息
	 */
	public function getWaresById($ware_ids,$field=true){
		if(!is_array($ware_ids)){
			$ware_ids = explode(',', $ware_ids);
		}
		$where = array(
				"ware_id"=>array('in',$ware_ids)
		);
		return $this->field($field)->scope()->where($where)->select();
	}
	/**
	 * 设置默认
	 */
	public function setDefault($ware_id){
		$where = array(
				'ware_id'=>array('neq',$ware_id)
		);
		return $this->setData($where, array('is_default'=>0));
	}
}