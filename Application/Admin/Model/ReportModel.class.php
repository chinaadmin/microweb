<?php
/**
 * 统计报表模型
 * @author xiongzw
 * @date 2015-09-22
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class ReportModel extends AdminbaseModel{
	Protected $autoCheckFields = false;
	/**
	 * 根据条件获取销售概况
	 */
	public function getSale($start_time,$end_time,$source=0){
		$where = array(
				"_string"=>$start_time."<=add_time  AND add_time<".($end_time+24*3600)
		);
		if($source){
			$where['source'] = $source;
		}
		$data_place = M("Order")->field("add_time,order_amount,status")->where($where)->select();
		$where['pay_status'] = array('in',array('1','2'));
		$data = M("Order")->field("add_time,money_paid,pay_time")->where($where)->select();
	    $return_array = $this->getTimeData($start_time, $end_time);
	    $report = array();
	    foreach($return_array as $key=>&$v){
	    	$place = 0;  //下单数
	    	$pay_num = 0; //支付笔数
	    	$pay_money = 0; //支付金额
	    	$amount_money=0;//当日下单总额
	    	$order_money=0;//当日下单当日付款
	    	$vaild_order = 0 ;//有效订单
	    	$pay_order = 0;//当日下单当日付款数
	    	foreach($data_place as $vo){
	    		if(date('Y-m-d',$vo['add_time'])==$v){
	    			$place++;
	    			$amount_money+=$vo['order_amount'];
	    			if(in_array($vo['status'],array(0,1,6))){
	    				$vaild_order++;
	    			}
	    		}
	    	}
	    	foreach($data as $vs){
	    		if(date('Y-m-d',$vs['add_time'])==$v){
	    			$pay_num++;
	    			$pay_money+=$vs['money_paid'];
	    			if(date('Y-m-d',$vs['add_time'])==date('Y-m-d',$vs['pay_time'])){
	    				$order_money+=$vs['money_paid'];
	    				$pay_order++;
	    			}
	    		}
	    	}
	    	$time = $v;
	    	$v = array(
	    	   'place'=>$place,
	    	   'pay_num'=>$pay_num,
	    	   'pay_money'=>$pay_money,
	    	    'time'=>$v		
	    	);
	    	$report[$key] = array(
	    			'place'=>$place,
	    			'pay_money'=>$pay_money,
	    			'amount_money'=>$amount_money,
	    			'order_money'=>$order_money,
	    			'payment_ratio'=>(($order_money/$amount_money)*100)."%",//当日付款比例
	    			'vaild_order'=>$vaild_order,//当日有效订单
	    			'pay_order'=>$pay_order,//当日下单当日付款订单数
	    			'payment_order'=>(($pay_num/$place)*100)."%",
	    			'time'=>$time
	    	);
	    }
	    $return_array['report'] = $report;
	    return $return_array;
	}
	/**
	 * 获取查询天数
	 * @param  $start_time 开始时间
	 * @param  $end_time   结束时间
	 */
	private function getTimeData($start_time,$end_time){
		$diff_day = ceil(($end_time-$start_time)/(24*3600))+1;
		$dataTime = array();
		for($i=-1;$i<=$diff_day;$i++){
			$time = strtotime("+".$i." day",$start_time);
			if($start_time<=$time && $time<=$end_time){
				$time = date('Y-m-d',$time);
				$dataTime[] = $time;
			}
		}
		return $dataTime;
	}	
	
	/**
	 * 计算刻度
	 */
	public function tickInterval($count,$maxWidth=10){
		$tickInterval = ($count/$maxWidth);
		//return $tickInterval>1?$tickInterval:1;
		if($tickInterval>5){
			$tickInterval = 5;
		}
		return $tickInterval>1?$tickInterval:1;
		
	}
	/**
	 * 导出销售单
	 */
	public function exportSale($data){
		$excel = new \Admin\Org\Util\ExcelComponent ();
		$excel = $excel->createWorksheet ();
		$excel->head ( array (
				'日期',
				'当日有效付款（元）',
				'当日下单当日付款（元）',
				'当日下单总额（元）',
				'当日付款比例',
				'当日有效订单数',
				'当日下单当日付款订单数',
				'当日订单总数',
				'当日已付订单比例' 
		) );
		$excel->listData ( $data, array (
				'time',
				'pay_money',
				'order_money',
				'amount_money',
				'payment_ratio',
				'vaild_order',
				'pay_order',
				'place',
				'payment_order'
		) );
		$excel->output ( "销售单.xlsx" );
	}
}