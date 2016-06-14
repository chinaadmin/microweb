<?php
/**
 * 结果返回类
 * @author cwh
 * @date 2015-03-27
 */
namespace Common\Org\Util;
class Results {

    /**
     * 成功编码
     */
    const SUCCESS_CODE = 'SUCCESS';
    /**
     * 错误编码
     */
    const ERROR_CODE = 'UNKNOWN_ERROR';

    /**
     * 编号
     * @var string
     */
    private $code = 'SUCCESS';
    /**
     * 返回信息
     * @var string
     */
    private $msg = '';
    /**
     * 返回值
     * @var null|mixed
     */
    private $result = null;
    /**
     * 编号文件
     * @var string
     */
    private $file = 'common';

    /**
     * 设置返回信息
     * @param string $code 编号
     * @param string $msg 信息
     * @return $this
     */
    public function set($code = '',$msg = ''){
        $this->setCode($code);
        $this->setMsg($msg);
        return $this;
    }

    /**
     * 设置成功返回信息
     * @param string $msg 信息
     * @return $this
     */
    public function success($msg=''){
        $this->setCode(self::SUCCESS_CODE);
        $this->setMsg($msg);
        return $this;
    }

    /**
     * 设置错误返回信息
     * @param string $msg 信息
     * @param string $code 错误编码
     * @return $this
     */
    public function error($msg='',$code=''){
        $this->setCode(empty($code)?self::ERROR_CODE:$code);
        $this->setMsg($msg);
        return $this;
    }

    /**
     * 设置返回值
     * @param mixed $result 返回值
     * @return $this
     */
    public function content($result = null){
        return $this->setResult($result);
    }

    /**
     * 设置编号
     * @param string $code 编号
     * @return $this
     */
    public function setCode($code = ''){
        if($code !== '') {
            $this->code = $code;
        }else{
            $this->code = self::SUCCESS_CODE;
        }
        return $this;
    }

    /**
     * 设置返回信息
     * @param string $msg 信息
     * @return $this
     */
    public function setMsg($msg){
        if(empty($msg)){
            if(!empty($this->code)) {
                $common_code = require(APP_PATH . '/Common/Code/common.php');
                $other_code = [];
                if(!empty($this->file)) {
                	if(is_file(APP_PATH . '/Common/Code/' . $this->file . '.php')){
	                    $other_code = require(APP_PATH . '/Common/Code/' . $this->file . '.php');
                	}else{
                		$other_code = [];
                	}
                }
                $other_code = $other_code + $common_code;
                $this->msg = $other_code[$this->code];
            }
        }else {
            $this->msg = $msg;
        }
        return $this;
    }

    /**
     * 设置返回值
     * @param mixed $result 返回值
     * @return $this
     */
    public function setResult($result = null){
        $this->result = $result;
        return $this;
    }

    /**
     * 获取编号
     * @return string
     */
    public function getCode(){
        return $this->code;
    }

    /**
     * 获取返回信息
     * @return string
     */
    public function getMsg(){
        return $this->msg;
    }

    /**
     * 获取返回值
     * @return mixed
     */
    public function getResult(){
        return $this->result;
    }

    /**
     * 设置编号文件
     * @param $file
     * @return string
     */
    public function setFile($file){
        $this->file = $file;
        return $this;
    }

    /**
     * 获取编号文件
     * @return string
     */
    public function getFile(){
        return $this->file;
    }

    /**
     * 是否成功
     * @param null $code 编号
     * @return bool
     */
    public function isSuccess($code = null){
        if(is_null($code)){
            if($this->code === self::SUCCESS_CODE){
                return true;
            }
        }else{
            if(is_array($code)){
                if(in_array($this->code,$code)){
                    return true;
                }
            }else{
                if($this->code === $code){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取数组
     * @return array
     */
    public function toArray(){
        $return_array = [
            'status' => $this->getCode(),
            'msg' => $this->getMsg()
        ];
        $result = $this->getResult();
        if(!is_null($result)){
            $return_array['result'] = $result;
        }
        return $return_array;
    }

}