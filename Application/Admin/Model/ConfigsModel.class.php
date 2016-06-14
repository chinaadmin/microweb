<?php
/**
 * 网站配置类
 * @author cwh
 * @date 2014-04-07
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class ConfigsModel extends AdminbaseModel {

	protected $_auto = [ 
			[ 
					'code',
					'autoPrefix',
					self::MODEL_INSERT,
					'callback' 
			] 
	];

	protected $_validate = [ 
			[ 
					'code',
					'require',
					'配置代码不能为空',
					self::EXISTS_VALIDATE 
			],
			[ 
					'code',
					'',
					'配置代码已经存在',
					self::EXISTS_VALIDATE,
					'unique' 
			],
			[ 
					'name',
					'require',
					'配置名称不能为空',
					self::EXISTS_VALIDATE 
			] 
    ];

	/**
	 * 配置选项HTML类型
	 * @var array
	 */
	private $configs_type = [
			'text' => '单行文本',
			'textarea' => '多行文本',
			'password' => '密码文本',
			'editor' => '编辑框',
			'radio' => '单选框',
			'select' => '下拉列表框',
			'group' => '分组',
			'image' => '图片' 
	];

    /**
     * 获取配置类型
     * @return array
     */
    public function getType(){
        return $this->configs_type;
    }

    /**
     * 保存配置
     * @param array $data 配置信息
     * @return mixed
     */
	public function saveConf(array $data) {
        $sql = array2UpdateSql($data, $this->getTableName(), 'value', 'code');
        $this->startTrans();
        $result = $this->execute($sql);
        if ($result !== false) {
            $this->commit();
        } else {
            $this->rollback();
        }
        return $result !== false ? $this->result()->set() : $this->result()->set('SUBMIT_CONFIG_FAILED');
	}

    /**
     * 反序列号配置项
     * @param string $option 配置项
     * @return mixed
     */
	public function unserializeOption($option) {
		return unserialize ( $option );
	}

    /**
     * 序列号配置项
     * @param array $option 配置项
     * @return string
     */
	public function serializeOption($option) {
		return serialize ( $option );
	}
	

    /**
     * 自动加入前缀
     * @param string $string 需要加入的配置代码
     * @param string $prefix 前缀
     * @return string
     */
	public function autoPrefix($string ,$prefix = 'web_'){
		return stripos($string, $prefix) !== false ? $string : "$prefix{$string}";
	}

    /**
     * 网站配置
     */
    public function Configs(){
        $clists = F('Configs');
        if($clists !== false){
            return $clists;
        }
        $clists = [];
        $where = [];
        $where['is_system'] = ['neq',1];
        $lists = $this->where($where)->field('code,value,type')->select();
        foreach($lists as $v){
            if($v['type']=='image'){//图片特殊处理
                $atta_info = D('Upload/AttachMent')->getAttach($v['value']);
                $v['value'] = $atta_info[0]['path'];
            }
            $clists[$v['code']] = $v['value'];
        }
        F('Configs',$clists);
        return $clists;
    }

    /**
     * 系统配置
     * @param boolean 是否强制性更新
     * @return array|bool|mixed
     */
    function SysConfigs($is_bool=false){
        $clists = F('SysConfigs');
        if($clists === false || $is_bool === true){
            $where = array();
            $where['is_system'] = ['eq',1];
            $lists = $this->where($where)->field('code,value')->select();
            $clists = array();
            if($lists){
                foreach($lists as $v){
                    $clists[$v['code']] = $v['value'];
                }
                F('SysConfigs',$clists);
            }else{
                return false;
            }
        }
        return $clists;
    }
}