<?php
/**
 * 用户登入|找回密码
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;
use Think\Verify;
use User\Org\Util\Integral;

class PassportController extends HomeBaseController {

    const VERIFYID = 'jt_verify';
	private $verify = ['jt_backpassord'];		
    /**
     * 登录
     */
    public function login(){
        if($this->user_instance->isLogin()){
            $this->redirect ( 'I/index' );
        }
        session("referer_url",$_SERVER['HTTP_REFERER']);
        $this->assign('need_verify',$this->_need_verify());
        $this->display();
    }

    /**
     * 产生验证码
     */
    public function genreateVerify() {
        $verify_config = C ( 'VERIFY_CONFIG' );
        $vertify = new Verify ( $verify_config );
        $vertify->entry ( self::VERIFYID );
    }
    /**
     * 验证登录
     */
    public function verify(){
        $username = I ( 'post.username', '', 'htmlspecialchars,trim' );
        $password = I ( 'post.password', '', 'htmlspecialchars,trim' );
        if (empty ( $username )) {
            $this->ajaxReturn($this->result->set('ACCOUNT_REQUIRE')->toArray());
        }
        if (empty ( $password )) {
            $this->ajaxReturn($this->result->set('PASSWORD_REQUIRE')->toArray());
        }

        if($this->_need_verify()) {
           //验证码验证
           $this->doVerify();
        }

        // 启用登录日志
        $this->user_instance->recordLog = false;
        //验证账号和密码
        $credentials = [
            'account' => $username,
            'password' => $password,
            'source'=>SharedModel::SOURCE_PC
        ];
        $result = $this->user_instance->login ( $credentials,0,true );
        if (!$result->isSuccess()) {//登录失败
            $need_verify = session('need_verify');
            $need_verify = empty($need_verify)?1:($need_verify+1);
            session('need_verify',$need_verify);
            $this->ajaxReturn($result->content(['need_verify'=>$this->_need_verify()])->toArray());
        } else {
            //$uid = $result->getResult();
        }
        session('need_verify',null);
        $this->ajaxReturn($this->result->success('登录成功')->toArray());
    }

    /**
     * 需要验证码
     */
    public function _need_verify(){
        $need_verify = session('need_verify');
        return $need_verify > 3;
    }
    /**
     * 验证用户名是否唯一
     */
    public function verifyTel() {
		if (IS_AJAX) {
			$username = I ( 'request.username');
			$result = D ( 'User/Account' )->loginAuth($username);
			if ($result->isSuccess()) {
				$this->ajaxReturn ( false );
			} else {
				$this->ajaxReturn ( true);
    	    }
    	}
    }
    
    /**
     * 验证码验证
     */
    public function doVerify(){
    	//验证验证码
    	$vertify = new Verify();
    	$verify_code = I('post.verify', '', 'htmlspecialchars,trim');
    	if ($vertify->check($verify_code, self::VERIFYID) === false) {
    		$this->ajaxReturn($this->result->set('VERIFICATION_CODE_ERROR')->toArray());
    	}
    }

    /**
     * 注册
     */
    public function reg(){
    	if(IS_AJAX){
    		$id = I('post.id');
    		if(!session('dealCode_'.$id)){ //未通过图形验证码验证
    			$this->ajaxReturn($this->result->set('VERIFICATION_CODE_ERROR')->toArray());
    		}
    		//手机验证码验证
    		ob_start();
    		ob_implicit_flush(0);
    		$this->checkRegMobCode();
    		$checkCode = ob_get_clean();
    		if(!$checkCode){ //未通过手机验证码验证
    			$this->ajaxReturn($this->result->set('MOBILE_CODE_ERROR')->toArray());
    		}
    		$data = array(
    			'username' => I('post.username',''),
    			'mobile' => I('post.username',''),
    			'pass' => I('post.password',''),
    			'status'=>1,
    			'mobile_status'=>1,
    			'grade_id'=>D("User/UserGrade")->getDefaultGrade(),	
                'come_from' =>SharedModel::SOURCE_PC //来源
    		);
            $user_model = D('User/User');
    		$result = $user_model->addData($data);
    		if($result->isSuccess()){
                if ($this->user_instance->isLogin ()) {//已有帐号登入就登出
                    $this->user_instance->logout ();
                }
                $uid = $result->getResult();

                //是好友推荐
                $invitation = cookie('invitation');
                if(!empty($invitation)){
                    $user_model->recommend($uid,$invitation);
                }
					
                //地推推荐
                $invite_code = I('invite_code','','trim');
                if($invite_code){
                    D('Admin/Admin')->recommend($uid,$invite_code);
                }
                
                //注册积分添加
                $integral = Integral::getInstance();
                $integral->run('register',$uid);
                $this->user_instance->toLogin($uid);
    		}
    		$this->ajaxReturn($result->toArray());
    	}
    	cookie('vertifyConfig',['imageH' => 42,'imageW' => 128,'fontSize' => 18]);
        $invitation = I('get.invitation');
        if(!empty($invitation)){
            cookie('invitation',$invitation,60*60*24);//一天之内有效
        }
        $this->display();
    }

    /**
     * 用户登出
     */
    public function logout() {
        $uid = $this->user_instance->isLogin ();
        if ($uid) {
            $this->user_instance->logout ();
            //$this->success ( '登出成功');
        } else {
            //$this->error ( '已经退出登录' );
        }
        if($_SERVER['HTTP_REFERER']){
        	redirect($_SERVER['HTTP_REFERER']);
        }else{
        	$this->redirect ( '/' );
        }
    }
    /**
     * 找回密码页
     */
   public function findPassport(){
   		$this->display();
   }
   /**
    * 取加密码验证码
    */
   public function backpasswordVerify() {
	   	$verify_config = C ( 'VERIFY_CONFIG' );
	   	$vertify = new Verify ( $verify_config );
	   	$vertify->entry ( $this->verify[0] );
   }
   /**
    * 处理找加密码页提交的信息
    */
   public function doFindPassport(){
	   	$vertify = new Verify();
	   	$mixAcount = I('post.userName','','trim');
	   	$verify_code = I('post.verify', '', 'htmlspecialchars,trim');
	   	if ($vertify->check($verify_code, $this->verify[0]) === false) {//验证码证码
	   		$this->ajaxReturn($this->result->set('VERIFICATION_CODE_ERROR')->toArray());
	   	}
	   	$where = [];
	   	$where['mobile|email|username'] = $mixAcount;
	   	$where['_logic'] = 'or';
	   	$map['_complex'] = $where;
	   	$map['status'] = 1;
	   	$find= M('user')->where($map)->find();
	   	if($find){
	   		S('userinfo_doFindPassport'.session_id(),$find);
		   	$this->ajaxReturn($this->result->success()->toArray());
	   	}else{
	   		$this->ajaxReturn($this->result->set('USER_NOT_EXIST')->toArray());
	   	}
   }
   //选择找回密码方式
   public function passwordWay(){
   	    $find = S('userinfo_doFindPassport'.session_id());
   	    $way['phone'] = '' ;
   	    $way['email'] = '';
   	    if($find['mobile_status'] == 1){
   	    	$way['phone'] = substr_replace($find['mobile'],'*****',3,5) ;
   	    }
   	    if($find['email_status'] == 1){
   	    	$tmp = explode('@',$find['email']);
   	    	$len = strlen($tmp[0]) - 2;
   	    	$len = $len >= 0 ? $len : 0; 
   	    	$tmp[0] = substr_replace($tmp[0], '***', 1,$len);
   	    	$way['email'] = implode('@',$tmp);
   	    	S('userinfo_doFindPassport'.session_id(),array_merge($find,['way' =>$way])) ;
   	    }
   	    $this->way = $way;
   		$this->display();
   }
   public function sendMess(){
	   	$tel_code= to_guid_string("sendMess");
	   	$tel = S('userinfo_doFindPassport'.session_id())['mobile'];
	   	//发送短信
	   	$mess = new \Common\Org\Util\MobileMessage();
	   	$arr['mobile_code'] = rand(100000,999999);
	   	if($mess->sendMessByTel($tel, $arr,'bind_mobile')){//发送成功
	   		S($tel_code,$tel.'_'.$arr['mobile_code'],C('JT_CONFIG_WEB_SORT_MESSAGE_VAILD')*60);
	   		$this->ajaxReturn($this->result->set('MESSAGE_CODE_SEND')->toArray());
	   	}
	   	$this->ajaxReturn($this->result->set('SEND_MESSAGE_FAIL')->toArray());
   }
   //找回密码
   public function  doBackPass(){
   		$type = I('post.way');// phone email
   		if($type == 'phone'){
   			$checkCode = I('post.mess_code','','trim');
   			$code = S(to_guid_string('sendMess'));
   			$tel = S('userinfo_doFindPassport'.session_id())['mobile'];
	    	if(!$code){//验证码已过期
	    		$this->ajaxReturn($this->result->set('MESSAGE_CODE_EXPIRE')->toArray());
	    	}else if($code !== $tel.'_'.$checkCode){//验证码不正确
	    		$this->ajaxReturn($this->result->set('MESSAGE_CODE_MATCHERROR')->toArray());
	    	}
   		}else{ //发送邮件
   			$email = S('userinfo_doFindPassport'.session_id())['email'];
   			//发送邮件--------------------------------------
   			   //缓存到数据库
	   			$codeData['code'] = rand_string(10,5);
	   			$codeData['type'] = 5;
	   			$codeData['add_time'] = NOW_TIME;
	   			$codeData['extend'] = '接收邮件地址  '.$email;
	   			$codeData['token'] = md5(NOW_TIME.$codeData['code']);
	   			$res = M('code')->add($codeData);
   			//模板变量
   			$tempArr['backPassword'] = U("setPassword",['token' => urlencode($codeData['token'])],true,true);
   			$emailContent = getTempContent('email_back_password',2, $tempArr);
   			$res = sendMail($email, '必好货用户','找回密码(必好货)',$emailContent);
   			$this->ajaxReturn($this->result->success()->toArray());
   		}
   		$this->ajaxReturn($this->result->set('TEL_MATCH_SUCCESS')->toArray());
   }
   //重设密码
   public  function  setPassword(){
   		if(IS_AJAX){
   			$data['pass'] = I('passWord');
   			$where['uid'] = S('userinfo_doFindPassport'.session_id())['uid']; 
   			$res = D('User/User')->setData($where,$data);
   			$this->ajaxReturn($res->toArray());
   		}
   		$them = '';
   		$token = I('token','','urldecode');
   		if($token){//邮件取回密码
   			//已绑定 ...
   			$co_model = M('Code');
   			$where = array();
   			$where['token'] = $token;//$token
   			$where['type'] = 5;
   			$where['add_time'] = array('egt',strtotime('-2 hours'));
   			$info = $co_model->field(true)->where($where)->find();
   			if(!empty($info)){//删除绑定记录
   				S('userinfo_doFindPassport'.session_id(),array_merge(S('userinfo_doFindPassport'.session_id()),['ifSeting' => 1]));
   				$co_model->delete($info['code_id']);
   			}else{//过期
   				S('userinfo_doFindPassport'.session_id(),array_merge(S('userinfo_doFindPassport'.session_id()),['ifEmailExpire' => 1]));
   				$them = 'emailexpire';//过期页
   			}
   		}
   		$this->display($them);
   }
   //设置成功页
   public function backPasswordSuc(){
   		$them = '';
   		S('userinfo_doFindPassport'.session_id(),array_merge(S('userinfo_doFindPassport'.session_id()),['ifChanged' => 1]));
   		$token = I('token','','trim,urldecode');
   		$this->display($them);
   }
   //找回密码提示已经发送邮件
   public function  sendingEmail(){
   		if(S('userinfo_doFindPassport'.session_id())['ifSeting']){//正在修改
   			$this->display('setpassword');
   			exit;
   		}else if(S('userinfo_doFindPassport'.session_id())['ifChanged']){//已经修改
   			$this->display('backpasswordsuc');
   			exit;
   		}else if(S('userinfo_doFindPassport'.session_id())['ifEmailExpire']){//过期
   			$this->display('emailexpire');
   			exit;
   		}
   		$this->goEmail = 'http://mail.'.explode('@', S('userinfo_doFindPassport'.session_id())['email'])[1];
	   	$this->email =  S('userinfo_doFindPassport'.session_id())['way']['email'];
	    $this->display();
   }
   /**
    * 手机注册码发送
    */
   public function regMobCode(){
   		$tel = I('username');
   		if(!$tel){
   			$this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
   		}
	   	$res = D('Code')->sendMobileCode('regMobCode',$tel);
   		$this->ajaxReturn($this->result->set($res)->toArray());
   }
   /**
    * 验证注册手机验证码
    */
   public function checkRegMobCode(){
   		$tel = I('username');
   		$checkCode = I('mobile_code');
   		if(!$tel || !$checkCode){
   			echo 0;
   			return;
   		}
   		$res = D('Code')->checkMobileCode('regMobCode',$tel,$checkCode);
   		echo $res ? 'true' : 0;
   }
   public function ifExistAccount(){
   		$acount = I('userName','','trim');
   		if(!$acount){
   			echo 0;
   			return;
   		}
   		$res = D('User/Account')->isExistAccount($acount);
   		echo $res ? 'true' : 0;
   }
   //检查邀请码是否有效
   function checkInviteCode(){
   		$invite_code = I('invite_code','','trim');
   		if(!$invite_code){
   			echo 0;
   			return;
   		}
   		//检查是否存在该邀请码
   		//$count = D('Admin/Admin')->scope()->where(['invite_code' => $invite_code])->count();
   		$count = D('Admin/Admin')->isHasCode($invite_code);
   		if(!$count){
   			echo 0;
   			return;
   		}
   		echo 'true';
   }
}