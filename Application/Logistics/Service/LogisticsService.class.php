<?php
//快递100快递单订阅服务类
namespace Logistics\Service;
class LogisticsService extends BaseService{
	//发起订阅
	function callKauaiDi100($company,$number){
		$company = I('company',$company,'trim');
		$number = I('number',$number,'trim');
		$from = I('from','','trim');
		$to = I('to','','trim');
		/*
		 	*$company = 'yunda';
			* $number = '3100447289887';
			* $from = '上海嘉定区';
			* $to = '广东深圳南山区';
			*/
		if(!$company){
			return 'LOGISTICS_COMPANY_REQUIRE';
		}
		if(!$number){
			return 'LOGISTICS_NUMBER_REQUIRE';
		}
		//如果已经订阅且成功直接返回成功
		$se_subscribe_status = M('subscribe_express')->where(['se_company' => $company,'se_express_bill' => $number])->getField('se_subscribe_status');
		if($se_subscribe_status){
			return 'SUCCESS';
		}
		//发起订阅
		$post_data = array();
		$post_data["schema"] = 'json' ;
		//callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
		$post_data["param"] = '{"company":"'.$company.'", "number":"'.$number.'","from":"'.$from.'", "to":"'.$to.'", "key":"UGolkwKB6632", "parameters":{"callbackurl":"http://api.tp-bihaohuo.cn/Logistics/kauaiDi100CallBack"}}';
		$url='http://www.kuaidi100.com/poll ';
		$o="";
		foreach ($post_data as $k=>$v){
			$o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
		}
		$post_data=substr($o,0,-1);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//如果成功只将结果返回，不自动输出任何内容。
		$result = curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）
		curl_close($ch);
		//处理订阅结果
		return $this->dealCall($result,$company,$number);
	}
	/**
	 * 处理订阅返回结果
	 * @param json $res 格式如:{"result":"true","returnCode":"200","message":"提交成功"}
	 * @param String $company 公司名
	 * @param String $number 快递单号 
	 */
	private function dealCall($res,$company,$number){
		/*
			result: "true"表示成功，false表示失败
			returnCode:
			200: 提交成功
			701: 拒绝订阅的快递公司
			700: 订阅方的订阅数据存在错误（如不支持的快递公司、单号为空、单号超长等）
			600: 您不是合法的订阅者（即授权Key出错）
			500: 服务器错误（即快递100的服务器出理间隙或临时性异常，有时如果因为不按规范提交请求，比如快递公司参数写错等，也会报此错误）
			501:重复订阅（请格外注意，501表示这张单已经订阅成功且目前还在跟踪过程中（即单号的status=polling），快递100的服务器会因此忽略您最新的此次订阅请求，从而返回501。一个运单号只要提交一次订阅即可，若要提交多次订阅，请在收到单号的status=abort或shutdown后隔半小时再提交订阅，详见本文档第7页“重要提醒”部份说明）
			*/
		//保存数据库
		$res = json_decode($res,true);
		$m = M('subscribe_express');
		$data['se_company'] = $company;
		$data['se_express_bill'] = $number;
		$data['se_subscribe_status'] = $res['result'] ? 1 : 0;
		$data['se_return_code'] = $res['returnCode'];
		$data['se_return_message'] = $res['message'];
		$data['se_update_time'] = NOW_TIME;
		//查看是否已经订阅过 
		if($id = $m->where(['se_company' => $company,'se_express_bill' => $number])->getField('se_id')){
			$data['se_id'] = $id;
			$m->save($data);
		}else{
			$data['se_add_time'] = NOW_TIME;
			$m->add($data);
		}
		if(!$res['result']){ //订阅失败
			return 'LOGISTICS_SUBSCRIBE_FAIL';
		}
		return 'SUCCESS';
	}
	//物流查询订阅回调 servlet
	function kauaiDi100CallBack(){
		header("Content-Type:text/html;charset=utf-8");
		//订阅成功后，收到首次推送信息是在5~10分钟之间，在能被5分钟整除的时间点上，0分..5分..10分..15分....
		$param=$_POST['param'];
		$res = $this->dealCallBack($param);
		if($res){
			//$param包含了文档指定的信息，...这里保存您的快递信息,$param的格式与订阅时指定的格式一致
			echo  '{"result":"true",	"returnCode":"200","message":"成功"}';
			//要返回成功（格式与订阅时指定的格式一致），不返回成功就代表失败，没有这个30分钟以后会重推
		}else{
			echo  '{"result":"false",	"returnCode":"500","message":"失败"}';
			//保存失败，返回失败信息，30分钟以后会重推
		}
	}
	//处理快递查询回调的数据
	private  function dealCallBack($param){
		/**
		 * $param
		 {
		 "status":"polling",	 监控状态:polling:监控中，shutdown:结束，abort:中止，updateall：重新推送。其中当快递单为已签收时status=shutdown，当message为“3天查询无记录”或“60天无变化时”status= abort ，对于stuatus=abort的状度，需要增加额外的处理逻辑，详见本节最后的说明
		 "billstatus":"got",	 包括got、sending、check三个状态，由于意义不大，已弃用，请忽略
		 "message":"",		 监控状态相关消息，如:3天查询无记录，60天无变化
		 	
			"lastResult":{		  最新查询结果，全量，倒序（即时间最新的在最前）
			"message":"ok",  消息体，请忽略
			"state":"0",     快递单当前签收状态，包括0在途中、1已揽收、2疑难、3已签收、4退签、5同城派送中、6退回、7转单等7个状态，其中4-7需要另外开通才有效，详见章3.3
			"status":"200",         通讯状态，请忽略
			"condition":"F00",		快递单明细状态标记，暂未实现，请忽略
			"ischeck":"0",			是否签收标记，明细状态请参考state字段
			"com":"yuantong",      快递公司编码,一律用小写字母，见章五《快递公司编码》
			"nu":"V030344422",     单号
			"data":[
					{
					"context":"上海分拨中心/装件入车扫描 ",   内容
					"time":"2012-08-28 16:33:19",           时间，原始格式
					"ftime":"2012-08-28 16:33:19",         格式化后时间
					"status":"在途",	       本数据元对应的签收状态。只有在开通签收状态服务（见上面"status"后的说明）且在订阅接口中提交resultv2标记后才会出现
					"areaCode":"310000000000", 本数据元对应的行政区域的编码，只有在开通签收状态服务（见上面"status"后的说明）且在订阅接口中提交resultv2标记后才会出现
					"areaName":"上海市",       本数据元对应的行政区域的名称，开通签收状态服务（见上面"status"后的说明）且在订阅接口中提交resultv2标记后才会出现
			
					},{
					"context":"上海分拨中心/下车扫描 ",     内容
					"time":"2012-08-27 23:22:42",          时间，原始格式
					"ftime":"2012-08-27 23:22:42",        格式化后时间
					"status":"在途",			本数据元对应的签收状态。只有在开通签收状态服务（见上面"status"后的说明）且在订阅接口中提交resultv2标记后才会出现
					"areaCode":"310000000000",  本数据元对应的行政区域的编码，只有在开通签收状态服务（见上面"status"后的说明）且在订阅接口中提交resultv2标记后才会出现
					"areaName":"上海市",       本数据元对应的行政区域的名称，开通签收状态服务（见上面"status"后后的说明）且在订阅接口中提交resultv2标记后才会出现
						}
				]
			}
		}
			* */
/* 		{"status":"abort","billstatus":"","message":"3天查询无记录","lastResult":{"message":"快递公司参数异常：单号不存在或者已经过期","nu":"3100447289887","ischeck":"0","condition":"","com":"yunda","status":"201","state":"0","data":[]}}
		data为空
*/
		if(!$param){
			return false;
		}
// 	M('test')->add(['con' => $param]);
	 	$param = json_decode($param,true);
		//记录到数据库中
		$lastResult = $param['lastResult'];
		$data['fk_se_id'] = M('subscribe_express')->where(['se_company' => $lastResult['com'],'se_express_bill' => $lastResult['nu']])->getField('se_id');
		if(!$data['fk_se_id']){
				return false;
		}
		//尝试删除旧纪录
		$this->deleOldRecord($data['fk_se_id']);
		$data['sed_listen_status'] = $param['status'];
		$data['sed_listen_message'] = $param['message'];
		$data['sed_com'] = $lastResult['com'];
		$data['sed_express_bill'] = $lastResult['nu'];
		$data['sed_sign_status'] = $lastResult['state'];
		$data['sed_is_check'] = $lastResult['ischeck'];
		$data['sed_add_time'] = NOW_TIME;
		$main_id = M('subscribe_express_detail')->add($data); //主表
		//增加副表
		$all = $lastResult['data'];
		if($all){ 
			$sub_data = [];
			foreach ($all as $v){
				$tmp = [];
				$tmp['fk_sed_id'] = $main_id;
				$tmp['ses_context'] = $v['context'];
				$tmp['ses_add_time'] = strtotime($v['ftime']);
				$tmp['ses_status'] = $v['ses_status'] ? $v['ses_status'] : '' ;
				$tmp['ses_areaCode'] = $v['areaCode'] ? $v['areaCode'] : '';
				$tmp['ses_areaName'] = $v['areaName'] ? $v['areaName'] : '';
				$sub_data[] = $tmp;
			}
			M('subscribe_express_subtabulation')->addAll($sub_data);
		}
		return true;
	}
	/**
	 * 删除已经存在的快递单记录
	 * @param int $subscribe_express_id 订阅id
	 */
	private function deleOldRecord($subscribe_express_id){
			$m = M('subscribe_express_detail');
			$main_id = $m->where(['fk_se_id' => $subscribe_express_id])->getField('se_id');
			if($main_id){
				$m->where(['fk_se_id' => $subscribe_express_id])->delete();
				M('subscribe_express_subtabulation')->where(['fk_sed_id' => $main_id])->delete();
			}	
	}
}