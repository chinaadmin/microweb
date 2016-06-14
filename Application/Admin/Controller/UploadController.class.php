<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class UploadController extends AdminbaseController{
	protected $no_auth_actions = ['editor'];
	public function __call($method, $args){
		$request = I('request.');
		$url = "Upload/Editor/".$method;
		R($url);
	}
}