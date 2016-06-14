<?php
/**
 * 统计报表模型
 * @author xiongzw
 * @date 2015-09-22
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ReportController extends AdminbaseController{
	protected $curent_menu = '';
	/**
	 * 销售概况
	 */
	public function sale(){
		$this->curent_menu = "Report/sale";
		$time = $this->_time();
		$source = I('request.source',0,'intval');
		$data = D("Admin/Report")->getSale($time['start_time'],$time['end_time'],$source);
		$report = $data['report'];
		unset($data['report']);
		$this->assign('source',$source);
		$this->assign("data",json_encode($data));
		$this->assign('report',$report);
		$this->assign('report_str',json_encode($report));
		$this->assign('tickInterval',D("Admin/Report")->tickInterval(count($data)));
		$this->display();
	}
	
	/**
	 * 按条件查询
	 */
	public function _time(){
		//最近7天
		$data['start_time'] = strtotime("-6 day",time());
		$data['end_time'] = time();
		$day = I("request.day",'7','');
		if($day==30){
			$data['start_time'] = strtotime("-29 day",time());
		    $data['end_time'] = time();
		}
		if($day=='year'){
			$data['start_time'] = strtotime("-364 day",time());
			$data['end_time'] = time();
		}
		if($day=='quarter'){
			 $season = ceil((date('n'))/3)-1;//上季度是第几季度
             $data['start_time'] = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y'))));
             $data['end_time'] = strtotime(date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'))));
		}
		$start_time = I("request.start_time",'');
		$end_time = I("request.end_time",'');
		if($start_time && $end_time){
			$data['start_time'] = strtotime($start_time);
			$data['end_time'] = strtotime($end_time);
			$this->assign("start_time",$start_time);
			$this->assign("end_time",$end_time);
		}else{
			$this->assign('day',$day);
		}
		return $data;
	}
	
	/**
	 * 导出
	 */
	public function export(){
		$data = I('post.data');
		$data = html_entity_decode($data);
		$data = json_decode($data,true);
		D("Admin/Report")->exportSale($data);
	}
	
	
}
