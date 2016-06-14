<?php
namespace  Admin\Model;
use Common\Model\AdminbaseModel;
class CrowdfundingOrderMakefileModel extends AdminbaseModel{
	public $statusStringArr = [ //订单状态名数组
			'0' => '订单开始',
			'1' => '订单完成',
			'2' => '订单过期'
			
	];
	protected $_scope = [
			'default' => [
				'where' => [
					'jt_com_delete_time' => 0
				]
			]
	]; 

	// 配送方式 
	public $shippingType = [  
		0 => '门店自提',
		1 => '普通快递',
		2 => '送货上门' 
	];
			
	// 订单来源
	public $orderSource = [ 
		1 => '线上订单',
		2 => '线下订单' 
	];

	function viewModel(){
		$fields = [
				'CrowdfundingOrderMakefile' => [
					"_as" => 'com',
					'_type' => 'left',	
					'*'	
				],
				'Crowdfunding' => [
					"_as" => 'cd',
					'_type' => 'left',	
					'_on' => 'com.fk_cr_id = cd.cr_id',	
					'cr_name' => 'jt_com_crowdfunding_name' 
				],
				'CrowdfundingOrder' => [
					"_as" => 'co',
					'_type' => 'left',
					'_on' => 'co.fk_com_ordersn = com.jt_com_ordersn',
					'cor_delivery_type', //订单配送方式
					'cor_store_id'
				],
				'User' => [
					"_as" => 'u',
					'_on' => 'u.uid = com.jt_com_uid',	
					'username'	,
					'aliasname'	,
					'mobile'	,
					'email',
					'is_inside_user', // 是否内部员工
					'_type' => 'left'
				],
				'OrderReceipt' => [
					"name" => 'receipt_name',
					'mobile' => 'receipt_mobile',
					"_as" => "ort",
					'_on' => "ort.order_id = cast(com.jt_com_id as char)",
					'_type' => 'left'
				],
				'Stores' => [
					'name' => 'store_name',
					'_as' => 's',
					'_on' => 's.stores_id = co.cor_store_id'
				]
		];
		return $this->dynamicView($fields)->distinct(true);
	}
	/**
	 * 将订单状态转化成状态名称
	 * @param int $statusInt 订单状态
	 */
	function statusToString($statusInt){
		if(!$this->statusStringArr[$statusInt]){
			return '状态码有误!';
		}
		return $this->statusStringArr[$statusInt];
	}

	/**
	 * 导出订单
	 * @param array $lists 要导出的数据  	
	 */
	public function exportExcel($lists) {
		$excel = new \Admin\Org\Util\ExcelComponent ();
		$excel = $excel->createWorksheet();
		$excelName = "众筹订单导出_"; // 导出文件前缀

		// 表设置
		$excel->advancedHead(
			[
				[
					'title' => '主订单号',		//	表头名称
					'width' => '40'				//	列宽
				],
				[
					'title' => '子订单号',
					'width' => '60'
				],
				[
					'title' => '下单时间',
					'width' => '40'
				],
				[
					'title' => '众筹项目名',
					'width' => '50'
				],
				[
					'title' => '用户名',
					'width' => '25'
				],
				[
					'title' => '用户电话',
					'width' => '25'
				],
				[
					'title' => '项目方案',
					'width' => '50'
				],
				[
					'title' => '金额',
					'width' => '20'
				],
				[
					'title' => '份数',
					'width' => '15'
				],
				[
					'title' => '订单来源',
					'width' => '30'
				],
				[
					'title' => '配送方式',
					'width' => '30'
				],
				[
					'title' => '地址/门店',
					'width' => '30'
				]
			]
		);

		$data = array ();
		$key = 0;
		foreach ( $lists as  $v ) {
			$data[$key]["orderSn"] = $v["jt_com_ordersn"];	// 主订单号
			$data[$key]["subOrderSn"] = $v ["subOrder_sn"];	// 子订单号
			$data[$key]["addTime"] = date('Y-m-d H:i:s', $v['jt_com_add_time'] ); // 下单时间
			$data[$key]["crowdfundingName"] = $v ['jt_com_crowdfunding_name'];	// 众筹项目名
			$data[$key]["userName"] = $v['username'];	// 用户名
			$data[$key]["mobile"] = $v['mobile'];	// 用户电话
			$data[$key]["crowdfundingPlan"] = $v['jt_com_crowdfunding_plan'];	// 项目方案
			$data[$key]["money"] = $v ["jt_com_money"];	// 金额
			$data[$key]["goodsTotal"] = $v ["goods_total"];	// 份数
			$data[$key]["type"] = $this->orderSource[$v["jt_com_type"]]; // 订单来源
			$data[$key]["deliveryType"] = $this->shippingType[$v ['cor_delivery_type']]; // 配送方式 
			$data[$key]["storeName"] = $v ["store_name"];	// 门店地址
			$key++;
		}
		$excel->listData( $data, array (
				"orderSn", // 主订单号
				"subOrderSn", // 子订单号
				"addTime", // 下单时间
				"crowdfundingName", // 众筹项目名
				"userName",	// 用户名
				"mobile",	// 用户电话
				"crowdfundingPlan", // 项目方案
				"money",	// 金额
				"goodsTotal",	// 份数
				"type", // 订单来源
				"deliveryType", // 配送方式 
				"storeName" // 门店地址 
		));
		$excel->output($excelName . date('YmdHis') . ".xlsx" );
	}

}