<?php
/**
 * 提货模型
 * @author cwh
 * @date 2015-05-27
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class DeliveryModel extends HomebaseModel{

    protected $tableName = 'user_delivery';

    public $_validate = [
        ['name','require','收货人姓名不能为空'],
        ['stores_id','require','门店不能为空'],
        ['mobile','require','手机号码不能为空'],
    ];
    public $_auto = [
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    /**
     * 获取提货列表
     * @param string $uid 用户id
     * @return mixed
     */
    public function getLists($uid){
        $where = [
            'uid'=>$uid
        ];
        $data = $this->getView()->where($where)->select();
        return $data;
    }

    /**
     * 提货试图
     */
    public function getView(){
        $viewFields = array (
            'user_delivery' => array (
                'delivery_id',
                'uid',
                'stores_id',
                'name'=>'username',
                'mobile',
                'add_time',
                '_type' => 'LEFT'
            ),
            'stores' => array (
                "name",
                "provice",
                "city",
                "county",
                "localtion",
                "address",
                '_on' => 'user_delivery.stores_id=stores.stores_id'
            )
        );
        return $this->dynamicView($viewFields);
    }

}