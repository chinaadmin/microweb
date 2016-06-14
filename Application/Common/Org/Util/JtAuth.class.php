<?php
/**
 * 权限控制
 * @author cwh
 */
namespace Common\Org\Util;
class JtAuth {
	
	/**
	 * 保存类实例的静态成员变量
	 */
	private static $_instance;
	
	/**
	 * 配置信息
	 */
	protected $_config = array(
			'JTAUTH_ON'           => true,                      // 认证开关
			'JTAUTH_TYPE' => 1 									// 认证方式，1为时时认证；2为登录认证。
	);
	
	/**
	 * 初始化
	 */
	private function __construct() {
		if (C('JTAUTH_CONFIG')) {
			//可设置配置项 JTAUTH_CONFIG, 此配置项为数组。
			$this->_config = array_merge($this->_config, C('JTAUTH_CONFIG'));
		}
	}
	
	/**
	 * 访问实例的公共的静态方法
	 * @return AgAuth
	 */
	public static function getInstance(){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self;
		}
		return self::$_instance;
	}
	
	/**
	 * 私有化克隆函数，防止外界克隆对象
	 */
	private function __clone(){}
	
	/**
	 * 检查权限
	 * @param string|array name  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
	 * @param int uid            认证用户的id
	 * @param string mode        执行check的模式
	 * @param string relation    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
	 * @return boolean           通过验证返回true;失败返回false
	 */
	public function check($name, $uid, $mode='url', $relation='or') {
		if (!$this->_config['JTAUTH_ON'])
			return true;
		//获取通过验证的菜单
		$menu_lists = $this->getAuthMenu($name,$uid,$mode);
		$list = array(); //保存验证通过的规则名
		foreach ( $menu_lists as $val ) {
			$list[] = $val['name'];
		}
		//or操作
		if ($relation == 'or' and !empty($list)) {
			return true;
		}
		//and操作
		$diff = array_diff($name, $list);
		if ($relation == 'and' and empty($diff)) {
			return true;
		}
		return false;
	}

    /**
     * 获取需要验证的url的菜单ID
     * @param string|array $url 需要验证的url列表
     * @param int $uid 用户ID
     * @param string $mode 类型:url：为url验证，其他为完全匹配
     * @return array
     */
    public function getAuthMenuIDs($url,$uid,$mode='url'){
        //获取通过验证的菜单
        $menu_lists = $this->getAuthMenu($url,$uid,$mode);
        $menu_ids = array();//保存验证通过的所有菜单ID
        foreach ( $menu_lists as $val ) {
            $ids = $val['id'];
            if (is_string($ids)) {
                if (strpos($ids, ',') !== false) {
                    $ids = explode(',', $ids);
                } else {
                    $ids = array($ids);
                }
            }
            $menu_ids = array_merge($ids,$menu_ids);
        }
        return array_unique($menu_ids);
    }

    /**
     * 获取需要验证的url的菜单
     * @param string|array $url 需要验证的url列表
     * @param int $uid 用户ID
     * @param string $mode 类型:url：为url验证，其他为完全匹配
     * @return array
     */
    public function getAuthMenu($url,$uid,$mode='url'){
        $auth_list = $this->getAuthList($uid); //获取用户需要验证的所有有效规则列表
        if (is_string($url)) {
            $url = strtolower($url);
            if (strpos($url, ',') !== false) {
                $url = explode(',', $url);
            } else {
                $url = array($url);
            }
        }
        $menu = array();//保存验证通过的所有菜单
        foreach ( $auth_list as $val ) {
            if($this->inUrl($val['name'],$url,$mode)){//验证是否在url列表里
                $menu[] = $val;
            }
        }
        return $menu;
    }

	/**
	* 是否在url数组里
	* @param string $url        url链接
	* @param array $arr_url     url数组
	* @param string $mode          类型:url：为url验证，其他为完全匹配
	* @return boolean           通过验证返回true;失败返回false
	*/
	public function inUrl($url,$arr_url,$mode = 'url'){
		$query = preg_replace('/^.+\?/U','',$url);
		if ($mode=='url' && $query!=$url ) {
			$REQUEST = unserialize( strtolower(serialize($_REQUEST)) );
			parse_str($query,$param); //解析规则中的param
			$intersect = array_intersect_assoc($REQUEST,$param);
			$url = preg_replace('/\?.*$/U','',$url);
			if ( in_array($url,$arr_url) && $intersect==$param ) {  //如果节点相符且url参数满足
				return true;
			}
		}else if (in_array($url , $arr_url)){
			return true;
		}
		return false;
	}
	
	/**
	 * 获得权限列表
	 * @param int $uid  用户id
	 * @return array
	 */
	protected function getAuthList($uid) {
		static $_auth_list = array(); //保存用户验证通过的权限列表
		$auth_mark = $uid;
		if (isset($_auth_list[$auth_mark])) {
			return $_auth_list[$auth_mark];
		}
		if( $this->_config['JTAUTH_TYPE']==2 && isset($_SESSION['_JTAUTH_LIST_'.$auth_mark])){
			return $_SESSION['_JTAUTH_LIST_'.$auth_mark];
		}

		//读取用户所属用户组
		$ids = $this->getMenuIds($uid);//保存用户所属用户组设置的所有权限规则id
		if (empty($ids)) {
			$_auth_list[$auth_mark] = array();
			return array();
		}
		
		$where = array(
				'menu_id'=>array('in',$ids),
				'relation'=>array('not in',['','[]'])
				);
		$menu_model = D('Admin/Menubar');
		$menu_lists = $menu_model->scope()->where($where)->getField('relation',true);
		foreach($menu_lists as $v){
			$relation = json_decode($v);
			$ids = array_merge($ids,$relation);
		}
		$ids = array_filter(array_unique($ids));
		
		$where = array(
			'menu_id'=>array('in',$ids),
            'url'=>array('neq','')
		);
		//读取用户组所有权限规则
		$rules = $menu_model->scope()->where($where)->field('menu_id,module,url,GROUP_CONCAT(menu_id)')->group('url')->select();

        $auth_list = array_map(function($info){
            return [
                'id' => $info['menu_id'],
                'name'=>strtolower($info['module'].'/'.$info['url'])
            ];
        },$rules);
		
		$_auth_list[$auth_mark] = $auth_list;
		if($this->_config['JTAUTH_TYPE']==2){
			//规则列表结果保存到session
			$_SESSION['_JTAUTH_LIST_'.$auth_mark]=$auth_list;
		}
		return $auth_list;
	}
	
	/**
	 * 根据用户id获取菜单Ids
	 * @param int $uid 用户id
	* @return array
	 */
	public function getMenuIds($uid) {
		static $menus = [];
		$menu_mark = $uid;
		if (isset($menus[$menu_mark]))
			return $menus[$menu_mark];
		$menuids = D('Admin/Admin')->getPermissionIds($uid);
		$menus[$menu_mark]=$menuids?:[];
		return $menus[$menu_mark];
	}

}