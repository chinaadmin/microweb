<?php
/**
 * 版本接口
 * @author xiongzw
 * @date 2015-11-02
 */
namespace Api\Controller;
use Api\Controller\ApiBaseController;
class VersionController extends ApiBaseController{
	/**
	 * 检查版本更新
	 */
	public function version(){
		$version = I('post.version','1.0.0');//版本号
		$data = array(
				'version'=>"1.0.0",
				'isUpdate'=>'0',
				'hasUpdate'=>'0',
				'updateTime'=>'2015-11-02',
				'content'=>''
		);
		$this->ajaxReturn($this->result->content(['date'=>$data])->success());
	}
}