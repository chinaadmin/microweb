<?php
/**
 * 物流信息跟踪 
 * @author wxb
 * @date 2015/07/28
 * @lastmodify 2015/07/29
 */
namespace Common\Org\Util;
use Think\Model;
use Think\Model\RelationModel;
class ExpressDelivery{
	private $AppKey = '';
    private $getWay = 'http://api.kuaidi100.com/api'; 
    private $queryWay = 'http://m.kuaidi100.com/query'; 
    private $logisticModel = null;
    private $curl = null;
    private $sendModel = null;
	function __construct(){
		   header('content-type:text/html;charset=utf-8');
   		   $this->AppKey = '6683aff92eb9be51';
   		   $this->curl = new Curl();
   		   $this->sendModel = D('Admin/SendGoods');
   		   $this->logisticModel = M('logistic_trace_record');
	}
	/**
	 *获取跟踪信息并保存到数据库  快递100 api
	 *@param  string $typeCom 要查询的快递公司代码，不支持中文
	 *@param  string $typeNu 要查询的快递单号，请勿带特殊符号，不支持中文（大小不敏感）
	 *@param string  $type 接口请求方式
	 */
	function fetch($typeCom,$typeNu,$type = 'post'){
		/* $typeCom = 'huitongkuaidi'; //quanfengkuaidi
		$typeNu = '70378241507671';//390021868410 */
		//如果已签收且数据库已保存则不再调第三方查询
		$m = $this->logisticModel;
	    if(($list = $m->where(['ltr_mail_no' => $typeNu])->select()) && $list[0]['ltr_mail_no_status'] == 3){
			return true;
		} 
		$curl = $this->curl;
		$type = 'st_'.strtolower($type);
		$data = [];		
		$data['id'] = $this->AppKey;//身份授权Key，请点 快递查询接口 进行申请（大小敏感）
		$data['com'] = $typeCom;//要查询的快递公司代码，不支持中文
		$data['nu'] = $typeNu;//要查询的快递单号，请勿带特殊符号，不支持中文（大小不敏感）
		$data['show'] = 0;//返回类型。0：返回json字符串，1：返回xml对象，2：返回html对象，3：返回text文本。如果不填，默认返回json字符串
		$data['muti'] = 1;//返回信息数量，1:返回多行完整的信息，0:只返回一行信息。不填默认返回多行
		$data['order'] = 'asc';//排序。desc：按时间由新到旧排列，asc：按时间由旧到新排列。不填默认返回倒序（大小不敏感）
		$res = $curl->$type($this->getWay,$data);
		$res =  json_decode($res,true);
		if(!$res || $res['status'] != 1){ //获取数据失败
			$res = $this->queryApi($typeCom,$typeNu);
			if(!$res){
				return false;
			}
		}
		$this->addToStorage($res);
		return true;
	}
	//接口查询所有物流信息 并纪录到数据库 用于定时任务
	function fetchAll(){
		$m = $this->sendModel;
		$list = $this->findTraceMailArr(); //send_num,lc_code
		foreach($list as $v){
			$this->fetch($v['lc_code'],$v['send_num']);
		}
		return true;
	}
	//将跟踪信息保存到数据库
	private function addToStorage($data){
		$tmpModel = new Model();
		$tmpModel->startTrans();
		$m = $this->logisticModel;
		if($data['state'] === 3){//已签
			$this->sendModel->where(['send_num' => $data['nu']])->save(['send_is_signed' => 2]);
		}
		$m->where(['ltr_mail_no' => $data['nu']])->delete();
		$dataList = [];
		foreach ($data['data'] as $v){
			$tmp = [];
			$tmp['ltr_mail_no'] = $data['nu'];
			$tmp['ltr_mail_no_status'] = $data['state'];
			$tmp['ltr_accept_time'] = strtotime($v['time']);
			$tmp['ltr_remark'] = $v['context'];
			$tmp['ltr_update_time'] = NOW_TIME;
			$dataList[] = $tmp;
		}
		$res = $m->addAll($dataList);
		if($res === false){
			$tmpModel->rollback();
			return;
		}
		$tmpModel->commit();
	}
	/**
	 * 数据库物流信息 
	 * @param array $mail_no 运单号
	 */
	private function storageRecord($mail_no){
		$m = $this->logisticModel;
		return $m->where(['ltr_mail_no' => ['in',(array)$mail_no]])->select();
	}
	private function myCurl($getWay,$data,$type){
		$curl = new Curl();
		$type = 'st_'.strtolower($type);
		$res = $curl->$type($getWay,$data);
		return json_decode($res,true);
	}
	/**
	 * 找出未签收的运单信息
	 */
	private function findTraceMailArr(){
		$viewModel = [
				'order_send' => [
						'_as' => 'os',
						'_type' => 'left',
						'*'
				],
				'logistics_company' => [
						'_as' => 'lc',
						'_on' => 'lc.lc_id = os.logistics',
						'lc_code'
				],
		];
		$m = D('User/User');
		$res = $m->dynamicView($viewModel)->where(['send_is_signed' => 1,'send_num' => ['neq',''],'logistics' => ['neq',0]])->field('send_num,lc_code')->select();
		return $res;
	}
	/**
	 *数据库获取物流信息 
	 * @param array $mail_no_arr 订单号数组
	 */
	function getRecord(array $mail_no_arr,$field = true){
		if(!$mail_no_arr){
			return [];
		}
		return $this->logisticModel->where(['ltr_mail_no' => ['in',$mail_no_arr]])->field($field)->order('ltr_accept_time asc')->select();
	}
	
	/**
	 * 获取快递跟踪信息
	 *@url  http://www.kuaidi100.com/frame/index.html
	 *@param  string $typeCom 要查询的快递公司代码，不支持中文
	 *@param  string $typeNu 要查询的快递单号，请勿带特殊符号，不支持中文（大小不敏感）
	 *@param string  $type 接口请求方式
	 */
	function queryApi($typeCom,$typeNu,$type = 'post'){
		// zhongtong 	372855369574
		$data = [];
		$data['type']  = $typeCom;
		$data['postid']  = $typeNu;
		$data['id'] = 11;//常量
		$data['temp'] = uniqid();
	    $data['valicode'] = '';
		$res = $this->myCurl($this->queryWay, $data, $type);
		if(!$res || $res['status'] != 200){
			return false;
		}
		return $res;
	}
}