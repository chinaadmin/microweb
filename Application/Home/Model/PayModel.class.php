<?php
/**
 * 支付模型
 * @author cwh
 * @date 2015-05-28
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class PayModel extends HomebaseModel{

    protected $autoCheckFields = false;

    /**
     * 账户付款
     * @param string $order_id 订单id
     * @return \Common\Org\Util\Results
     */
    public function accountPay($order_id){
        $result = $this->result();
        $this->startTrans();
        $order_model = D('Home/order');
        $order_info = $order_model->where(['order_id'=>$order_id])->find();
        if($order_info['pay_type'] != 'ACCOUNT' || (intval($order_info['pay_time'])!=0)){
            $this->rollback();
            return $result->error('支付方式错误');
        }

        //使用余额
        $credits_model = D('User/Credits');
        $credits_model->setOperateType(4,'ACCOUNT');

        $sql = "LOCK TABLES jt_ WRITE";
        
        $credits_result = $credits_model->setCredits($order_info['uid'], $order_info['order_amount'], '支付订单'.$order_info['order_sn'], 1, 0);
        if (!$credits_result->isSuccess()) {
            $this->rollback();

            $code = $credits_result->getCode();
            switch($code){
                case 'CREDITS_INADEQUATE':
                    $credits_result->error('余额不足',$code);
                    break;
                case 'SET_CREDITS_FAIL':
                    $credits_result->error('扣除余额失败',$code);
                    break;
            }

            return $credits_result;
        }

        $pay_result = D('Home/Pay')->paying($order_id,false);
        if(!$pay_result->isSuccess()){
            $this->rollback();
            return $pay_result;
        }

        $this->commit();
		if($order_info['shipping_type'] == 0){
			$order_code = rand_string ( 6, 1 );
			$res = M("order") -> where(array('order_id'=>$order_info['order_id'])) -> save(array('order_code'=>$order_code));
			$phone = M("stores") -> where(array('stores_id'=>$order_info['stores_id'])) -> field('name,phone') -> find();
			
			// 发送短信
			$where = array(
					"uid"=>$order_info['uid']
			);
			$mobile = M("user")->where($where)->getField("mobile");
			$messageObj = new \Common\Org\Util\MobileMessage ();
			$arr = array(
				'trade_name' =>empty($phone['name'])?"系统:":$phone['name'],
				'code' =>$order_code,
				'stores_tel' =>empty($phone['phone'])?"4007777927":$phone['phone'],
			);

			$mobileResult = $messageObj->sendMessByTel($mobile,$arr,'pk_up_code');
		}
        return $this->result()->success('支付成功');
    }

    /**
     * 付款
     * @param string $order_id 订单id
     * @param bool $is_start_trans 是否开启事务
     * @return \Common\Org\Util\Results
     */
    public function paying($order_id,$is_start_trans = true){
        $result = $this->result();
        $order_model = D('Home/order');
        $order_info = $order_model->where(['order_id'=>$order_id])->find();
        $data = [
            'money_paid'=>$order_info['order_amount'],
            'pay_status'=>2,
            'pay_time'=>time()
        ];
        if($is_start_trans) {
            $this->startTrans();
        }
        $order_result = D('Home/order')->where(['order_id'=>$order_id])->data($data)->save();
        if($order_result === false){
            if($is_start_trans) {
                $this->rollback();
            }
            return $result->error('付款失败');
        }else{
            //订单提交操作记录
            if($order_model->orderAction($order_id,[ 'pay_status'=>2],'订单付款','您的订单已支付完成，请等待审核确认') === false){
                if($is_start_trans) {
                    $this->rollback();
                }
                return $result->error('付款失败');
            }

            //添加商品销售统计
            $order_goods_lists = M('OrderGoods')->where(['order_id'=>$order_id])->field(true)->select();
            foreach($order_goods_lists as $v) {
                M("GoodsStatistics")->where(['goods_id' => $v['goods_id']])->setInc('sales', $v['number']);
            }

            //用户付款后后台消息提醒
            $order_message = array(
                'order_sn' => $order_info['order_sn'],
                'order_id' => $order_info['order_id'],
                'order_type' => 0,
                'message_add_time' => time()
            );
            $order_message_result = M('OrderMessage')->add($order_message); //添加订单提醒消息

            //订单支付记录(余额支付的不记录)
            //避免重复扣款
            if($order_info['order_amount'] > 0 && $order_info['pay_type'] != 'ACCOUNT') {
                $credits_model = D('User/Credits');
                $credits_model->setOperateType(4, $order_info['pay_type']);
                $credits_result = $credits_model->setCredits($order_info['uid'], $order_info['order_amount'], '支付订单' . $order_info['order_sn'], 1, 0);
                if (!$credits_result->isSuccess()) {
                    if($is_start_trans) {
                        $this->rollback();
                    }
                    return $credits_result;
                }
            }

            //用户数据统计
            $analysis_data = [//付款金额
                'pay_count'=>['exp','pay_count + 1'],
                'consume_money'=>['exp','consume_money + '.$order_info['order_amount']],
            ];
            if(M('UserAnalysis')->where(['uid'=>$order_info['uid']])->data($analysis_data)->save() === false){
                if($is_start_trans) {
                    $this->rollback();
                }
                return $result->error('付款失败');
            }

            if($is_start_trans) {
                $this->commit();
            }
            return $result->success('付款成功');
        }
    }

}