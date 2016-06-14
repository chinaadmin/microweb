<?php
/**
 * 发货管理
 * @author xiongzw
 * @date 2015-08-10
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CartgoController extends AdminbaseController{
	protected $cartgo_model;
	protected $curent_menu = 'Cartgo/index';
	public function _initialize(){
		parent::_initialize();
		$this->cartgo_model = D("Admin/Cartgo");
	}
	/**
	 * 发货管理列表
	 */
	public function index(){
		$view_model = $this->cartgo_model->listView();
		$lists = $this->lists($view_model,$this->_where(),"send_time desc");
		$lists = $this->cartgo_model->getLists($lists);
		$this->assign("lists",$lists);
		$this->display();
	}
	/**
	 * 列表查询条件
	 */
	private function _where(){
		$where = array();
		$send_type = I("request.send_type",'-1','intval');  //配送方式
		$keywords = I("request.keywords","",'trim'); //关键字查询
		$start_time = I("request.start_time","","strtotime");//发货开始时间
		$end_time = I("request.end_time","","strtotime");//发货结束时间
		if($send_type>-1){
			$where['send_type'] = $send_type;
			$this->assign('send_type',$where['send_type']);
		}
		if($keywords){
			$where["send_sn|send_num|name|mobile|order_sn"] = array("like","%{$keywords}%");
			$this->assign('keywords',$keywords);
		}
		if($start_time && !$end_time){
			$where['send_time'] = array("EGT",$start_time);
		}
		if($end_time && !$start_time){
			$where['send_time'] = array("ELT",$end_time);
		}
		if($start_time && $end_time){
			$where['send_time'] = array("between",array($start_time,$end_time));
		}
		if($start_time){
		 $this->assign('start_time',date('Y-m-d',$start_time));
		}
		if($end_time){
		 $this->assign('end_time',date('Y-m-d',$end_time));
		}
		return $where;
	}
	
	/**
	 * 发货详情
	 */
	public function info(){
		$send_id = I("request.send_id",0,'intval');
		$info = $this->cartgo_model->getInfo($send_id);
		$this->assign("info",$info);
		$this->display();
	}
	
	/**
	 * 发货单备注
	 */
	public function mark(){
		$send_id = I("request.send_id",0,'intval');
		$mark = I("post.mark",'');
		$result = $this->cartgo_model->setData(['send_id'=>$send_id],['send_remark'=>$mark]);
		$this->ajaxReturn($result->toArray());
	}
	
	/**
	 * 上门送货确认收货
	 */
	public function receipt(){
		$order_id = I ( "request.order_id", "" );
		$result = D ( "Admin/Order" )->setData ( [ 
				'order_id' => $order_id 
		], [ 
				'shipping_status' => 1,
				'shipping_time' => NOW_TIME
		] );
		$action = array(
				"order_id"=>$order_id,
				"action" => json_encode(array("shipping_status" => 1)),
				"is_seller"=>1,
				"handle"=>$this->user['uid'],
				"remark"=>"商家已发货",
				"front_remark"=>"送货服务已完成，感谢您的光临",
				"type"=>0,
				"add_time"=>NOW_TIME
		);
		M("OrderAction")->add($action);
		if($result->isSuccess()){
			$result = $result->success("确认收货成功!");
		}
		$this->ajaxReturn ( $result->toArray () );
	}
}