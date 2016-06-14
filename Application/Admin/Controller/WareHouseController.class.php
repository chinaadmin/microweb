<?php
/**
 * 发货点管理
 * @author xiongzw
 * @date 2015-10-09
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class WareHouseController extends  AdminbaseController{
	protected $curent_menu = 'WareHouse/index';
	protected $ware_model;
	public function _initialize(){
		parent::_initialize();
		$this->ware_model = D("Admin/WareHouse");
	}
	/**
	 * 发货点列表
	 */
	public function index(){
		$data = $this->lists($this->ware_model,'','add_time desc');
		foreach ($data as &$v){
			$v['ware_location'] = preg_replace("/\s+/","", $v['ware_location']);
		}
		$this->assign('lists',$data);
		$this->display();
	}

	/**
	 * 添加/编辑发货点
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::edit()
	 */
	public function edit(){
		$ware_id = I("request.ware_id",0,'intval');
		if($ware_id){
			$info = $this->ware_model->getWareById($ware_id);
			$this->assign('info',$info);
		}
		$this->display();
	}

	/**
	 * 更新
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::update()
	 */
	public function update(){
		$ware_id = I('post.ware_id',0,'intval');
		$where = array();
		$data = array(
				'ware_name'=>I('post.name',''),
				'ware_username'=>I('post.username',''),
				'provice'=>I('post.provice_id',''),
				'city'=>I('post.city_id',''),
				'county'=>I('post.county_id',''),
				'ware_location'=>I('post.provice')." ".I('post.city')." ".I('post.county'),
				'ware_address'=>I('post.address'),
				'ware_zipcode'=>I('post.zip_code'),
				'ware_tel'=>I('post.tel'),
				'ware_mobile'=>I('post.mobile'),
				'is_default'=>I('post.default'),
				'ware_mark'=>I('post.remark',''),
				'add_time'=>NOW_TIME
		);
		if($ware_id){
			$where['ware_id']=$ware_id;
			$result = $this->ware_model->setData($where,$data);
		}else{
			$result = $this->ware_model->addData($data);
			$ware = $result->toArray();
			$ware_id = $ware['result'];
		}
		if($data['is_default']){
			$this->ware_model->setDefault($ware_id);
		}
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 批量删除
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::del()
	 */
	public function del(){
		$ware_id = I('post.ware_id','');
		$where = array(
				'ware_id'=>array('in',$ware_id)
		);
		$result = $this->ware_model->delData($where);
		$this->ajaxReturn($result->toArray());
	}
}