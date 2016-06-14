<?php
/**
 * 免费试吃活动
 * @author xiongzw
 * @date 2015-09-14
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class TastController extends AdminbaseController{
	public function index(){
		$tast_model = D("Admin/Tast");
		$lists = $this->lists($tast_model,'','add_time DESC');
		$this->assign("lists",$lists);
		$this->assign("people",$tast_model->peopleNum());
		$this->assign("position",$tast_model->position());
		$this->display();
	}
	
	/**
	 * 审核
	 */
	public function status(){
		$status = I("request.status",'');
		$tast_id = I('request.tast_id',0,'intval');
		$where = array(
				"tast_id"=>$tast_id
		);
		$result = D("Admin/Tast")->setData($where,array('status'=>$status));
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 备注
	 */
	public function remark(){
		$tast_id = I('request.tast_id',0,'intval');
		$remark = I("post.remark",'');
		$where = array(
				"tast_id"=>$tast_id
		);
		$result = D("Admin/Tast")->setData($where,array('remark'=>$remark));
		$this->ajaxReturn($result->toArray());
	}
}