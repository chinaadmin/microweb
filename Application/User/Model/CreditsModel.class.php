<?php
/**
 * 用户积分模型类
 * @author cwh
 * @date 2015-05-05
 */
namespace User\Model;
use Common\Model\BaseModel;
use User\Org\Util\User;

class CreditsModel extends BaseModel{

    protected $tableName = 'account_credits';

    private $operate_type_param = [
        'operate'=>0,
        'pay'=>'ACCOUNT'
    ];

    private $type = [
        0=>[
            'name'=>'金额',//名称
            'unit'=>'元',//单位
        ],
        1=>[
            'name'=>'积分',
            'unit'=>'分'
        ],
        2=>[
            'name'=>'不可提现金额',
            'unit'=>'元'
        ],
		3=>[
            'name'=>'成长值',
            'unit'=>'点'
        ]
    ];

    /**
     * 获取积分类型
     * @param int|null $type_id 类型id
     * @return array
     */
    public function getType($type_id = null){
        if(is_null($type_id)){
            return $this->type;
        }
        return $this->type[$type_id];
    }

    private $operate_type = [
        0=>'其他类型',
        1=>'充值',
        2=>'提现',
        3=>'系统调整',
        4=>'订单支付',
        5=>'退款',
        6=>'活动赠送',
        7=>'积分抵扣',
        8=>'购物积分',
        9=>'活动抽奖',
        10=>'邀请好友下单积分',
    ];

    /**
     * 获取操作类型
     * @param int|null $type_id 类型id
     * @return array
     */
    public function getOperateType($type_id = null){
        if(is_null($type_id)){
            return $this->operate_type;
        }
        return $this->operate_type[$type_id];
    }

    /**
     * 初始化积分账户
     * @param string $uid 用户id
     * @return \Common\Org\Util\Results
     */
    public function initAccountCredits($uid){
        $all_data = [];
        $types = $this->getType();
        $data = [
            'uid'=>$uid,
            'credits'=>0
        ];
        foreach($types as $key=>$val){
            $data['type'] = $key;
            $all_data[] = $data;
        }
        return $this->addAll($all_data)===false?$this->result()->error('初始化积分账户失败'):$this->result()->success();
    }

    /**
     * 注销积分账户
     * @param string $uid 用户id
     * @return \Common\Org\Util\Results
     */
    public function destroyAccountCredits($uid){
        return $this->where(['uid'=>$uid])->delete()===false?$this->result()->error('注销积分账户失败'):$this->result()->success();
    }

    /**
     * 获取账户积分
     * @param string $uid 用户id
     * @param null $type 积分类型
     * @return array|double
     */
    public function getCredits($uid,$type = null){
        $where = [
            'uid'=>$uid
        ];
        if(!is_null($type)){
            $where['type'] = $type;
        }
        $credits = $this->where($where)->field(['type','credits'])->select();
        if(count($credits)>1){
            return array_column($credits,'credits','type');
        }
        return current($credits)['credits'];
    }

    /**
     * 操作类型
     * @param int $operate_type 类型:0为其他类型,1为充值,2为提现,3为管理员调节,4为订单支付,5为退款,6为活动中奖
     * @param string $pay_type 支付方式
     * @return $this
     */
    public function setOperateType($operate_type,$pay_type='ACCOUNT'){
        $this->operate_type_param = [
            'operate'=>$operate_type,
            'pay'=>$pay_type
        ];
        return $this;
    }

    /**
     * 改变会员金额
     * @param int $uid 会员ID
     * @param double $credits 积分
     * @param string $desc 备注
     * @param int $is_change 增减:0为增,1为减
     * @param int $credits_type 积分类型:0为金额,1为积分,2为不可提现金额
     * @param string $editor 操作人
     * @return int -1为金额不足,0操作失败,1操作成功
     */
    public function setCredits($uid,$credits,$desc='',$is_change=0,$credits_type=0,$editor='系统'){
        $result = $this->result();
        if(empty($credits)){
            return $result->success();
        }

        $cur_credits = $this->getCredits($uid,$credits_type);

        $where = [
            'uid'=>$uid,
            'type'=>$credits_type
        ];

        $balance = 0;//余额
        $credits_result = true;
        $operate_type = $this->operate_type_param['operate'];
        switch($operate_type){
            case 4://订单支付
            case 5://退款
                $balance = $cur_credits;
                break;
            case 3://管理员调节
                $user = User::getInstance ();
                $user_info = $user->getUser();
                $editor =  $user_info['username'].'::'.$user_info['uid'];
                break;
            default://账号支付的
                break;
        }

        if($this->operate_type_param['pay'] == 'ACCOUNT') {
            if ($is_change == 1) {
                if ($cur_credits < $credits) {
                    return $result->set('CREDITS_INADEQUATE');
                }
                $credits_result = $this->where($where)->setDec('credits', $credits);
                $balance = $cur_credits - $credits;
            } else {
                $credits_result = $this->where($where)->setInc('credits', $credits);
                $balance = $cur_credits + $credits;
            }
        }

        $log_result = true;
        if($credits_result!=false) {
            $log_result = M('AccountLog')->add([
                'uid' => $uid,
                'credits' => $is_change == 1 ? -$credits : $credits,
                'credits_type' => $credits_type,
                'balance' => $balance,
                'remark' => $desc,
                'type' => $operate_type,
                'pay_type' => $this->operate_type_param['pay'],
                'add_time' => NOW_TIME,
                'editor' => $editor
            ]);
        }

        if($credits_result==false||$log_result==false){
            return $result->set('SET_CREDITS_FAIL');
        }else{
            return $result->success();
        }
    }

    /**
     * 获取记录试图
     * @param int|array $queryView 试图
     * @return array
     */
    public function getLogView($queryView = null){
        if(is_null($queryView)) {
            $queryView = [
                'user' => [
                    'username',
                    'aliasname'
                ],
                'account_log' => [
                    'log_id',
                    'uid',
                    'credits',
                    'credits_type',
                    'balance',
                    'type',
                    'remark',
                    'editor',
                    'add_time',
                    '_on' => 'user.uid = account_log.uid',
                ]
            ];
        }
        return $this->dynamicView($queryView);
    }

}