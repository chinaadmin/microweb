<?php
/**
 * 类型属性
 * @author xiongzw
 * @date 2015-04-13
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AttributeController extends AdminbaseController{
	protected $curent_menu = 'Type/index';
	protected $attr_model;
	public function _initialize() {
		parent::_initialize ();
		$this->attr_model = D ( 'Attr' );
	}
	/**
	 * 类型属性列表
	 */
	public function index() {
		$type_id = I ( 'request.id', 0, 'intval' );
		$where = array (
				'type_id' => $type_id 
		);
		$data = $this->lists ( $this->attr_model, $where, "sort desc,attr_id desc" );
		$type_name = current ( D ( 'Type' )->getById ( $type_id, "name" ) );
		$this->assign ( "type_name", $type_name );
		$this->assign ( "lists", $data );
		$this->display ();
	}
	/**
	 * 添加/编辑属性
	 */
	public function edit() {
		$id = I ( 'request.id', 0, 'intval' );
		$types = D ( 'Type' )->getTypes ();
		if ($id) {
			$info = $this->attr_model->getById ( $id );
			$this->assign ( "info", $info );
		}
		$this->assign ( "types", $types );
		$this->display ();
	}
	
	/**
	 * 更新
	 */
	public function update() {
		if (IS_POST) {
			$data = array (
					'name' => I ( 'post.name', '' ),
					'sort' => I ( 'post.sort', 0, 'intval' ),
					'type_id' => I ( 'post.type', 0, 'intval' ),
					'attr_group' => I ( 'post.attr_group', 0, 'intval' ),
					'index_type' => I ( 'post.index_type', 0, 'intval' ),
					'is_relation' => I ( 'post.relation', 0, 'intval' ),
					'attr_type' => I ( 'post.attr_type', 0, 'intval' ),
					'input_type' => I ( 'post.input_type', 0, 'intval' ),
					'value' => I ( 'post.value', '' ) 
			);
			$id = I ( 'request.id', 0, 'intval' );
			if ($id) {
				$data ['attr_id'] = $id;
				$result = $this->attr_model->setData ( array (
						"attr_id" => $id 
				), $data );
			} else {
				$result = $this->attr_model->addData ( $data );
			}
			$this->ajaxReturn ( $result->toArray () );
		}
	}
	/**
	 * 获取类型属性分组
	 */
	public function attGroup() {
		$type_id = I ( 'post.type_id', 0, 'intval' );
		$result ['success'] = false;
		if ($type_id) {
			$data = D ( 'Type' )->getAttgroup ( $type_id, 2 );
			if ($data) {
				$result ['success'] = true;
				$result ['data'] = $data;
			}
		}
		$this->ajaxReturn ( $result );
	}
	
	/**
	 * 删除类型
	 */
	public function del() {
		$attr_id = I ( 'request.id', 0, 'intval' );
		$result = $this->attr_model->delData ( array (
				'attr_id' => $attr_id 
		) );
		$this->ajaxReturn ( $result->toArray () );
	}
	/**
	 * 排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->attr_model->saveSort ( $sort, false, 'sort', 'attr_id' );
		$this->ajaxReturn ( $result->toArray () );
	}
	
}