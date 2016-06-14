<?php
/**
 * 到货通知模型
 * @author xiongzw
 * @date 2015-09-17
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class ArrivalModel extends AdminbaseModel{
	protected $tableName = "arrival_notice";
	/**
	 * 试图模型
	 */
	public function viewModel(){
	  $viewFields = array (
				'ArrivalNotice' => array (
						"*",
						"_type"=>"LEFT"
				),
				'Goods' => array (
						'name',
						'_on' => 'ArrivalNotice.goods_id=Goods.goods_id',
						'_type'=>'LEFT'
				),
				'User' => array (
						'username',
						'mobile',
						'email',
						'_on' => 'ArrivalNotice.uid=User.uid' 
				) 
		);
	  return $this->dynamicView($viewFields);
	}
}
