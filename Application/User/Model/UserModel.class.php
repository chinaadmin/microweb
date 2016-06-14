<?php
/**
 * 用户类
 * @author cwh
 * @date 2015-04-09
 */
namespace User\Model;
use Common\Model\BaseModel;
use Common\Org\Util\Results;
use Think\Model\RelationModel;
use User\Interfaces\UserInterface;
use User\Org\Util\Integral;

class UserModel extends BaseModel implements UserInterface{

    protected $tableName = 'user';

    public $_validate = [
        ['username','require','ACCOUNT_REQUIRE::账号不能为空'],
    	['username','ifUniqueUsername','MOBILE_CODE_ERROR::用户名已注册',self::EXISTS_VALIDATE,'callback',self::MODEL_INSERT],

        ['aliasname','require','NICKNAME_REQUIRE::昵称不能为空'],
        /*['real_name','require','REALNAME_REQUIRE::真实姓名不能为空'],*/ 
        ['pass','require','PASSWORD_REQUIRE::密码不能为空',self::MUST_VALIDATE,'',self::MODEL_INSERT],
    	//邮箱
        ['email','email','EMAIL_FORMAT_ERROR::邮箱格式不正确',self::VALUE_VALIDATE],
    	['email','ifUniqueEmail','EMAIL_EXISTS::邮箱已经存在',self::VALUE_VALIDATE,'callback',self::MODEL_INSERT],
        //手机
        ['mobile','number','MOBILE_FORMAT_ERROR::手机格式不正确',self::VALUE_VALIDATE],
    	['mobile','ifUniqueMobile','MOBILE_EXISTS::手机已经存在',self::VALUE_VALIDATE,'callback',self::MODEL_INSERT],

        ['mobile_code','verifyCode','MOBILE_CODE_ERROR::手机验证码错误',self::EXISTS_VALIDATE,'callback']
    ];

    public $_auto = [
        ['pass','pwdHash',self::MODEL_BOTH,'callback'],
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>['status'=>1],
        ],
        'default'=>[// 获取没有被删除状态
            'where'=>[
                'delete_time'=>['eq',0]
            ]
        ],
    	'normalDefault' =>[
    			'where'=>[
    					'delete_time'=>['eq',0],
    					'status'=>1
    			]
    	]
    ];
 //验证用户名唯一性
    public function ifUniqueUsername($username){
    	$res = $this->scope()->where(array_merge($this->_scope['normal']['where'],['username' => $username]))->count();
    	return $res ?  false : true ;
    }
//验证邮箱唯一性
    public function ifUniqueEmail($email){
    	$res = $this->scope()->where(array_merge($this->_scope['normal']['where'],['email' => $email]))->count();
    	return $res ?  false : true ;
    }
//验证手机唯一性
    public function ifUniqueMobile($mobile){
    	$res = $this->scope('normal,default')->where(['mobile' => $mobile])->count();
    	return $res ?  false : true ;
    }

    /**
     * 验证手机验证码
     * @param $code 验证码
     * @param $mobile 手机号
     * @param $type 1:注册 2：修改密码 3：修改手机
     * @return bool|mixed
     */
    public function verifyCode($code,$type=1,$mobile=''){
    	$vaild_time = intval(C("JT_CONFIG_WEB_SORT_MESSAGE_VAILD"));
    	$vaild_time = $vaild_time *60;
    	$mobile = I('request.username',$mobile);
    	if(empty($code) || empty($mobile)){
    		return false;
    	}else{
    		$where = array(
    			'code' => $code,
    			'type' => $type,
    			'extend' => $mobile,
    			'add_time' => array('EGT',time()-$vaild_time)
    		);
    		if(M('Code')->where($where)->find()){
    			return true;
    		}else{
    			return false;
    		}
    	}

    }

    /**
     *  修改用户信息
     *  @param $where 条件
     *  @param $data 修改user表的数据
     *  @param $dat 修改UserProfile表的数据
     */
    public function saveUserInfo($where,$data,$dat,$field=false){
        $this->startTrans();
        $UserProfileModel = D('User/UserProfile');
        if($field){
            $result = $this->setData($where,$data,true);
        }else{
            $result = $this->setData($where,$data);
        }
        
        if($result){
            if(!$dat){  //如果birthday字段为空，直接返回主表修改结果
                $this->commit();
                return $result; 
            }
            if($field){
                $res = $UserProfileModel->setData($where,$dat,true);
            }else{
                $res = $UserProfileModel->setData($where,$dat);
            }
            
            if($res){
                $this->commit();
                return $res;
            }else{
                $this->rollback();
                return $res;
            }
        }else{
            $this->commit();
            return $result;
        }
    }

    /**
     * 密码加密
     * @param string $password 密码
     * @return bool|mixed
     */
    protected function pwdHash($password) {
        if(!empty($password)) {
            $pass = getpass($password);
            if($pass){
                return $pass;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 用户密码强度
     * @param string $password 密码
     * @return bool|mixed
     */
    protected function pwdStrength($password) {
        if(!empty($password)) {
            return passStrength($password);
        }else{
            return false;
        }
    }

    /**
     * 插入用户数据前的回调方法
     * @param array $data
     * @param array $options
     */
    protected function _before_insert(&$data,$options) {
        //用户标识
        $mark = uniqueId();
        $data['uid'] = $mark;
        $this->data['uid'] = $mark;

        //用户密码强度
        $pass_length = $this->pwdStrength($data['pass']);
        $data['pass_length'] = $pass_length;
        $this->data['pass_length'] = $pass_length;

        //用户注册ip
        $this->data['reg_ip'] = get_client_ip(1,true);
    }

    /**
     * 新增用户成功后的回调方法
     * @param array $data
     * @param array $options
     * @return bool
     */
    protected function _after_insert($data,$options) {
        $uid = $data['uid'];
        /*//添加用户统计
        $where = array();
        $where['uid'] = $uid;
        M('UserCount')->data($where)->add();*/

        $account_model = $this->getAccountModel();
        //添加用户账号
        if(!empty($data['username']) && !$account_model->addAccount($uid,$data['username'],3)->isSuccess()){
            return false;
        }

        if(!empty($data['email']) && !$account_model->addAccount($uid,$data['email'],1)->isSuccess()){
            return false;
        }

        if(!empty($data['mobile']) && !$account_model->addAccount($uid,$data['mobile'],2)->isSuccess()){
            return false;
        }

        //初始化积分账户
        if(!$this->getCreditsModel()->initAccountCredits($uid)->isSuccess()){
            return false;
        }
        $data = array('uid'=>$uid);
        $this->addUserRelation($data);
        return true;
    }
    /**
     * user关联表预插入数据
     * @param  $data 要插入的数据
     */
    protected function addUserRelation($data,Array $tables=array()){
    	if(empty($tables)){
    	 $tables = array(
    			'UserAddress','UserProfile','UserAnalysis'
    	 );
    	}
    	foreach($tables as $v){
    		M($v)->add($data);
    	}
    	return true;
    }

    /**
     * 用户关联模型
     *
     */
    public function userRelation($reletion=true){
    	$_link = array(
    		'UserConnect'=> array(
    		   'mapping_type'=>RelationModel::HAS_ONE,
    		   'foreign_key' => 'uid'
    		),
    		'UserProfile' => array(
    		   'mapping_type' =>RelationModel::HAS_ONE,
    		   'foreign_key' => 'uid'
    		),
    		'UserAddress' => array(
    			'mapping_type' =>RelationModel::HAS_ONE,
    			'foreign_key' => 'uid'
    		)
    	);
    	return $this->dynamicRelation($_link)->relation($reletion);
    }
    /**
     * 用户视图模型
     * @param 要关联的表 user $field = array('User')
     * @param 要查询的字段    $filed = array('User'=>"username,mobile",'UserProfile'=>'')
     */
    public function userView($viewTable=array(),$field=array()){
    	$field = array(
    		'User'=> !empty($field['User'])?$field['User']:"*",
    		'UserConnect'=>!empty($field['UserConnect'])?$field['UserConnect']:"*",
    		'UserProfile'=>!empty($field['UserProfile'])?$field['UserProfile']:"*",
    		'UserAddress'=>!empty($field['UserAddress'])?$field['UserAddress']:"*"
    	);
    	$view = array(
    	    'User' => array($field['User'],"_type"=>'LEFT'),
    		'UserConnect'=>array($field['UserConnect'],"_on"=>"User.uid=UserConnect.uid","_type"=>"LEFT"),
    	    'UserProfile'=>array($field['UserProfile'],"_on"=>"User.uid=UserProfile.uid","_type"=>"LEFT"),
    	    'UserAddress'=>array($field['UserAddress'],"_on"=>"User.uid=UserAddress.uid","_type"=>"LEFT")
    	);
    	$viewFields = array();
    	if($viewTable){
    		foreach($viewTable as $v){
    			if(array_key_exists($v, $view)){
    				$viewFields[$v] = $view[$v];
    			}
    		}
    	}
    	if(empty($viewFields)){
    		$viewFields = $view;
    	}
    	return $this->dynamicView($viewFields);
    }
    /**
     * 修改用户前的回调方法
     * @param array $data
     * @param array $options
     * @return bool
     */
    protected function _before_update(&$data,$options) {
        //用户密码强度
        if(!empty($data['pass'])) {
            $pass_length = $this->pwdStrength($data['pass']);
            $data['pass_length'] = $pass_length;
            $this->data['pass_length'] = $pass_length;
        }
    }

    /**
     * 修改用户成功后的回调方法
     * @param array $data
     * @param array $options
     * @return bool
     */
    protected function _after_update($data,$options) {
        $uid = $options['where']['uid'];

        $account_model = $this->getAccountModel();
        //修改邮箱后更新账号
        if(isset($data['username']) && !$account_model->replaceAccount($uid,$data['username'],'',3)->isSuccess([Results::SUCCESS_CODE,'ACCOUNT_IS_MODIFY'])){
            return false;
        }

        if(isset($data['email']) && !$account_model->replaceAccount($uid,$data['email'],'',1)->isSuccess([Results::SUCCESS_CODE,'ACCOUNT_IS_MODIFY'])){
            return false;
        }

        //修改手机后更新账号
        if(isset($data['mobile']) && !$account_model->replaceAccount($uid,$data['mobile'],'',2)->isSuccess([Results::SUCCESS_CODE,'ACCOUNT_IS_MODIFY'])){
            return false;
        }

        return true;
    }

    /**
     * 删除用户成功前的回调方法
     * @param array $options
     * @return bool
     */
    protected function _before_delete($options) {
        $uid = $options['where']['uid'];
        if(!$this->getAccountModel()->delAllAccount($uid)->isSuccess()){
            return false;
        }
        if(!$this->getCreditsModel()->destroyAccountCredits($uid)->isSuccess()){
            return false;
        }
        return true;
    }


    /**
     * 通过id获取用户
     * @param integer $id 用户id
     * @return \Common\Org\util\Results
     */
    public function getUserById($id,$field=true){
        $result = $this->result();
        $where = [
            'uid' => $id
        ];
        $user_info = $this->field($field)->where($where)->find();
        if($user_info === false){
            return $result->set('GET_USER_FAILED');
        }
        //获取头信息
        if($user_info['headattr']){
			$attach = D('Upload/AttachMent')->getAttach($user_info['headattr']);
			$user_info['path'] = fullPath($attach[0]['path']);
        }else{
        	$user_info['path'] = '';
        }
        return $result->content($user_info)->set();
    }

    /**
     * 通过用户名获取用户/验证用户是否唯一
     * @param $username 用户名
     * @return \Common\Org\util\Results
     */
    public function getUserByName($username){
    	$result = $this->result();
    	$where = [
    		'username' => $username
    	];
    	$user_info = $this->scope('normalDefault')->where($where)->find();
    	if(empty($user_info)){
    		return $result->set('GET_USER_FAILED');
    	}
    	return $result->content($user_info)->set();
    }

    /**
     * 登录账户的类型
     * @param string $account 账号
     * @param int $type 类型
     * @return \Common\Org\util\Results
     */
    private function selectMode($account, $type) {
        return $this->getAccountModel()->loginAuth($account,$type);
    }

    /**
     * 登录流程
     * @param string $account 用户帐号
     * @param string $pasword 用户密码
     * @param $type 1为用户名
     * @return \Common\Org\util\Results
     */
    public function loginAuth($account, $pasword, $type = 0){
        $result = $this->result();
        $sel_result = $this->selectMode($account, $type);
        if ($sel_result->isSuccess()) {
            $uid = $sel_result->getResult();
        }

        $where = [
            'uid' => $uid
        ];
        $user = $this->field('uid,pass,status')->scope()->where($where)->find();
        if ($user) {
            if ($user ['status'] == 0) {
                return $result->set('USER_IS_LOCKED');
            }

            if ($user ['pass'] != getpass($pasword)) {
                return $result->set('PASSWORD_ERROR');
            }
            return $result->content($user['uid'])->set();
        } else {
            return $result->set('USER_NOT_EXIST');
        }
    }

    /**
     * 登录日志回调
     * @param integer $id 用户id
     * @param array $data 登录数据
     * @param integer $status 登录结果代码
     * @param string $info 登录结果
     */
    public function recordLogin($id, $data, $status, $info = ''){
        // TODO: Implement recordLogin() method.
    }

    /**
     * 获取账户模型
     * @return \Model|\Think\Model
     */
    public function getAccountModel(){
        return D('User/Account');
    }

    /**
     * 获取积分模型
     * @return \Model|\Think\Model
     */
    public function getCreditsModel(){
        return D('User/Credits');
    }
    /**
     * 检查某些唯一字段是否已经存在
     * @param int $type 类型  1：邮件 2：手机  3:用户名
     * @param string|int $val
     * @param string $uid 用户id
     * @return boolean 存在某字段返回false 反之true
     */
    public function ifExist($type,$val,$uid){
    	$where = $this->_scope['normal']['where'];
    	$where = array_merge($where,$this->_scope['default']['where']);
    	if($uid){
	    	$where['uid'] = ['not in',$uid];
    	}
    	$res = true;
    	if($type == 1){
    		$where['email'] = $val;
    		$res = $this->where($where)->count();
    	}else if($type == 2){
    		$where['mobile'] = $val;
    		$res = $this->where($where)->count();
    	}else if($type == 3){
    		$where['username'] = $val;
    		$res = $this->where($where)->count();
    	}
    	return $res ? false : true;
    }
    //用户名显示
    public function showUserName($uid){
    	$res = $this->scope()->where(['uid' => $uid])->find();
    	if(!$res){
    		return '';
    	}
    	if($res['aliasname']){
    		return $res['aliasname'];
    	}
    	return $res['username'];
    }
    //核对密码
    function checkPassWord($uid,$passwd){
    	$passwd = getpass($passwd);
    	$yumPasswd = $this->scope()->where([$this->getPk() => $uid])->getField('pass');
    	return ($passwd == $yumPasswd) ? true : false;
    }

    /**
     * 推荐用户
     * @param $uid 用户id
     * @param $invitation 好友id
     *
     */
    public function recommend($uid,$invitation){
        $invitation_info = $this->scope()->where(['uid'=>$invitation])->getField('uid');
        if(empty($invitation_info)){
            return ;
        }

        D('UserRecommend')->data([
            'uid'=>$uid,
            'recommend'=>$invitation,
            'add_time'=>NOW_TIME
        ])->add();

        //推荐用户积分添加
        $integral = Integral::getInstance();
        $integral->run('recommend_user',$invitation);
    }
    /**
     * 检查是否为内部员工
     *
     */
    function isInsideUser($userIdArr){
    	$userIdArr = (array)$userIdArr;
    	$count = $this->scope('default,normal')->where(['is_inside_user' => ['in',$userIdArr]])->count();
    	return $count ? true : false;
    }
    /**
     * 导出会员
     *
     * @param array $user
     *          要导出的数据
     */
    public function exportExcel($user) {
        $excel = new \Admin\Org\Util\ExcelComponent ();
        $excel = $excel->createWorksheet ();
        $excel->head ( array (
                '用户名',
                '用户昵称',
                '真实姓名',
                '交易额/次数',
                '上次交易时间',
                '用户余额',
                '用户来源',
                '最后一次登入时间',
                '内部员工'
        ) );
        $sn = "export";
        $data = array ();
        $key = 0;
        foreach ( $user as  $v ) {
            $sn = (string)$v ['username'];
            $data [$key] ["username"] = $v ["username"];
            $data [$key] ["aliasname"] = $v ["aliasname"];
            $data [$key] ["real_name"] = $v ["real_name"];
            $data [$key] ["dealTotalCount"] = $v ['dealTotalCount'];
            $data [$key] ["last_buy_time"] = $v ["last_buy_time"];
            $data [$key] ["account"] = $v ["account"];
            $data [$key] ["come_from"] = $v ['come_from'];
            if(!empty($v ["last_login_time"])){
               $data [$key] ["last_login_time"]=date('Y-m-d H:i:s',$v ["last_login_time"]);
            }else{
               $data [$key] ["last_login_time"]=$v ["last_login_time"];
            }
            if($v['is_inside_user']){
               $data [$key] ['is_inside_user']="是";
            }else{
                $data [$key] ['is_inside_user']="否";
            }
            // $this->addUserRow($v['goods'],$key,$data);
            $key++;
        }
        $excel->listData ( $data, array (
                "username",
                "aliasname",
                "real_name",
                "dealTotalCount",
                "last_buy_time",
                "account",
                "come_from",
                "last_login_time",
                "is_inside_user"
        ));
        $excel->output ( $sn . ".xlsx" );
    }

    /**
     *  获取用户的积分信息
     *  @param $uid Array 用户id
     */
    public function getUserCreditsInfo($uid){
        if(is_array($uid)){
            $list = M("AccountCredits")->where(array('uid'=>array("IN",$uid)))->select();
        }else{
            $list = M("AccountCredits")->where(['uid'=>$uid])->select();
        }
        
        $dat = array();
        $arr = array();
        foreach($list as $k=>$v){
            $dat[$v['uid']][]=$v;
        }
        // return $dat;
        foreach($dat as $k=>$v){
            foreach($v as $val){
                if($val['type']==1){
                    $arr[$k]['integral']=$val['credits'];
                }elseif($val['type']==3){
                    $arr[$k]['growth']=$val['credits'];
                }elseif($val['type']==0){
                    $arr[$k]['balance']=$val['credits'];
                }
            }
        }
        return $arr;
    }

    /**
     *  获取邀请人信息
     *  @param $uid String 用户id
     */
    public function getInviter($uid){
        $invite = M("UserRecommend")->where(['uid'=>$uid])->find();
        if($invite){
            /*$where = array(
                'uid'=>$invite['recommend'],
                'delete_time'=>$invite['recommend'],
            );*/
            $userInfo = M('User')->field("username,mobile")->where(['uid'=>$invite['recommend']])->order('add_time DESC')->find();
            if($userInfo['username']){
                return $userInfo['username'];
            }else{
                return $userInfo['mobile'];
            }
        }else{
            return;
        }
    }


    /**
     *  清空积分的推送消息
     *  @param $uid 用户uid
     */
    public function emptyUserIntegral($uid){
		$LastThird = M('User')->field('uid')->where(array(
			'uid' => $uid,
			'delete_time' => 0,
			'last_empty_integral' => array(
				'elt',(NOW_TIME-362*2*24*60*60)
			),

			'add_time' => array(
				'lt',
				NOW_TIME - 362*2*24*60*60
			)
		))->find();

		$LastOne = M('User')->field('uid')->where(array(
			'uid' => $uid,
			'delete_time' => 0,
			'last_empty_integral' => array(
				'elt',(NOW_TIME-364*2*24*60*60)
			),
			'add_time' => array(
				'lt',
				NOW_TIME - 364*2*24*60*60
			)
		))->find();
		// return $LastOne;

		if(!$LastThird && !$LastOne){
			return;
		}elseif($LastOne){
			$push_brief = '您的积分将在一天后清空，请尽快使用！';
		}elseif(!$LastOne && $LastThird){
			$push_brief = '您的积分将在三天后清空，请尽快使用！';
		}

		$da=array(
			'push_name' => 3,
			'push_title' => '尊敬的必好货用户：您好！',
			'push_brief' => $push_brief,
			'push_url' => C('JT_CONFIG_WEB_MOBILE_WAP_URL').'/html/memcenter.html',  //会员中心查看积分详情
			'push_is_app' => 2,
			'push_addtime' => NOW_TIME,
            'is_bind' => 1,
			'push_name_type' => 1,
		);

		$ThirdPush = M('MessagePush')->alias('mp')->join('LEFT JOIN __MESSAGE_USER__ mu ON mp.push_id=mu.fk_push_id')->where(array('push_brief'=>array('LIKE','%三天后清空%'),'user_id'=>$uid))->order('mu.addtime DESC')->find();
		$FirstPush = M('MessagePush')->alias('mp')->join('LEFT JOIN __MESSAGE_USER__ mu ON mp.push_id=mu.fk_push_id')->where(array('push_brief'=>array('LIKE','%一天后清空%'),'user_id'=>$uid))->order('mu.addtime DESC')->find();
		// return $FirstPush;

		if($ThirdPush && date('Ymd',$ThirdPush['addtime'])==date('Ymd',NOW_TIME)){
			if(!$LastOne){
				return;
			}
		}
		if($FirstPush && date('Ymd',$FirstPush['addtime'])==date('Ymd',NOW_TIME)){
			if($LastOne){
				return;
			}
		}

		$this->startTrans();
		if($LastThird && !$LastOne){
			$push_id=M('MessagePush')->add($da);
			if($push_id){
				$dat=array(
					'user_id' => $uid,
					'fk_push_id' => $push_id,
					'addtime' => NOW_TIME,
					'fk_push_name' => 3,
				);
				
				if (M('MessageUser')->add($dat) === false) {
					$this->rollback ();
					return false;
				}
				$this->commit();     
			}
		}elseif($LastOne){
			$push_id=M('MessagePush')->add($da);
			if($push_id){
				$dat=array(
					'user_id' => $uid,
					'fk_push_id' => $push_id,
					'addtime' => NOW_TIME,
					'fk_push_name' => 3,
				);
				if (M('MessageUser')->add($dat) === false) {
					$this->rollback();
					return false;
				}
				$this->commit();  
			}
		}
    }

    /**
     *	会员等级变更通知
     *	@param $uid 会员uid
     *	@param $grade_id 会员等级id
     */
    public function gradeChangeMessage($uid,$grade_id){
    	if(empty($uid)){
    		return;
    	}

    	$growth = M('AccountCredits')->where(array('uid'=>$uid,'type'=>3))->getField('credits');
    	$gradeInfo = D("User/UserGrade")->getUserGradeInfo(intval($growth));

        if(empty($gradeInfo)){  //查不到等级信息就退出
            return;
        }

    	if($grade_id != $gradeInfo['grade_id']){
    		$this->startTrans();
    		$da=array(
				'push_name' => 3,
				'push_title' => '尊敬的必好货用户：恭喜您！',
				'push_brief' => '您的会员等级已提升为'.$gradeInfo['grade_name'].'!',
				'push_url' => C('JT_CONFIG_WEB_MOBILE_WAP_URL').'/html/memcenter.html',  //会员中心查看积分详情
				'push_is_app' => 2,
				'push_addtime' => NOW_TIME,
				'is_bind' => 1,
                'push_name_type' => 2,
			);

			$push_id=M('MessagePush')->add($da);
			if($push_id){
				$dat=array(
					'user_id' => $uid,
					'fk_push_id' => $push_id,
					'addtime' => NOW_TIME,
					'fk_push_name' => 3,
				);
				if (M('MessageUser')->add($dat) === false) {
					$this->rollback();
					return false;
				}else{
					M('User')->where(['uid'=>$uid])->save(['grade_id'=>$gradeInfo['grade_id']]);

					$this->commit();
				}
			}
    	}
    }

    /*//增加产品行
    private function addUserRow($goodsArr,&$key,&$data){
        if(count($goodsArr) <= 1){
            return;
        }
        unset($goodsArr[0]);
        foreach ($goodsArr as $v){
            $key++;
            $data [$key] ["goods_name"] = $v['name'];
            $data [$key] ["goods_number"] = $v['number'];
            $data [$key] ["goods_price"] = $v['goods_price'];
        }
        
    }*/
}
