<?php
namespace Common\Org\ThinkPay\Driver\Alipay;
class AlipayMd5{

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * @return string 签名结果
     */
    public function sign($prestr, $key){
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     * @return bool 签名结果
     */
    public function verify($prestr, $sign, $key){
        $mysgin = $this->sign($prestr, $key);
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

}