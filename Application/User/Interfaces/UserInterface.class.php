<?php
/**
 * 用户接口
 * @author cwh
 * @date 2015-04-01
 */
namespace User\Interfaces;
interface UserInterface {

	/**
	 * 通过id获取用户
	 * @param integer $id
     * @return Results
	 */
	public function getUserById($id);
	
	/**
	 * 登录流程
	 * @param string $account 用户帐号
	 * @param string $pasword 用户密码
	 * @param $type 1为用户名,2为电子邮箱,3为手机账号
     * @return Results
	 */
	public function loginAuth($account , $pasword , $type  = 1);

	/**
	 * 登录日志回调
	 * @param integer $id 用户id
	 * @param array $data 登录数据
	 * @param integer $status 登录结果代码
	 * @param string $info 登录结果
	 */
	public function recordLogin($id , $data , $status , $info = '');
	/**
	 * 用户名显示
	 * @param int $uid 用户id
	 * @return string $showname 用户显示名
	 */
	public function showUserName($uid);	
}