<?php
/**
 * 地推人员管理模型
 */
namespace Api\Model;
use Common\Model\BaseModel;
class MarketPushingRecommendModel extends BaseModel{
    protected $code_file = 'api';
    function viewModel($filedsMap = null){
    	$viewFields = [
    			'MarketPushingRecommend' =>[
    					'_as' => 'm',
    					'_type' => 'left',
    					'*'
    			],
    			'AdminUser' => [
    				'_as' => 'a',
    				'_on' => 'a.uid = m.mpc_recommend',
    				'*'		
    			]
    	];
    	if($filedsMap['MarketPushingRecommend']){
    		unset($viewFields['MarketPushingRecommend'][0]);
    		if($filedsMap['MarketPushingRecommend'] != -1){ // -1 不为取任何字段
    			$viewFields['MarketPushingRecommend'] = array_merge($viewFields['MarketPushingRecommend'],$filedsMap['MarketPushingRecommend']); 
    		}
    	}
    	if($filedsMap['AdminUser']){
    		unset($viewFields['AdminUser'][0]);
    		if($filedsMap['AdminUser'] != -1){
    			$viewFields['AdminUser'] = array_merge($viewFields['AdminUser'],$filedsMap['AdminUser']);
    		}
    	}
    	return $this->dynamicView($viewFields);
    }
}