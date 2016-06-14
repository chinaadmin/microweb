<?php
namespace Api\Controller;
class MessageController extends ApiBaseController {

    /**
     * 发送短信
     * @author cwh
     *         传入参数:
     *         <code>
     *         type 用途： 1:注册 2:忘记密码 3:修改手机
     *         mobile 手机
     *         </code>
     */
    public function sendSMS(){
        $type = I('post.type','','intval');
        $mobile = I('post.mobile','','trim');

        if(empty($mobile) || !checkMobile($mobile)){
            $this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
        }
		if($type == 1){ //检查是否已经存在这样的手机号 或用户
			$d = D('User/User');
			$user_is_exist = $d->ifUniqueUsername($mobile);//注册时手机号同时也为用户名
			$mobile_is_exist = $d->ifUniqueMobile($mobile);
			if(!$user_is_exist || !$mobile_is_exist){
				$this->ajaxReturn($this->result->set('MOBILE_EXISTS')); //统一提示手机号已存在
			}
		}		
        $code_model = D('Code');
        $code = $code_model->setCode(6,1)->generate($type,$mobile);

        //发送短信
        $mess = new \Common\Org\Util\MobileMessage();
        $arr = [];
        $arr['mobile_code'] = $code;
        $tmp = 'verify_mobile';
        if($type == 1){
            $tmp = 'bind_mobile';
        }
        if($mess->sendMessByTel($mobile, $arr,$tmp) === true){//发送成功
            $this->ajaxReturn($this->result->success());
        }

        $this->ajaxReturn($this->result->set('SEND_MESSAGE_FAIL'));
    }

    /**
     * 验证短信
     * @author cwh
     *         传入参数:
     *         <code>
     *         type 用途：2:忘记密码 3:修改手机
     *         mobile 手机
     *         code 验证码
     *         </code>
     */
    public function verifySMS(){
        $type = I('post.type','','intval');
        $code = I('post.code','','intval');
        $mobile = I('post.mobile','','trim');
        if(empty($mobile) || !checkMobile($mobile)){
            $this->ajaxReturn($this->result->set('MOBILE_FORMAT_ERROR'));
        }

        $code_model = D('Code');
        $where = [
            'extend' => json_encode($mobile)
        ];
        $result = $code_model->where($where)->getInfo($code,$type);
        if($result->isSuccess()){
            $token = D('Api/User')->mobileEncrypt($mobile,$code);
            $this->ajaxReturn($this->result->content(['token'=>$token])->success());
        }
        $this->ajaxReturn($result);
    }

}