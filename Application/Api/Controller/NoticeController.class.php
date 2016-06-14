<?php
/**
 * 消息通知接口
 * @author xiongzw
 * @date 2015-12-02
 */
namespace Api\Controller;
class NoticeController extends ApiBaseController{
	public function _initialize(){
	       	parent::_initialize();
	       	$this->authToken();
	}
	
	/**
	 * 获取用户消息列表
	 */
	public function getNoticeList(){
		$notice = D("Admin/Notice")->noticeList($this->user_id);
		$notice = D("Api/Notice")->formatList($notice);
		$this->ajaxReturn($this->result->content(['notice'=>$notice]));
	}
	
	/**
	 * 获取用户消息详情
	 */
	public function noticeInfo(){
		$notice_id = I("post.noticeId",0);
		$model = D("Admin/Notice");
		$info = $model->getNoticeById($notice_id);
		$model->setNotice($notice_id);
		$return_array = array(
				'noticeId'=>$info['notice_id'],
				'title'=>$info['title'],
				'content'=>$info['content'],
				'addTime'=>date('Y-m-d H:i:s',$info['add_time'])
		);
		$this->ajaxReturn($this->result->content(['info'=>$return_array]));
	}
}