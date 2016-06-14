<?php
/**
 * 品牌模型
 * @author
 * @date xiongzw
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class BrandModel extends AdminbaseModel{
	public $_validate = [ 
			[ 
					'name',
					'require',
					'品牌名称不能为空' 
			],
			[ 
					'name',
					"1,20",
					'品牌名称不能超过20个字',
					0,
					'length' 
			],
			[ 
					'name',
					'',
					'品牌名称已存在',
					0,
					'unique',
					self::MODEL_BOTH 
			]
	];
	/**
	 * 自动验证
	 * @var unknown
	 */
	protected $_auto = array (
			array('logo','updateLogo',self::MODEL_UPDATE,'callback')  //更新图片，删除原图
	);
	
	public function updateLogo(){
	     $id = I('post.id',0,'intval');
	     if(id){
	     	$logo = $this->getById($id,"logo");
	     	$logo = json_decode($logo['logo'],true);
	     	$upLogo = I ( 'post.attachId');
	     	if($logo && $upLogo){
	     		$diff = array_diff($logo,$upLogo);
	     		if($diff){
	     			D('Upload/AttachMent')->delById($diff);
	     		}
	     	}
	     }	
	     return json_encode($upLogo);
	}
	/**
	 * 获取所有品牌
	 * @param $where  where条件
	 * @return array
	 */
	public function getBrands($where=array(),$field=true){
		$where['status'] =1;
		if(is_string($field)){
			$field.=",logo";
		}
		if(is_array($field)){
			$field[] = "logo";
		}
		$data = $this->field($field)->where($where)->select();
		foreach($data as &$v){
			$v['logo'] = json_decode($v['logo'],true);
			$path = D('Upload/AttachMent')->getAttach($v['logo']);
			$v['logo'] = $path[0]['path'];
		}
		return $data;
	}
	
	/**
	 * 通过id获取品牌
	 * @param $id 品牌id
	 * @param $field 字段
	 * @return array
	 */
	public function getById($id,$field=true){
		return $this->field($field)->where("brand_id={$id}")->find();
	}
}