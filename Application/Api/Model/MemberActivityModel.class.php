<?php 
/**
 * 会员权益 接口模型
 * @author: xiaohuakang
 * @date: 2016-05-23
 */
namespace Api\Model;

use Api\Model\ApiBaseModel;

class MemberActivityModel extends ApiBaseModel
{
	/**
	 * 获取活动状态
	 * @param: int $endTime 活动结束时间
	 * @param: int $quantum 活动总份数
	 * @param：int $surplus 活动剩余份数
	 * @return: int $status : 1-进行中 2-已抢光 3-已结束 
	 */
	public function getActStatus($endTime, $quantum, $surplus)
	{
		$now = time();
		if ($endTime > $now) {
			if ($quantum > 0 && $surplus > 0) {
				$status = 1;
			} elseif ($quantum > 0 && $surplus == 0) {
				$status = 2;
			} elseif ($quantum == 0) {
				$status = 1;
			}
		} else {
			$status = 3;
		}
		return $status;
	}

	/**
	 * 获取用户的活动领取状态
	 * @param: int $endTime 活动结束时间
	 * @param: int $quantum 活动总份数
	 * @param: int $surplus 活动剩余份数
	 * @return: int $status 1-可领取 2-已抢光 3-已结束 4-已领取
	 */
	public function getReceiveStatus($endTime, $quantum, $surplus)
	{
		$now = time();
		// 活动进行中
		if ($endTime > $now) {
			if ($quantum > 0) { // 有数量限制的活动
				if ($surplus > 0) { // 还有剩余数量
					$status = 1; // 可领取
				} else {
					$status = 2; // 已抢光
				}
			} else { // 不限制数量的活动
				$status = 1;
			}
		} else {	// 活动已结束
			$status =  3;
		}
		return $status;
	}

	/**
	 * 用户领取活动
	 * @param: int $id 活动id
	 * @param：string $username 用户名
	 */
	public function receiveActivity($id, $username, $uid)
	{
		$receiveMember = $this->where(['ma_id' => $id])->getField('ma_receive_member'); // 已领取会员
		$receiveStatus = strripos($receiveMember, $username); // 检测用户是否已领取
		if ($receiveStatus === false) {
			$result = $this->execute("update __TABLE__ set ma_receive_member = CONCAT(ma_receive_member, ',', {$username} ) WHERE ma_id = {$id}");
			if ($result) {
				$this->where(['ma_id' => $id])->setDec('ma_surplus');
				$varityCode = $this->where(['ma_id'  => $id])->getField('ma_verify_code');
				//	设置推送消息
				$data = [
					'push_name' => 3,
					'push_title' => "免费试吃活动领取成功！",
					'push_brief' => '您已成功获得免费试吃活动资格，验证码为 ' . $varityCode . ' ，在线下参与免品试吃活动时出示此验证码给店员验证即可免费参与活动！',
					'push_is_app' => 2,
					'push_addtime' => time(),
					'fk_ma_id' => $id,
					'is_bind' => 1,
					'push_name_type' => 3
				];
				$this->_setPush($data, $uid);
				return $varityCode;
			}
		} else {
			return $this->where(['ma_id'  => $id])->getField('ma_verify_code');
		}
	}

	/**
	 * 设置活动消息推送
	 * @param array $data 设置消息推送内容
	 * @param int $uid 用户id
	 */
	private function _setPush($data, $uid)
	{
        $MessagePush = D('Admin/MessagePush');
		$result = $MessagePush->add($data);
		if ($result) {
			$data = [
				'user_id' => $uid,
				'fk_push_id' => $result,
				'state' => 1, // 消息是否已读 1为否 2为是
				'addtime' => time(),
				'fk_push_name' => 3
			];
			M('MessageUser')->add($data);
		}
	}

}