<?php
/**
 * 品牌模型
 * @author wxb
 * @date 2015/8/31
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class CustomizePageModel extends AdminbaseModel{
	public $_validate = [ 
			[ 
					'cg_title',
					'require',
					'自定义页标题不为空', 
					self::EXISTS_VALIDATE 
			],
			[ 
					'cg_title',
					'ifUniqueTitle',
					'自定义页标题已存在', 
					self::EXISTS_VALIDATE ,
					'callback'
			],
			[ 
					'cg_url',
					'require',
					'自定义页地址不为空',
					self::EXISTS_VALIDATE 
			],
			[ 
					'cg_url',
					'ifUniqueUrl',
					'自定义页地址已存在',
					self::EXISTS_VALIDATE ,
					'callback'
			],
			[ 
					'cg_content',
					'require',
					'自定义页内容不为空',
					self::EXISTS_VALIDATE 
			]
	];
	/**
	 * 自动验证
	 */
	protected $_auto = array (
			['cg_update_time','time',self::MODEL_BOTH,'function'],
			['cg_add_time','time',self::MODEL_UPDATE,'function']
	);
	function ifUniqueTitle($title){
		$id = I('cg_id',0,'int');
		$where = ['cg_id' => ['neq',$id],'cg_title' => $title];
		if(!$id){
			unset($where['cg_id']);
		}
		$count = $this->where($where)->count();
		return !$count;
	}
	function ifUniqueUrl($url){
		$id = I('cg_id',0,'int');
		$where = ['cg_id' => ['neq',$id],'cg_url' => $url];
		if(!$id){
			unset($where['cg_id']);
		}
		$count = $this->where($where)->count();
		return !$count;
	}
}