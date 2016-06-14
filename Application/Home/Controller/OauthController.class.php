<?php
/**
 * 第三方登陆
 * @author xiongzw
 * @date 2015-05-06
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Org\ThinkSDK\ThinkOauth;
class OauthController extends HomeBaseController {
   private $sns;
   private $type;
   public function _initialize() {
		parent::_initialize ();
		if(in_array(ACTION_NAME,['checktel','checkusername'])){
			return;
		}
		$this->type = I ( 'request.type', '','strtolower' );
		if (empty ( $this->type ))
			$this->error ( "参数错误！" );
		$this->sns = ThinkOauth::getInstance ( $this->type );
	}   
	/**
	 * 登陆
	 */
	public function login($role=null) {
		if ($this->user_instance->isLogin ()) {
			$this->redirect ( 'I/index' );
		}
		if($this->type=='wechat'){
			$this->wechat($role);
		}
		redirect ( $this->sns->getRequestCodeURL ($role) ); // 跳转到授权页
	}
	
	/**
	 * 微信生成二维码登陆
	 */
	private function wechat($role=""){
		$wechatApi= "https://open.weixin.qq.com/connect/qrconnect?";
		$redirect_uri = C('THINK_SDK_WECHAT.CALLBACK');
		if($role){
			$redirect_uri .= "/role/".$role;
		}
		$options = array(
				'appid' => C('THINK_SDK_WECHAT.APP_KEY'),
				'redirect_uri' => $redirect_uri,
				'response_type' => 'code',
				'scope' => 'snsapi_login',
				'state' => md5(time().rand(100,999))
		);
		$url = $wechatApi.http_build_query($options)."#wechat_redirect";
		redirect($url);
	}
	
	/**
	 * 授权回调地址
	 * 
	 * @param string $type
	 *        	类型
	 * @param string $code
	 *        	返回的code值
	 */
	public function callback($type = null, $code = null) {
		if(empty($code)){
			$this->redirect('/');//跳到首页
		}
		$extend = null;
		$type = $this->type;
		if ($type == 'tencent') { // 腾讯微博需传递的额外参数
			$extend = array (
					'openid' => $this->_get ( 'openid' ),
					'openkey' => $this->_get ( 'openkey' ) 
			);
		}
		// 请妥善保管这里获取到的Token信息，方便以后API调用
		// 调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
		try {
			$token = $this->sns->getAccessToken ( $code, $extend );
		}catch (\Exception $e){
			$this->error('页面过期',U('/'));
		}
	/* 	array (size=4)
		'access_token' => string '4A2070FC9DE6E7737FDE58FD798D297C' (length=32)
		'expires_in' => string '7776000' (length=7)
		'refresh_token' => string '361F4F766C9B4DC1002E8F2027D9B47E' (length=32)
		'openid' => string 'CDF306297FE94A80F3CAEF23925476B8' (length=32) 
	    'unionid' => string 'oP_T-t5htfn3Eiyo0-IGMJ58BWd4' (length=28)
	*/
		$uid = 0;
		if (is_array ( $token )) {
			$openid = $token ['openid'];
			$oauth_model = D ( 'Home/Oauth' );
			$unionid = empty($token['unionid'])?'':$token['unionid'];
			$connect = $oauth_model->isBind ( $openid, $type,$unionid );
			if (! empty ( $connect )) {//已绑定 直接跳到首页
				$uid = $connect ['uid'];
				$url = session ( 'referer_url' ) ? session ( 'referer_url' ) : "/";
				$this->user_instance->toLogin ( $uid );//直接登入
				redirect ( $url );
			} else {
			   $this->token = urlencode(json_encode($token));
			   $this->assign('type',$type);
				//填写注册信息页 
				$this->display('bindopen');
			}
		}else{
			$this->error('获取第三方数据失败!');
		}
	}
	/**
	 * 下载头像
	 * 
	 * @param $uid 用户id        	
	 * @param $url 头像路径        	
	 */
	private function userPic($url, $uid) {
		$attUser = array (
				'is_admin' => 0,
				'uid' => $uid,
				'remark' => '',
				'model' => 'Home' 
		);
		return D ( 'Home/Oauth')->userPic($url,$attUser);
	}
	/**
	 * 创建第三方帐号
	 */
	public function  ajaxCreateOpen(){
		$token = I('token','','trim');
		$type = I('type','','trim');
		$way = I('way','','trim');//绑定或创建
		$user_connect = [];
     	$token = json_decode(urldecode($token),true);
		$user_info = A ( 'Login', 'Event' )->$type ( $token ); // 获取当前第三用户信息
		if($way == 'new'){//两种 1：未有帐号绑定第三方  2：已有帐号绑定第三方
			// 插入user数据
			$user = array (
					'username' => I('userName','','trim'),
					'aliasname' => $user_info ['nick'],
					'pass' => I('passWord','','trim'),
					'status' => 1,
					'add_time' => NOW_TIME,
					'mobile' => I('telephone','','trim')
			);
			$result = D('User/User')->addData($user);
			$uid = $result->getResult();
			if(!$result->isSuccess()){
				$this->ajaxReturn($result->toArray());
			}
			$att = $this->userPic ( $user_info ['head'], $uid );//下载头像
			D('User/User')->where(['uid' => $uid])->save(['headAttr' => $att ['att_id']]);//追加头像数据到用户表
		}else{//帐号主动绑定
			$user = array (
					'username' => I('userName','','trim'),
					'pass' => getpass(I('passWord','','trim'))
			);
			$uid = D('User/User')->scope()->where($user)->getField('uid');
			if(!$uid){
				$this->ajaxReturn($this->result->set('PASSWORD_ERROR')->toArray());//密码错误
			}
		}
		//绑定第三方**************************start**********************************
		$user_connect = array (
				'nick' => $user_info ['nick'],
				'pic' => $user_info ['head'],
				'openid' => $token['openid'],
				'type' => $type,
				'add_time' => NOW_TIME
		);
		$user_connect['uid']=$uid;
		M('UserConnect')->add($user_connect);//绑定
		if($token['unionid']){
			D ( 'Home/Oauth')->addUnionid($token['openid'],$token['unionid']);
		}
		$this->userLogin($uid);
		//绑定第三方**************************end**********************************	
		$this->ajaxReturn($this->result->success()->toArray());	
	}
	function checkUserName(){
		$userName = I('userName');
		$ifCanUse = D('User/User')->ifExist(3,$userName);
		$type = I('type',1,'int');
		if($type == 1){
			$ifCanUse = !$ifCanUse;
		}
		if($ifCanUse){
			echo 'true';
		}else{
			echo 0;
		}
	}
	function checkTel(){
		$tel = I('telephone',0,'trim');
		$ifCanUse = D('User/User')->ifExist(2,$tel);
		if($ifCanUse){
			echo 'true';
		}else{
			echo 0;
		}	
	} 
	private function userLogin($uid){
		if ($this->user_instance->isLogin ()) {//已有帐号登入就登出
			$this->user_instance->logout ();
		}
		$this->user_instance->toLogin($uid);
	}
}