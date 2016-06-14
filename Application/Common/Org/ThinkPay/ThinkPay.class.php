<?php
namespace Common\Org\ThinkPay;
/**
 * 支付
 * Class ThinkPay
 * @package Common\Org\ThinkPay
 */
abstract class ThinkPay{

    /**
     * 流程--支付
     */
    const PROCESS_PAY = 1;
    /**
     * 流程--退款
     */
    const PROCESS_REFUND = 2;

    /**
     * 模式--表单
     */
    const MODE_FROM = 1;
    /**
     * 模式--参数
     */
    const MODE_PARAM = 2;

    /**
     * 模式--字符串
     */
    const MODE_STRING = 3;

    /**
     * 配置参数
     * @var type
     */
    protected  $config;

    /**
     * 调用接口类型
     * @var string
     */
    private $type = '';

    /**
     * 验证通过后获取订单信息
     * @var
     */
    protected $info;

    /**
     * 流程
     * @var string
     */
    private $process = self::PROCESS_PAY;

    /**
     * 构造方法，配置应用信息
     */
    public function __construct(){
        //设置支付类型
        $class = get_class($this);
        $type = strtolower(substr($class, 0, strlen($class)));
        if ( $pos = strrpos($type,'\\') ) {//有命名空间
            $this->type = substr($type,$pos+1);
        }else{
            $this->type = $type;
        }
        $this->config();
    }

    /**
     * 设置流程
     * @param int $process 流程：1为支付，2为退款
     * @return $this
     */
    public function setProcess($process){
        $this->process = $process;
        return $this;
    }

    /**
     * 获取流程
     * @return string
     */
    public function getProcess(){
        return $this->process;
    }

    /**
     * 获取验证通过后获取订单信息
     * @return type
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * 取得Pay实例
     * @param $type 类型
     * @return mixed 返回Pay
     */
    public static function getInstance($type) {
        $name = ucfirst(strtolower($type));
        //require_once "Driver/{$name}.class.php";
        $name = strpos($name, '\\') ? $name : 'Common\\Org\\ThinkPay\\Driver\\' . ucfirst(strtolower($name));
        if (class_exists($name)) {
            return new $name();
        } else {
            E(L('_CLASS_NOT_EXIST_') . ':' . $name);
        }
    }

    /**
     * 初始化配置
     */
    private function config(){
        $common_config = C("THINK_PAY.common");
        $common_config = empty($common_config)?[]:$common_config;
        $type_config = C("THINK_PAY.{$this->type}");
        $type_config = empty($type_config)?[]:$type_config;
        $this->config = array_merge($common_config,$type_config);
        if(empty($this->config)){
            E('请配置您的支付参数');
        }
        $this->config['return_url'] .= $this->type;
        $this->config['notify_url'] .= $this->type;
        $this->config['refund_notify_url'] .= $this->type;
    }

    /**
     * 配置检查
     * @return boolean
     */
    public function check() {
        return true;
    }

    /**
     * 提交
     * @param PayVo|RefundVo $vo
     */
    public function submit($vo){
        $this->check();

        //生成本地记录数据
        $check = $vo->record();

        if ($check !== false) {
            switch($this->getProcess()){
                case self::PROCESS_PAY:
                    return $this->payForm($vo);
                case self::PROCESS_REFUND:
                    return $this->refundForm($vo);
            }
        } else {
            E($vo->getDbError());
        }
    }

    /**
     * 异步通知验证成功返回信息
     */
    public function notifySuccess() {
        echo "success";
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param array $params 参数
     * @param string $gateway 请求地址
     * @param string $method 请求方式
     * @param string $charset 编码
     * @return string
     */
    protected function _buildForm($params, $gateway, $method = 'post', $charset = 'utf-8') {
        /*header("Content-type:text/html;charset={$charset}");
        $sHtml = "<form id='paysubmit' name='paysubmit' action='{$gateway}' method='{$method}'>";

        foreach ($params as $k => $v) {
            $sHtml.= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\" />\n";
        }

        $sHtml = $sHtml . "</form>Loading......";

        $sHtml = $sHtml . "<script>document.forms['paysubmit'].submit();</script>";
        return $sHtml;*/
        $view = new \Think\View();
        $view->assign('gateway',$gateway);
        $view->assign('params',$params);
        $view->assign('method',$method);
        return $view->theme('default')->fetch('Common@ThinkPay/form');
    }

    final protected function fsockOpen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE', $allowcurl = TRUE, $position = 0, $files = array()) {
        $return = '';
        $matches = parse_url($url);
        $scheme = $matches['scheme'];
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : ($scheme == 'http' ? '80' : '');
        $boundary = $encodetype == 'URLENCODE' ? '' : random(40);

        if ($post) {
            if (!is_array($post)) {
                parse_str($post, $post);
            }
            $this->formatPostkey($post, $postnew);
            $post = $postnew;
        }
        if (function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
            $ch = curl_init();
            $httpheader = array();
            if ($ip) {
                $httpheader[] = "Host: " . $host;
            }
            if ($httpheader) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
            }
            curl_setopt($ch, CURLOPT_URL, $scheme . '://' . ($ip ? $ip : $host) . ($port ? ':' . $port : '') . $path);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            if ($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
                if ($encodetype == 'URLENCODE') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                } else {
                    foreach ($post as $k => $v) {
                        if (isset($files[$k])) {
                            $post[$k] = '@' . $files[$k];
                        }
                    }
                    foreach ($files as $k => $file) {
                        if (!isset($post[$k]) && file_exists($file)) {
                            $post[$k] = '@' . $file;
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                }
            }
            if ($cookie) {
                curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $data = curl_exec($ch);
            $status = curl_getinfo($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            if ($errno || $status['http_code'] != 200) {
                return;
            } else {
                $GLOBALS['filesockheader'] = substr($data, 0, $status['header_size']);
                $data = substr($data, $status['header_size']);
                return !$limit ? $data : substr($data, 0, $limit);
            }
        }

        if ($post) {
            if ($encodetype == 'URLENCODE') {
                $data = http_build_query($post);
            } else {
                $data = '';
                foreach ($post as $k => $v) {
                    $data .= "--$boundary\r\n";
                    $data .= 'Content-Disposition: form-data; name="' . $k . '"' . (isset($files[$k]) ? '; filename="' . basename($files[$k]) . '"; Content-Type: application/octet-stream' : '') . "\r\n\r\n";
                    $data .= $v . "\r\n";
                }
                foreach ($files as $k => $file) {
                    if (!isset($post[$k]) && file_exists($file)) {
                        if ($fp = @fopen($file, 'r')) {
                            $v = fread($fp, filesize($file));
                            fclose($fp);
                            $data .= "--$boundary\r\n";
                            $data .= 'Content-Disposition: form-data; name="' . $k . '"; filename="' . basename($file) . '"; Content-Type: application/octet-stream' . "\r\n\r\n";
                            $data .= $v . "\r\n";
                        }
                    }
                }
                $data .= "--$boundary\r\n";
            }
            $out = "POST $path HTTP/1.0\r\n";
            $header = "Accept: */*\r\n";
            $header .= "Accept-Language: zh-cn\r\n";
            $header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data; boundary=$boundary\r\n";
            $header .= 'Content-Length: ' . strlen($data) . "\r\n";
            $header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $header .= "Host: $host:$port\r\n";
            $header .= "Connection: Close\r\n";
            $header .= "Cache-Control: no-cache\r\n";
            $header .= "Cookie: $cookie\r\n\r\n";
            $out .= $header;
            $out .= $data;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $header = "Accept: */*\r\n";
            $header .= "Accept-Language: zh-cn\r\n";
            $header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $header .= "Host: $host:$port\r\n";
            $header .= "Connection: Close\r\n";
            $header .= "Cookie: $cookie\r\n\r\n";
            $out .= $header;
        }

        $fpflag = 0;
        if (!$fp = @fsocketopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout)) {
            $context = array(
                'http' => array(
                    'method' => $post ? 'POST' : 'GET',
                    'header' => $header,
                    'content' => $post,
                    'timeout' => $timeout,
                ),
            );
            $context = stream_context_create($context);
            $fp = @fopen($scheme . '://' . ($ip ? $ip : $host) . ':' . $port . $path, 'b', false, $context);
            $fpflag = 1;
        }

        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp) && !$fpflag) {
                    $header = @fgets($fp);
                    $headers .= $header;
                    if ($header && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }
                $GLOBALS['filesockheader'] = $headers;

                if ($position) {
                    for ($i = 0; $i < $position; $i++) {
                        $char = fgetc($fp);
                        if ($char == "\n" && $oldchar != "\r") {
                            $i++;
                        }
                        $oldchar = $char;
                    }
                }

                if ($limit) {
                    $return = stream_get_contents($fp, $limit);
                } else {
                    $return = stream_get_contents($fp);
                }
            }
            @fclose($fp);
            return $return;
        }
    }

    final protected function formatPostkey($post, &$result, $key = '') {
        foreach ($post as $k => $v) {
            $_k = $key ? $key . '[' . $k . ']' : $k;
            if (is_array($v)) {
                $this->formatPostkey($v, $result, $_k);
            } else {
                $result[$_k] = $v;
            }
        }
    }

    /**
     * 设置验证通过后获取订单信息
     * @param array $notify 通知信息
     * @return mixed
     */
    abstract protected function setInfo($notify);

    /**
     * 支付表单
     * @param PayVo $vo
     * @return mixed
     */
    abstract public function payForm(PayVo $vo);

    /**
     * 退款表单
     * @param RefundVo $vo
     * @return mixed
     */
    abstract public function refundForm(RefundVo $vo);

    /**
     * 支付通知验证
     * @param array $notify 通知信息
     * @return 验证结果
     */
    abstract public function verifyNotify($notify);
}