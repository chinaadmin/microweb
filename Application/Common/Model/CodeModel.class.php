<?php
/**
 * 验证码数据模型  
 */
namespace Common\Model;
use Think\Model;
class CodeModel extends BaseModel{

    /**
     * 验证码参数
     * @var array
     */
    private $code_param = [];

    /**
     * 过期时间
     * @var int
     */
    private $expire = 60;

    public function _initialize(){
        parent::_initialize();
        $vaild_time = C('JT_CONFIG_WEB_SORT_MESSAGE_VAILD');
        $vaild_time = empty($vaild_time)?30:$vaild_time;
        $this->expire = $vaild_time * 60;
    }

    /**
     * 设置验证码
     * @param int $len 长度
     * @param string|int $type 字串类型 0 字母 1 数字 其它 混合
     * @param string $add_chars 额外字符
     * @return $this
     */
    public function setCode($len = 6, $type = '', $add_chars = ''){
        $this->code_param = [
            'length'=>$len,
            'type'=>$type,
            'add_chars'=>$add_chars
        ];
        return $this;
    }

    /**
     * 获取验证码
     * @return string
     */
    public function getCode(){
        if(empty($this->code_param)){
            $this->setCode();
        }
        $param = $this->code_param;
        return rand_string($param['length'],$param['type'],$param['add_chars']);
    }

    /**
     * 设置过期时间
     * @param int $expire 过期时间
     * @return $this
     */
    public function setExpire($expire){
        $this->expire = $expire;
        return $this;
    }

    /**
     * 生成验证码
     * @param int $type 1：手机注册 2：手机修改密码 3：修改手机 4:邮件绑定 5:邮件找回密码 6:提货码 7:修改邮件
     * @param string $extend 扩展信息
     * @return bool|string
     */
    public function generate($type,$extend = ''){
        $code = $this->getCode();
        $data = [];
        $data['code'] = $code;
        $data['type'] = $type;
        $data['add_time'] = NOW_TIME;
        $data['status'] = 1;
        $data['extend'] = json_encode($extend);
        $data['uid'] = \User\Org\Util\User::getInstance()->isLogin();
        if($this->add($data)){
            return $code;
        }else{
            return false;
        }
    }

    /**
     * 获取验证码信息
     * @param string $code 验证码
     * @param int $type 类型
     * @param array $where 扩展条件
     * @param bool $is_once 是否一次性
     * @return $this
     */
    public function getInfo($code,$type,$where = [],$is_once = false){
        $result = $this->result();
        $info = $this->field(true)->where([
            'type'=>$type,
        	'code'=>$code
        ])->where($where)->order(['add_time'=>'desc'])->find();
        if(empty($info) || $info['code'] != $code){
            return $result->set('CODE_NOT_EXIST');
        }

        if($info['add_time'] + $this->expire < NOW_TIME){
            return $result->set('CODE_HAS_EXPIRED');
        }

        $info['extend'] = json_decode($info['extend'],true);
        $result->content($info);
        if($info['status'] != 1){
            return $result->set('CODE_USED');
        }

        //一次性的删除验证码
        if($is_once && ($info['add_time'] + 30 < NOW_TIME)){
            $this->where([
                'code_id'=>$info['code_id']
            ])->data([
                'status'=>0
            ])->save();
        }

        return $result->success();
    }

	/**
	 * 验证验证码
	 * @param mixed $token 唯一标识码 
	 * @param int $expire 过期时间 秒为单位
	 * @param boolean $isDelete 是否删除记录
	 * @return bool|mixed
	 */
	public function verifyCode($token,$expire,$isDelete = false){
		$vaild_time = intval($expire);
		$vaild_time = $vaild_time *1000;
		if(empty($token)){
			return false;
		}else{
			$where = array(
					'token' => $token,
					'add_time' => array('EGT',time()-$vaild_time)
			);
			$find = $this->where($where)->find();
			if($find){
				if($isDelete){
					$where = array();
					$where['type'] = $find['type'];
					$where['code'] = $find['code'];
					$this->where($where)->delete();
				}
				return $find;
			}else{
				return false;
			}
		}
	}
	/**
	 *  保存验证码
	* @param mixed $code 验证码
	 *@param int $type 1：手机注册 2：手机修改密码 3：修改手机 4:邮件绑定 5:邮件找回密码 6:提货码 7:修改邮件
	 *@param $extend 扩展信息
	 *@return sting
	 */
	public function  saveVerifyCode($code = '',$type=1,$extend=' '){
		if(!$code){
			$code = md5(rand_string(10,5).NOW_TIME);
		}
		$data['code'] = $code;
		$data['type'] = $type;
		$data['add_time'] = NOW_TIME;
		$data['extend'] = $extend;
		$data['token']= $this->getUnique($code);
		$data['uid'] = \User\Org\Util\User::getInstance()->isLogin();
		$res = $this->add($data);
		if($res){
			return $data['token'];
		}
	}
	private  function getUnique($code){
		while($this->where(['token' => $code])->count()){
			$code = md5(rand_string(10,5).NOW_TIME);
		}
		return $code;
	}
	/**
	 * 发送验证码给用户
	 * @param int $id 存储id
     * @param string $tpl 模板
     */
	public function sendMobileCode($id,$tel,$tpl = 'bind_mobile'){
		$tel_code = to_guid_string(session_id().'_'.$id);
		if(!$tel){
			return 'DATA_ERROR';
		}
        if(!checkMobile($tel)){
            return 'MOBILE_FORMAT_ERROR';
        }
		//发送短信
		$mess = new \Common\Org\Util\MobileMessage();
		$arr['mobile_code'] = rand(100000,999999);
		if($mess->sendMessByTel($tel, $arr,$tpl) === true){//发送成功
			S($tel_code,$tel.'_'.$arr['mobile_code'],C('JT_CONFIG_WEB_SORT_MESSAGE_VAILD')*60);
			return 'MESSAGE_CODE_SEND';
		}
		return 'SEND_MESSAGE_FAIL';
	}
	
	/**
	 * 验证手机验证码
	 * @param int $id 存储id
	 */
	public  function checkMobileCode($id,$tel,$checkCode){
		if(!$tel || !$checkCode){
			return false;
		}
		$code = S(to_guid_string(session_id()."_".$id));
		if(!$code || ( $code !== $tel.'_'.$checkCode)){//验证码已过期
			return false;
		}
		return true;
	}
}