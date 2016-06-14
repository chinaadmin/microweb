<?php
/**
 * 众筹订单提成
 * @author wxb
 * @date 2015/12/23
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CrowdfundingPercentageController extends AdminbaseController{
	protected $curent_menu = 'CrowdfundingPercentage/index';
	public function _initialize() {
		parent::_initialize ();
	}
	//订单提成
	function index(){
		$this->title = '订单提成';
		$where = [
				'cor_pay_status' => 2,//已支付 
				//'cor_order_status' => 4, //已完成订单
				'mpc_id' => ['gt',0], //有提成订单
				'cd_percentage' => ['neq',0], //提成不能为零
		];
		$this->_doWhere($where);
		$model = D('Admin/CrowdfundingOrder')->percentageOrderView();
		$field = [
				'username', //编号
				'nickname',//真实姓名
				'mobile',
				'cd_name',
				'cd_subhead',
				'cor_order_sn',
				'cor_should_pay',
				'cor_order_id',
				'uid',
				'cd_percentage',
				'de_name',	//所属部门
				'cor_add_time',
				'cor_sign_time'
		];
		if(I('exportExcel',0,'int')){
			$list = $model->where($where)->field($field)->select();
			$this->exportExcel($list);
			exit;
		}
		$lists = $this->lists($model,$where,'cor_add_time desc',$field);
		$list = array();
		foreach($lists as $key => $v){	//过滤提成为0的子单
			if(intval($v['cor_should_pay'])!=0){
				$list[]=$v;
			}
		}

		$this->list = $list;
		$this->department = M("AdminDepartment")->select(); //所属部门
		$this->display();
	}
	private function _doWhere(&$where){
		if($order_sn = I('cor_order_sn','','trim')){
			$where['cor_order_sn'] = ['like','%'.$order_sn.'%'];
		}
		if($username = I('username','','trim')){
			$where['username'] = ['like','%'.$username.'%'];
		}
		//下单时间
		if(($startTime = I('start_time','','trim')) && ($endTime = I('end_time','','trim'))){
			$startTime = strtotime($startTime);
			$endTime = strtotime($endTime.' 23:59:59');
			if($endTime < $startTime){
				$tmp = $endTime;
				$endTime = $startTime;
				$startTime = $tmp;
			}
			$where['_string'] = "(cor_sign_time > 0 and cor_sign_time between {$startTime} and {$endTime}) or (cor_sign_time = 0 and cor_add_time  between {$startTime} and {$endTime} )";
		} 
		if($uid = I('uid','','trim')){
			$where['uid'] = $uid;
		}
		//所属部门
		if($did = I('fk_de_id', '', 'trim')){
			$where['de_id'] = $did;
		}
	}
	//员工提成统计
	function percentageStatistics(){
		$this->title = '员工提成统计';
		$field = [
				'username', //编号
				'nickname',//真实姓名
				'uid'
		];
		$where = [];
		if($username = I('username','','trim')){
			$where['username'] = ['like','%'.$username.'%'];
		}
		$m = D('Admin/CrowdfundingOrder');
		$model = $m->percentageStatisticsView();
		$list = $this->lists($model,$where,'add_time desc',$field);
		foreach ($list as &$v){
			$v['myMoney'] = $m->getOneMoney($v['uid']);
		}
		$this->list = $list;
		$this->display();
	}
	/**
	 * 导出订单提成信息
	 * @param array $lists
	 *        	要导出的数据
	 */
	public function exportExcel($lists) {
		$excel = new \Admin\Org\Util\ExcelComponent ();
		$excel = $excel->createWorksheet ();
		$excel->head ( array (
				'员工编号',
				'姓名',
				'手机',
				'部门',
				'方案',
				'订单号',
				'下单时间',
				'订单金额',
				'订单提成',
				'订单提成金额'
		) );
		$fileName = "订单提成数据";
		foreach ( $lists as $key => &$v ) {
			$v['order_time'] = $v['cor_sign_time'] ? date('Y-m-d H:i:s',$v['cor_sign_time']) :  date('Y-m-d H:i:s',$v['cor_add_time']);
			$v['percentage_money'] = $v["cd_percentage"]*$v["cor_should_pay"]/100;
		}
		$excel->listData ( $lists, array (
				"username",
				"nickname",
				"mobile",
				"de_name",
				"cd_name",
				"cor_order_sn",
				"order_time",//下单时间
				"cor_should_pay",//订单金额
				"cd_percentage",//订单提成
				"percentage_money",//提成金额
		) );
		$excel->output ( $fileName . ".xlsx" );
	}
}