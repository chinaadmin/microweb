<?php
/**
 * 退款数据模型
 */
namespace Common\Org\ThinkPay;

class RefundVo {

    protected $_batch_no;
    protected $data = [];

    /**
     * 设置退款号
     * @return \Common\Org\ThinkPay\RefundVo
     */
    public function setBatchNo() {
        mt_srand((double) microtime() * 100000);
        $this->_batch_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $this;
    }

    /**
     * 获取退款号
     * @return string
     */
    public function getBatchNo() {
        return $this->_batch_no;
    }

    /**
     * 添加退款数据
     * @param array $refund_id 退款id
     * @return \Common\Org\ThinkPay\RefundVo
     */
    public function addData($refund_id){
        if(!is_array($refund_id)){
            $refund_id = (array)$refund_id;
        }
        $where = [
            'refund_id'=>['in',$refund_id]
        ];
        $refund_model = D('Admin/Refund');
        $refund_lists = $refund_model->field(true)->where($where)->select();
        $order_ids = [];
        foreach($refund_lists as $k=>$v){
            if($v['refund_status'] == 1){//待退款的
                $order_ids[] = $v['order_id'];
            }else{
                unset($refund_lists[$k]);
            }
        }
        $where = [
            'order_id'=>['in',$order_ids],
            'status'=>1
        ];
        $pay_log = M('PaymentLog')->field(true)->where($where)->select();
        $trade_no = array_column($pay_log,'trade_no','order_id');

        $data = [];
        foreach($refund_lists as $v){
            $data['refund_id'] = $v['refund_id'];
            $data['money'] = $v['refund_money'];
            $data['reasons'] = $refund_model->refund_reasons[$v['refund_reasons']];
            $data['trade_no'] = $trade_no[$v['order_id']];
            $this->data[] = $data;
        }
        return $this;
    }

    /**
     * 获取退款数据
     * @return string
     */
    public function getData(){
        return $this->data;
    }

    /**
     * 获取总数
     * @return int
     */
    public function getCount(){
        return count($this->data);
    }

    /**
     * 获取总金额
     * @return double
     */
    public function getAmount(){
        $amount = 0;
        array_map(function($info) use(&$amount){
            $amount += $info['money'];
        },$this->data);
        return $amount;
    }

    /**
     * 记录本地记录数据
     * @return mixed
     */
    public function record(){
        $batch_no = $this->getBatchNo();
        $result = M("PaymentRefundLog")->add([
            'batch_no'=>$batch_no,
            'batch_num' => $this->getCount(),
            'amount' => $this->getAmount(),
            'success_num'=>0,
            'status' => 0,
            'update_time' => time(),
            'add_time' => time()
        ]);

        if($result===false){
            return false;
        }

        //退款关联
        $all_data = [];
        $data = [
            'batch_no' => $batch_no
        ];
        foreach($this->data as $v){
            $data['refund_id'] = $v['refund_id'];
            $data['trade_no'] = $v['trade_no'];
            $all_data[] = $data;
        }
        $result = M("PaymentRefundX")->addAll($all_data);
        return $result===false?false:true;
    }

    /**
     * 获取数据库错误
     * @return string
     */
    public function getDbError(){
       return M("PaymentRefundLog")->getDbError();
    }

}