<?php
/**
 * 广告模型
 * @author cwh
 * @date 2015-04-20
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class AdModel extends AdminbaseModel{

    protected $tableName = 'ad';

    //命名范围
    protected $_scope = [
        'default'=>[// 获取没有被删除状态
            'where'=>[
                'status'=>1,
                'end_time'=>['gt',NOW_TIME]
            ],
            'order'=>[
                'sort'=>'desc',
                'add_time'=>'desc'
            ]
        ]
    ];

    public $_auto = [
        ['start_time','tomktime',self::MODEL_BOTH,'function'],
        ['end_time','tomkendtime',self::MODEL_BOTH,'function'],
        ['update_time','time',self::MODEL_BOTH,'function'],
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    public function _after_insert($data,$options){
        //广告数加1
        $where = array();
        $where['adp_id'] = $data['adp_id'];
        D('Admin/AdPlace')->where($where)->setInc("num",1);
    }


    // 删除数据前的回调方法
    protected function _before_delete($options) {
        $resultSet  = $this->db->select($options);
        foreach($resultSet as $v){
            $where = array();
            $where['adp_id'] = $v['adp_id'];
            D('Admin/AdPlace')->where($where)->setDec("num",1);
        }
    }

    /**
     * 是否存在广告
     * @param int $adp_id 广告位id
     */
    public function isExistAd($adp_id){
        return $this->where(['adp_id'=>$adp_id])->count();
    }

    /**
     * 获取广告列表
     * @param int $adp_id 广告id
     * @return array
     */
    public function getLists($adp_id,$num=0){
        $adp_model = D('Admin/AdPlace');
        $adp_info = $adp_model->scope()->where(['adp_id'=>$adp_id])->field(true)->find();
        if(empty($adp_info)){
            return [];
        }
        return $this->getListsToInfo($adp_info,$num);
    }

    /**
     * 获取广告列表
     * @param array $adp_info 广告位信息
     * @return array
     */
    public function getListsToInfo(array $adp_info,$num=0){
        $ad_lists = $this->scope()->where(['adp_id'=>$adp_info['adp_id']])->order('sort desc')->limit($num)->select();
        $attr_ids = array_column($ad_lists,'photo');
        $image_size_str = imageSizeStr($adp_info['width'],$adp_info['height']);
        $attach = D('Upload/AttachMent')->getAttach($attr_ids,true,true,$image_size_str);
        $attr_ids = array_column($attach,$image_size_str,'att_id');
        return array_map(function($data) use($attr_ids){
            $data['photo'] = $attr_ids[$data['photo']];
            return $data;
        },$ad_lists);
    }

}