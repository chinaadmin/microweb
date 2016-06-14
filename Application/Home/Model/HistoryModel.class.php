<?php
/**
 * 用户浏览记录模型
 * @author xiongzw
 * @date 2015-07-06
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class HistoryModel extends HomebaseModel{
	protected $tableName = "user_history";
	/**
	 * 记录手机的历史记录
	 * @param $goods 要插入的历史记录
	 * @param $uid 用户id
	 * @return boolean
	 */
	public function appHistory(array $goods,$uid){
		$data = array();
		foreach($goods as $k=>$v){
			$data[$k] = array(
				'uid' =>$uid,
				'goods_id'=>$v['goods_id'],
				'add_time'=>strtotime($v['time']),
				'source'=>2			
			);
		}
		if($data){
			return $this->addAll($data,'',true);
		}
	}
	/**
	 * 根据用户id获取历史记录
	 * @param  $uid 用户id
	 * @param string $field
	 * @return array
	 */
	public function getHisByUid($uid, $source = 1, $order = 'add_time desc', $field = true) {
		$where = array (
				'uid' => $uid,
				'source' => $source 
		);
		return $this->field ( $field )->where ( $where )->order ( $order )->select ();
	}
}