<?php
/**
 * 快递接口控制器
 * @author wxb
 */
namespace Api\Controller;
class LogisticsController extends ApiBaseController {
	//发起快递100查询订阅
	function callKauaiDi100(){
		$d = D('Logistics/Logistics','Service');
		$resCode = $d->callKauaiDi100();
		//如果订阅失败重新请求三次
		$count = 0;
		while($count < 3 && $resCode != 'SUCCESS'){ //失败连续请求最多三次
			$resCode = $d->callKauaiDi100();
		}
		$this->ajaxReturn($this->result->set($resCode));
	}
	//快递100订阅回调
	function kauaiDi100CallBack(){
		D('Logistics/Logistics','Service')->kauaiDi100CallBack();
	}
	//查询快递跟踪
	function getLogisticsTrace(){
		$orderSn = I('orderSn','','trim');
		if(!$orderSn){ //订单号不为空
			$this->ajaxReturn($this->result->set('LOGISTICS_NUMBER_REQUIRE'));
		}
		$orderId = D('Admin/order')->where(['order_sn' => $orderSn])->getField('order_id');
		if(!$orderId){
			$this->ajaxReturn($this->result->set('LOGISTICS_NUMBER_ERROR'));
		}
		$mail_no = M('order_send')->where(['order_id' => $orderId])->getField('send_num');
		$where['ltr_mail_no'] = $mail_no;
		$logisticsFollowing = M('logistic_trace_record')->where($where)->field(['ltr_remark' => 'remark','ltr_accept_time' => 'accept_time' ])->order('ltr_accept_time')->select();
		foreach ($logisticsFollowing as &$v){
						$v['accept_time'] = date('Y-m-d H:i:s',$v['accept_time']);
		}
		$tmpList = D('Admin/Order')->getGoodsById ( $orderId );
		$goodsList = [];
		foreach ($tmpList as $key => &$val){
			$goodsList[$key]['goods_price'] = $val['goods_price'];
			$goodsList[$key]['number'] = $val['number'];
			$goodsList[$key]['name'] = $val['name'];
			$goodsList[$key]['pic'] = fullPath($val['pic']);
			$goodsList[$key]['norms_value'] = $val['norms_value'];
			$goodsList[$key]['id'] = $val['goods_id'];
		}
		$this->ajaxReturn($this->result->success()->content(['mailNo' => $mail_no,'traceList' => $logisticsFollowing,'goodsList' => $goodsList]));
	}
}