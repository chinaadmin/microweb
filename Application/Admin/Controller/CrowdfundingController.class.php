<?php
/**
 *众筹后台管制器
 * @author wxb
 * @date 2015-12-03
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CrowdfundingController extends AdminbaseController{
	protected $curent_menu = 'Crowdfunding/index';
	public function _initialize() {
		parent::_initialize ();
	}
	/**
	 * 众筹列表
	 */
	public function index() {
		$this->title  = '众筹列表';
		$model = D('Crowdfunding')->scope('default');
		$this->lists = $this->lists($model);
		$this->display();
	}
	/**
	 * 添加/编辑属性
	 */
	public function edit() {
		$id = I('id');
		if($id){
			$info = D('Crowdfunding')->showOne($id);
			$this->info = $info;
		}
		$this->display('edit_add');
	}
	
	/**
	 * 更新
	 */
	public function update() {
		$crowdfunding = D('crowdfunding');
		$res = $crowdfunding->updateOne($_REQUEST);
		$this->ajaxReturn($this->result->success()->toArray());
	}
	
	/**
	 * 删除类型
	 */
	public function del() {
		$id = I('id',0,'int');
		$res = D('crowdfunding')->delOne($id);
		$this->ajaxReturn ( $res->toArray() );
	}
	function addTravel(){
		$m = M('travel_project');
		$info = $m->find();
		$info['tp_content'] = htmlspecialchars_decode($info['tp_content']);
		$this->info = $info;
		$this->display();
	}
	function updatetravel(){
		$m = M('travel_project');
		if($m->create()){
			if(!$m->tp_content){
				return $this->ajaxReturn($this->result->set('TRAVEL_CONTENT_REQUIRE')->toArray());
			}
			$m->tp_content = htmlspecialchars($m->tp_content);
			$m->tp_update_time = time();
			if(I('tp_id')){
				$m->save();	
			}else{
				$m->tp_add_time = time();
				$m->add();
			}	
			return $this->ajaxReturn($this->result->success()->toArray());		
		}
		return $this->ajaxReturn($this->result->error()->toArray());
	}
	function travelList(){
		$m = D('TravelRegister')->viewModel();
		$keyWord = I('keyWord','','trim');
		$where = [];
		if($keyWord){
			$where = [
					'username|aliasname' => ['like','%'.$keyWord.'%']
			];
		}
		$fields = [
				'cr_name',
				'aliasname',
				'username',
				'mobile',
				'email',
		];
		$this->list = $this->lists($m,$where,'tr_add_time desc',$fields);
		$this->display();
	}
	//推荐人列表
	function recommendList(){
		$this->title = '推荐人列表';
		$where = [];
		$where['is_inside_user'] = 0;//排除内部员工
		if($cor_order_sn = I('cor_order_sn','','trim')){
			$where['cor_order_sn'] = $cor_order_sn;
		}
		if($username = I('username','','trim')){
			$where['username|aliasname'] = ['like',$username];
		}
		if((($cor_pay_status = I('cor_pay_status','-1','trim')) && $cor_pay_status != -1) || $cor_pay_status == 0){
			$where['cor_pay_status'] = $cor_pay_status;
		}
		if((($cr_receive_status = I('cr_receive_status','-1','trim')) && $cr_receive_status != -1) || $cr_receive_status == 0){
			$where['cr_receive_status'] = $cr_receive_status;
		}
		$model = D('Crowdfunding')->recommendListView();
		$fields = ['username','aliasname','cor_order_sn','cor_pay_status','cr_receive_status','cr_id'];
		$list = $this->lists($model,$where,'cr_add_time desc',$fields);
		$this->list = $list;
		$this->pay_status_arr= D('CrowdfundingOrder')->payStatusName;
		$this->display();
	}
	//推荐人物品已经领取状态
	function doReceive(){
		$id = I('id');
		$data = I('data');
		parse_str($data,$data);
		$data['cr_id'] = $id;
		M('CrowdfundingRecommend')->save($data);
		$this->ajaxReturn($this->result->success()->toArray());
	}
}