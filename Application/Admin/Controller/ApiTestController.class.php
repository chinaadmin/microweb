<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ApiTestController extends AdminbaseController{
	function index(){
		header('Content-type:text/html;charset=utf8');
		$origins = I('start');
		$destinations = I('end');
		if(!$destinations || !$destinations){
			echo '传参错误';
			exit;
		}
		$obj = new \Common\Org\Util\BaiduMap();
		$res = $obj->calcLong($origins,$destinations,1);
	}
}