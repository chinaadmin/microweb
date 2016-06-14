<?php
/**
 * APP图片启动管理
 * @author xiongzw
 * @date 2015-08-18
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Org\Util\Date;
class PutController extends AdminbaseController{
	protected $curent_menu = 'Put/index';
	protected $put_model;
	public function _initialize(){
		parent::_initialize();
		$this->put_model = D("Admin/Put");
	}
	/**
	 * 列表
	 */
	public function index(){
		$lists = $this->lists($this->put_model,"","put_sort desc,add_time desc");
		$Date = new Date();
		foreach($lists as $i=>&$v){
			$v['put_type'] = json_decode($v['put_type'],true);
			if ($v['end_time'] < NOW_TIME) {
				$v['date'] = 0;
				$v['status_name'] = toStress('已过期', 'label-important');
			} else if ($v['start_time'] > NOW_TIME) {
				$ad_start_time = date('Y-m-d', $v['start_time']);
				$day = $Date->dateDiff($ad_start_time);
				$v['date'] = ceil($day) <= 0 ? 0 : ceil($day);
				$v['status_name'] = toStress('未开始', 'label-danger');
			} else {
				$ad_end_time = date('Y-m-d', $v['end_time']);
				$day = $Date->dateDiff($ad_end_time);
				$v['put_up'] =1 ;
				$v['date'] = ceil($day) <= 0 ? 0 : ceil($day);
				$v['status_name'] = toStress('投放中', 'label-success');
			}
		}
		D("Home/List")->getThumb($lists,"",'put_attr');
		$this->assign("lists",$lists);
		$this->display();
	}
	
	/**
	 * 新增、更新
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::edit()
	 */
	public function edit(){
		$put_id = I("request.put_id",0,'intval');
		if($put_id){
			$info = $this->put_model->getById($put_id);
			$info['put_attr'] = D('Upload/AttachMent')->getAttach($info['put_attr']);
			$info['put_type'] = json_decode($info['put_type'],true);
			$this->assign("info",$info);
		}
		$this->display();
	}
	
	/**
	 * 新增、编辑
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Common\Controller\AdminbaseController::update()
	 */
	public function update(){
		$put_id = I("post.put_id",0,'intval');
		$data = array(
				"put_title" => I("post.title",""),
				"put_url" => I("post.url",""),
				"put_attr" => I("post.attachId",''),
				"start_time"=> strtotime(I("post.start_time",0)),
				"end_time" => strtotime(I("post.end_time",0)),
				"put_type" => json_encode(I("post.type",'')),
				"put_description"=>I("post.remark",""),
				"add_time" => NOW_TIME,
				'put_status'=>I("post.status",'1','intval'),
				'is_guide'=>I("post.is_guide",'0','intval'),
		);
		if($put_id){
			$data['put_id'] = $put_id;
			$result = $this->put_model->setData(['put_id'=>$put_id],$data);
		}else{
			$result = $this->put_model->addData($data);
		}
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 保存排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->put_model->saveSort ( $sort, false, 'put_sort', 'put_id' );
		$this->ajaxReturn ( $result->toArray());
	}
	
	/**
	 * 删除分类
	 */
	public function del() {
		$id = I ( 'request.put_id', 0, 'intval' );
		$where = [
		'put_id' => $id
		];
		$result = $this->put_model->delData ( $where );
		$this->ajaxReturn ( $result->toArray () );
	}
}