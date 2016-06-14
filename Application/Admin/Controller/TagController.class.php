<?php
/**
 * 商品标签管理
 * @author xiongzw
 * @date 2015-07-16
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class TagController extends AdminbaseController{
	protected $curent_menu = 'Tag/index';
	private $tag_model;
	public function _initialize(){
		parent::_initialize();
		$this->tag_model = D("Admin/Tag");
	}
	/**
	 * 标签列表
	 */
	public function index(){
		$lists = $this->lists($this->tag_model,"",'tag_sort desc,update_time desc,add_time desc');
		$this->assign("lists",$lists);
		$this->display();
	}
	
	/**
	 * 添加/编辑标签
	 */
	public function edit(){
		$tag_id = I("request.tag_id","0",'intval');
		if($tag_id){
			$info = $this->tag_model->getById($tag_id);
			if($info['tag_attr']){
				$info['tag_attr'] = D('Upload/AttachMent')->getAttach($info['tag_attr'],true);
			}
			$this->assign("info",$info);
		}
		$this->display();
	}
	
	/**
	 * 更新标签
	 */
	public function update(){
		if(IS_POST){
			$tag_id = I("post.tag_id",'0','intval');
			$data = array(
					'name' => I("post.name",''),
					'tag_attr' => I('post.attachId','0','intval'),
					'tag_status'=>I('post.status',1,'intval'),
					'tag_sort'=>I('post.sort',0,'intval'),
					'add_time' => NOW_TIME,
			);
			if($tag_id){
				$data['tag_id'] = $tag_id;
				$data['update_time'] = NOW_TIME;
				$where = array(
						'tag_id' => $tag_id
				);
				$result = $this->tag_model->setData($where,$data);
			}else{
				$result = $this->tag_model->addData($data);
			}
			$this->ajaxReturn($result->toArray());
		}
	}
	
	/**
	 * 删除标签
	 */
	public function del(){
	    $tag_ids = I('post.tag_id','');
	    $result = $this->tag_model->delTag($tag_ids);
	    $this->ajaxReturn($result->toArray());
	}
}