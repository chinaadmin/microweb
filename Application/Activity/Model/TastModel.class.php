<?php
/**
 * 免费试吃模型
 * @author xiongzw
 * @date 2015-09-11
 */
namespace Activity\Model;
class TastModel extends ActivityBaseModel{
	protected  $tableName="activity_tast";
	protected $_validate = array(     
			array('company','require','请填写公司名称！'),
			array('name','require','请填写您的姓名！'),
			array('mobile','require','请填写手机号！'),
			array('mobile','/1[3458]{1}\d{9}$/','请填写正确的手机号！'),
			array('mobile','','手机号已存在！',0,'unique'),
			array('wechat','require','请填写您的微信号！'),
			array('wechat','','该微信号已存在！',0,'unique'),
			array('code','require','验证码不能为空！'),
			array('code','checkVerify','验证码错误！',0,'callback')
	);
	
	public function checkVerify(){
		$code = I("post.code","");
		$verify = new \Think\Verify();
		if(!$verify->check($code)){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 *公司人数 
	 */
	public function peopleNum(){
		return array(
				'1'=>'20-50',
				'2'=>'50-100',
				'3'=>'100-200',
				'4'=>'200以上'
		);
	}
	
	/**
	 * 公司职位
	 */
	public function position(){
		return array(
				'1'=>'员工',
				'2'=>'主管',
				'3'=>'经理',
				'4'=>'总监',
				'5'=>'总裁',
				'6'=>'董事长'
		);
	}
}