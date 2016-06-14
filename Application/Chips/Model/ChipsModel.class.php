<?php
/**
 * 众筹方案
 * @author xiongzw
 * @date 2015-12-10
 */
namespace Chips\Model;
use Api\Model\ApiBaseModel;
class ChipsModel extends ApiBaseModel{
	protected $autoCheckFields = false;
	protected $inside_discount_count = 2;//内部员工限购次数
	/**
	 * 通过项目获取众筹方案
	 * @param 项目id
	 */
	public function getScheme($id){
		return M("CrowdfundingDetail")->where(['fk_cr_id'=>$id])->select();
	}
	
	/**
	 * 获取众筹方案下的商品
	 * @param $sid 方案id
	 */
	public function getSchemeGoods($sid){
		$where = array(
				'fk_cd_id'=>array('in',(array)$sid)
		);
		return M("CrowdfundingGoods")->where($where)->select();
	}
	
	/**
	 * 获取众筹项目详情
	 * @param  $id 众筹id
	 */
	public function getChipsInfo($id){
		return M('Crowdfunding')->where(['cr_id'=>$id])->find();
	}
	
	/**
	 * 获取方案详情
	 * @param $sid 方案id
	 * @param $only_term 第几期
	 */
	public function schemeInfo($sid,$only_term=1,$uid=''){
		$info = M("CrowdfundingDetail")->where(['cd_id'=>$sid])->find();
		// $discount = $this->userDiscount($info['fk_cr_id'], $uid);	// 去掉员工价
		$discount = 10;
		
		//获取方案每期金额
		$termilly = M("CrowdfundingPerpay")->where(['fk_cd_id'=>$sid])->order("cp_term_index asc")->select();
		$term = array();
		$unit = "";
		switch($info['cd_period_unit']){
			case 1:
				   $unit="年";
				   break;
			case 2:
				   $unit="季";
				   break;
			default:
				   $unit = "月";
		}
		$count_money = 0;//一个产口总金额
		$money=0;
		foreach($termilly as $key=>$v){
			$term[$key] = array(
					'term'=>"第".$v['cp_term_index'].$unit."支付",
					'money' => $v['cp_pay_money']*($discount/10)
			);
			$count_money+=$v['cp_pay_money'];
			if($only_term==$v['cp_term_index']){
				$money = $v['cp_pay_money'];
			}			
		}
		$return_data = array(
			'payType'=>$info['cd_pay_type'],
			'cdName'=>$info['cd_name'],
			'subhead'=>$info['cd_subhead'],
		    'money'=>$money*($discount/10), //当前支付金额
		    'only_trem'=>$only_term,
			'term'=>$term,
			'oneGoodsTotal' => $count_money*($discount/10), //当前一个产品所有周期总金额		
			'discount'=>$discount,
			'discountMoney'=>$money*($this->userDiscount($info['fk_cr_id'], '',true)/10) //产品会员折扣金额
		);
		return $return_data;
	}
	
	/**
	 * 计算内部员工折扣
	 * @param $fid 项目id
	 * @param $uid 用户id
	 * @param boolean $both 
	 */
	public function userDiscount($cr_id,$uid,$both = false){
		$is_insert_user = M("User")->where(['uid'=>$uid,'inside_discount_use' => ['lt',$this->inside_discount_count]])->getField("is_inside_user");
		$discount = 10;
		if($is_insert_user || $both){
			$discount = M("Crowdfunding")->where(['cr_id'=>$cr_id])->getField("cr_staff_discount");
		}
		return $discount;
	}
	
	/**
	 * 获取众筹商品
	 * @param $cgIds 商品id
	 */
	public function getChipsGoods($cgIds){
		$where = array(
				'cg_id'=>array('in',(array)$cgIds)
		);
		return M("CrowdfundingGoods")->where($where)->select();
	}
	
	/**
	 * 增加申请众筹人数
	 * @param int $projectId 众筹项目id
	 * @param int $count 增加人数
	 * @return boolean
	 */
	function addApply($projectId,$count){
		$m = M('Crowdfunding'); 
		$allCount = $m->where(['cr_id' => $projectId])->field(['cr_count','cr_apply_count'])->find();
		$maxCount = $allCount['cr_count'] - $allCount['cr_apply_count'];
		if($count > $maxCount){ //大于可报名数
			return false;
		}
		$res = $m->where(['cr_id' => $projectId])->setInc('cr_apply_count',$count);
		return $res == false ? false : true;
	}
	//减少申请众筹人数
	function removeApply($projectId,$count){
		$m = M('Crowdfunding');
		$allCount = $m->where(['cr_id' => $projectId])->field(['cr_count','cr_apply_count'])->find();
		if($count > $allCount['cr_apply_count']){ //大于最大已申请人数
			return true;
		}
		$res = $m->where(['cr_id' => $projectId])->setDec('cr_apply_count',$count);
		return $res == false ? false : true;
	}


	/**
	 * 获取众筹方案下的商品和备选方案商品
	 * @param $arr 方案下的商品
	 * @param $sid 方案id
	 */
	public function getAlternativeGoods($arr, $sid){
		$list = array();
		$where = array(
			'fk_cd_id'=>array('in',(array)$sid)
		);
		foreach($arr as $v){
			$where['cg_id'] = array('neq', $v['cg_id']);
			$v['alternativegoods'] = M("CrowdfundingGoods")->where($where)->select();
			$list[] = $v;
		}
		return $list;
	}




}