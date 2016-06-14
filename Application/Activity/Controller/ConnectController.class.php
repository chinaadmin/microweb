<?php
/**
 * 添加用户
 * @author xiongzw
 * @date 2015-07-10
 */
namespace Activity\Controller;
class ConnectController extends ActivityBaseController{
    protected $connect_model;
	public function _initialize(){
    	parent::_initialize();
    	$this->connect_model = D("Activity/Connect");
    }
	/**
	 * 添加手机用户
	 * <code>
	 *  mobile
	 * </code>
	 */
	public function addUser(){
		//验证验证码
		$this->verifySMS();
		$mobile = I("post.mobile",'');
		$user = $this->connect_model->getUser($mobile);
		if($user){
			$uid = $user['uid'];
		}else{
			$data = array(
					"username"=>$mobile,
					"mobile" => $mobile,
					"nick" => substr($mobile, 0,3)."***".substr($mobile, -2,2),
					"add_time"=>NOW_TIME
			);
			$result = $this->connect_model->addData($data);
			if($result->isSuccess()){
				$arr = $result->toArray();
				$uid = $arr['result'];
			}else{
				$this->ajaxReturn($result);
			}
		}
		$uid = path_encrypt($uid);
		$this->ajaxReturn($this->result->success()->content(['user'=>['uid'=>$uid]]));
	}
    
	/**
	 * 发送短信
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         mobile 手机
	 *         </code>
	 */
	public function sendSMS(){
		$type = I('post.type',8,'intval');
		$mobile = I('post.mobile','','trim');
	
		if(empty($mobile) || !checkMobile($mobile)){
			$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
		}
	
		$code_model = D('Code');
		$code = $code_model->setCode(6,1)->generate($type,$mobile);
	
		//发送短信
		$mess = new \Common\Org\Util\MobileMessage();
		$arr = [];
		$arr['mobile_code'] = $code;
		$tmp = 'verify_mobile';
		if($mess->sendMessByTel($mobile, $arr,$tmp) === true){//发送成功
			$this->ajaxReturn($this->result->success());
		}
	
		$this->ajaxReturn($this->result->set('SEND_MESSAGE_FAIL'));
	}
	
	/**
	 * 验证短信
	 * @author cwh
	 *         传入参数:
	 *         <code>
	 *         mobile 手机
	 *         code 验证码
	 *         </code>
	 */
	public function verifySMS(){
		$type = I('post.type',8,'intval');
		$code = I('post.code','');
		$mobile = I('post.mobile','','trim');
		if(empty($mobile) || !checkMobile($mobile)){
			$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
		}
	
		$code_model = D('Code');
		$where = [
		'extend' => json_encode($mobile)
		];
		$result = $code_model->where($where)->getInfo($code,$type);
		if(!$result->isSuccess()){
			$this->ajaxReturn($result);
		}
	}
}
