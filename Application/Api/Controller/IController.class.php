<?php
/**
 * 我页面
 * @author wxb
 * @date 2015-08-12
 */
namespace Api\Controller;
class IController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
	}
	/**
	 * 我页面首页
	 */
	public function index(){
		$res = D('User/Credits')->getCredits($this->user_id);
		
		$growth = $res[3];
		$grade_name  = D("User/UserGrade")->getUserGrade(intval($growth));
		$this->ajaxReturn($this->result->success()->content(['money' => $res[0], 'integral' => intval($res[1]),'grade_name'=>$grade_name]));
	}
	function creditsDetail(){
		$credits = D('User/Credits')->getCredits($this->user_id);
		$account_log_model = M('AccountLog');
		$where = [
				'uid'=>$this->user_id,
				'credits_type'=>1
		];
		//搜索类型，0为全部，1为收入，2为消费
		$type = I('type', 0, 'intval');
		switch($type){
			case 1:
				$where['credits'] = ['egt',0];
				break;
			case 2:
				$where['credits'] = ['lt',0];
				break;
		}
		$field = [
				'log_id',
				'credits', //消费金额
				'remark',//备注
				'add_time' //添加时间
		];
		$account_log_lists = $this->_lists ($account_log_model, $where ,'add_time desc',$field);
		foreach ($account_log_lists['data'] as &$v){
			$v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			unset($v['type']);
		}
		$account_log_lists['credits'] = $credits[1];
		$coupon_model = D('User/Coupon')->viewModel();
		$five_days_time = strtotime('+5 days');//计算5天后的时间戳
		$where = [];
        $where['start_time'] = ['lt',NOW_TIME];
		$where['end_time'] = ['between',[NOW_TIME,$five_days_time]];
        $where['use_time'] = 0;
        $where['CouponCode.uid'] = $this->user_id;
		$account_log_lists['credits_ending'] = $coupon_model->where($where)->sum('count');
		$this->ajaxReturn($this->result->content($account_log_lists)->success());
	}
}