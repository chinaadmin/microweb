<?php
use Common\Controller\BaseController;
/**
 * 计划任务事例
 */
class CronTask extends BaseController {

	/**
	 * 返回信息
	 */
	private $message = '';
	
	/**
	 * 任务主体
	 * @param int $cronId 任务ID
	 */
    public function run($cronId) {
    	$this->message = "我执行了计划任务事例 CronTask.php！";
        //Log::write($this->message,"NOTICE");
    }
	
    /**
     * 返回信息
     * @return string
     */
    public function getMessage(){
    	return $this->message;
    }
    
}