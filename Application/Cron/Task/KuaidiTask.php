<?php
use Think\Controller;
use Common\Org\KuaiDi\KuaiDi;
/**
 * 获取快递跟踪信息 
 * @author wxb 
 * @date 2015/11
 */
class KuaidiTask extends Controller {

	/**
	 * 返回信息
	 */
	private $message = '';
	
	/**
	 * 任务主体
	 * @param int $cronId 任务ID
	 */
    public function run($cronId) {
    	/* $obj = new Common\Org\Util\DeBangKuaidi();
    	$res = $obj->traceKuiDi(2);  */
		
    	$obj = new Common\Org\Util\ExpressDelivery();
    	$res = $obj->fetchAll();
		
		//普通物流信息
		$KuaiDi = new KuaiDi();
		$KuaiDi -> submit_data();
    	if($res){
	    	$mes = "计划任务 KuaidiTask.php(更新订单物流跟踪) 执行成功!";
    	}else{
    		$mes = "计划任务 KuaidiTask.php(更新订单物流跟踪) 执行失败!";
    	}
    	$this->message = $mes;
    }
	
    /**
     * 返回信息
     * @return string
     */
    public function getMessage(){
    	return $this->message;
    }
    
}