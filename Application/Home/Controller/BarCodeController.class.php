<?php
namespace Home\Controller;
class BarCodeController {
	function index (){
		$text = I('text','0123456790001','trim');
		include_once APP_PATH.'Common/Org/Util/BarCode.class.php';
		$code = new \BarCode();
		$code->showCode($text);
	}
}