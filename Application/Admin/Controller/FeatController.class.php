<?php
/**
 * 商品展位
 * @author xiongzw
 * @date 2015-08-28
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class FeatController extends AdminbaseController{
	protected $curent_menu = 'Feat/index';
	protected $feat_model;
	public function _initialize(){
		parent::_initialize();
		$this->feat_model = D("Admin/Feat");
	}
	
	/**
	 * 展位列表
	 */
	public function index(){
		$lists = $this->lists($this->feat_model,"","feat_sort DESC,update_time DESC");
		$feats = array_column($lists, "feat_id");
		if($feats){
		 $feat_goods = $this->feat_model->getGoodsFeat($feats);
		}
		foreach ($lists as &$v){
			$v['count'] =0;
			if($feat_goods){
				foreach ($feat_goods as $vo){
					if($v['feat_id'] == $vo['feat_id']){
						$v['count'] += 1;
					}
				}
			}
		}
		$this->assign("lists",$lists);
		$this->display();
	}
	
	/**
	 * 添加展位
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::edit()
	 */
	public function edit(){
		$feat_id = I("request.feat_id",0,'intval');
		if($feat_id){
			$info = $this->feat_model->getById($feat_id);
			if($info['attr_id']){
				$info['attr_id'] = D('Upload/AttachMent')->getAttach($info['attr_id']);
			}
			$this->assign("info",$info);
		}
		$this->display();
	}
	
	/**
	 * 更新展位
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::update()
	 */
	public function update(){
		$data = array(
				"feat_name"=>I("post.name",""),
				"feat_url"=>I("post.url",""),
				"feat_status"=>I("post.status",1,'intval'),
				"feat_sort"=>I("post.sort",0,'intval'),
				"attr_id"=>I("post.feat_photo","0",'intval'),
				"feat_position"=>I("post.position","1","intval"),
				"update_time"=>NOW_TIME,
				"add_time"=>NOW_TIME
		);
		$feat_id = I("post.feat_id",0,'intval');
		if($feat_id){
			$data['feat_id'] = $feat_id;
			unset($data['add_time']);
			$result = $this->feat_model->setData(['feat_id'=>$feat_id],$data);
		}else{
			$result = $this->feat_model->addData($data);
		}
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 删除展位
	 */
	public function del() {
		$id = I ( 'request.feat_id', 0, 'intval' );
		$result = $this->feat_model->del($id);
		$this->ajaxReturn ( $result->toArray () );
	}
	
	/**
	 * 保存排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->feat_model->saveSort ( $sort, false, 'feat_sort', 'feat_id' );
		$this->ajaxReturn ( $result->toArray());
	}
}	