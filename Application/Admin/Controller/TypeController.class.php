<?php
/**
 * 商品类型
 * @author xiongzw
 * @date 2015-04-10
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class TypeController extends AdminbaseController{
	protected $curent_menu = 'Type/index';
	protected $type_model;
	public function _initialize(){
		parent::_initialize();
		$this->type_model = D('Type');
	}
	/**
	 * 类型列表
	 */
	public function index(){
		$data = $this->lists($this->type_model,'',"sort desc,type_id desc");
		foreach($data as &$v){
			$v['att_group'] = $this->type_model->getAttgroup($v['type_id']);
			$v['att_group'] = implode(",", $v['att_group']);
			$v['att_count'] = $this->type_model->getAttcount($v['type_id']);
		}
		$this->assign('lists',$data);
		$this->display();
	}
	
	/**
	 * 类型添加/编辑 
	 */
	public function edit(){
		$id = I('request.id',0,'intval');
		if($id){
			$info = $this->type_model->getById($id);
			$this->assign("info",$info);
		}
		$this->display();
	}
	
	/**
	 * 更新数据
	 */
	public function update(){
		if (IS_POST) {
			$data = array (
					'name' => I ( 'post.name' ),
					'status' => I ( 'post.status', 0, 'intval' ),
					'sort' => I ( 'post.sort', 0, 'intval' ) 
			);
			$type_id = I ( 'request.type_id', 0, 'intval' );
			if ($type_id) {
				$data ['type_id'] = $type_id;
				// 更新属性分组
				$result = $this->type_model->updateAttgroup ( $type_id );
				if (! $result->isSuccess ()) {
					$this->ajaxReturn ( $result->toArray () );
				}
				// 添加属性分组
				$result = $this->type_model->addAttgroup ( $type_id, 2 );
				if (! $result->isSuccess ()) {
					$this->ajaxReturn ( $result->toArray () );
				}
				$result = $this->type_model->setData ( array (
						"type_id" => $type_id 
				), $data );
			} else {
				$result = $this->type_model->addData ( $data );
				$id = $this->type_model->getLastInsID ();
				if ($id) {
					$this->type_model->addAttgroup ( $id );
				}
			}
			$this->ajaxReturn ( $result->toArray () );
		}
	}
	
	/**
	 * 删除类型
	 */
	public function  del(){
		$type_id = I('request.id',0,'intval');
		if($this->type_model->goodsById($type_id)){
			$this->ajaxReturn($this->result->error("请先删除类型下商品")->toArray());
		}
		$this->type_model->del($type_id);
		$this->ajaxReturn($this->result->set()->toArray());
	}
	/**
	 * 排序
	 */
	public function sort(){
		$sort = I ( 'request.sort' );
		$result = $this->type_model->saveSort ( $sort, false, 'sort', 'type_id' );
		$this->ajaxReturn ( $result->toArray());
	}
	
}