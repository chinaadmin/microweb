<?php
/**
 * 积分
 *
 * 示例：
 * $integral = Integral::getInstance();
 * 注册增加积分：
 *      $integral->run('register','a4481bce403a05a7d36e6edd50fe9aa8');
 * 购买商品赠送积分：
 *      $integral->setParam([
'appoint_price'=>15,//指定积分
 *          'price'=>100,//商品价格
 *          'is_special'=>0,//是否特价商品
 *      ])->run('buy_goods','a4481bce403a05a7d36e6edd50fe9aa8');
 *
 */
namespace User\Org\Util;
class Integral {
	
	/**
	 * 保存类实例的静态成员变量
	 */
	private static $_instance;

    /**
     * 额外参数
     * @var array
     */
    private $param = [];
	
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
     * 设置参数
     * @param array $param 参数
     * @return $this
     */
    public function setParam($param){
        $this->param = is_array($param)?$param:[];
        return $this;
    }

    /**
     * 运行积分
     * @param string $code 积分编码
     * @param string $uid 用户id
     * @return bool
     */
    public function run($code,$uid){
        $where = [
            'status'=>1,
            'rule_code'=>$code
        ];
        $param = M('ConfigsIntegral')->where($where)->getField('value');
        $rule = Rule::getInstance();
        if($rule->check($code,$uid)){
            $rule_info = M('Rule')->where(['code'=>$code])->field(true)->find();
            $param = unserialize($param);
            $param = is_array($param)?$param:[];
            $integral = $this->runFormula($rule_info['formula'],$param);
            if(D('User/Credits')->setCredits($uid,$integral,$rule_info['name'],0,1)->isSuccess()){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 获取积分
     * @param string $code 积分编码
     * @param string $uid 用户id
     * @return bool
     */
    public function getIntegral($code,$uid){
        $where = [
            'status'=>1,
            'rule_code'=>$code
        ];
        $param = M('ConfigsIntegral')->where($where)->getField('value');
        $rule = Rule::getInstance();
        if($rule->check($code,$uid)){
            $rule_info = M('Rule')->where(['code'=>$code])->field(true)->find();
            $param = unserialize($param);
            $param = is_array($param)?$param:[];
            return $this->runFormula($rule_info['formula'],$param);
        }
        return 0;
    }

    /**
     * 运行积分公式
     * @param string $formula 公式
     * @param array $param 参数
     * @return mixed
     */
    public function runFormula($formula,$param = []){
        $param = array_merge($param,$this->param);
        extract($param);
        $formula = html_entity_decode($formula);
        eval("\$result = {$formula};");
        return floor($result);
    }

}