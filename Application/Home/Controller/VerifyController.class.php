<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class VerifyController extends HomeBaseController {
	function dealCode(){
		D('Verify','Service')->dealCode();
	}
}