<?php
namespace Activity\Controller;
use Api\Controller\ApiBaseController;
class ActivityBaseController extends ApiBaseController{
	protected $code_file = 'activity';
	public function _initialize(){
		parent::_initialize();
		header ( 'Access-Control-Allow-Origin: *' );
		header ( 'Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,' );
	}
}