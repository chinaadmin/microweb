<?php
/**
 * 管理员用户类
 * @author cwh
 * @date 2015-04-01
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use User\Interfaces\UserInterface;
use Org\Net\IpLocation;
class AdminModel extends AdminbaseModel implements UserInterface{

    protected $tableName = 'admin_user';

    public $_validate = [
        ['email','email','EMAIL_FORMAT_ERROR::邮箱格式不正确',self::EXISTS_VALIDATE],
        ['role_id','require','ROLE_REQUIRE::角色不能为空'],
    	['role_id','modRole','ROLE_MODI_FAIL::修改角色失败',self::VALUE_VALIDATE,'callback',self::MODEL_UPDATE ],
        ['username','require','ACCOUNT_REQUIRE::账号不能为空'],
        ['username','ifUniqueUsername','ACCOUNT_EXISTS::账号已经存在',self::EXISTS_VALIDATE,'callback',self::MODEL_BOTH],
        ['password','require','PASSWORD_REQUIRE::密码不能为空',self::MUST_VALIDATE,'',self::MODEL_INSERT]
    ];
    public $_auto = [
        ['password','pwdHash',self::MODEL_BOTH,'callback'],
        ['add_time','time',self::MODEL_INSERT,'function'],
        ['invite_code','create_invite_code',self::MODEL_INSERT,'callback'],
    ];

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>['status'=>1],
        ],
        'default'=>[// 获取没有被删除状态
            'where'=>[
                'delete_time'=>['eq',0]
            ],
        ]
    ];
    function create_invite_code(){
    	$code = rand_string(4,5);
    	if($this->scope()->where(['invite_code' => $code])->count()){
			$code = $this->create_invite_code();	    			
    	}
    	return $code;
    }
    //用户名是否有重名
    function  ifUniqueUsername(){
    	$username = I('request.username');
    	$uid = I('request.uid');
        $where['username'] = $username;
    	if($uid){ //修改时 不等于本身
	   		$where[$this->getPk()] = ['not in',[$uid]];
    	}
    	$flag = $this->scope()->where($where)->count();
    	return $flag ? false : true;
    }
	public function modRole($role_id){
		$uid = I('request.uid');
		if(!$uid){
			return false;
		}
		$role_id_old = M('admin_user')->where(['uid' => $uid])->getField('role_id');
		$typeOldId = M('admin_role')->where(['role_id' => $role_id_old])->getField('type');
		$typeNowId = M('admin_role')->where(['role_id' => $role_id])->getField('type');
		if(($typeNowId != $typeOldId) && $typeOldId == 2){ //类型不同 且由门店角色转换为后台角色  删除原门店成员数据
			$res = D('Stores/StoresUser')->delData(['uid' => $uid]);			
			if($res->isSuccess()){
				return true;
			}else{
				return false;
			}
		}
		return true;
	}
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
     * 插入数据前的回调方法
     * @param array $data
     * @param array $options
     */
    protected function _before_insert(&$data,$options) {
        //用户标识
        $mark = uniqueId();
        $data['uid'] = $mark;
        $this->data['uid'] = $mark;
    }

    /**
     * 通过id获取用户
     * @param integer $id 用户id
     * @return \Common\Org\util\Results
     */
    public function getUserById($id){
        $result = $this->result();
        $where = [
            'uid' => $id
        ];
        $user_info = $this->where($where)->find();
        if($user_info === false){
            return $result->set('GET_USER_FAILED');
        }
        return $result->content($user_info)->set();
    }
    /**
     * 批量获取管理员信息
     * @param array $id
     * @param string $field
     * @return Ambigous <\Common\Org\Util\$this, \Common\Org\Util\Results>
     */
    public function getUserByIds(Array $id,$field=true){
    	$result = $this->result();
    	$where = [
    	'uid' => array('in',$id)
    	];
    	$user_info = $this->field($field)->where($where)->select();
    	if($user_info === false){
    		return $result->set('GET_USER_FAILED');
    	}
    	return $result->content($user_info)->set();
    }

    /**
     * 登录账户的类型
     * @param string $account 账号
     * @param int $type 类型
     * @return array
     */
    private function selectMode($account, $type) {
        $where = [
            'username' => $account
        ];
        /*if ($type == 1) {
            $where = [
                'username' => $account
            ];
        } else {
            $where = [
                'email' => $account
            ];
        }*/
        return $where;
    }

    /**
     * 登录流程
     * @param string $account 用户帐号
     * @param string $pasword 用户密码
     * @param $type 1为用户名
     * @return \Common\Org\util\Results
     */
    public function loginAuth($account, $pasword, $type = 1){
        $result = $this->result();
        $where = $this->selectMode ( $account, $type );
        $user = $this->field ( 'uid,password,status' )->scope()->where ( $where )->find ();
        if ($user) {
            if ($user ['status'] == 0) {
                return $result->set('USER_IS_LOCKED');
            }
            if ($user ['password'] != getpass ( $pasword )) {
                return $result->set('PASSWORD_ERROR');
            }
            $this->_after_login($account);
            return $result->content($user['uid'])->set();
        } else {
            return $result->set('USER_NOT_EXIST');
        }
    }
    //登入成功后
	private  function _after_login($username){
		$data = ['last_login_time' => NOW_TIME];
		$data['last_login_ip'] = get_client_ip();
		$this->where(['username' => $username])->setInc('login_count');
		$this->where(['username' => $username])->save($data);
	}
    /**
     * 获取权限
     * @param int $uid 用户id
     * @return array
     */
    public function getPermissionIds($uid){
        $role_ids = $this->getRoleIds($uid);
        return D('Admin/Role')->getPermissionIds($role_ids);
    }

    /**
     * 获取用户下的角色ids
     * @param string $uid
     * @return array
     */
    public function getRoleIds($uid){
        return $this->where(['uid'=>$uid])->getField('role_id',true);
    }

    /**
     * 获取门店id
     * @param array $info 用户信息
     * @return int
     */
    public function getStoresId($info){
        $is_store_role = D('Admin/Role')->isStoreRole($info['role_id']);
        if(!$is_store_role){
            return 0;
        }
        $stores_ids = D('Stores/StoresUser')->getStoresIds($info['uid']);
        $stores_id = current($stores_ids);
        return empty($stores_id)?0:$stores_id;
    }

    /**
     * 登录日志回调
     * @param integer $id 用户id
     * @param array $data 登录数据
     * @param integer $status 登录结果代码 1:成功 0:失败
     * @param string $info 登录结果
     */
    public function recordLogin($id, $data, $status, $info = ''){
				/* $data = [ 
							'user' => $account,
							'password' => $password,
							'type' => $type
						];
				 */
   	           $myData['adlog_account'] = $data['user'];
   	           $myData['adlog_password'] = $status ?  '' : $data['password']  ;
 	           $myData['adlog_status'] = $status;
   	           $myData['adlog_info'] = $info;
   	           $myData['adlog_uid'] = $id;
   	           $myData['adlog_add_time'] = NOW_TIME;
   	           $myData['adlog_ip'] = get_client_ip();
    	        M('admin_loginlog')->add($myData);
    }
    //用户名显示
    public function showUserName($uid){
    	$res = $this->scope()->where(['uid' => $uid])->find();
    	if(!$res){
    		return '';
    	}
    	if($res['nickname']){
    		return $res['nickname'];
    	}
    	return $res['username'];
    }
    
    /**
     * 获取用户信息
     * @param  $uid 用户id
     */
    public function getUserInfo($uid,$field=true){
    	$where = array(
    			'uid' => array('in',(array)$uid)
    	);
    	return $this->field($field)->scope()->scope("normal")->where($where)->select();
    }
    /**
     * 增加推荐人  邀请人
     */
    function recommend($uid,$invite_code){
		$where = array(
			'invite_code|username' => $invite_code
		);
		$recommendUid = $this->scope()->where($where)->getField('uid');
		if($recommendUid){
			$data['mpc_uid'] = $uid;
			$data['mpc_recommend'] = $recommendUid;
			$data['mpc_add_time'] = NOW_TIME;
			M('market_pushing_recommend')->add($data);
		}
		return true;
    }

	/**
     * 增加推荐人  员工编号
     */
    public function recommends($invite_id,$uid){
		$data = array();
		if($invite_id){
			$data['mpc_uid'] = $uid;
			$data['mpc_recommend'] = $invite_id;
			$data['mpc_add_time'] = NOW_TIME;
			M('market_pushing_recommend')->add($data);
		}
		return true;
    }

    //判断邀请码是否存在
    public function isHasCode($invite_code){
        return $this->scope()->where(['invite_code' => $invite_code])->count();
    }

    //判断员工编号是否存在  2016/4/1 qrong
    public function isHasCodes($invite_code){
        return $this->scope()->where(['username' => $invite_code])->count();
    }
}