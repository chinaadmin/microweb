<?php
/**
 * 消息推送
 * @author LIU
 * @date 2015-03-30
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class MessagePushModel extends AdminbaseModel{

    protected $tableName = 'message_push';
	
	//删除消息
	public function del_push($push_id){
	   $data = $this  -> where(['push_id' => $push_id]) -> find();
		if($data){
			$re = $this -> where(['push_id' => $push_id]) -> delete();
			
			//删除用户推送信息
			M('message_user') -> where(['fk_push_id'=>$push_id]) ->delete();
			
			//$Attach = D('Upload/Attachment');
			
			//删除图片
			if($data['push_att_id']){
				//D('Upload/Attach') -> delById(array($data['push_att_id']));
				D('Upload/AttachMent') -> delById(array($data['push_att_id']));
			}
			
			if($re){
				return true;
			}
		}
		return false;
	}
   
} 