<?php
/**
 * 验证码服务模型
 * @author wxb
 * @date 2015-06-08
 */
namespace Common\Service;
use Think\Verify;

class VerifyService {
	/**
	 * 展示验证码
	 * @param int $vertifyId 验证码id
	 */
	function createCode($vertifyId){
		$vertifyConfig = [];
		if(is_array(cookie('vertifyConfig'))){
			$vertifyConfig = cookie('vertifyConfig'); 
		}
		$vertifyConfig = array_merge(C('VERIFY_CONFIG'),$vertifyConfig);
	   	$vertify = new Verify ($vertifyConfig);
	   	$vertify->entry ( $vertifyId );
	}
	/**
	 * 验证验证码
	 * @param int $vertifyId 验证码id
	 * @param mixed $verify_code 验证码
	 */
	function checkCode($vertifyId,$verify_code){
		$vertify = new Verify();
		$verify_code = trim(htmlspecialchars($verify_code));
		if ($vertify->check($verify_code,$vertifyId) === false) {//验证码证码
			return false;
		}
		return true;
	}
	
	/**
	 * 处理验证码方法 (面向前端)
	 * @param int $type 类型 1:生成验证码 其它:核对验证码
	 * @param int $id 验证码id
	 * @return Ambigous <string, number>
	 */
	function dealCode(){
		$type = I('type',1,'int');
		$id = I('id',0,'trim');
		if($type == 1){//生成验证码
			$this->createCode($id);
		}else{//核对验证码
			$inputCode = I('verifyCode');
			$res = $this->checkCode($id,$inputCode);
			if($res){
				$res = 'true';
				session('dealCode_'.$id,true);
			}else{
				$res = 0;
				session('dealCode_'.$id,false);
			}
			if(!IS_AJAX ){
				return $res;
			}
			echo $res ;
		}
	}
}