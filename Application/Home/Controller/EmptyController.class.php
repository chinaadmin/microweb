<?php

namespace Home\Controller;
use Common\Controller\HomeBaseController;
class EmptyController extends HomeBaseController {
	public function index() {
		$this->_404();
	 } 
}