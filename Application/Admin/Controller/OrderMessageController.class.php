<?php
/**
 * 订单消息控制器
 * @author xhk
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class OrderMessageController extends AdminbaseController{
	//查询新订单
	public function queryNewMsg(){
		D('Admin/OrderMessage')->queryTimer($this->user['uid']);
	}

}