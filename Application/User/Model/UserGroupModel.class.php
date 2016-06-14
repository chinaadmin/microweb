<?php
/**
 * 会员等级模型
 * @author wxb
 * @date 2015-09-1
 */
namespace User\Model;
use Common\Model\BaseModel;
class UserGroupModel extends BaseModel{
	/**
	 * 通过用户组id获取下面的所有用户
	 * @param int $groupId array
	 * @return array 
	 */
	function getUidByGid($groupId){
			$viewFields = [
					'user_group' =>[
							'_as' => 'ug',
							'_type' => 'left'
					],
					'user_group_x' =>[
							'uid',
							'_as' => 'ugx',
							'_on' => 'ugx.group_id = ug.id'
					]
			];
			$list = $this->dynamicView($viewFields)->where([ 'id' => $groupId])->select();
			return array_column($list,'uid');
	}
	/**
	 *删除组 同时清空下面的用户 
	 * @param int 用户组id $group_id
	 */
	function delGroup($group_id){
		//删除组
		$this->delete($group_id);
		//删除组用户
		M('user_group_x')->where(['group_id' => $group_id])->delete();
		return true;
	}
}