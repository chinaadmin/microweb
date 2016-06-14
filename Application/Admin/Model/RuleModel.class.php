<?php
/**
 * 规则模型类
 * @author cwh
 * @date 2015-07-11
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class RuleModel extends AdminbaseModel{

    protected $tableName = 'rule';

    public $_validate = [
        [
            'name',
            'require',
            '规则名称不能为空'
        ],
        [
            'code',
            'require',
            '规则编号不能为空'
        ],
        [
            'code',
            '',
            '规则编号已经存在',
            self::EXISTS_VALIDATE,
            'unique'
        ]
    ];

    /**
     * 类型
     * @var array
     */
    private $type = [
        1=>'普通认证',
        2=>'表达式认证',
        3=>'模型认证'
    ];

    /**
     * 获取菜单类型
     * @param int|null $type_id 类型id
     * @return array
     */
    public function getType($type_id = null){
        if(is_null($type_id)){
            return $this->type;
        }
        return $this->type[$type_id];
    }

}