<?php
namespace Api\Controller;
class AtivityController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
	}
	/**
	 * 十周年庆活动说明接口
	 * [description] 活动说明接口
	 * Author CL
	 * @return [type] [description]
	 */
	public function tenYearDetail(){
		$id = I('request.id');
		$list = M("awardPlan")->field('ap_name as title, ap_remark as content')->find($id);
		$this->ajaxReturn($this->result->content(['content' => $list])->success());
	}
}
