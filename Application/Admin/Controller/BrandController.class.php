<?php
/**
 * 品牌管理
 * @author xiongzw
 * @date 2015-04-21
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class BrandController extends AdminbaseController{
	protected $brand_model;
	protected $curent_menu = "Brand/index";
	public function _initialize(){
		parent::_initialize();
		$this->brand_model = D('Brand');
	}
	/**
	 * 品牌列表
	 */
	public function index(){
		$lists = $this->lists($this->brand_model,array(),'sort desc');
		foreach($lists as &$v){
			if($v['logo']){
			$logo = D('Upload/AttachMent')->getAttach(json_decode($v['logo'],true));
			$v['logo'] = current(array_column($logo, 'path'));
			}
		}
		$this->assign("lists",$lists);
		$this->display();
	}
	/**
	 * 添加编辑品牌
	 */
	public function edit(){
		$id = I('request.id',0,'intval');
		if($id){
			$info = $this->brand_model->getById($id);
			if($info['logo']){
			 $info['logo'] = D('Upload/AttachMent')->getAttach(json_decode($info['logo'],true));
			}
			$this->assign("info",$info);
		}
		$this->display();
	}
	/**
	 * 更新品牌
	 */
	public function update(){
		if(IS_POST){
			$data = array(
					'name' => I('post.name',''),
					'url'  => I('post.url',''),
					'logo' => I ( 'post.attachId')?json_encode(I ( 'post.attachId')):"",
					'sort' => I('post.sort'),
					'desc' => I('post.content'),
					'status' => I('post.status'),
					'brand_id'=>I('post.id',0,'intval')
  			);
			if(!$data['logo']){
				$this->ajaxReturn($this->result->error('必须上传图片')->toArray());
			}
			$id = I('post.id',0,'intval');
			if($id){
				$where = array('brand_id'=>$id);
				$result = $this->brand_model->setData($where,$data);
			}else{
				$result = $this->brand_model->addData($data);
			}
			$this->ajaxReturn($result->toArray());
		}
	}
	/**
	 * 删除
	 */
	public function del(){
		$id= I('request.id',0,'intval');
		if($id){
			$logo = $this->brand_model->getById($id,"logo");
			$logo = json_decode($logo['logo'],true);
			D('Upload/AttachMent')->delById($logo);
			$result = $this->brand_model->delData(array('brand_id'=>$id));
			$this->ajaxReturn($result->toArray());
		}
	}
	/**
	 * 保存排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->brand_model->saveSort ( $sort, false, 'sort', 'brand_id' );
		$this->ajaxReturn ( $result->toArray());
	}
}