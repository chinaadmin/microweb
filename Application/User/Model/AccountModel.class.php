<?php
/**
 * 用户账号模型类
 * @author cwh
 * @date 2015-04-09
 */
namespace User\Model;
use Common\Model\BaseModel;
class AccountModel extends BaseModel{

    protected $tableName = 'account_user';

    /**
     * 添加账号
     * @param int $uid 用户ID
     * @param string $account 账号
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function addAccount($uid,$account,$type = 1){
        $result = $this->result();
        if(empty($uid) ){
            return $result->set('USER_ID_REQUIRE');
        }
        if(empty($account) ){
            return $result->set('ACCOUNT_REQUIRE');
        }
        //检测账号是否存在
        if($this->isExistAccount($account,0,$type)){
            return $result->set('ACCOUNT_IS_OCCUPIED');
        }
        $data = [];
        $data['uid'] = $uid;
        $data['account'] = $account;
        $data['type'] = $type;
        return $this->data($data)->add()===false?$result->set('ADD_ACCOUNTS_FAILED'):$result->set();
    }


    /**
     * 登陆验证
     * @param string $account 账号
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function loginAuth($account,$type = 0){
        //检测账号是否存在
        $uid = $this->isExistAccount($account,0,$type);
        if($uid){
            return $this->result()->content($uid)->success();
        }
        return $this->result()->set('ACCOUNT_NOT_EXISTS');
    }

    /**
     * 账号是否存在
     * @param string $account 账号
     * @param int $uid 用户ID
     * @param int|array $type 账号类型:1邮箱，2手机，3用户名
     * @return bool
     */
     function isExistAccount($account,$uid = 0,$type = 0){
        $where = [];
        if(!is_null($account)){
            $where['account'] = $account;
        }
        if(!empty($uid)){
            $where['uid'] = $uid;
        }
        if(!empty($type)){
            $where['type'] = $type;
        }
        $uid = $this->where($where)->getField('uid');
        return empty($uid)?false:$uid;
    }
	
    /**
     * 获取账号列表
     * @param int $uid 用户ID
     * @param int|array $type 账号类型:1邮箱，2手机，3用户名
     * @return array
     */
    public function getAccount($uid,$type = 0){
        $where = [];
        $where['uid'] = $uid;
        if(!empty($type)){
            $where['type'] = is_array($type)?array('in',$type):$type;
        }
        return $this->where($where)->field(true)->select();
    }

    /**
     * 修改账号
     * @param int $uid 用户ID
     * @param string $account 账号
     * @param string $old_account 旧账号
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function updateAccount($uid,$account,$old_account = '',$type = 1){
        $result = $this->result();
        //检测账号是否修改避免重复修改
        if($this->isExistAccount($account,$uid,$type)){
            return $result->set('ACCOUNT_IS_MODIFY');
        }

        //检测账号是否存在
        if($this->isExistAccount($account,0,$type)){
            return $result->set('ACCOUNT_IS_OCCUPIED');
        }

        $where = [];
        $where['uid'] = $uid;
        $where['type'] = $type;
        if(!empty($old_account)){
            $where['account'] = $old_account;
        }
        $data = [];
        $data['account'] = $account;
        return $this->where($where)->data($data)->save()===false?$result->set('UPDATE_ACCOUNTS_FAILED'):$result->set();
    }

    /**
     * 替换账号
     * @param int $uid 用户ID
     * @param string $account 账号
     * @param string $old_account 旧账号
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function replaceAccount($uid,$account,$old_account = '',$type = 1){
        if(empty($account)){
            return $this->delAccountToType($uid,$type);
        }
        if($this->isExistAccount(null,$uid,$type)){
            return $this->updateAccount($uid,$account,$old_account,$type);
        }
        return $this->addAccount($uid,$account,$type);
    }

    /**
     * 根据类型删除账号
     * @param int $uid 用户ID
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function delAccountToType($uid,$type = 1){
        $where = [];
        $where['uid'] = $uid;
        $where['type'] = $type;
        return $this->where($where)->delete()===false?$this->result()->set('DELETE_ACCOUNTS_FAILED'):$this->result()->set();
    }

    /**
     * 删除账号
     * @param int $uid 用户ID
     * @param string $account 账号
     * @param int $type 账号类型:1邮箱，2手机，3用户名
     * @return \Common\Org\util\Results
     */
    public function delAccount($uid,$account,$type = 1){
        $where = [];
        $where['uid'] = $uid;
        $where['account'] = $account;
        $where['type'] = $type;
        return $this->where($where)->delete()===false?$this->result()->set('DELETE_ACCOUNTS_FAILED'):$this->result()->set();
    }

    /**
     * 删除用户所有账号
     * @param int $uid 用户ID
     * @return \Common\Org\util\Results
     */
    public function delAllAccount($uid){
        $where = [];
        $where['uid'] = $uid;
        return $this->where($where)->delete()===false?$this->result()->set('DELETE_ACCOUNTS_FAILED'):$this->result()->set();
    }

}