<?php
/**
 * 通知模型
 * @author xiongzw
 * @date 2015-12-03
 */
namespace Api\Model;
class NoticeModel extends ApiBaseModel{
	/**
	 * 格式化通知列表
	 */
	public function formatList($lists){
		$return_array = array();
		foreach($lists as $key=>$v){
			$return_array[$key] = array(
					'noticeId'=>$v['notice_id'],
					'title'=>$v['title'],
					'content'=>$v['content'],
					'noticeIsRead'=>$v['notice_is_read']
			);
		}
		return $return_array;
	}
}