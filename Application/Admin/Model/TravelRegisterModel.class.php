<?php
/**
 * 旅游报名表
 * @author wxb
 * @date 2015-12-7
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class TravelRegisterModel extends AdminbaseModel{
	/**
	 * 试图模型
	 */
	public function viewModel(){
		$viewFields = array(
				'TravelRegister'=>array(
						'*',
						'_as' => 'tr',
						"_type"=>"LEFT"
				),
				'User'=>array(
						'*',
						'_as' => 'u',
						"_on"=>"tr.tr_user_id = u.uid",
						"_type"=>"LEFT"
				),
				'Crowdfunding'=>array(
						'*',
						'_as' => 'cr',
						"_on"=>"cr.cr_id = tr.fk_cr_id"
				),
		);
		return $this->dynamicView($viewFields);
	}
}