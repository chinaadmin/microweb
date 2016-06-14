<?php
/**
 * 会员权益活动模型
 * @author xiaohuakang
 * @date 2016-05-19
 */
namespace Admin\Model;

use Common\Model\AdminbaseModel;
use Common\Org\Jpush\examples\PushExample;

class MemberActivityModel extends AdminbaseModel
{
	protected $_validate = [
		['ma_title', 'require', '活动名称必填！']
	];

	protected $_auto = [
		['ma_start_time', 'time', 1, 'function'],
        ['ma_end_time','strtotime', 3, 'function'],
        ['ma_surplus', 'ma_quantum', 1, 'field'], // 剩余数量
        ['ma_verify_code', 'rand_string', 1, 'function', [4]],
		['ma_add_time', 'time', 1, 'function']
	];

	/**
	 * 设置活动消息推送
	 * @param array $data 设置消息推送内容
	 */
	public function setPush($data)
	{
        $MessagePush = D('Admin/MessagePush');
		$result = $MessagePush->addData($data);
		// if ($result) {
		// 	$jpush = new PushExample();
		// 	$jpush->push($data['push_title']);
		// }
	}

}