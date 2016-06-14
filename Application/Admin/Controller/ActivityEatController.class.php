<?php
/**
 * 微信试吃活动管理
 * @author xiongzw
 * @date 2015-07-13
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ActivityEatController  extends AdminbaseController{
	private $eat_model;
	public function _initialize(){
		parent::_initialize();
		$this->eat_model = D("Admin/ActivityEat");
	}
	/**
	 * 试吃照片列表
	 */
	public function index(){
		$model = $this->eat_model->viewModel();
		$where = $this->_where();
		$lists = $this->lists($model,$where,'ActivityPhoto.add_time desc');
		$lists = $this->eat_model->formatList($lists);
		$this->assign("lists",$lists);
		$this->display();
	}
	/**
	 * 查询条件
	 */
	public function _where(){
		$where = array();
		$keyword = I("request.keywords","");
		$status = I("request.status","-1",'intval');
		if($keyword){
			$where["_string"] = "photo_id =".intval($keyword-$this->eat_model->number)." OR mobile=".$keyword;
			$this->assign("keywords",$keyword);
		}
		if($status>-1){
			$where['ActivityPhoto.status'] = $status;
			$this->assign("status",$status);
		}
		return $where;
	}
	
	/**
	 * 照片审核
	 *    <code>
	 *    photo_id 照片id
	 *    status 
	 *    
	 *    </code>
	 */
	public function status(){
		$photo_id = I("post.photo_id",0,'intval');
		$status = I('post.status',1,'intval');
		$user_status = I('post.user_status','');
		$mobile = current(array_keys($user_status));
		$user_status = $user_status[$mobile];
		if($photo_id){
			$this->eat_model->startTrans();
			$return_p = $this->eat_model->status($photo_id,$status);
			$return_u = $this->eat_model->userStatus($mobile,$user_status);
			if($return_p!==false && $return_u!==false){
				$this->eat_model->commit();
				$this->ajaxReturn($this->result->success()->toArray());
			}else{
				$this->eat_model->rollback();
				$this->ajaxReturn($this->result->error()->toArray());
			}
		}else{
			$this->ajaxReturn($this->result->error()->toArray());
		}
	}
	
	/**
	 * 批量审核照片状态
	 */
	public function batchStatus(){
		$status = I("request.status",'-1','intval');
		$photo_ids = I('post.photo_id','');
		if(empty($photo_ids)){
			$this->ajaxReturn($this->result->error("请选择要更改的照片！")->toArray());
		}
		if($status>-1){
			$where = array(
					"photo_id"=>array('in',$photo_ids)
			);
			$result = $this->eat_model->setData($where,['status'=>$status]);
			$this->ajaxReturn($result->toArray());
		}
	    $this->ajaxReturn($this->result->error()->toArray());
	}
}