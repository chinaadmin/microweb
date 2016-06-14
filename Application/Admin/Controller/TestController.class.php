<?php
/**
 * * 测试用类
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Model;
class TestController extends BaseController{
	function index(){
		echo phpinfo();
	}
}