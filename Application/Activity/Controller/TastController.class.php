<?php
/**
 * 免费试吃
 * @author xiongzw
 * @date 2015-09-11
 */
namespace Activity\Controller;
class TastController extends ActivityBaseController{
	public function _initialize(){
		parent::_initialize();
		header ( 'Access-Control-Allow-Origin: *' );
	}
	/**
	 * 添加申请
	 */
	public function addTast(){
		//$this->check(I("post.code","")); 
		$data = array(
				"company"=>I("post.company",""),
				"company_address"=>I("post.company_address",""),
				"people_num"=>I("post.people_num",0,'intval'),
				"sector"=>I("post.sector",""),
				"position"=>I("post.position",0,'intval'),
				"name"=>I("post.name",""),
				"office_address"=>I("post.office_address",""),
				"mobile"=>I("post.mobile",""),
				"wechat"=>I("post.wechat",""),
				"add_time"=>NOW_TIME,
				"status"=>'0',
				"remark"=>'',
				"code"=>I("post.code","")
		);
		$result = D("Activity/Tast")->addData($data);
		$this->ajaxReturn($result);
	}
	
	/**
	 * 验证码
	 */
	public function verify(){
        $verify_config = [
            'imageH' => 30,
            'imageW' => 100,
            'fontSize' => 14,
            'length' => 4,
            'useNoise' => false,
            'useCurve' => false
        ];
        $vertify = new \Think\Verify ($verify_config);
        $vertify->entry();
	}
	
	public function check($code){
		$verify = new \Think\Verify();
		if(!$verify->check($code)){
			$this->ajaxReturn($this->result->error("验证码错误！"));
		}
	}
}