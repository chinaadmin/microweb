<?php
/**
 * 前台公共模型类
 * @author cwh
 * @date 2015-04-01
 */
namespace Common\Model;
class HomebaseModel extends BaseModel{

    protected $code_file = 'home';
    protected $size; //缩略图尺寸
	protected $_scope = [ 
			'goods' => [  // 获取正常状态
					'where' => [ 
							'delete_time' => 0,
							'is_goods' => 1,
							'is_sale' => 1 
					] 
			] 
	];
	public function _initialize(){
		parent::_initialize();
		$this->size = C('THUMB_SIZE');
	}
}