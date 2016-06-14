<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class ErrorController extends HomeBaseController{
	function index(){
		header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
		$this->display();
	}
}