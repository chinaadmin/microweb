<?php
/**
 * 商品收藏接口
 * @author xiongzw
 * @date 2015-08-08
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
class CollectController extends  ApiBaseController{
	private $collect_model;
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
		$this->collect_model = D("Home/Collect");
	}

	/**
	 * 添加收藏
	 */
	public function addCollect() {
		$data = array (
				'goods_id' => I ( 'post.goodsId', '0', 'intval' ),
				'uid' => $this->user_id,
				'add_time' => NOW_TIME,
				"norms_value" => I("post.norms",""),
				"source"=>2
		);
		$result = $this->collect_model->addCollect ( $data);
		$this->ajaxReturn ( $result );
	}

	/**
	 * 我的收藏
	 */
	public function myCollect(){
		$where = array (
				'Collect.uid' => $this->user_id
		);
		$viewModel = $this->collect_model->viewModel();
		$lists = $this->_lists($viewModel,$where,'Collect.add_time DESC');
		D("Home/List")->getThumb($lists['data'],1);
		$lists['data'] = D("Api/Collect")->fomatList($lists['data']); 
	    $this->ajaxReturn($this->result->content($lists)->success());
	}

	/**
	 * 删除我的收藏
	 * <code>
	 * goods_id 商品id
	 * </code>
	 */
	public function del() {
		$goods_id = I ( 'post.goodsId', '' );
		$result = $this->collect_model->delCollect ( $goods_id, $this->user_id );
		$this->ajaxReturn ( $result);
	}
}