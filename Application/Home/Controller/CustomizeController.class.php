<?php
/**
 * 自定义控制器
 * @author wxb
 * @date 2015-9-18
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class CustomizeController extends HomeBaseController{

	public function _initialize(){
		parent::_initialize();
		$this->model = D ( 'Admin/CustomizePage' );
	}

	function showPage() {
		$urlCode = I ( 'urlCode', 0, 'trim' );
		if(!$urlCode){
			$this->error('传参有误');
			exit;
		}
		$content = $this->model->where(['cg_url' => $urlCode])->getField('cg_content');
		echo htmlspecialchars_decode($content);
	}
}