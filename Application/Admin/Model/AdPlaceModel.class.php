<?php
/**
 * 广告位模型
 * @author cwh
 * @date 2015-04-20
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class AdPlaceModel extends AdminbaseModel{

    protected $tableName = 'ad_place';

    //命名范围
    protected $_scope = [
        'default'=>[// 获取没有被删除状态
            'where'=>[
                'status'=>1
            ]
        ]
    ];

    public $_auto = [
        ['update_time','time',self::MODEL_BOTH,'function'],
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    /**
     * 类型
     * @var array
     */
    private $type = [
        1=>'图片广告',
        2=>'轮播广告',
        3=>'商品推荐'
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