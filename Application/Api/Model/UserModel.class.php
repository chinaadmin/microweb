<?php
namespace Api\Model;
class UserModel extends ApiBaseModel{

    protected $autoCheckFields  =   false;

    /**
     * 手机加密token
     * @param string $mobile 手机
     * @param string $code 编号
     * @return string
     */
    public function mobileEncrypt($mobile,$code){
        return path_encrypt($mobile.'|'.$code.'|'.NOW_TIME);
    }

    /**
     * 解密token手机
     * @param string $token token
     * @return array
     */
    public function mobileDecrypt($token){
        $info = path_decrypt($token);
        $info = explode('|',$info);
        $result = $this->result();
        if(count($info)<3){
            return $result->set('TOKEN_AUTH_FAILED');
        }

        if($info[2] + 60*10<NOW_TIME){
            return $result->set('TOKEN_HAS_EXPIRED');
        }

        return $result->content([
            'mobile'=>$info[0],
            'code'=>$info[1]
        ])->success();
    }
    
    /**
     * 判断第三方账号是否绑定
     */
    public function isBind($openid,$unionid){
    	if(!empty($unionid)){
    		$uid = M("UserConnect")->where(['unionid'=>$unionid])->getField("uid");
    	}else{
    		$uid = M("UserConnect")->where(['openid'=>$openid])->getField("uid");
    	}
    	if($uid){
    		if(M('User')->where(['uid'=>$uid])->find()){ //用户已绑定
    			return false;
    		}else{
    			return true;
    		}
    	}
    	return true;
    }
    
    /**
     * 通过uid判断是否已绑定
     * @param unknown $uid
     */
    public function bindByUid($uid,$type){
    	return M("UserConnect")->where(['uid'=>$uid,'type'=>strtolower($type)])->find();
    }

}