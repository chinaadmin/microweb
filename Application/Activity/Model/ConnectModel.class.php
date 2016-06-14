<?php
/**
 * 微信用户模型
 * @author xiongzw
 * @date 2015-07-09
 */
namespace Activity\Model;
class ConnectModel extends ActivityBaseModel{
	protected $tableName = "activity_user";
	/**
	 * 通过手机号获取用户信息
	 * @param String $mobile 手机号
	 */
	public function getUser($mobile,$field=true){
		$where = array(
				"mobile"=>$mobile
		);
		$data = $this->field($field)->where($where)->find();
		return $data;
	}
	/**
	 * 判断用户是否被禁用
	 * @param $uid 用户id
	 */
	public function isDidsble($uid){
		$status = current($this->getUserByUid($uid,"status"));
		if(is_null($status)){
			return $this->result()->set("UID_REQUIRE");
		}
		if(!$status){
			return $this->result()->set("USER_ISDISABLE");
		}else{
			return $this->result()->success();
		}
	}
	/**
	 * 根据用户id查询用户
	 * @param $uid 用户id
	 */
	public function getUserByUid($uid,$field=true){
		return $this->field($field)->where(['uid'=>$uid])->find();
	}
}