<?php
namespace Common\Org\ThinkPay\Driver;
use Common\Org\ThinkPay\PayVo;
use Common\Org\ThinkPay\RefundVo;
use Common\Org\ThinkPay\ThinkPay;

/**
 * 支付宝
 * Class Alipay
 */
class Alipay extends ThinkPay{

    /**
     *支付宝网关地址（新）
     */
    protected $gateway = 'https://mapi.alipay.com/gateway.do';
    /**
     * HTTPS形式消息验证地址
     */
    protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    /*protected $config = [
        'email' => '',
        'key' => '',
        'partner' => ''
    ];*/

    /**
     * 是否网银支付
     * @var bool
     */
    public $is_bank_pay = false;

    /**
     * 银行支付编码
     * @var string
     */
    public $bank_code = '';

    private $mode = self::MODE_FROM;

    /**
     * 接口名称
     * @var string
     */
    private $service = null;

    /**
     * 设置模式
     * @param $mode
     * @return $this
     */
    public function setMode($mode){
        $this->mode = $mode;
        return $this;
    }

    /**
     * 获取模式
     * @return int
     */
    public function getMode(){
        return $this->mode;
    }

    /**
     * 设置接口名称
     * @param string $service_type 接口方式
     * @return $this
     */
    public function setService($service_type = 'default'){
        switch($service_type){
            case 'mobile':
                $service = 'mobile.securitypay.pay';
                break;
            case 'default':
            default:
                $service = 'create_direct_pay_by_user';
                break;
        }
        $this->service = $service;
        return $this;
    }

    /**
     * 获取接口名称
     * @return string
     */
    public function getService(){
         if(is_null($this->service)){
             $this->setService();
         }
        return $this->service;
    }

    /**
     * 配置检查
     * @return boolean
     */
    public function check() {
        if (!$this->config['email'] || !$this->config['key'] || !$this->config['partner']) {
            E("支付宝设置有误！");
        }
        return true;
    }

    /**
     * 设置银行支付
     * @param string $code 银行编号
     * @return $this
     */
    public function setBankPay($code){
        $this->is_bank_pay = true;
        $this->bank_code = $code;
        return $this;
    }

    /**
     * 支付表单
     * @param PayVo $vo
     * @return mixed
     */
    public function payForm(PayVo $vo){
        $param = [
            'service' => $this->getService(),
            'payment_type' => '1',
            '_input_charset' => 'utf-8',
            //'seller_email' => $this->config['email'],
            'seller_id' => $this->config['partner'],
            'partner' => $this->config['partner'],
            'notify_url' => $this->config['notify_url'],
            //'return_url' => $this->config['return_url'],
            'out_trade_no' => $vo->getOrderNo(),
            'total_fee' => $vo->getFee()
            //"paymethod"	=> 'bankPay', 支付方式
            //"defaultbank"	=> $defaultbank, 支付银行
            //"show_url"	=> $show_url, 商品展示地址
            //"anti_phishing_key"	=> $anti_phishing_key, 防钓鱼时间戳
            //"exter_invoke_ip"	=> $exter_invoke_ip, 客户ip地址
        ];

        if($this->service != 'mobile.securitypay.pay') {//不是移动支付的
            $param['return_url'] = $this->config['return_url'];
        }

        $subject = $vo->getTitle();
        if(empty($subject)){
            $subject = $vo->getOrderNo();
        }
        $param['subject'] = $subject;

        $body = $vo->getBody();
        if(!empty($body)){
            $param['body'] = $body;
        }

        $this->bankPay($param);

        return $this->buildRequestForm($param);
    }

    /**
     * 银行支付
     * @param array $param 支付参数
     */
    public function bankPay(&$param){
        if($this->is_bank_pay) {
            $param['paymethod'] = 'bankPay';
            $param['defaultbank'] = $this->bank_code;
        }
    }

    /**
     * 退款表单
     * @param RefundVo $vo
     * @return mixed
     */
    public function refundForm(RefundVo $vo){
        $param = [
            'service' => 'refund_fastpay_by_platform_pwd',
            '_input_charset' => 'utf-8',
            'seller_email' => $this->config['email'],
            'partner' => $this->config['partner'],

            'notify_url' => $this->config['refund_notify_url'],
            'refund_date'=>date('Y-m-d H:i:s',time()),
            'batch_no' => $vo->getBatchNo(),
            'batch_num' => $vo->getCount(),
            'detail_data'=> $this->getBatchData($vo)
            /*"notify_url"	=> $notify_url,
            "refund_date"	=> $refund_date,
            "batch_no"	=> $batch_no,
            "batch_num"	=> $batch_num,
            "detail_data"	=> $detail_data,*/
        ];
        return $this->buildRequestForm($param);
    }

    /**
     * 获取退款数据
     * @param RefundVo $vo
     * @return mixed
     */
    public function getBatchData(RefundVo $vo){
        $data = $vo->getData();
        $batch_data = [];
        foreach($data as $v){
            $batch_data[] = $v['trade_no'].'^'.$v['money'].'^'.$v['reasons'];
        }
        return implode('#',$batch_data);
    }

    /**
     * 建立提交表单
     * @param array $param 参数
     * @return mixed
     */
    public function buildRequestForm($param){
        //对待签名参数数组排序
        $param = $this->argSort($param);

        //生成签名结果
        $param['sign'] = $this->buildRequestMysign($param);

        //签名类型
        $param['sign_type'] = $this->singType();

        switch($this->getMode()){
            case self::MODE_STRING:
                $param['sign'] = urlencode($param['sign']);
                return $this->createLinkstring($param);
            case self::MODE_PARAM:
                return $param;
            case self::MODE_FROM:
            default:
                return  $this->_buildForm($param, $this->gateway, 'get');
        }
    }

    /**
     * 支付通知验证
     * @param array $notify 通知信息
     * @return bool 验证结果
     */
    public function verifyNotify($notify){
        //生成签名结果
        $isSign = $this->getSignVeryfy($notify, $notify["sign"]);
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'true';
        if (!empty($notify["notify_id"])) {
            $responseTxt = $this->getResponse($notify["notify_id"]);
        }
        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            $this->setInfo($notify);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置验证通过后获取订单信息
     * @param array $notify 通知信息
     * @return mixed|void
     */
    protected function setInfo($notify) {
        $info = [];
        //支付状态
        $info['notify_id'] = $notify['notify_id'];
        $info['details'] = json_encode($notify);
        switch($this->getProcess()){
            case self::PROCESS_PAY:
                $info['status'] = ($notify['trade_status'] == 'TRADE_FINISHED' || $notify['trade_status'] == 'TRADE_SUCCESS') ? true : false;
                $info['money'] = $notify['total_fee'];
                $info['out_trade_no'] = $notify['out_trade_no'];
                $info['trade_no'] = $notify['trade_no'];
                $info['seller'] = $notify['seller_email'];
                $info['buyer'] = $notify['buyer_email'];
                break;
            case self::PROCESS_REFUND:
                $info['batch_no'] = $notify['batch_no'];
                $info['result_data'] = $this->getResultDetails($notify['result_details']);
                $info['success_num'] = $notify['success_num'];
                break;
        }
        $this->info = $info;
    }

    /**
     * 获取退款明细
     * @param $result_details  2010031906272929^80^SUCCESS$jax_chuanhang@alipay.com^2088101003147483^0.01^SUCCESS
     * @return array
     */
    public function getResultDetails($result_details){
        $result_details = explode('#',$result_details);
        $data = [];
        foreach($result_details as $v){
            $v = explode('$',$v);
            $v = explode('^',$v[0]);
            $info = [];
            $info['trade_no'] = $v[0];
            $info['status'] = $v[2]=='SUCCESS'? true : false;
            $data[] = $info;
        }
        return $data;
    }

    /**
     * 生成签名结果
     * @param array $para_sort 已排序要签名的数组
     * @return string 签名结果字符串
     */
    function buildRequestMysign(array $para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //签名字符串
        return $this->sign($prestr);
    }

    /**
     * 获取返回时的签名验证结果
     * @param array $param 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return bool 签名验证结果
     */
    protected function getSignVeryfy(array $param, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $param_filter = $this->paraFilter($param);

        //对待签名参数数组排序
        $para_sort = $this->argSort($param_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        //生成签名结果
        return $this->veryfy($prestr,$sign,$param['sign_type']);
    }

    /**
     * 签名类型
     * @return string
     */
    function singType(){
        return strtoupper(trim($this->config['sign_type']));
    }

    /**
     * 获取签名class
     * @param string $sign_type 签名类型
     */
    function getSignClass($sign_type = null){
        if(is_null($sign_type)){
            $sign_type = $this->singType();
        }
        $name = 'Alipay'.ucfirst(strtolower($sign_type));
        $name =  'Common\\Org\\ThinkPay\\Driver\\Alipay\\' . $name;
        if (class_exists($name)) {
            return new $name();
        } else {
            E(L('_CLASS_NOT_EXIST_') . ':' . $name);
        }
    }

    /**
     * 签名字符串
     * @param string $prestr 需要签名的字符串
     * @param string $key 私钥
     * @return string 签名结果
     */
    function sign($prestr, $key = null) {
        $mysign = "";
        $sign_class = $this->getSignClass();
        switch ($this->singType()) {
            case "MD5" :
                if(is_null($key)){
                    $key = $this->config['key'];
                }
                $mysign = $sign_class->sign($prestr,$key);
                break;
            case "RSA" :
                if(is_null($key)) {
                    $key = $this->config['private_key_path'];
                }
                $mysign = $sign_class->sign($prestr,$key);
                break;
            default :
                $mysign = "";
        }
        return $mysign;
    }

    /**
     * 验证签名
     * @param string $prestr 需要签名的字符串
     * @param string sign 签名结果
     * @param string sign_type 签名类型
     * @param string $key 私钥
     * @return bool 签名结果
     */
    function veryfy($prestr,$sign,$sign_type = null,$key = null){
        $mysign = false;
        if(empty($sign_type)){
            $sign_type = $this->singType();
        }

        $sign_class = $this->getSignClass($sign_type);
        switch ($sign_type) {
            case "MD5" :
                if(is_null($key)){
                    $key = $this->config['key'];
                }
                $mysign = $sign_class->verify($prestr,$sign,$key);
                break;
            case "RSA" :
                if(is_null($key)) {
                    $key = $this->config['ali_public_key_path'];
                }
                $mysign = $sign_class->verify($prestr,$sign,$key);
                break;
            default :
                $mysign = false;
        }
        return $mysign;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    function createLinkstring(array $para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            if($this->service != 'mobile.securitypay.pay') {
                $arg .= $key . "=" . $val . "&";
            }else{
                $arg .= $key.'='.'"'.$val.'"&';
            }
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        //$arg = substr($arg, 0, -1);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param array $para 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter(array $para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else
                $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param array $para 排序前的数组
     * @return array 排序后的数组
     */
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id) {
        $transport = strtolower(trim($this->config['transport']));
        $partner = trim($this->config['partner']);
        $veryfy_url = '';
        if($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->fsockOpen($veryfy_url);
        return $responseTxt;
    }

    /**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
     * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * return 时间戳字符串
     */
    /*function query_timestamp() {
        $url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->alipay_config['partner']))."&_input_charset=".trim(strtolower($this->alipay_config['input_charset']));
        $encrypt_key = "";

        $doc = new DOMDocument();
        $doc->load($url);
        $itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
        $encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

        return $encrypt_key;
    }*/
}