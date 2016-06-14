<?php
/**
 * 用户类
 * @author cwh
 * @date 2015-04-10
 */
namespace User\Service;
use User\Model\UserModel;

class UserService extends UserModel{

    /**
     * 新增数据
     * @param array $data 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function addData(array $data, $mode = true , $replace=false,$options='') {
        if (! $this->create ( $data, self::MODEL_INSERT )) {
            return $this->getResultError();
        }
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->add ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('注册成功') : $this->result()->set('DATA_INSERTION_FAILS');
    }

    /**
     * 修改数据
     * @param array $where 条件
     * @param array $data 数据
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function setData(array $where, array $data, $mode = true) {
        if (! $this->create ( $data, self::MODEL_UPDATE)) {
            return $this->getResultError();
        }
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->save ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('修改成功') : $this->result()->set('DATA_MODIFICATIONS_FAIL');
    }

    /**
     * 删除数据
     * @param array $where 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function delData(array $where, $mode = true) {
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->delete ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('删除成功') : $this->result()->set('DATA_DELETE_FAILED');
    }

    /**
     * 逻辑删除数据
     * @param array $where 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function tombstoneData(array $where, $mode = true){
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->data(['delete_time'=>time()])->save ();
       	//物理删除帐户信息
       	$accountModel = $this->getAccountModel();
       	$id = $this->where ( $where )->getField('uid' , true);
        $delWhere = [
            'uid' => ['in' , $id]
        ];
       	$res = $accountModel->where($delWhere)->delete();
       	//尝试物理删除第三方用户绑定记录
       	M('user_connect')->where($delWhere)->delete();
       	//尝试物理删除黑名单
		$this->removeBlackList($id);
        //删除登录token信息
        $delToken = D('User/Token')->where($delWhere)->delete();
        if ($mode) {
            if ($result !== false && $res !== false && $delToken !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('删除成功') : $this->result()->set('DATA_DELETE_FAILED');
    }

    /**
     * 操作黑名单
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
	public function removeBlackList($uid){
		$res = M('user_blacklist')->where(['ub_uid' => ['in' ,$uid]])->delete();
		if($res === false){
			return $this->result()->error('移除黑名单失败!');
		}
		return $this->result()->success('移除黑名单成功!');
	}
}
