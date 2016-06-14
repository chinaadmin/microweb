<?php
/**
 * 众筹抽奖
 * @author wxb
 * @date 2016-01-15
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
class AwardController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		if(!in_array(strtoupper(ACTION_NAME), ['GETAWARDCONTENT'])){
			$this->authToken();
		}
	}
	//获取方案详情
	function getAwardContent(){
		if($this->token){//登入
			$this->authToken();
		}
		$uid = $this->user_id;//可以不登入
		$field = [
			'ap_id' => 'id',	//方案id
			'ap_name' => 'name',	//抽奖名
			'ap_remark' => 'remark',	//抽奖说明
		];
		$awardModel = D('Admin/Award');
		$plan = $awardModel->getUseingAward($field);
		$plan['remark'] = htmlspecialchars_decode($plan['remark']);
		$plan['remark'] = "<div style='color:white;background-color:#AB387A;'>".$plan['remark']."<div>";
		$award = [];
		$fieldDetail = [
				'apd_alias_name' => 'alias_name',
				'apd_pic_id',
				"fk_as_id",
				'apd_id' => 'id' //方案详情id
		];
		$plan['lessChance'] = $awardModel->getAwardChance($uid);
		$award = M('award_plan_detail')->where(['fk_ap_id' => $plan['id']])->field($fieldDetail)->select();
		$subject = M('award_subject');
		$picModel = D('Upload/AttachMent');
		foreach ($award as &$v){
			$v['name'] = $subject->where(['as_id' => $v['fk_as_id']])->getField('as_name');
			$v['src'] = fullPath($picModel->getAttach($v['apd_pic_id'])[0]['path']);
			unset($v['fk_as_id']);
			unset($v['apd_pic_id']);
		}
		$this->ajaxReturn($this->result->content(['plan' => $plan,'award' => $award])->success());
	}		
	//抽奖
	function draw(){
		$detailId = D('Admin/Award')->draw($this->user_id);
		if(!$detailId){
			$this->ajaxReturn($this->result->set('DRAW_LESS_ZERO'));
		}
		$tmp = M('award_plan_detail')->where(['apd_id' => $detailId])->find();
		$detail['alias_name'] = $tmp['apd_alias_name'];
		$detail['name'] =  M('award_subject')->where(['as_id' => $tmp['fk_as_id']])->getField('as_name');
		$detail['src'] = fullPath(D('Upload/AttachMent')->getAttach($tmp['apd_pic_id'])[0]['path']);
		$this->ajaxReturn($this->result->content(['detailId' => $detailId,'detailData' => $detail])->success());
	}
	//获取用户剩余抽奖名额
	function myDrawChance(){
		$lessChance=  D('Admin/Award')->getAwardChance($this->user_id);
		$this->ajaxReturn($this->result->content(['myAwardChance' => $lessChance])->success());
	}
	/*//我的奖品列表 add by yt 2016-03-22 我的奖品里不要展示红包及优惠劵 赠品正在规划中
	function myDrawList(){
		$data = D('Admin/Award')->myDrawList($this->user_id);
		$this->ajaxReturn($this->result->content(['data' => $data])->success());
	}*/
	//我的奖品列表 add by yt 2016-03-22 我的奖品里不要展示红包及优惠劵 赠品正在规划中
	function myDrawList() {
	    $data = [];
	    $this->ajaxReturn($this->result->content(['data' => $data])->success());
	}
	
	//领取奖品
	function getMyDraw(){
		$id = I('id',0,'int'); //奖品纪录id
		if(!$id){
			$this->ajaxReturn($this->result->set('DATA_ERROR'));
		}
		$m = M('award_record');
		$recordInfo = $m->where(['ar_id' => $id])->find();
		if($recordInfo['ar_is_reveive']){
			$this->ajaxReturn($this->result->set('DRAW_REVEIVE_ALREADY'));
		}
		//查看是否有收货地址
		$tmpModel = M('user_award_address');
		$addressId = $tmpModel->where(['aaa_uid' => $this->user_id])->getField('fk_address_id');
		if(!$addressId){
			$this->ajaxReturn($this->result->set('DRAW_ADDRESS_EMPTY'));
		}
		$shipping_address = M('user_shipping_address');
		$data = [];
		$field = [
				'user_provice' => "province",
				'user_city' => "city",
				'user_county' => "county",
				'user_localtion' => "localtion",
				'user_detail_address' => "address" ,
				'name',
		];
		$data = $shipping_address->where(['address_id' => $addressId])->field($field)->find();
		//核对地址是否有效
		if(!$data){
			$this->ajaxReturn($this->result->set('DRAW_ADDRESS_EMPTY'));
		}
		//纪录当前收货地址
		$receiptModel = M('award_receipt');
		$data['uid'] = $this->user_id;
		$data['fk_apd_id'] = $recordInfo['fk_apd_id'];
		$data['fk_ar_id'] = $recordInfo['ar_id'];
		$receiptModel->create($data);
		$receiptModel->add();
		//标记领取状态
		$res = $m->where(['ar_id' => $id])->save(['ar_is_reveive' => 1,'ar_reveive_time' => NOW_TIME]);
		if($res === false){
			$this->ajaxReturn($this->result->error());
		}
		$this->ajaxReturn($this->result->success());
	}
	//修改或者增加收货地址
	function addAddress(){
		$address_id = I('addressId',0,'int');
		if(!$address_id){
			$this->ajaxReturn($this->result->set('DATA_ERROR'));
		}
		$m = M('user_award_address');
		$data = [];
		$data['fk_address_id'] = $address_id;
		if($one = $m->where(['aaa_uid' => $this->user_id])->find()){
			$data['aaa_id'] = $one['aaa_id'];
			$res = $m->save($data);
		}else{
			$data['aaa_uid'] = $this->user_id;
			$res = $m->add($data);
		}
		if($res === false){
			$this->ajaxReturn($this->result->error());
		}
		$this->ajaxReturn($this->result->success());
	}
	//显示奖品收货地址
	function showMyAddress(){
		$m = M('user_award_address');
		$fk_address_id = $m->where(['aaa_uid' => $this->user_id])->getField('fk_address_id');
		if(!$fk_address_id){
			$this->ajaxReturn($this->result->set('DRAW_ADDRESS_EMPTY'));
		}
		$shipping = M('user_shipping_address');
		$field = [
				'address_id' => 'id',
				'name',
				'mobile',
				'user_provice' => 'provice',
				'user_city' => 'city',
				'user_county' => 'county',
				'user_localtion' => 'localtion',
				'user_detail_address' => 'detail_address'
		];
		$data = $shipping->where(['address_id' => $fk_address_id,'uid' => $this->user_id])->field($field)->find();
		$this->ajaxReturn($this->result->success()->content(['data' => $data ? $data:[]]));
	}
}