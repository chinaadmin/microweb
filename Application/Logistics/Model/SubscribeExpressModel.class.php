<?php
//跟踪快递单模型
namespace Logistics\Model;
use Common\Model\BaseModel;
class SubscribeExpressModel extends BaseModel{
	function getSubscribeView(){
		$viewField = [
				'subscribe_express' =>[
						'_as' => 'se',
						'_type' => 'left'
				],	
				'subscribe_express_detail' =>[
						'_as' => 'sed',
						'sed_sign_status',
						'sed_is_check',
						'_type' => 'left',
						'_on' => 'sed.fk_se_id = se.se_id'
				],
				'subscribe_express_subtabulation' =>[
						'_as' => 'ses',
						'_on' => 'ses.fk_sed_id = sed.se_id',
						'ses_context',
						'ses_add_time'
				]
		];
		return $this->dynamicView($viewField);
	}
}