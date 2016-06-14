<?php
/**
 * 地推人员管理
 * @author wxb
 * @date 2015-11-24
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
class MarketPushingRecommendController extends ApiBaseController{
	public function _initialize() {
		parent::_initialize ();
		$this->authToken();
	}
	/**
	 * 我的地推人员联系方式
	 */
	public function contactDetail(){
		$uid = $this->user_id;
		$fields = [
				'AdminUser' => [
						'nickname' => 'real_name' ,
							'mobile',
							'email',
							'motto',
							'att_id'
				],
				'MarketPushingRecommend' => -1
		];
		$model = D('Api/MarketPushingRecommend')->viewModel($fields);
		$list = $this->_lists($model,['mpc_uid' => $uid],'mpc_add_time desc');
		foreach($list as $k =>$v){
			foreach($v as $key=>$val){
				$img_src = M('attachment') -> where(['att_id'=>$val['att_id']])->find();
				$list[$k][$key]['img'] = fullPath($img_src['path'].'/'.$img_src['name'].'.'.$img_src['ext']);
			}
			
		}
		$this->ajaxReturn($this->result->content($list)->success());
	}
}