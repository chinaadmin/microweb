<?php
/**
 * 商品分类
 * @author xiongzw
 * @date 2015-04-09
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common;
class CategoryController extends AdminbaseController {
	protected $curent_menu = 'Category/index';
	protected $category_model;
	public function _initialize() {
		parent::_initialize ();
		$this->category_model = D ( 'Category' );
	}
	/**
	 * 商品分类列表
	 */
	public function index() {
		$data = $this->treeCategory ();
		$this->assign ( 'lists', $data );
		$this->display ();
	}
	
	/**
	 * 树形分类
	 * 
	 * @param string $field        	
	 * @return $data
	 */
	private function treeCategory($field = true) {
		$icon = array (
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			);
		return $this->category_model->getTree($field,$icon);
	}
	/**
	 * 添加/编辑分类
	 */
	public function edit() {
		// 获取分类
		$category = $this->treeCategory ();
		$id = I ( 'request.id', 0, 'intval' );
		if ($id) {
			$childs = $this->category_model->getChilds ( $id );
			$childs [] = $id;
			$info = $this->category_model->getById ( $id );
			if($info['mobile_photo']){
				$info['photo'] = D('Upload/AttachMent')->getAttach($info['mobile_photo']);
			}
			//获取类型属性
			$attrs = D('Attr')->getByType($info['type_id'],"name,attr_id");
			//获取所有被选中的属性
			$checkAttr = $this->category_model->getByCat($id);
			$checkAttr = array_column($checkAttr,"attr_id");
			$checkNorms = $this->category_model->getNormsById($id);
			$this->assign('checkAttr',$checkAttr);
			$this->assign("attrs",$attrs);
			$this->assign ( 'childs', $childs );
			$this->assign ( 'info', $info );
		}
		$types = D ( 'Type' )->getTypes ();
		$this->assign('types',$types);
		$this->assign ( 'categorys', $category );
		$norms = D('Admin/Norms')->getNorms();
		if(!empty($checkNorms)){
			foreach($norms as &$v){
				foreach($checkNorms as $vo){
					if($v['norms_id'] == $vo['norms_id']){
						$v['checked'] = "checked";
					}
				}
			}
		}
		$this->assign('norms',$norms);
		$this->display ();
	}
	
	/**
	 * 更新
	 */
	public function update() {
		if (IS_POST) {
			$data = array (
					"name" => I ( 'post.name', '' ),
					"pid" => I ( 'post.pid', 0, 'intval' ),
					"keywords" => I ( 'post.keywords', '' ),
					"description" => I ( 'post.remark', '' ),
					"sort" => I ( 'post.sort', 0, 'intval' ),
					"status" => I ( 'post.status', 0, 'intval' ),
					"price_interval" => I ( 'post.price', 1, 'intval' ),
					"add_time" => NOW_TIME,
					'type_id' => I('post.type',0,'intval'),
					'icon' => I('post.icon',''),
					'mobile_photo'=>I('post.mobile_photo',0,'intval')
			);
			$id = I ( 'post.id', 0, 'intval' );
			if ($id) {
				$norms_type =1;
				$data ['cat_id'] = $id;
				$data ['update_time'] = NOW_TIME;
				unset ( $data ['add_time'] );
				$result = $this->category_model->setData ( array (
						'cat_id' => $id 
				), $data );
			} else {
				$norms_type =0;
				$result = $this->category_model->addData ( $data );
				$id = $this->category_model->getLastInsID();
			}
			if($result->isSuccess()){
				$norms = I('post.norms','');
				$this->category_model->addAttr($id);
				$this->category_model->setNorms($norms,$id,$norms_type);
			}
			$this->ajaxReturn ( $result->toArray () );
		}
	}
	/**
	 * 删除分类
	 */
	public function del() {
		$id = I ( 'request.id', 0, 'intval' );
		$child = $this->category_model->getChilds ( $id );
		if ($child) {
			$this->ajaxReturn ( $this->result->error ('请先删除子分类！' )->toArray () );
		}
		$child [] = $id;
		$goods = $this->category_model->goods ( $child );
		if (! empty ( $goods )) {
			$this->ajaxReturn ( $this->result->error ('请先删除分类下的商品！')->toArray () );
		}
		$where = [ 
				'cat_id' => $id 
		];
		$this->category_model->delAttr($id);
		$result = $this->category_model->delData ( $where );
		$this->ajaxReturn ( $result->toArray () );
	}
	
	/**
	 * 保存排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->category_model->saveSort ( $sort, false, 'sort', 'cat_id' );
		$this->ajaxReturn ( $result->toArray());
	}
	/**
	 * 通过类型获取属性
	 */
	public function attrs(){
		$type_id = I('post.type_id',0,'intval');
		$result['success'] = false;
		if($type_id){
			$atts = D('Attr')->getByType($type_id);
			if($atts){
				$result['success'] = true;
				$result['data'] = $atts;
			}
		}
		$this->ajaxReturn($result);
	}
}
