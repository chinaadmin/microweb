<?php
/**
 * 蜂蜜众筹
 * @author wenxiaobin
 * @date 2015-12-12
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: X-Requested-With');
class CrowdfundingController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
	}
	/**
	 *获取某众筹项目内容
	 *@param fkId 众筹项目id 
	 */
	function getProject(){
		$cr_id = I('request.fkId','0','intval');
		if(!$cr_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY'));
		}
		$fields = [
			'cr_id' => 	'fk_id',
			'cr_name' => 'project_name',
			'cr_content' => 	'project_content',
			'cr_travel_status',
			'cr_travel_content' => 'travel_content',
			'cr_count',
			'cr_apply_count'
		];
		$oneInfo = D('Admin/Crowdfunding')->scope('default,using')->where(['cr_id' => $cr_id])->field($fields)->find();
		if(!$oneInfo){ //无效
			$this->ajaxReturn($this->result->set('CROWDFUNDING_INVALID'));
		}
		if(!$oneInfo['cr_travel_status']){
			$oneInfo['travel_content'] = '';	
		}else{
			$oneInfo['travel_content'] = addEditorSrc($oneInfo['travel_content']); 
		}
		$oneInfo['project_content'] = addEditorSrc($oneInfo['project_content']); 
		$oneInfo['percentage'] = ceil($oneInfo['cr_apply_count']/$oneInfo['cr_count']).'%' ; 
		$oneInfo['ifTravelRegister'] = 0;
		$token = I("post.token","");
		if($token){
			$this->authToken();
			$where['cc_uid'] = $this->user_id;
			$where['fk_cr_id'] = $cr_id;
			$count = M('crowdfunding_collect')->where($where)->count();
			$oneInfo['ifTravelRegister'] = $this->ifTravelRegister(['tr_user_id' => $this->user_id,'fk_cr_id' => $cr_id]);
		}
		$oneInfo['isCollect'] = $count ? 1 : 0;//是否收藏
 		$oneInfo['left_count'] = $oneInfo['cr_count'] - $oneInfo['cr_apply_count'];
		unset($oneInfo['cr_travel_status']);
		unset($oneInfo['cr_count']);
		unset($oneInfo['cr_apply_count']);
		$this->ajaxReturn($this->result->success()->content(['data' => $oneInfo]));
	}
	//收藏项目
	function doCollect(){
		$this->authToken();
		$fk_cr_id = I('fkId',0,'int');
		if(!$fk_cr_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY'));
		}
		$m = M('crowdfunding_collect');
		$data['cc_uid'] = $this->user_id;
		$data['fk_cr_id'] = $fk_cr_id;
		if(!$m->where($data)->find()){ //未收藏过
			$data['cc_add_time'] = NOW_TIME;
			$m->add($data);
		}
		$this->ajaxReturn($this->result->success());
	}
	//展示众筹收藏
	function showCollect(){
		$this->authToken();
		$uid = $this->user_id;
		$m = new \Think\Model();
		$sql = "SELECT
			cr.cr_id fkid,
			cr.cr_name name,
			cc.cc_add_time
		FROM
			__PREFIX__crowdfunding_collect cc
		LEFT JOIN __PREFIX__crowdfunding  cr
		ON cc.fk_cr_id = cr.cr_id where cc.cc_uid = '{$uid}'";
		$res = $m->query($sql);
		if($res){
			$dateObj = new \Org\Util\Date();
			foreach ($res as &$v){
				$v['leave_now'] = $dateObj->timeDiff(date ( 'Y-m-d H:i:s', $v['cc_add_time'] ));
				$v['time'] = date ( 'Y-m-d H:i:s', $v['cc_add_time'] );
			}
		}else{
			$res = [];
		}
		unset($res['cc_add_time']);
		$this->ajaxReturn($this->result->content(['data' => $res])->success());
	}
	function delCollect(){
		$this->authToken();
		$fk_cr_id = I('fkId',0,'int');
		if(!$fk_cr_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY'));
		}
		$m = M('crowdfunding_collect');
		$res = $m->where(['fk_cr_id' => $fk_cr_id,'cc_uid' => $this->user_id])->delete();
		$this->ajaxReturn($this->result->success());
	}
	//报名旅游项目
	function travelRegister(){
		$this->authToken();
		$fk_cr_id = I('fkId',0,'int');
		if(!$fk_cr_id){
			$this->ajaxReturn($this->result->set('PARAM_EMPTY'));
		}
		$m = M('travel_register');
		$data['tr_user_id'] = $this->user_id;
		$data['fk_cr_id'] = $fk_cr_id;
		if($this->ifTravelRegister($data)){
			$this->ajaxReturn($this->result->success());
		}else{
			$data['tr_add_time'] = NOW_TIME;
			$m->add($data);
		}
		$this->ajaxReturn($this->result->success());
	}
	//是否已经有报名过
	protected function ifTravelRegister($where){
		$res = M('travel_register')->where($where)->count();
		return $res ? 1 : 0;
	}

	/**
	*	确认收货
	* @param String $orderId 订单id
	* @param String $goodsId 订单id
	* @date 2016-12-03
	*/
	public function receiveConfirm(){
		$orderId = I('orderId');  //订单id
		$goodsId = I('goodsId'); // 商品id

		// M('Crowdfunding_order')->where(['cor_order_id'=>$orderId])->save(['cor_shipping_status'=>3]);
		// $res = M('Crowdfunding_order_goods')->where(['fk_cor_order_id'=>$orderId])->save(['cog_shipping_status'=>3]);
		$res = M('Crowdfunding_order_goods')->where(['fk_cor_order_id'=>$orderId, 'cog_id' => $goodsId])->save(['cog_shipping_status'=>3]);

		$this->ajaxReturn($this->result->success()->content(['data' => $res]));
	}


	//获取物流跟踪信息
	function getLogisticTrace(){
		$this->authToken();
		$mail_no = I('mail_no','','trim');
		if(!$mail_no){
			$this->ajaxReturn($this->result->set('CROWDFUNDING_MAIL_NO_REQUIRE'));
		}
		$mail_no = [$mail_no];
		$kuidiObj = new \Common\Org\Util\ExpressDelivery();
		$fields = [
				'ltr_mail_no_status' => 'status',
				'ltr_accept_time' => 'accept_time',
				'ltr_remark' => 'content',
		];
		$res = $kuidiObj->getRecord($mail_no,$fields);
		foreach ($res as $k => &$v){
			$v['accept_time_one'] = date('Y/m/d',$v['accept_time']);			
			$v['accept_time_two'] = date('H:i',$v['accept_time']);			
			unset($res[$k]['accept_time']);
		}
		$summary = [];
		if($res){
			$find = M('order_send')->where(['send_num' => ['in',$mail_no]])->find();
			$orderSn = M('crowdfunding_order')->where(['cor_order_id' => $find['order_id']])->getField('cor_order_sn');
			$commpanyName = M('logistics_company')->where(['lc_id' => $find['logistics'] ])->getField('lc_name');
			$summary = [
					'commpanyName' => $commpanyName,
					'orderSn' => $orderSn
			];
		}
		$this->ajaxReturn($this->result->success()->content(['data' => $res,'summary' => $summary]));
	}
}