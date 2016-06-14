<?php
/**
 * 用户基础类
 * @author cwh
 * @date 2014-04-01
 */
namespace User\Org\Util;
use Common\Org\Util\Helpers;
class User {

	public $memberClass = 'User/User';
    
	/**
	 * 用户
	 * @var array
	 */
	protected $user ;
	
	// 是否登录后记录日志
	public $recordLog = false;
	private static $_instance = false;

	/**
	 * 访问实例的公共的静态方法
	 *
	 * @return Member
	 */
	public static function getInstance() {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self ();
		}
		return self::$_instance;
	}

	private function __construct() {
		$module = C ( 'USER_MODEL' );
		if ($module) {
			$this->memberClass = $module;
		}
	}

	/**
	 * 私有化克隆函数，防止外界克隆对象
	 */
	private function __clone() {
	}

	/**
	 * session管理函数(对session进行加密和解密处理)
	 *
	 * @param string|array $name
	 *        	session名称 如果为数组则表示进行session设置
	 * @param mixed $value
	 *        	session值
	 * @return mixed
	 */
	public function session($name, $value = '') {
        return Helpers::session($name,$value);
	}

	/**
	 * 存储key值
	 *
	 * @return string
	 */
	public function getStorageName() {
		return 'user_' . md5 ( $this->memberClass );
	}

	public function getUser() {
		$id = $this->session ( $this->getStorageName () );
		$user = null;
		if (! empty ( $id )) {
			$memberModel = D ( $this->memberClass );
			//$memberModel->updataLogin ( $id );
			$result = $memberModel->getUserById ( $id );
            if($result->isSuccess()){
                $this->user = $result->getResult();
            }
		}
		return $this->user;
	}

	/**
	 * 设置用户
	 *
	 * @param
	 *        	$user
	 */
	public function setUser($user) {
		$this->user = $user;
		$this->loggedOut = false;
	}

	/**
	 * 清除session
	 */
	public function clearSession() {
		$this->session ( $this->getStorageName (), null );
		$this->session ( 'user_auth_sign_'.$this->getStorageName (), null );
	}

	/**
	 * 数据签名认证
	 *
	 * @param array|string $data
	 *        	被认证的数据
	 * @return string 签名
	 */
	public function dataAuthSign($data) {
		// 数据类型检测
		if (! is_array ( $data )) {
			$data = ( array ) $data;
		}
		ksort ( $data ); // 排序
		$code = http_build_query ( $data ); // url编码并生成query字符串
		$sign = sha1 ( $code ); // 生成签名
		return $sign;
	}

	/**
	 * 检测用户是否登录
	 *
	 * @return integer 0-未登录，大于0-当前登录用户ID
	 */
	public function isLogin() {
		$user_id = $this->session ( $this->getStorageName () );
		if (empty ( $user_id )) {
			return 0;
		} else {
			return $this->session ( 'user_auth_sign_'.$this->getStorageName () ) == $this->dataAuthSign ( $user_id ) ? $user_id : 0;
		}
	}


    /**
     * 根据用户id登录
     * @param string $uid 用户id
     */
    public function loginUsingId($uid){
        $this->toLogin($uid);
    }

	/**
	 * 登录
	 *
	 * @param array $credentials
	 *        	凭证 （account：帐号，password：密码，source：来源）
	 * @param integer $type
	 *        	(3为用户名、1为电子邮箱、2为手机号)
	 * @param bool $beHavior        	
	 * @return Results
	 */
	public function login($credentials, $type = 0, $beHavior = false) {
		extract ( $credentials );
		$meModel = D ( $this->memberClass );
		$result = $meModel->loginAuth ( $account, $password, $type );
		$data = [ 
			'user' => $account,
			'password' => $password,
			'type' => $type
		];
		// 登录成功
		if ($result->isSuccess()) {
            $data['user_id'] = $user_id = $result->getResult();
			//session
			$this->toLogin ( $user_id );
			if ($beHavior) {
				// 登陆后触发的行为
				B ( MODULE_NAME.'\Behaviors\AfterLogin','',$this->isLogin() );
			}

            if( $this->memberClass == 'User/User') {
                //记录用户登录记录
                $user_analysis = M('UserAnalysis')->where(['uid' => $user_id])->field('recharge_count,order_count')->find();
                $is_new = $user_analysis['recharge_count'] > 0 || $user_analysis['order_count'] > 0 ? 0 : 1;
                M('RecordLogin')->add([
                    'uid' => $user_id,
                    'source' => $source,
                    'is_new' => $is_new,
                    'is_synch'=>0,
                    'add_time' => NOW_TIME
                ]);
            }

			if ($this->recordLog) {
				$meModel->recordLogin ( $user_id, $data, 1  , '登录成功');
			}
		}else{
            $data['user_id'] = 0;
			if ($this->recordLog) {
				$meModel->recordLogin ( 0, $data, 0  , $result->getMsg());
			}
		}
        return $result;
	}

	/**
	 * 成功，缓存登录，记录session
	 *
	 * @param int $user_id
	 *        	用户id
	 */
	public function toLogin($user_id) {
        $meModel = D ( $this->memberClass );
        // $me_model->updataUserInfo ( $user_id );
        $result = $meModel->getUserById ( $user_id );
        if($result->isSuccess()) {
            $user = $result->getResult();
            $this->updateSession($user_id);
            $this->setUser($user);
        }
	}

	/**
	 * 返回用户
	 */
	public function user(){
		return $this->user;
	}
	
	/**
	 * 记录登录session
	 * 
	 * @param int $user_id
	 *        	用户id
	 */
	public function updateSession($user_id) {
		$this->session ( $this->getStorageName (), $user_id );
		$this->session ( 'user_auth_sign_'.$this->getStorageName (), $this->dataAuthSign ( $user_id ) );
	}

	/**
	 * 设置管理员
     * @param bool $is_super 是否超级管理员
	 * @return boolean
	 */

	public function saveAdmin($is_super = false){
		$key = $this->getAdminStorageName();
		if ($key){
			$this->session($key , $is_super?2:1);
			return true;
		}
		return false;
	}

    /**
     * 是否超级管理员
     * @return boolean
     */
	public function isSuperAdmin(){
        $key = $this->getAdminStorageName();
        if(!$key){
            return false;
        }
        return $this->session($key) == 2 ? true : false;
    }

	/**
	 * 是否管理员
	 * @return boolean
	 */
	public function isAdmin(){
        $key = $this->getAdminStorageName();
        if(!$key){
            return false;
        }
		return $this->session($key) ? true : false;
	}
	
	/**
	 * 退出登录
	 */
	public function logout() {
		$this->clearSession ();
		$this->user = null;
		$this->loggedOut = true;
	}
	
	/**
	 * 管理员登出
	 * @return boolean
	 */
	public function clearAdmin(){
        $key = $this->getAdminStorageName();
		if ($key){
			$this->session($key , null);
			return true;
		}
		return false;
	}

    /**
     * 存储管理员key值
     *
     * @return string
     */
    public function getAdminStorageName() {
        return 'admin_'. md5('jitu_auth');
    }
}