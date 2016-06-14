<?php

/**
 * 自定义控制器
 * @author wxb
 * @date 2015-8-31
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CustomizeController extends AdminbaseController {
	protected $model;
	protected $curent_menu = "Customize/index";
	public function _init() {
		$this->model = D ( 'CustomizePage' );
	}
	/**
	 * 自定义页列表
	 */
	public function index() {
		$this->title = '自定义页列表';
		$where = [];
		if($keywords = I('keywords','','trim')){
			$where['cg_title'] = ['like','%'.$keywords.'%'];
		}
		$lists = $this->lists ( $this->model, $where, 'cg_update_time desc' );
		$this->assign ( "lists", $lists );
		$this->assign ( "urlPrefix", $urlPrefix );
		$this->display ();
	}
}