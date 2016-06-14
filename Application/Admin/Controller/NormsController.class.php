<?php
/**
 * 商品规格
 * @author xiongzw
 * @date 2015-05-14
 */
namespace  Admin\Controller;
use Common\Controller\AdminbaseController;
class NormsController extends AdminbaseController{
	protected $curent_menu = 'Norms/index';
	protected $norms_model;
	public function _initialize(){
		parent::_initialize();
		$this->norms_model = D('Admin/Norms');
	}
	/**
	 * 规格列表
	 */
	public function index(){
		$data = $this->lists($this->norms_model,array('norms_delete_time'=>0),'norms_sort desc,norms_add_time desc');
		$norms_ids = array_column($data, "norms_id");
		$values = $this->norms_model->getNormsValue($norms_ids);
		foreach($data as &$v){
			foreach($values as $vo){
				if($v['norms_id']==$vo['norms_id']){
					$v['norms_value'].= $vo['norms_value']."&nbsp;&nbsp;"; 
				}
			}
		}
		$this->assign("lists",$data);
		$this->display();
	}
	/**
	 * 新增编辑规格
	 */
	public function edit(){
		$norms_id = I('request.norms_id',0,'intval');
		if($norms_id){
		 $info = $this->norms_model->getById($norms_id,2);
		 foreach($info['norms_values'] as &$v){
		 	$v['norms_attr'] = D('Upload/AttachMent')->getAttach($v['norms_attr']);
		 }
	     $this->assign('info',$info);
		}
		$this->display();
	}
	/**
	 * 更新规格
	 */
	public function update(){
		$norms_id = I('request.norms_id',0,'intval');
		$where = array();
		if($norms_id){
			$where = array(
					'norms_id'=>$norms_id
			);
		}
		$data = array(
			'norms' => array(
				'norms_name' => I('post.name',''),
				'norms_type' => I('post.type',1,'intval'),
				'norms_mark' => I('post.mark',''),
				'norms_sort' => I('post.sort'),
				'norms_add_time' => NOW_TIME
		     ),
			'norms_value'=>array(
					'norms_value' => I('post.value',''),
					'norms_attr'  => I('post.attr',''),
					'norms_value_sort'=>I('post.value_sort')
			)
		);
		$this->_normsValue($data);
		if(empty($where)){
		  $result = $this->norms_model->addData($data['norms']);
		}else{
			$data['norms']['norms_id'] = $norms_id;
			$result = $this->norms_model->setData($where,$data['norms']);
		}
		if($result->isSuccess()){
			if(empty($where)){
			 $norms_id = $result->getResult();
			}
			$data['norms_value']['norms_id'] = $norms_id;
			$data['norms_value']['type'] = $data['noems']['norms_type'];
			$this->norms_model->addNormsValue($data['norms_value'],$where);
		}
		$this->ajaxReturn($result->toArray());
	}
	/**
	 * 判断norms_value是否有值
	 */
	public function _normsValue($data){
		if(empty(array_filter($data['norms_value']['norms_value'])) &&  false){
			$this->ajaxReturn($this->result->error("请至少填写一项规格值")->toArray());
		}
		if($data['norms']['norms_type']==2 && empty(array_filter($data['norms_value']['norms_attr'])) &&  false){
			$this->ajaxReturn($this->result->error("请上传规格图片")->toArray());
		}
	}
	/**
	 * 排序
	 */
	public function sort(){
		$sort = I ( 'request.sort' );
		$result = $this->norms_model->saveSort ( $sort, false, 'norms_sort', 'norms_id' );
		$this->ajaxReturn ( $result->toArray () );
	}
	/**
	 * 删除规格值
	 */
	public function del(){
		$norms_id = I('request.norms_id',0,'intval');
		if($this->norms_model->getCats($norms_id)){
			$this->ajaxReturn($this->result->error("请先删除相关分类下的规格")->toArray());
		}
		if($this->norms_model->getGoods($norms_id)){
			$this->ajaxReturn($this->result->error("请先删除相关商品下的规格")->toArray());
		}
		if($norms_id){
			$where = array ('norms_id' => $norms_id);
			M('NormsValue')->where($where)->delete();
			$result = $this->norms_model->delData ($where);
		}
		$this->ajaxReturn ( $result->toArray () );
	}
}
