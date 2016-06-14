<?php
/**
 * 到货通知模型
 * @author xiongzw
 * @date 2015-09-17
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ArrivalController extends AdminbaseController{
	/**
	 * 到货通知列表
	 */
	public function index(){
		$model = D("Admin/Arrival")->viewModel();
		$lists = $this->lists($model,'','add_time desc');
		$this->assign("lists",$lists);
		$this->display();
	}
	/**
	 * 发送到货通知
	 */
	public function send(){
		
	}
	
	/**
	 * 批量删除
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::del()
	 */
	public function del(){
		$notice_id = I("post.notice_id");
		if($notice_id){
			$where = array(
					'notice_id'=>array('in',(array)$notice_id)
			);
			$result = D("Admin/Arrival")->delData($where);
			$this->ajaxReturn($result->toArray());
		}else{
			$this->ajaxReturn($this->result->error("请选择要删除的值！")->toArray());
		}
	}
}