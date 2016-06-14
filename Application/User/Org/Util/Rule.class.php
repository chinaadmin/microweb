<?php
/**
 * 规则
 * @author cwh
 */
namespace User\Org\Util;
class Rule {
	
	/**
	 * 保存类实例的静态成员变量
	 */
	private static $_instance;
	
	/**
	 * 初始化
	 */
	private function __construct() {

	}
	
	/**
	 * 访问实例的公共的静态方法
	 * @return Rule
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
	 * @param string relation    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
	 * @return boolean           通过验证返回true;失败返回false
	 */
	public function check($name, $uid, $relation='or') {
		//获取通过验证的菜单
		$auth_lists = $this->getAuthList($name,$uid);
		$list = []; //保存验证通过的规则名
		foreach ( $auth_lists as $val ) {
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
     * 获得权限列表
     * @param string $name 名称
     * @param string $uid 用户id
     * @return array
     */
    protected function getAuthList($name,$uid){
        //读取用户组所有权限规则(in)
        $map = [];
        $map['code'] = ['in', $name];
        $map['status'] = 1;
        $rules = M('Rule')->where($map)->field(true)->select();

        //循环规则，判断结果
        $auth_list = [];
        foreach ($rules as $r) {
            $config = unserialize($r['config']);
            switch ($r['type']) {
                case 1://存在就通过
                    $auth_list[] = $r['code'];
                    break;
                case 2://需要条件验证
                    //获取会员明细
                    $user = $this->getUserInfo($uid);
                    $command = preg_replace('/\{(\w*?)\}/e', '$user[\'\\1\']', $config['condition']);
                    @(eval('$condition=(' . $command . ');'));
                    if ($condition) {
                        $auth_list[] = $r['code'];
                    }
                    break;
                case 3://模型验证
                    $model = D(ucfirst($config['model']));
                    $method = $config ['function'];
                    $result = $model->$method ($uid);
                    if ($result) {
                        $auth_list[] = $r['code'];
                    }
                    break;
            }
        }
        return $auth_list;
    }

}