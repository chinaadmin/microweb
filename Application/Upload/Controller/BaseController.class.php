<?php
/**
 * 上传基础控制器
 * @author xiongzw
 * @date 2015-04-08
 */
namespace Upload\Controller; 
use Think\Controller;
use User\Org\Util\User;
class BaseController extends Controller{
	protected $arr = array();
	protected $user;
	public function _initialize() {
		// html5 跨域上传
		header ( 'Access-Control-Allow-Origin: *' );
		header ( 'Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,' );
		$model = I('request.model',0,'intval');
		if($model==1){
			C('USER_MODEL',"User/User");
		}
		$this->user = User::getInstance (); 
		$uid = $this->user->isLogin ();
		$login = I('request.login', 0, 'intval' );
		if (! $login) {
			if (! $uid) {
				$result = [ 
						'success' => false,
						'error' => '用户未登陆',
						'state' => '用户未登陆' 
				];
				$this->ajaxReturn ( $result );
			} else {
				$isAdmin = $this->user->isAdmin ();
				$this->arr = array (
						'is_admin' => $isAdmin ? 1 : 0,
						'uid' => $uid,
			);
		}  
		}
	}
}