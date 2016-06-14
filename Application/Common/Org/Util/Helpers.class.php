<?php
/**
 * 帮助工具类
 * @author cwh
 * @date 2015-05-12
 */
namespace Common\Org\Util;

class Helpers {

    /**
     * session管理函数(对session进行加密和解密处理)
     * @param string|array $name
     *        	session名称 如果为数组则表示进行session设置
     * @param mixed $value
     *        	session值
     * @return mixed
     */
    static public function session($name, $value = '') {
        if ($value === '') {
            $value = session ( $name );
            if (empty ( $value )) {
                return;
            } else if (0 === strpos ( $value, 'think:' )) {
                $value = substr ( $value, 6 );
                $value = path_decrypt ( $value );
                return array_map ( 'urldecode', json_decode ( MAGIC_QUOTES_GPC ? stripslashes ( $value ) : $value, true ) );
            } else {
                return path_decrypt ( $value );
            }
        } else if (is_null ( $value )) { // 清除session不处理
        } else if (is_array ( $value )) {
            $value = json_encode ( array_map ( 'urlencode', $value ) );
            $value = 'think:' . path_encrypt ( $value );
        } else {
            $value = path_encrypt ( $value );
        }
        return session ( $name, $value );
    }

}