<?php 
/**
 * 免费试吃 接口控制器
 * @author: xiaohuakang
 * @date: 2016-05-23
 */
namespace Api\Controller;

use Api\Controller\ApiBaseController;

class FreeEatController extends ApiBaseController
{
	private $model = '';
	public function _initialize()
	{
		parent::_initialize();
		// 检查是否登入
		$userInfo = $this->authToken();
		$this->model = D('MemberActivity');
	}

	// 活动列表
	public function activityList()
	{
		$where = [];
		$aLists = $this->_lists($this->model, $where, 'ma_add_time desc', 'ma_id as id, ma_title as name, ma_end_time as endtime, ma_quantum as  quantum, ma_surplus as surplus, ma_pic_id as picid');	
		foreach ($aLists['data'] as $k => &$v) {
			$v['statu'] = $this->model->getActStatus($v['endtime'], $v['quantum'], $v['surplus']);
			$v['endtime'] = $v['endtime'];
			$v['photo'] = $this->_getPhotoUrl($v['picid']);
			unset($v['picid']);
		}
		$this->ajaxReturn($this->result->content($aLists)->success());
	}

	// 活动详情
	public function actDetail()
	{
		$id = I('post.id', 0, 'intval');
		if (!$id) {
			$this->ajaxReturn($this->result->error('活动id不能为空！', 'ID_REQUIRE'));
		}
		$userInfo = D('User/Token')->device()->auth($this->token);
		$username = M('User')->where(['uid' => $userInfo['uid']])->getField('username');
		$list = $this->model->field('ma_id as id, ma_title as name, ma_end_time as endtime, ma_quantum as quantum, ma_pic_id as picid, ma_description as description, ma_surplus as surplus, ma_receive_member as receive_member')->find($id);
		$receiveStatu = strripos($list['receive_member'], $username); // 用户领取状态
		$list['receive'] = $list['quantum'] - $list['surplus']; // 已领取份数

		if ($receiveStatu === false) {
			$list['receive_statu'] = $this->model->getReceiveStatus($list['endtime'], $list['quantum'], $list['surplus']);
		} else {
			$list['receive_statu'] = 4;
		}
		
		$list['endtime'] = $list['endtime'];
		$list['photo'] = $this->_getPhotoUrl($list['picid']);
		$list['description'] = htmlspecialchars_decode($list['description']); // 将内容中的html字符实体转换为html标记
		unset($list['picid']);
		unset($list['receive_member']);
		$this->ajaxReturn($this->result->content(['data' => $list])->success());
	}

	/**
	 * 领取活动
	 */
	public function receiveActivity()
	{
		$id = I('post.id', 0, 'intval');
		if (!$id) {
			$this->ajaxReturn($this->result->error('活动id不能为空！', 'ID_REQUIRE'));
		}
		$userInfo = D('User/Token')->device()->auth($this->token);
		$username = M('User')->where(['uid' => $userInfo['uid']])->getField('username');
		$data = [];
		$data['verify_code'] = $this->model->receiveActivity($id, $username, $userInfo['uid']);
		$this->ajaxReturn($this->result->content(['data' => $data])->success());
	}

	/**
	 * 获取图片完整地址
	 * @param: int $picId 图片id
	 * @return string
	 */
	private function _getPhotoUrl($picId)
	{
		$path = D('Upload/AttachMent')->getAttach($picId, true);
		return fullPath($path[0]['path']);
	}

}