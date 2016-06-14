<?php
/**
 * 试吃活动模型
 * @author xiongzw
 * @date 2015-07-13
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class ActivityEatModel extends AdminbaseModel{
	protected $tableName = "activity_photo";
	public $number =1000; //编号规则 主键+1000
	/**
	 * 试图模型
	 */
	public function viewModel(){
		$viewFields = array(
				'ActivityPhoto'=>array(
						"photo_id",
						"attr_id",
						"title",
						"vote_num",
						"status",
						"add_time",
						"_type"=>"LEFT"
				),
				'ActivityUser'=>array(
						"uid",
						"nick",
						"mobile",
						"status"=>"user_status",
						"_on"=>"ActivityPhoto.uid=ActivityUser.uid"
				)
		);
		return $this->dynamicView($viewFields);
	}
	/**
	 * 格式化列表数据
	 */
	public function formatList($lists){
		D('Home/List')->getThumb($lists,1,"attr_id");
		foreach($lists as &$v){
			$v['number'] = $v['photo_id']+$this->number;
			$v['photo'] = $v['thumb'];
		}
		return $lists;
	}
	/**
	 * 更新状态
	 * @param $photo_id 照片id
	 * @param $status
	 */
	public function status($photo_id,$status){
		$where = array(
				"photo_id"=>$photo_id
		); 
		return $this->where($where)->save(['status'=>$status]);
	}
	/**
	 * 根据手机号修改用户状态
	 */
	public function userStatus($mobile,$status){
		$where = array(
				'mobile'=>$mobile
		);
		return M("ActivityUser")->where($where)->save(['status'=>$status]);
	}
}