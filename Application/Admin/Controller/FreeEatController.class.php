<?php
/**
 * 免费品尝控制器
 * @author: xiaohuakang
 * @date: 2016-05-19
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class FreeEatController extends AdminbaseController{
	protected $curent_menu = 'FreeEat/index'; // 设置当前菜单
	// 列表页
	public function index()
	{
		$where = [];
		$where['title'] = I('request.ma_title', '');
		$where['status'] = I('request.ma_status', 0, 'intval');
		$order = 'ma_id desc';
		$model = D('MemberActivity');
		$whereStr = $this->_doWhere($where);
		$this->lists = $this->lists($model, $whereStr, $order);
		$this->assign($where);
		$this->display();
	}

	// 新增/编辑页
	public function edit()
	{
		$id = I('request.id', '0', 'intval');
		if ($id) {
			$info = M('MemberActivity')->find($id);
			if ($info['ma_pic_id']) {
				$info['ma_pic_id'] = D('Upload/AttachMent')->getAttach($info['ma_pic_id'], true);
			}
			$this->info = $info;
		}
		$this->display();
	}

	// 写入数据
	public function update()
	{
		$model = D('MemberActivity');
		if ($model->create()) {
			$model->ma_pic_id = I('post.attachId', ''); // 上传图片
			if ($model->ma_id) {
				if ($model->save()) {
					$result = ['status' => 'SUCCESS', 'msg' => '编辑活动成功！'];
				} else {
					$result = ['status' => 'ERROR', 'msg' => '编辑活动失败！'];
				}
			} else {
				if ($id = $model->add()) {
					//	设置推送消息
					$data = [
						'push_name' => 3,
						'push_title' => "免费试吃活动开始了！",
						'push_brief' => '您好，新一轮的免费试吃活动"' . I('post.ma_title', '') . '"开始了，赶紧参加吧！',
						'push_is_app' => 2,
						'push_addtime' => time(),
						'fk_ma_id' => $id,
						'push_obj' => 1,
						'push_name_type' => 3
					];
					D('MemberActivity')->setPush($data);
					$result = ['status' => 'SUCCESS', 'msg' => '添加活动成功！'];
				} else {
					$result = ['status' => 'ERROR', 'msg' => '添加活动失败！'];
				}
			}
		}
		$this->ajaxReturn($result);
	}

	// 详情页
	public function detail($id)
	{
		$info = M('MemberActivity')->find($id);
		if ($info['ma_pic_id']) {
			$info['ma_pic_id'] = D('Upload/AttachMent')->getAttach($info['ma_pic_id'], true);
		}
		$this->info = $info;
		$this->display();
	}

	// 组合查询条件
	private function _doWhere($where)
	{
		$whereStr = '';

		// 活动标题
		if (!empty($where['title'])) {
			$whereStr .= " ma_title like '%{$where['title']}%' AND";
		}

		// 活动状态
		if ($where['status']) {
			$now = time();
			switch ($where['status']) {
				// 进行中
				case '1':
					$whereStr .= " ma_end_time > '{$now}' AND";
					break;
				// 已抢光
				case '2':
					$whereStr .= ' ma_quantum > 0  AND ma_surplus = 0 AND';
					break;
				// 已结束
				default:
					$whereStr .= " ma_end_time < '{$now}' AND";
					break;
			}
		}

		$whereStr = rtrim($whereStr, 'AND');
		return $whereStr;
	}

}