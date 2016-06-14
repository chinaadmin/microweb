<?php
namespace Stores\Org\Util;
use Common\Org\Util\Helpers;

/**
 * 门店类
 * @author cwh
 * @date 2015-05-12
 */
class Stores {

    private static $_instance = false;

    /**
     * 访问实例的公共的静态方法
     *
     * @return Member
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }

    private function __construct() {
    }

    /**
     * 私有化克隆函数，防止外界克隆对象
     */
    private function __clone() {
    }

    /**
     * 设置
     * @param int $stores_id 门店id
     */
    public function set($stores_id){
        $this->session ('stores_id',$stores_id);
    }

    /**
     * 检验
     * @return int
     */
    public function check(){
        $stores_id = $this->session('stores_id');
        if(empty($stores_id)){
            return 0;
        }else{
            return $stores_id;
        }
    }

    /**
     * 清除
     */
    public function clean(){
        $this->session('stores_id',null);
    }

    /**
     * session管理函数(对session进行加密和解密处理)
     * @param string|array $name
     *        	session名称 如果为数组则表示进行session设置
     * @param mixed $value
     *        	session值
     * @return mixed
     */
    public function session($name, $value = '') {
        return Helpers::session($name,$value);
    }

}