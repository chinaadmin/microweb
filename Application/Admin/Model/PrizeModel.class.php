<?php
/**
 * 奖品管理模型类.
 *
 * @author : liuwh
 * @DateTime : 2016-03-04
 */

namespace Admin\Model;

use Common\Model\AdminbaseModel;

class PrizeModel extends AdminbaseModel
{
    protected $tableName = 'award_subject';

    protected $_auto = [
        ['as_start_time', 'strtotime', self::MODEL_BOTH, 'function'],
        ['as_end_time', 'strtotime', self::MODEL_BOTH, 'function'],
        ['as_add_time', 'time', self::MODEL_INSERT, 'function'],
    ];

    protected $_validate = [
        ['as_name', 'require', '奖品名称不为空'],
        ['as_start_time', 'require', 'AWARD_IIIGAL_START_TIME::奖品有效时间开始不为空'],
        ['as_end_time', 'require', 'AWARD_IIIGAL_END_TIME::奖品有效时间结束开始不为空'],
        ['as_type', [1,2,3,4], 'AWARD_IIIGAL_TYPE::奖品类型不为空' , 1,  'in'],
        //['as_hongbao_amount' , 'validateAmount' , '奖品设置错误' , 1 ,'callback']
    ];

    protected $_scope = [
        'normal' => [
            'where' => ['status' => '1'],
        ],
    ];

    //商品类型映射
    public $typeHash = [
        '1' => ['name' => '赠品'],
        '2' => ['name' => '优惠券'],
        '3' => ['name' => '红包', 'unit' => '元'],
        '4' => ['name' => '积分', 'unit' => '分'],
    ];

    /**
     * 验证奖品有效时间.
     *
     * @param string $startTime [description]
     * @param string $endTime   [description]
     *
     * @return int 0未开始,-1已经失效,1为有效时间段
     */
    public function vaildAwardTime($startTime, $endTime)
    {
        if ($endTime < NOW_TIME) {
            return -1;
        } elseif ($endTime < NOW_TIME) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * 验证奖品的设置值是否合法
     * @param  number $amount [description]
     * @return boolean         [description]
     */
    public function validateAmount($amount){
        if(empty($amount)){
            return true;
        }
        return is_numeric($amount) && $amount > 0 ? true :false;
    }

    /**
     * 获取商品分类标签.
     *
     * @param int    $typeId 商品分类id
     * @param string $key    要获取的标签名称
     *
     * @return string [description]
     */
    public function getTypeName($typeId, $key = 'name')
    {
        return isset($this->typeHash[$typeId]) ? $this->typeHash[$typeId][$key] : 'UNKNOW_TYPE_NAME';
    }

    public function _before_delete($options)
    {
        $prizeId = $options['where']['as_id'];
    }


    /**
     * 领取奖品
     * @param string $uid 用户id
     * @param int $apdid 抽奖方案详情表id
     */
    public function receive($uid, $apdid){
        $pid = M('AwardPlanDetail')->where(['apd_id' => $apdid])->getField('fk_as_id'); //奖品id
        $prize = M('AwardSubject')->field('as_type, as_hongbao_amount')->find($pid); // 获取奖品信息
        $credits_model = D('User/Credits');
        switch ($prize['as_type']) {
            case 3:
                $credits_model->setOperateType(6, 'ACCOUNT');
                $credits_result = $credits_model->setCredits($uid, $prize['as_hongbao_amount'], '活动中奖获赠的红包', 0, 0, '系统');
                if (!$credits_result->isSuccess()) {
                    $this->rollback();
                    return 0;
                }
                break;
            case 4:
                $credits_model->setOperateType(6, 'ACCOUNT');
                $credits_result = $credits_model->setCredits($uid, $prize['as_hongbao_amount'], '活动中奖获赠的积分', 0, 1, '系统');
                if (!$credits_result->isSuccess()) {
                    $this->rollback();
                    return 0;
                }
                break;
                default:
                    return 0;
        }
        $result = M('AwardRecord')->where(['ar_uid' => $uid, 'fk_apd_id' => $apdid])->setField(['ar_is_reveive' => 1, 'ar_reveive_time' => time()]);
        if($result){
            return 1;
        }else{
            return 0;
        }
    }

	/**
     * 获取优惠券发放数量
     * @param string $as_id 奖品id
     * @param int $as_type 奖品类型
     */
    public function getCouponCount($as_id,$as_type){
		return M('Award_subject')->join('__COUPON_CODE__ ON __AWARD_SUBJECT__.as_coupon_id=__COUPON_CODE__.coupon_id')->where(array('as_id'=>$as_id,'as_type'=>$as_type))->count();
    }

    /**
     * 获取奖红包发放数量
     * @param string $as_id 奖品id
     * @param int $as_type 奖品类型
     */
    public function getHongCount($as_id,$as_type){
		return M('Award_subject')->join('JOIN __AWARD_RECORD__ ON __AWARD_SUBJECT__.as_id=__AWARD_RECORD__.fk_as_id')->where(array('as_id'=>$as_id,'as_type'=>$as_type))->count();
    }

}
