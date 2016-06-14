<?php
namespace Home\Model;
use Common\Model\HomebaseModel;
use User\Org\Util\User;

class RechargeModel extends HomebaseModel{

    protected $tableName = 'account_recharge';

    /**
     * 用户ID
     * @var int
     */
    public $user_id = 0;

    /**
     * 初始化
     * @see Model::_initialize()
     */
    public function _initialize(){
        parent::_initialize();
        $user = User::getInstance ();
        $user_id = $user->isLogin();
        $this->user_id = empty($user_id)?0:$user_id;
    }

    /**
     * 生成订单id
     * @return string
     */
    public function orderid() {
        return uniqueId();
    }

    /**
     * 生成订单号
     * @return string
     */
    public function ordersn() {
        mt_srand((double) microtime() * 1000000);
        return 'B'.date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 添加记录
     * @param int $money 金额
     * @param null $uid 用户id
     * @param int $source 来源
     * @return mixed
     */
    public function addRecord($money,$uid = null,$source = 3){
        $data = [
            'order_id'=>$this->orderid(),
            'order_sn'=>$this->ordersn(),
            'money'=>$money,
            'status'=>0,
            'source'=>$source,
            'add_time'=>time()
        ];
        //用户id
        if(empty($uid)){
            $data['uid'] = $this->user_id;
        }else{
            $data['uid'] = $uid;
        }
        $result =  $this->data($data)->add();
        if($result===false){
            return $this->result()->error('充值失败');
        }
        return $this->result()->content($data)->success();
    }

    /**
     * 充值
     * @param string $order_id 订单id
     * @return \Common\Org\Util\Results
     */
    public function paying($order_id){
        $result = $this->result();
        $recharge_info = $this->where(['order_id'=>$order_id])->field(true)->find();
        $this->startTrans();
        $uid = $recharge_info['uid'];

        //余额增加
        $credits_model = D('User/Credits');
        $credits_model->setOperateType(1,'ACCOUNT');
        $credits_result = $credits_model->setCredits($uid, $recharge_info['money'], '充值', 0, 0);
        if (!$credits_result->isSuccess()) {
            $this->rollback();
            return $credits_result;
        }

        //充值订单号设置为已支付
        $recharge_result = $this->where(['order_id'=>$order_id])->data(['status'=>1])->save();
        if($recharge_result === false){
            $this->rollback();
            return $result->error('充值失败');
        }

        $user_analysis_model = M('UserAnalysis');

        //获取促销活动
        $promotions_lists = D('User/Promotions')->getNormalList(2);
        $coupon_model = D('User/Coupon');

        foreach($promotions_lists as $promotions_v) {

            //来源
            if(!empty($promotions_v['source'])){
                if($recharge_info['source'] != $promotions_v['source']){
                    continue ;
                }
            }

            //对象
            if(!empty($promotions_v['object'])) {
                $object = json_decode($promotions_v['object'], true);
                if (!empty($object)) {
                    $grade_id = D('User/User')->where(['uid' => $uid])->getField('grade_id');
                    $group_id = M('UserGroupX')->where(['uid' => $uid])->getField('group_id', true);
                    $has_use = false;
                    foreach ($object as $v) {
                        if ($v['name'] == 'grade') {//用户等级
                            if ($grade_id == $v['id']) {
                                $has_use = true;
                                break;
                            }
                        }

                        if ($v['name'] == 'group') {//用户组
                            if (in_array($v['id'], $group_id)) {
                                $has_use = true;
                                break;
                            }
                        }
                    }

                    if (!$has_use) {
                        continue;
                    }
                }
            }

            $param_arr = json_decode($promotions_v['param'], true)[0];

            //需要满足首次充值
            if ($param_arr['condition']['first_single']['is_sel']) {
                $is_first_single = $user_analysis_model->where(['uid' => $uid])->getField('recharge_count');
                if ($is_first_single > 0) {//已经完成首次充值的返回
                    continue;
                }
            }

            $is_meets = false;
            //满足多少金额
            if ($param_arr['condition']['money']['is_sel']) {
                if ($recharge_info['money'] >= $param_arr['condition']['money']['val']) {
                    $is_meets = true;
                }
            }

            if ($is_meets) {
                $promotions_x_data = [
                    'promotions_id' => $promotions_v['id'],
                    'type' => $promotions_v['type'],
                    'status' => 1,
                    'add_time' => NOW_TIME
                ];
                if ($param_arr['content']['coupon']['is_sel']) {
                    $promotions_x_data['associate_id'] = $recharge_info['order_id'];
                    $promotions_x_data['uid'] = $recharge_info['uid'];
                    $promotions_x_result = M('PromotionsX')->data($promotions_x_data)->add();

                    //发放优惠劵
                    $coupon_val = $param_arr['content']['coupon']['val'];
                    $coupon_arr = explode(',', $coupon_val);
                    foreach ($coupon_arr as $coupon) {
                        $coupon_model->issuing($coupon, $recharge_info['uid'], 1,$promotions_x_result);
                    }
                }
            }
        }

        //用户数据统计
        $analysis_data = [
            'recharge_count'=>['exp','recharge_count + 1'],
            'recharge_money'=>['exp','recharge_money + '.$recharge_info['money']],
        ];
        if($user_analysis_model->where(['uid'=>$uid])->data($analysis_data)->save() === false){
            $this->rollback();
            return $result->error('充值失败');
        }
        $this->commit();
        //用户等级
        D("User/UserGrade")->userGrade($uid,2);
        return $result->success('充值成功');
    }

}