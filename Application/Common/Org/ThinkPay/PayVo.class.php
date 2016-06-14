<?php
/**
 * 订单数据模型
 */
namespace Common\Org\ThinkPay;

class PayVo {

    protected $_order_id;
    protected $_order_no;
    protected $_order_type = 0;//类型：0为订单，1为充值
    protected $_fee;
    protected $_title;
    protected $_body;
    protected $_callback = '';//回调地址


    /**
     * 设置订单id
     * @param string $order_id
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setOrderId($order_id) {
        $this->_order_id = $order_id;
        return $this;
    }

    /**
     * 设置订单类型
     * @param string $order_type
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setOrderType($order_type) {
        $this->_order_type = $order_type;
        return $this;
    }

    /**
     * 设置订单号
     * @param string $order_no
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setOrderNo($order_no) {
        $this->_order_no = $order_no;
        return $this;
    }

    /**
     * 设置商品价格
     * @param int $fee
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setFee($fee) {
        $this->_fee = $fee;
        return $this;
    }

    /**
     * 设置商品名称
     * @param string $title
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setTitle($title) {
        $this->_title = $title;
        return $this;
    }

    /**
     * 设置商品描述
     * @param string $body
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setBody($body) {
        $this->_body = $body;
        return $this;
    }

    /**
     * 设置回调地址
     * @param string $callback
     * @return \Common\Org\ThinkPay\PayVo
     */
    public function setCallback($callback) {
        $this->_callback = $callback;
        return $this;
    }

    /**
     * 生成订单号
     */
    public function createOrderNo() {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        return $year_code[intval(date('Y')) - 2010] .
        strtoupper(dechex(date('m'))) . date('d') .
        substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('d', rand(0, 99));
    }

    /**
     * 获取订单id
     * @return string
     */
    public function getOrderId() {
        return $this->_order_id;
    }

    /**
     * 获取订单号
     * @return string
     */
    public function getOrderNo() {
        return $this->_order_no;
    }

    /**
     * 获取订单类型
     * @return string
     */
    public function getOrderType() {
        return $this->_order_type;
    }

    /**
     * 获取商品价格
     * @return int
     */
    public function getFee() {
        return $this->_fee;
    }

    /**
     * 获取商品名称
     * @return string
     */
    public function getTitle() {
        return $this->_title;
    }

    /**
     * 获取商品描述
     * @return string
     */
    public function getBody() {
        return $this->_body;
    }

    /**
     * 获取回调地址
     * @return string
     */
    public function getCallback() {
        return $this->_callback;
    }

    /**
     * 记录本地记录数据
     * @return mixed
     */
    public function record(){
        return M("PaymentLog")->add([
            'order_id'=>$this->getOrderId(),
            'order_sn' => $this->getOrderNo(),
            'order_amount' => $this->getFee(),
            'order_type'=>$this->getOrderType(),
            'status' => 0,
            'callback'=>$this->getCallback(),
            'update_time' => time(),
            'add_time' => time()
        ]);
    }

    /**
     * 获取数据库错误
     * @return string
     */
    public function getDbError(){
       return M("PaymentLog")->getDbError();
    }

}