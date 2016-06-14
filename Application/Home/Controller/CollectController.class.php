<?php
/**
 * 商品收藏
 * @author xiongzw
 * @date 2015-07-21
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class CollectController extends  HomeBaseController{
	private $collect_model;
	public function _initialize(){
		parent::_initialize();
			if(!$this->uid){
				if(IS_AJAX){
					$this->ajaxReturn($this->result->set('NOT_LOGGED_IN','未登录')->toArray());
				}else{
					redirect(U('Passport/login'));
				}
			}
	
		$this->collect_model = D("Home/Collect");
	}
	
	/**
	 * 添加收藏
	 */
	public function addCollect() {
		$data = array (
				'goods_id' => I ( 'post.goods_id', '0', 'intval' ),
				'uid' => $this->uid,
				'add_time' => NOW_TIME,
				"norms_value" => I("post.norms","")
		);
		$result = $this->collect_model->addCollect ( $data);
		$this->ajaxReturn ( $result->toArray () );
	}

    /**
     * 用户选中样式
     */
    private function userSelCurrent($select = 'collect'){
        $this->assign('userSelCurrent',$select);
    }
	
	/**
	 * 我的收藏
	 */
	public function myCollect(){
        $this->userSelCurrent();
		$where = array (
				'Collect.uid' => $this->uid 
		);
		$viewModel = $this->collect_model->viewModel();
		$lists = $this->lists($viewModel,$where,'Collect.add_time DESC');
		foreach($lists as &$v){
			if($v['norms_value']){
				$v['norms_value'] = json_decode($v['norms_value'],true);
				$norms_data = "";
				foreach($v['norms_value'] as $vo){
					$norms_data .= $vo['id']."_";
				}
				$v['norms_data'] = trim($norms_data,"_");
			}
		}
		D("Home/List")->getThumb($lists,1);
		$this->assign('lists',$lists);
		$this->display("myCollect");
	}
	
	/**
	 * 删除我的收藏
	 * <code>
	 * goods_id 商品id
	 * </code>
	 */
	public function del() {
		$goods_id = I ( 'post.goods_id', '' );
		$result = $this->collect_model->delCollect ( $goods_id, $this->uid );
		$this->ajaxReturn ( $result->toArray () );
	}
}