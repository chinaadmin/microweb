<?php
/**
 * 消息推送
 * @author xiongzw
 * @date 2015-12-01
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Push\Push;
class NoticeModel extends AdminbaseModel{
	/**
	 * 向单用户推送消息
	 * @param $channel_id 推送用户id
	 * @param $msg 推送的消息
	 * @param $device_type 推送设备 3：android 4:ios
	 */
	public function sendNotice($uid,$msg){
		$notice_data = array(
				'title'=>$msg['title'],
				'content'=>$msg['content'],
				'add_time'=>NOW_TIME,
				'status'=>1
		);
		$notice_data_id = M("NoticeData")->add($notice_data);
		if($notice_data_id){
			$notice = array(
					'me_id'=>$uid,
					'notice_from'=>0,
					'notice_type'=>'',
					'notice_data_id'=>$notice_data_id,
					'notice_is_read'=>0,
					'notice_add_time'=>NOW_TIME
			);
			$notice_id = M("Notice")->add($notice);
		}
		$token = M("Token")->where(['uid'=>$uid])->getField("token");
		$tokenData = M("TokenOnline")->where(['token'=>$token])->find();
		if($tokenData!=1){
			$device_type = $tokenData['type']==2?4:3;
			$this->sendMsg($device_type, $msg,$tokenData['channel_id']);
		}
	}
	/**
	 * 发送通知
	 * @param $device_type 设备类型 3：android 4:ios
	 * @param $msg 发送消息
	 * @param $channel_id 发送设备
	 */
	private function sendMsg($device_type,$msg,$channel_id=null){
		$push = new Push();
		$result = $push->setDeviceType($device_type)->setChannelId($channel_id)->setRangetype(7)->pushMsg($msg);
	}
	
	/**
	 * 向所有设备发送公告
	 */
	public function sendAll($msg){
		$data = array(
				'title'=>$msg['title'],
				'content'=>$msg['content'],
				'add_time'=>NOW_TIME,
				'status'=>1,
				'me_id'=>'',
				'object'=>''
		);
		$result = M('notice_system')->add($data);
		$this->sendAndroidMsg($msg);
		$this->sendIosMsg($msg);
		
	}
	/**
	 * ios发送广播
	 * @param  $msg 消息数组
	 */
	private function sendIosMsg($msg){
		$push = new Push();
		return $push->setDeviceType(4)->setMsgType(1)->setRangetype(1)->pushMsg($msg);
	}
	/**
	 * android 发送广播
	 * @param $msg 消息数组
	 */
	private function sendAndroidMsg($msg){
		$push = new Push();
		return $push->setDeviceType(3)->setMsgType(1)->setRangetype(1)->pushMsg($msg);
	}
	
	/**
	 * 发送消息推送
	 */
	public function sendMessage($msg){
		$data = array(
				'title'=>$msg['title'],
				'content'=>$msg['content'],
				'type'=>$msg['type'],
				'data'=>json_encode($msg['data']),
				'add_time'=>NOW_TIME
		);
		M("NoticeMessage")->add($data);
		$this->sendAndroidMsg($msg);
		$this->sendIosMsg($msg);
	}
	
	/**
	 * 通知试图模型
	 */
	public function viewModel(){
		$viewFields = array(
				'Notice'=>array(
						"*",
						'_type' => "LEFT"
		        ),
				'NoticeData'=>array(
						'*',
						'_on'=>'Notice.notice_data_id = NoticeData.id'
		         )
		);
		return $this->dynamicView($viewFields);
	}
	
	/**
	 * 用户通知列表
	 * @param $uid 用户id
	 */
	public function noticeList($uid){
		$where = array(
				'me_id'=>$uid
		);
		return $this->viewModel()->where($where)->select();
	}
	
	/**
	 * 设置用户信息为已读
	 */
	public function setNotice($notice_id){
		$where = array (
				'notice_id'=>$notice_id
		);
		return $this->setData ( $where, array (
				'notice_is_read' => 1,
				'notice_read_time' => NOW_TIME 
		) );
	}
	
	/**
	 * 获取消息详情
	 */
	public function getNoticeById($notice_id){
		return $this->viewModel()->where(['notice_id'=>$notice_id])->find();
	}
}