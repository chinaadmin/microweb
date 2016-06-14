<?php
/**
 * 德邦接口类
 * @author wxb
 * @date 2015/09/7
 */
namespace Common\Org\Util;
class DeBangKuaidi{
	private $sendWay = '';
	private $gateWay = '';
	private $data ;
	private $companyCode = 'EWBJITU' ;
	private $apikey = 'deppontest';
	private $logisticCompanyID = 'DEPPON'; //物流公司ID
	private $orderStatus = [
			'GOT' => 1, //已开单 
			'SIGNSUCCESS' => 2//正常签收/异常签收
	];
	private $time;
	function __construct(){
		header('content-type:text/html;charset=utf-8');
		$data = [];
		$data['companyCode'] = $this->companyCode;
		$this->data = $data;
		$this->time = (int)NOW_TIME.'000';
	} 
	/**
	 *创建订单 	接口名:电子运单下单 
	 * @param array $input
	 * @return array $res
	 */
	function createOrder($input){
		$this->gateWay = 'http://api.deppon.com/dop/order/ewaybillNewSyncSieveOrder.action';
		$params = [];
		$params['logisticCompanyID'] = $this->logisticCompanyID;
		$params['logisticID'] = $input['order_sn']; //由第三方接入商产生的订单号
		$params['orderSource'] = $this->data['companyCode'];
		$params['serviceType'] = 2;	//2．快递在线下单 3、快递线下订单		
		$params['customerCode'] = 'test';	//与德邦crm中的客户编码保持一致
		$params['customerID'] = 'EJT';	//标志下单用户身份的ID和CRM的渠道用户ID一样
		$params['sender']['name'] = $input['senderName'];//发货人名称
		$params['sender']['mobile'] = $input['senderMobile']; //发货人手机
		$params['sender']['province'] = $input['senderProvinceName']; //带行政单位，例：江苏省
		$params['sender']['city'] = $input['senderCityName']; //带行政单位 例如：南京市
		$params['sender']['county'] = $input['senderCountyName']; //区 或 县
		$params['sender']['address'] = $input['senderAddress'];//发货人地址为必填 
		
		$params['receiver']['name'] = $input['receiverName'];//收货人名称	
		$params['receiver']['mobile'] = $input['receiverMobile'];//收货人手机	
		$params['receiver']['province'] = $input['receiverProvince']; //带行政单位，例：江苏省
		$params['receiver']['city'] = $input['receiverCity']; //带行政单位 例如：南京市
		$params['receiver']['county'] = $input['receiverCounty']; //区 或 县
		$params['receiver']['address'] = $input['receiverAddress'];//收货人地址为必填
	    $params['gmtCommit'] = $this->time;//订单提交时间 2012-11-27 18:44:19
		$params['cargoName'] = $input['cargoName']; //货物名称
		$params['payType'] = $input['payType']; //0:发货人付款（现付）1:收货人付款（到付）2：发货人付款（月结）	
		$params['transportType'] = 'PACKAGE'; //PACKAGE： 标准快递;RCP :360特惠件;EPEP:电商尊享;
		$params['deliveryType'] = 1;//0:自提1:送货（不含上楼）2:机场自提3:送货上楼
		$params['backSignBill'] = 2; //0:无需返单2:客户签收单传真返回4: 运单到达联传真返
		if(count($params) != count(array_filter($params))){
			return '传参不完整';
		}
 		$res = $this->circle($params);	
 		/**
 	 {
		"logisticID":"AL353453253",  //订单号
		"mailNo":"1144fafa",
		"result":"true", 
		"resultCode":"1000",
		"uniquerRequestNumber":"3600275537833690"
	}
 		 */
 		return $res; 
	}
	//查询订单
	function queryOrder($count = 1){
		if($count >= 3){
			return;
		}
		$params['logisticCompanyID'] = '';//物流公司ID
		$params['logisticID'] = '';//渠道单号 即订单号
		$res = $this->_curl($param);
		$tmp = $res;
		$res = json_decode($res,true);
		if($res['result'] == 'false'){
			$this->log($tmp,json_encode($params));
			// 再重新发起请求2次
			$res = $this->queryOrder(++$count);
		}
		return $res['result'] ? $res['responseParam'] : false;
	}
	//价格时效查询
	function queryPrice(){
	
	}
	/**
	 * 货物追踪查询 订时查询写入数据库(新订单发货时要手动触发一次)
	 * @param string $mailNo 运单号数组
	 * @param int $type 1:手动调用  2:定时执行
	 */
	function traceKuiDi($type = 2,array $mailNo){
		if($type == 1 && !$mailNo){
			return false;
		}else if($type == 2){
			$mailNo = $this->findTraceMailArr();
		}
		$this->gateWay = 'http://58.40.17.67/dop/order/traceOrder.action';
		$params['logisticCompanyID'] = 'DEPPON';
		$mailNoArr = [];
		foreach ($mailNo as $v){
			$mailNoArr[] = ['mailNo' => $v];
		}
		$params['orders'] = $mailNoArr;
		$this->setWay('post');
		$res = $this->circle($params);
		if($res['result'] == 'false'){
			return false;
		}
		$tmp = $res['responseParam'];
		$tmp = $tmp['orders'];
		$m = M('logistic_trace_record');
		$signedNo = [];
		foreach ($tmp as $v){
			//保存数据库
			$data = [];
			$middel['ltr_mail_no'] = $v['mailNo'];
			$middel['ltr_mail_no_status'] = $this->orderStatus[$v['orderStatus']];
			if($middel['ltr_mail_no_status'] == 2){
				$signedNo[] = $v['mailNo'];
			}
			$middel['ltr_trace_code'] = $v['traceCode'];
			foreach ($v['steps'] as $vv){
				$middel['ltr_accept_time'] = strtotime($vv['acceptTime']); 
				$middel['ltr_remark'] = $vv['remark']; 
				$middel['ltr_update_time'] = NOW_TIME; 
				$data[] = $middel;
			}
			$m->where(['ltr_mail_no' => $v['mailNo']])->delete();
			$m->addAll($data);
		}
// 		变更签收状态
		$this->signMainNo($signedNo, 2);
		return true;
	}
	//网点查询
	function queryKuaiDiPoint(){
		$params['logisticCompanyID'] = 'DEPPON';		
		$params['province'] = ''; //省份		
		$params['city'] = '';		//城市
		$params['county'] = '';//区县
		$params['address'] = '';//详细地址
		$params['matchType'] = '';//0 出发网点、1 自提网点、2 派送网点、3 自提+派送
		$res = $this->circle($params);
		if($res['result'] == 'false'){
			return false;
		}
		$deptInfoList = $res['responseParam']['deptInfoList'];	
		/*
		网点名称	deptName	String	100	是
		网点编码	deptCode	String	32	是	唯一表示
		网点电话	deptTel	String	80	是
		网点地址	deptAddress	String	256	是
		*/
		return $deptInfoList;
	}
	//状态推送
	function sendStatus(){
	
	}
	/**
	 * @param array $data 参数数组
	 * @param string $type 请求类型
	 * @return array $res 返回结果
	 */
	private function _curl($data,$type = 'get'){
		$data = $this->createParameter($data);
// 	myDump($data);exit;
		$curl = new Curl();
		if($this->getWay()){
			$type = $this->getWay();
		}
		$type = 'st_'.strtolower($type);
		$res = $curl->$type($this->gateWay,$data);
// 		myDump($res);exit;
		return $res;
	}
	//保存错误纪录
	private function log($return_json,$params_json){
		$data = [];
		$data['laf_url'] = $this->gateWay;
		$data['laf_param'] = $params_json;
		$data['laf_return_json'] = $return_json;
		$data['laf_add_time'] = NOW_TIME;
		M('logistic_api_fail')->add($data);
	}
	private function circle($params,$end = 1){
		if($end > 3){
			return false;
		}
		$res = $this->_curl($params);
		$tmp = $res;
		$res = json_decode($res,true);
		if($res['result'] == 'false' || !$res['result']){
			$this->log($tmp,json_encode($params));
			// 再重新发起请求2次
			if($tmp = $this->circle($params,++$end)){
					$res = $tmp;
			};
		}
		return $res;
	}
	/**
	 * $mail 运单号
	 * 
	 */
	private function getTrace($mail){
		$res = M('logistic_trace_record')->where(['ltr_mail_no' => $mail])->select();
		return $res;
	}
	private function formatTrace($list){
		$tmp = [];
		$middel['ltr_mail_no'] = $list['mailNo'];
		$middel['ltr_mail_no_status'] = $this->orderStatus[$list['orderStatus']];
		foreach ($list['steps'] as $v){
			$middel['ltr_accept_time'] = strtotime($v['acceptTime']);
			$middel['ltr_remark'] = $v['remark'];
			$tmp[] = $middel;
		}
		return $tmp;
	}	
	/**
	 *生成发送参数数组 
	 * @param array $params
	 * @return array $paramArr 
	 */
	private function createParameter($params){
		/*
		 params	请求参数
		 digest	密文摘要
		 timestamp	当前时间戳 ，当前时间毫秒数
		 companyCode	第三方接入商的公司编码(双方约定，建议公司简拼或者代码，字母大写)
		 */
		$paramArr = [];
		$paramArr['params'] = json_encode($params);
		$paramArr['companyCode'] = $this->companyCode;
		$paramArr['timestamp'] = $this->time;
		$paramArr['digest'] = base64_encode(MD5(json_encode($params).$this->apikey.$paramArr['timestamp']));
		return $paramArr;
	}
	/**
	 * 找出未签收的运单号
	 */
	private function findTraceMailArr(){
// 		return ['5081217712'];
		$mailNoArr = M('order_send')->where(['send_is_signed' => 1])->getField('send_num',true);
		return array_unique($mailNoArr);
	}
	/**
	 * 变更签收状态
	 */
	private function  signMainNo($mailNoArr,$status){
		if(!$mailNoArr){
			return;
		}
		M('order_send')->where(['send_num' => ['in',$mailNoArr]])->save(['send_is_signed' => $status]);
	}
	private function setWay($type = ''){
		$this->sendWay = $type;
	}
	private function getWay(){
		return $this->sendWay;
	}
}