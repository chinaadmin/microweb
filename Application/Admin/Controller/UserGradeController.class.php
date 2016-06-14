<?php
/**
 * 会员等级
 * @author xiongzw
 * @date 2015-06-10
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class UserGradeController extends AdminbaseController{
	protected $curent_menu = "UserGrade/index";
	protected $grade_model;
	public function _initialize(){
		parent::_initialize();
		$this->grade_model = D("User/UserGrade");
	}
	/**
	 * 会员等级列表
	 */
	public function index(){
		$lists = $this->lists($this->grade_model,'','start_value DESC,add_time DESC');
		$this->assign("lists",$lists);
		$this->assign("express",$this->grade_model->express);
		$this->display();
	}
	/**
	 * 添加编辑等级
	 */
	public function edit(){
		$grade_id = I('request.grade_id',0,'intval');
		if($grade_id){
			$info = $this->grade_model->getGradeById($grade_id);
			$this->assign("info",$info);
		}
		// dump($grade_id);
		// dump($info);exit;
		$this->display();
	}
	/**
	 * 更新等级
	 */
	public function update(){
		if(IS_POST){
			$data = array(
				"grade_name"	=>	I("post.name",''),
				"start_value"	=>	I("post.start_value",0,'intval'),
				"grade_status"  =>  I('post.grade_status','intval'),
				"add_time" 		=>  NOW_TIME,
			);
			
			$grade_id = I('request.grade_id',0,'intval');
			if($grade_id){
				unset($data['add_time']);
				$where = array(
					'grade_id'=>$grade_id
				);
				$result = $this->grade_model->setData($where,$data);
			}else{
				$result = $this->grade_model->addGrade($data);
			}
			
			$this->ajaxReturn($result->toArray());
		}
	}
	
	/**
	 * 批量删除
	 */
	public function del(){
		$grade_id = I('request.grade_id','');
		
		$result = $this->grade_model->delGrade($grade_id);
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 更新等级级别
	 */
	public function changeGrade(){
		$level = I('post.level',0,'intval');
		$gid = I('post.gid',0,'intval');
		$type = I('post.type','','intval');
		$result = $this->grade_model->updateLevel($gid,$level,$type);
		$this->ajaxReturn($result->toArray());
	}
}