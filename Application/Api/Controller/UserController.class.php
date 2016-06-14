<?php
namespace Api\Controller;
use Common\Model\SharedModel;
use User\Org\Util\User;
class UserController extends ApiBaseController {
	private $thumbSize = array (
			"164X218"
	);
    /**
     * 登录
     * @author cwh
     *         传入参数:
     *         <code>
     *         username 用户名
     *         password 密码
     *         isBind 是否绑定第三方登陆 1：是0：否
     *         part 第三方数据
     *         </code>
     */
    public function login(){
        $username = I ( 'post.username', '', 'htmlspecialchars,trim' );
        $password = I ( 'post.password', '', 'htmlspecialchars,trim' );
        $source = I ( 'post.source', SharedModel::SOURCE_MOBILE, 'intval' );
        if (empty ( $username )) {
            $this->ajaxReturn($this->result->set('ACCOUNT_REQUIRE'));
        }
        if (empty ( $password )) {
            $this->ajaxReturn($this->result->set('PASSWORD_REQUIRE'));
        }

        // 启用登录日志
        $user_instance = User::getInstance();
        $user_instance->recordLog = false;
        //验证账号和密码
        $credentials = [
            'account' => $username,
            'password' => $password,
            'source'=>$source
        ];
        $result = $user_instance->login ( $credentials,0,false );
        if (!$result->isSuccess()) {//登录失败
            $this->ajaxReturn($result);
        }

        $uid = $result->getResult();
        //绑定第三方数据
        if(I('post.isPart',0,'intval')){
        	$connect = I('post.part','');
        	$connect = htmlspecialchars_decode($connect);
        	$connect = json_decode($connect,true);
        	if(D("Api/User")->isBind($connect['openid'],$connect['unionid'])){
        		//判断当前账号是否已绑定过
        		if(!D("Api/User")->bindByUid($uid,$connect['type'])){
        			//$this->ajaxReturn($this->result->error("该账号已绑定，请使用其他账号！","CONNECT_EXISTS"));
        			if($connect){         
        				$connect['uid'] = $uid;
        				$this->_connect($connect);
        			}else{
        				$this->ajaxReturn($this->result->error("第三方数据不能为空！","CONNECT_REQUIRE"));
        			}
        		}
        	}
        }
        $this->_toLogin($uid);
    }
    /**
     * 登录操作
     * @param 用户id
     */
    private function _toLogin($uid){
    	$channel_id = I("request.channelId"); //绑定推送channel_id
    	$deviceType = I("request.deviceType",1,'intval'); //设备类型
    	$token = D('User/Token')->device()->setChannel($channel_id)->setType($deviceType)->setMore($uid);
    	if($token === false){
    		$this->ajaxReturn($this->result->set('TOKEN_SET_FAILED'));
    	}
    	$user_instance = User::getInstance();
    	$user = $user_instance->user();
    	if(empty($user)){
    		$user = M("User")->where(['uid'=>$uid])->find();
    	}
    	$return_data = [
			'uid'=>$uid,
			'token'=>$token,
			'aliasname'=>$user['aliasname'],
			'accountStatus'=>$user['account_status'],
			'mobile'=>$user['mobile']
    	];
		
    	$this->ajaxReturn($this->result->content($return_data)->success('登录成功'));
    }
	
	/**
	*	判断是否为老用户，且其用户名为空
	*/
	public function getBindInfo(){
		$uid = I('uid');
		$info = M('User')->field('uid,username,mobile')->where(['uid'=>$uid])->find();
		if(empty($info['username']) && empty($info['mobile'])){
			$res = D('MessageUser')->insertOldMessage($uid);
		}
		
		$this->ajaxReturn($this->result->content($res)->success());
	}
    
    /**
     * 第三方登录
     * @author xiongzw
     *      <code>
     *          openId 开放平台id
     *          headPic 头像
     *          type 登录方式
     *          nick 昵称
     *      </code>
     */
    public function oauth(){
    	$error = '';
    	$openId = I('post.openId','');
    	$headPic = I('post.headPic','');
    	$nick = I('post.nick','');
    	$type = I('post.type','');
    	$unionid = I('post.unionid','');
    	if(!$error && empty($openId)){
    		$error = "OPENID_REQUIRE";
    	}
    	/* if(!error && empty($headPic)){
    		$error = "HEADPIC_REQUIRE";
    	} */
    	if(!$error && empty($type)){
    		$error = "PART_TYPE_REQUIRE";
    	}
    	if($error){
    		$this->ajaxReturn($this->result->set($error));
    	}
    	$connect = D ( 'Home/Oauth' )->isBind ( $openId, $type,$unionid );
    	//已注册用户
    	if($connect){
    		$uid = $connect['uid'];
    		$this->_toLogin($uid);
    	}else{
    		//未注册用户
    		$part = array(
    				'openid'=>$openId,
    				'pic' => $headPic,
    				'nick'=>$nick,
    				'type'=>$type,
    				'unionid'=>$unionid
    		);
    		$data = array(
    				 "isReg"=>false,
    				 'part'=>$part
    		);
    		$this->ajaxReturn($this->result->content(['data'=>$data]));
    	}
    }

    /**
     * 退出
     * @author cwh
     *         传入参数:
     *         <code>
     *         token token值
     *         </code>
     */
    public function loginOut(){
        D('User/Token')->del($this->token);
        $this->ajaxReturn($this->result->success('退出成功'));
    }
	
    /**
     * 注册
     * @author cwh
     *         传入参数:
     *         <code>
     *         username 用户名
     *         password 密码
     *         type 注册类型：1为手机注册 
     *         code 验证码
     *         isPart 是否有第三方数据  1：有 0：无
     *         part 第三方数据(如果是第三方注册)
     *         </code>
     */
    public function register(){
        $recommend_id = I('uid');   //邀请链接后代参数uid就是推荐者id
        $hasRecommend = M('Token')->where(['uid'=>$recommend_id])->find();
        if($hasRecommend){  //看邀请链接带来的uid用户是否存在
            $recommend = $recommend_id;
        }else{
            $recommend = 0;
        }

        $data = [
            'username' => I('post.username',''),
            'mobile' => I('post.username',''),
            'pass' => I('post.password','')	,
            'status'=>1,
            // 'grade_id'=>D("User/UserGrade")->getDefaultGrade(),//用户等级
        ];
        $invite_code = I('post.invite_code','');
        $type = I('post.type','');
       switch($type){
            case 1:
                $code_result = D('Code')->getInfo(I('post.code',''),1,[
                    'extend'=>json_encode($data['mobile'])
                ],true);
                if(!$code_result->isSuccess()){
                    $this->ajaxReturn($code_result);
                }
                $data['mobile_status'] = 1;
                break;
        }

        // 判断邀请码是否存在
		/*$tmp = D('Admin/Admin')->isHasCode($invite_code);
		if($invite_code  && empty($tmp)){
			$this->ajaxReturn($this->result->error("邀请码不存在！"));
		}*/

		// 判断员工编号是否存在 2016/4/1  qrong
		$tmp = D('Admin/Admin')->isHasCodes($invite_code);
		if($invite_code  && empty($tmp)){
			$this->ajaxReturn($this->result->error("员工编号不存在！"));
		}


        //第三方注册数据
        $connect = I('post.part','');
        if(I('post.isPart',0,'intval')){
			if($connect){
				$connect = htmlspecialchars_decode($connect);
				$connect = json_decode($connect,true);
				$data['aliasname'] = $connect['nick'];
				if($connect['pic']){   //下载第三方头像
					$pic = D("Home/Oauth")->userPic($connect['pic']);
					$data['headAttr'] = $pic['att_id'];
				}
			}else{
				$this->ajaxReturn($this->result->error("第三方数据不能为空！","CONNECT_REQUIRE"));
			}
        }
        $data['aliasname'] = empty($data['aliasname'])?"BHH".rand_string(8,1):$data['aliasname'];
        $data['come_from'] = I('request.source',1);//来源
        $result = D('User/User')->addData($data,true);
        $user_instance = User::getInstance();
        if($result->isSuccess()){
            if ($user_instance->isLogin ()) {//已有帐号登入就登出
                $user_instance->logout ();
            }
            $uid = $result->getResult();
            $user_instance->toLogin($uid);

            //设置token
            $token = D('User/Token')->device()->setMore($uid);
            if($token === false){
                $this->ajaxReturn($this->result->set('TOKEN_SET_FAILED'));
            }

            $return_data = [
                'uid'=>$uid,
                'token'=>$token
            ];
            if(is_array($connect) && $pic['path']){
				//返回头像数据
				$return_data['photo'] = fullPath($pic['path']);
				//插入第三方数据
				$connect['uid'] = $uid;
				$this->_connect($connect);
            }
            $result->content($return_data);

            //邀请用户存在 （会员邀请）
            if($recommend){
                $recommend_data = array(
                    'uid'=>$uid,
                    'recommend'=>$recommend,
                    'add_time'=>NOW_TIME,
                );

                M('UserRecommend')->add($recommend_data);   //写入会员推荐列表
            }

            //有邀请码 （地推邀请）
            if($invite_code){
                // D('Admin/Admin')->recommend($uid,$invite_code);    //邀请码注册

				//员工编号注册  2016/4/1
                $invite_id = M('Admin_user')->where(['username'=>$invite_code])->getField('uid');//通过员工编号查询对推人员id
				D('Admin/Admin')->recommends($invite_id,$uid);
            }

            //注册赠券
            D("User/Promotions")->giveCoupon($uid,7);
        }
        $this->ajaxReturn($result);
    }
    /**
     * 插入第三方数据
     * @param  $connect
     */
    private function _connect($connect){
    	$connect['add_time'] = NOW_TIME;
    	M("UserConnect")->add($connect,'',true);
    }

    /**
     * 用户资料绑定
     * @author cwh
     *         传入参数:
     *         <code>
     *         username 用户名
     *         password 密码
     *         type 注册类型：1为手机注册
     *         code 验证码
     *         source 来源：1为微信端，2为手机端
     *         token 登录令牌
     *         </code>
     */
    public function accountBind(){
        $this->authToken();
        $data = [
            'username' => I('post.username',''),
            'mobile' => I('post.username',''),
            'pass' => I('post.password',''),
            'account_status'=>0
        ];
        $type = I('post.type','');
        switch($type){
            case 1:
                $code_result = D('Code')->getInfo(I('post.code',''),1,[
                    'extend'=>json_encode($data['mobile'])
                ],true);
                if(!$code_result->isSuccess()){
                    $this->ajaxReturn($code_result);
                }
                $data['mobile_status'] = 1;
                break;
        }
        $data['come_from'] = I('request.source',1);//来源
        $where = [];
        $where['uid'] = $this->user_id;
        $res = D('User/User')->setData($where,$data);
        $this->ajaxReturn($res);
    }

    /**
     * 修改密码
     * @author cwh
     *         传入参数:
     *         <code>
     *         oldPass 旧密码
     *         newPass 新密码
     *         newRepass 新确认密码
     *         token 登录令牌
     *         </code>
     */
    public function updatePass(){
        $this->authToken();
        $old_pass = I('post.oldPass','','trim');
        $new_pass = I('post.newPass','','trim');
        $new_repass = I('post.newRepass','','trim');

        if(empty($old_pass)){
            $this->ajaxReturn($this->result->set('OlD_PASSWORD_REQUIRE'));
        }

        if(empty($new_pass)){
            $this->ajaxReturn($this->result->set('NEW_PASSWORD_REQUIRE'));
        }

        if($new_pass !== $new_repass){
            $this->ajaxReturn($this->result->set('PASSWORD_INCONSISTENCY'));
        }

        //验证旧密码
        $user_model = D('User/User');
        $user_info = $user_model->getUserById($this->user_id);
        if($user_info->isSuccess()) {
            $user_info = $user_info->getResult();
            if ($user_info['pass'] != getpass($old_pass)) {
                $this->ajaxReturn($this->result->set('OlD_PASSWORD_ERROR'));
            }
        }else{
            $this->ajaxReturn($user_info);
        }

        $where = [];
        $where['uid'] = $this->user_id;
        $data['pass'] = $new_pass;
        $res = D('User/User')->setData($where,$data);
        $this->ajaxReturn($res);
    }
	
    /**
     * 找回密码
     * @author cwh
     *         传入参数:
     *         <code>
     *         newPass 新密码
     *         newRepass 新确认密码
     *         token 登录令牌
     *         </code>
     */
    public function forgotPass(){
        $new_pass = I('post.newPass','','trim');
        $new_repass = I('post.newRepass','','trim');
        if(empty($new_pass)){
            $this->ajaxReturn($this->result->set('NEW_PASSWORD_REQUIRE'));
        }

        if($new_pass !== $new_repass){
            $this->ajaxReturn($this->result->set('PASSWORD_INCONSISTENCY'));
        }

        $token = I('post.token','','trim');
        $token_result = D('Api/User')->mobileDecrypt($token);
        if($token_result->isSuccess()){
            $token_result = $token_result->getResult();
            $mobile = $token_result['mobile'];
        }else{
            $this->ajaxReturn($token_result);
        }

        $user_model = D('User/User');
        $uid = $user_model->where([
            'mobile|username'=>$mobile,
            'delete_time'=>0,
        ])->getField('uid');

        $where = [];
        $where['uid'] = $uid;
        $data['pass'] = $new_pass;
        $res = $user_model->setData($where,$data);
        $this->ajaxReturn($res);
    }

    /**
     * 修改用户名
     * @author cwh
     *         传入参数:
     *         <code>
     *         username 用户名
     *         token 登录令牌
     *         </code>
     */
    public function updateUsername(){
        $this->authToken();
        //接收和验证参数
        $realname = I('post.realname','','trim');
        $birthday = strtotime(I('post.birthday','','trim'));
        /*if(empty($realname)){
            $this->ajaxReturn($this->result->set('USER_REALNAME_REQUIRE'));
        }*/
        
        $nickname = I('post.nickname','','trim');//用户昵称
        if(empty($nickname)){
        	$this->ajaxReturn($this->result->set('USER_NICKNAME_REQUIRE'));
        }
        
        $sex = I('post.sex','','trim,int');//姓别
        if(empty($sex)){
        	$this->ajaxReturn($this->result->set('USER_SEX_REQUIRE'));
        }else if(!in_array($sex, [1,2])){
        	$this->ajaxReturn($this->result->set('USER_SEX_FORMAT_ERROR'));
        }
        
    /*    //获取用户信息
        $user_model = D('User/User');
        $user_info = $user_model->getUserById($this->user_id);
        if(!$user_info->isSuccess()) {
            $this->ajaxReturn($user_info);
        }

        //检查是否可以修改密码
        $user_info = $user_info->getResult();
         if($user_info['is_modify_username'] == 1){
            $this->ajaxReturn($this->result->set('USERNAME_NOT_MODIFY'));
        }

        //检查用户名是否已经存在
        if(D('User/Account')->isExistAccount($username,0,3)){
            $this->ajaxReturn($this->result->set('USERNAME_EXISTS'));
        } */

        //获取用户birthday
        $hasBirthday = M('UserProfile')->where(['uid'=>$this->user_id])->getField('user_birthday');
        if(!$hasBirthday){  //如果没有生日信息才可修改生日
            $dat = array(
                'user_birthday' => $birthday
            );
        }

        $where = [];
        $where['uid'] = $this->user_id;
        $data['real_name'] = $realname;
        $data['aliasname'] = $nickname;
        $data['sex'] = $sex;
        //$data['is_modify_username'] = 1;

        // $res = D('User/User')->setData($where,$data,true);

        //现在修改生日信息涉及两张表，需开启事务，另写一个方法
        $res = D('User/User')->saveUserInfo($where,$data,$dat,true);        
        $this->ajaxReturn($res);
    }

    /**
     * 获取用户信息
     * @author cwh
     *         传入参数:
     *         <code>
     *         token 登录令牌
     *         </code>
     */
    public function getInfo(){
        $this->authToken();
        //获取用户信息
        $user_model = D('User/User');
        $user_info = $user_model->getUserById($this->user_id);
        if(!$user_info->isSuccess()) {
            $this->ajaxReturn($user_info);
        }
        $user = $user_info->getResult();

        $user_model->gradeChangeMessage($this->user_id,$user['grade_id']);		//会员等级变更通知
        $user_model->emptyUserIntegral($this->user_id);							//触发清空积分通知

        $birthday = M('UserProfile')->where(['uid'=>$this->user_id])->getField("user_birthday");
        $return_data = [
            'uid'=>$user['uid'],
            'username'=>$user['username'],
            'birthday'=>$birthday==0?'':$birthday,
            'mobile'=>$user['mobile'],
            'aliasname'=>$user['aliasname'],
            'realName'=>$user['real_name'],
            'mobileStatus'=>$user['mobile_status'],
            'photo'=>$user['path'],
            'sex'=>$user['sex']
        ];
        $this->ajaxReturn($this->result->content($return_data)->success());
    }

    /**
     *  会员中心页面
     *  @param token 登录令牌
     */
    public function memberCenter(){
        $this->authToken();

        //获取用户信息
        $user_model = D('User/User');
        $memberInfo = $user_model->getUserById($this->user_id,'aliasname,headAttr');
        $memberInfo = $memberInfo->toArray()['result'];
        $gradeList  = M('UserGrade')->field('grade_name,start_value')->where(['grade_status'=>1])->select();

        $credits    = D('User/Credits')->getCredits($this->user_id);
        $growth     = intval($credits[3]);      //会员成长值
        $grade_name = D("User/UserGrade")->getUserGrade($growth);

        foreach($gradeList as $k=>$v){
            if($grade_name==$v['grade_name']){
                $next_grade = $gradeList[$k+1]['grade_name'];
                $next_grade_growth = intval($gradeList[$k+1]['start_value']);
                break;
            }
        }

        if($growth >= $gradeList[count($gradeList)-1]['start_value']){
            $differGrowth = 0;
        }else{
            $differGrowth = $next_grade_growth-$growth;
        }

        $return_data = array(
            'headPic'       =>  $memberInfo['path'],
            'aliasname'     =>  $memberInfo['aliasname'],
            'grade_name'    =>  $grade_name,
            'growth'        =>  $growth,
            'next_grade'    =>  $next_grade,
            'differGrowth'  =>  $differGrowth,
            'gradeList'     =>  $gradeList,
        );
        $this->ajaxReturn($this->result->content($return_data)->success());
    }

    /**
     *  会员积分
     *  @param token 登录令牌
     *  @param type  收入或支出   1为收入，2为支出
     */
    public function memberIntegral(){
        $this->authToken();

        //获取用户信息
        $credits_model = D('User/Credits');

        $where = array();
        $type = I('type',0,'intval');
        if($type==1){
            $where['credits']=array('gt',0);
        }elseif($type==2){
            $where['credits']=array('lt',0);
        }
        $where['uid']=$this->user_id;
        $where['credits_type']=1;

        $integral = M('AccountCredits')->where(array('uid'=>$this->user_id,'type'=>1))->getField('credits');
        $logList = M('AccountLog')->where($where)->field('remark,add_time,type,credits')->order('add_time DESC')->select();
        $newLogList = array();
        $i=0;
        foreach($logList as $k=>$v){
        	if($v['type']!=0){	//过滤其他类型的日志(清空日志记录不让用户看到)
        		
        		if(intval($v['credits'])>0){
        			$newLogList[$i] = $v;
	        		$newLogList[$i]['credits']='+'.intval($v['credits']);
	        	}else{
	        		$newLogList[$i] = $v;
	        		$newLogList[$i]['credits']=intval($v['credits']);
	        	}
	            $newLogList[$i]['remark']=$v['remark'];
	            $newLogList[$i]['type']=$credits_model->getOperateType($v['type']);

	            $i++;
        	}
        }

        $return_data = array(
            'integral'=>intval($integral),
            'logList'=>$newLogList
        );
        
        if($newLogList){
            $this->ajaxReturn($this->result->content($return_data)->success());
        }else{
            $return_data = array(
                'integral'=>intval($integral),
                'logList'=>array(
                	0 => array(
                		"remark" => "",
	                    "add_time" => "",
	                    "type" => "",
	                    "credits" => ""
                	),
                ),
            );
            $this->ajaxReturn($this->result->content($return_data)->success());
        }
    }

    /**
     * 验证用户手机 发送验证短信
     * @param int $mobile 手机号 
     * @param str $token 登入token 
     */
    function checkTelSend($ifcheckTel = false){
    	//用户是否登入
    	$userInfo = $this->authToken();
    	$mobile = I('post.mobile',0,'htmlspecialchars,trim' );
        if(empty($mobile) || !checkMobile($mobile)){
            $this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
        }
    	//合对手机号是否正确
		$oldTel = D('User/User')->scope()->where(['uid' => $this->user_id])->getField('mobile');
		if($mobile != $oldTel){
			$this->ajaxReturn($this->result->set('MOBILE_NOT_MATCH'));
		}
		if($ifcheckTel){ //匹配验证码
			$code = I('post.code',0,'int');
			if(!$code){
				$this->ajaxReturn($this->result->set('CODE_REQUIRE'));
			}
			$_POST['type'] = 3;
			R('Api/Message/verifySMS');
		}
		//发送手机验证码
		$_POST['type'] = 3;
		$_POST['mobile'] = $mobile;
		R('Api/Message/sendSMS');
    }
    /**
     * 验证用户手机
     * @param int $mobile 手机号
     * @param str $token 登入token
     * @param int $code 验证码
     */
    function checkTel(){
    	$this->checkTelSend(true);
    }
    /**
     * 接受新手机号-发送验证码
     * @param int $mobile 手机号 
     * @param str $token 登入token 
     */
    function recieveTelSendCode($ifcheckTel = false){
    	//用户是否登入
    	$userInfo = $this->authToken();
		
		
		
    	$mobile = I('post.mobile',0,'htmlspecialchars,trim');
    	if(empty($mobile) || !checkMobile($mobile)){
    		$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
    	}
    	
		//合对手机号是否存在
		$oldTel = D('User/User')->scope()->where(['mobile|username' => $mobile])->getField('mobile,username');
		//print_r($oldTel);
		//die();
		if($oldTel){
			$this->ajaxReturn($this->result->set('MOBILE_EXISTS'));
		}
    	if($ifcheckTel){ //匹配验证码
    		$code = I('post.code',0,'int');
    		if(!$code){
    			$this->ajaxReturn($this->result->set('CODE_REQUIRE'));
    		}
    		$_POST['type'] = 3;
    		$this->verifySMS();
    	}
    	//发送手机验证码
    	$_POST['type'] = 3;
    	$_POST['mobile'] = $mobile;
    	R('Api/Message/sendSMS');
    }
    private function verifySMS(){
    	$type = I('post.type','','intval');
    	$code = I('post.code','','intval');
    	$mobile = I('post.mobile','','trim');
    	if(empty($mobile) || !checkMobile($mobile)){
    		$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
    	}
    	
    	$code_model = D('Code');
    	$where = [
    			'extend' => json_encode($mobile)
    	];
    	$result = $code_model->where($where)->getInfo($code,$type);
    	if($result->isSuccess()){
    		//写入新手机号
    		$res = D('User/User')->scope()->where(['uid' => $this->user_id])->save(['mobile' => $mobile]);
    		if($res !== false){
	    		$this->ajaxReturn($this->result->success());
    		}else{
    			$this->ajaxReturn($this->result->set('MOBILE_CHANGE_FAIL'));
    		}
    	}
    	$this->ajaxReturn($this->result->set('MOBILE_CHANGE_FAIL'));
    }
	
	/**
     * 修改密码
     * @author qrong
     * 传入参数:
     * <code>
     *   newPass 新密码
     *   newRepass 新确认密码
     *   token 登录令牌
     * </code>
     */
    public function setPass(){
        $this->authToken();
        $new_pass = I('post.newPass','','trim');
        $new_repass = I('post.newRepass','','trim');

        if(empty($new_pass)){
            $this->ajaxReturn($this->result->set('NEW_PASSWORD_REQUIRE'));
        }

        if($new_pass !== $new_repass){
            $this->ajaxReturn($this->result->set('PASSWORD_INCONSISTENCY'));
        }

        $where = [];
        $where['uid'] = $this->user_id;
        $data['pass'] = $new_pass;
        $res = D('User/User')->setData($where,$data);
        $this->ajaxReturn($res);
    }
	
	/**
     * 老用户发送验证码
     * qrong
     * @param int $mobile 手机号 
     * @param str $token 登入token 
     */
    public function sendOldUserCode($ifcheckTel = true){
		//用户是否登入
    	$userInfo = $this->authToken();
    	$mobile = I('post.mobile',0,'htmlspecialchars,trim' );
        if(empty($mobile) || !checkMobile($mobile)){
            $this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
        }
    	
		//发送手机验证码
		$_POST['type'] = 3;
		$_POST['mobile'] = $mobile;
		R('Api/Message/sendSMS');
	}
	
	
	/**
     * 老用户核对验证码
     * qrong
     * @param int $mobile 手机号 
     * @param str $token 登入token 
     */
    public function checkUserCode($ifcheckTel = true){
    	//用户是否登入
    	$userInfo = $this->authToken();
		
    	$mobile = I('post.mobile',0,'htmlspecialchars,trim');
    	if(empty($mobile) || !checkMobile($mobile)){
    		$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
    	}
    	
    	if($ifcheckTel){ //匹配验证码
    		$code = I('post.code',0,'int');
    		if(!$code){
    			$this->ajaxReturn($this->result->set('CODE_REQUIRE'));
    		}
    		$_POST['type'] = 3;
    		$this->oldUserBind();
    	}
    }
	
	/**
	 *	老用户验证绑定
	 *	qrong
	 */
	private function oldUserBind(){
    	$type = I('post.type','','intval');
    	$code = I('post.code','','intval');
    	$mobile = I('post.mobile','','trim');
		$uid = I('post.uid');
		$is_order = I('post.is_order',0,'intval');
		$cart_id = I('post.cart_id');
    	if(empty($mobile) || !checkMobile($mobile)){
    		$this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
    	}
    	
    	$code_model = D('Code');
    	$where = [
    		'extend' => json_encode($mobile)
    	];
    	$result = $code_model->where($where)->getInfo($code,$type);
    	if($result->isSuccess()){
			$oldTel = M('User')->alias('u')->field('u.uid,u.aliasname,u.account_status,u.mobile,t.token')->join('LEFT JOIN __TOKEN__ t ON u.uid=t.uid')->where(array('username' => $mobile,'delete_time'=>0))->find();
			$return_data = [
				'uid'=>$oldTel['uid'],
				'token'=>$oldTel['token'],
				'aliasname'=>$oldTel['aliasname'],
				'accountStatus'=>$oldTel['account_status'],
				'mobile'=>$oldTel['mobile'],
				'is_order'=>$is_order,
				'cart_id'=>$cart_id,
			];
			
			if($oldTel){
				$dat = array(
					'uid'=>$oldTel['uid'],
				);
				
				/* //将旧账号的购物车数据归到新账号下
				$cart = M('Cart')->where(['uid'=>$this->user_id])->save($dat); */
				
				//写入手机号
				$userData = array('mobile'=>$mobile);
				$userinfo = D('User/User')->scope()->where(['uid' => $this->user_id])->save($userData);
				
				//核对手机号是否存在，存在则执行自动绑定到旧账号的操作(将微信登陆表绑定的uid替换为旧账号的uid)
				$wechatInfo = M('UserConnect')->where(array('uid'=>$uid,'type'=>'wechat'))->save($dat);
				
				$this->ajaxReturn($this->result->success()->content($return_data));exit;
				// $this->ajaxReturn($this->result->set('MOBILE_EXISTS'));
			}
			
    		//手机号不存在就写入新手机号
			$data = array(
				'username'=>$mobile,
				'mobile'=>$mobile,
				'mobile_status'=>1,
			);
    		$res = D('User/User')->scope()->where(['uid' => $this->user_id])->save($data);
    		if($res !== false){
	    		$this->ajaxReturn($this->result->success()->content(['is_order'=>$is_order]));
    		}else{
    			$this->ajaxReturn($this->result->set('MOBILE_CHANGE_FAIL'));
    		}
    	}
    	$this->ajaxReturn($this->result->set('MOBILE_CHANGE_FAIL'));
    }
	
    /**
     *  接受新手机号
     * @param int $mobile 手机号
     * @param str $token 登入token
     * @param int $code 验证码
     */
    function recieveTel(){
		$this->recieveTelSendCode(true);	
    }
    function uploadHeadPic(){
    	//用户是否登入
    	$userInfo = $this->authToken();
    	$uid = $this->user_id;
    	$baseImg = I('post.base','');
    	if(!$baseImg){ //照片
    		$this->ajaxReturn($this->result->set("PHOTO_REQUIRE"));
    	}
    	$ext = I ( "post.ext", '', 'trim' );
    	if(!$ext){ //照片
    		$this->ajaxReturn($this->result->set("PHOTO_EXT_REQUIRE"));
    	}
    	$data = $this->_base64Upload($baseImg,$ext);
    	/* array (size=6)
    	'path' => string 'http://www.jitu.com/Uploads/headPic/20150811/55c9bc5047a6a.jpg' (length=62)
    	'name' => string '55c9bc5047a6a' (length=13)
    	'ext' => string 'jpg' (length=3)
    	'size' => int 16293
    	'thumbName' => string '["55c9bc5047a6a_164X218"]' (length=25)
    	'att_id' => int 1106 */
    	$myData['headAttr'] = $data['att_id'];
    	$res = D('User/User')->setData(['uid' => $this->user_id],$myData);
    	$res->content(['src' => $data['path']]);
    	$this->ajaxReturn($res);
    }
    /**
     * base64上传
     */
    private function _base64Upload($baseImg,$ext) {
    	foreach ( $this->thumbSize as $v ) {
    		$size = explode ( "X", $v );
    		$width .= $size [0] . ",";
    		$height .= $size [1] . ",";
    	}
    	$width = rtrim ( $width, "," );
    	$height = rtrim ( $height, "," );
    	$baseImg = preg_replace("/^data:image\/\w+;base64,/","",$baseImg);
    	if(empty($baseImg)){
    		$this->ajaxReturn($this->result->set("UPLOAD_ERROR"));
    	}
    	$baseImg = base64_decode ( $baseImg );
    	$config = array (
    			"thumb" => true,
    			'ThumbImage' => array (
    					'thumbWidth' => $width, // 缩略图宽度
    					'thumbHeight' => $height, // 缩略图高度
    					'thumbType' => 3
    			),
    			'savePath' =>'headPic/'
    	);
    	$result = D ( "Upload/UploadImage" )->base64Upload ( $baseImg, $ext, $config );
    	if ($result) {
    		//原图宽高判断缩略图尺寸
    		$size = getimagesize(".".$result ['path']);
    		$thumbSize = explode ( "X", $this->thumbSize [0] );
    		if($size[0]>$size[1]){
    			$sizeH = $thumbSize[0];
    			$sizeW = intval($sizeH*($size[0]/$size[1]));
    		}else{
    			$sizeW = $thumbSize[0];
    			$sizeH = intval($sizeW/($size[0]/$size[1]));
    		}
    		$this->_cut ( $result ['path'], 1,$sizeW, $sizeH );
    		$this->_cut ( $result ['path'], 3, $sizeW, $sizeW );
    		$result['path'] = fullPath ( $result ['path'] );
    		return $result;
    	} else {
    		$this->ajaxReturn ( $this->result->error () );
    	}
    }
    /**
     * 图片裁剪
     * @path 图片路径
     */
    private function _cut($path,$type=3,$width='',$height='',$filename=''){
    	$image = new \Think\Image (\Think\Image::IMAGE_IMAGICK);
    	if(stripos($path, ".")!=0){
    		$path = ".".$path;
    	}
    	$image->open($path);
    	$width = $width?$width:$image->width();
    	$height = $height?$height:$image->height();
    	$filename = $filename?$filename:$path;
    	if(!is_dir(dirname($filename))){
    		mkdir ( dirname($filename), '0777', true );
    		$old = umask(0);
    		chmod($path, 0777);
    		umask($old);
    	}
    	$image->thumb($width,$height,$type)->save($filename);
    	return $filename;
    }
}