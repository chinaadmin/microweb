<?php
/**
 * 抽奖模型
 * @author: xiaohuakang
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class DrawModel extends AdminbaseModel{
	protected $tableName = 'award_record';
	//视图模型
	public function viewModel(){
		$viewFields = [
			'award_record' => [
				'*',
				'_as' => 'ar',
				'_type' => 'LEFT'
			],
			'User' => [
				'username',
				'aliasname',
				'mobile',
				'_as' => 'u',
				'_on' => 'ar.ar_uid = u.uid',
				'_type' => 'LEFT' 
			],
			'award_plan_detail' =>[
				'_as' => 'apd',
				'_on' => 'apd.apd_id = ar.fk_apd_id',
			   '_type' => 'LEFT'
			],	
			'award_subject' => [
				'as_name',
				'as_type',
				'_as' => 'asj',
				'_on' => 'asj.as_id = apd.fk_as_id',
				'_type' => 'LEFT'
			],
			'award_plan' => [
				'ap_name' => 'awardPlanName',
				'ap_start_time' => 'awardPlanStartTime',
				'ap_end_time' => 'awardPlanEndTime',
				'_as' => 'ap',
				'_on' => 'ar.fk_ap_id = ap.ap_id',
				'_type' => 'LEFT'
			],
			'award_receipt' => [
				'localtion',
				'address',
				'tel',
				'mobile' => 'awardReceiptMobile',
				'name' => 'awardReceiptName',
				'_as' => 'art',
				'_on' => 'art.fk_ar_id = ar.ar_id',
				'_type' => 'LEFT'
			]
		];
		return $this->dynamicView($viewFields);
	}

	//获取快递订单数据
	public function getExpressBillData($arid){
		$lists = $this->viewModel()->where(['ar_id' => $arid])->find();
		$data['senderName'] = '深圳积土电子商务有限公司';
		$data['senderTel'] = '4007777927 ';
		$data['remark'] = '抽奖快递';
		$data['senderAdd'] = '';
		$data['senderPostcode'] = '';
		$data['recipientsName'] = $lists['awardreceiptname'];
		$data['recipientsTel'] = $lists['awardreceiptmobile'];
		$data['recipientsAddr'] = $lists['localtion'].'  '.$lists['address'];
		return  $data;
	}
}