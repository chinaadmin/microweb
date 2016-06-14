<?php
/**
 * 支付方式模型
 * @author cwh
 * @date 2015-05-26
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class PaymentModel extends AdminbaseModel{

    protected $tableName = 'payment';

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>['status'=>1],
        ],
        'default'=>[

        ]
    ];

    /**
     * 类型
     * @var array
     */
    private $type = [
        0=>'支付平台',
        1=>'网银支付'
    ];

    /**
     * 获取类别
     * @param int|null $type 类型
     * @return string
     */
    public function getType($type = null){
        if(is_null($type)){
            return $this->type;
        }
        return $this->type[$type];
    }

    /**
     * 获取支付方式列表
     * @return mixed
     */
    public function getLists(){
        $lists = $this->scope('normal')->field('id,name,photo,code,type,remark')->select();
        $attr_ids = array_column($lists,'photo');
        $attach = D('Upload/AttachMent')->getAttach($attr_ids,true);
        $attr_ids = array_column($attach,'path','att_id');
        return array_map(function($data) use($attr_ids){
            $data['photo'] = $attr_ids[$data['photo']];
            return $data;
        },$lists);
    }
    /**
     * 通过code获取支付方式
     * @param unknown $codes
     * @param string $field
     */
    public function getPayType($codes,$field=true){
    	$where = array(
    			"code"=>array('in',(array)$codes)
    	);
    	return $this->field($field)->where($where)->select();
    }
    
    /**
     * 获取支付方式
     */
    public function getPays($field=true){
    	return $this->field($field)->select();
    }

}