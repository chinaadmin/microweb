<?php
/**
 * 物流管理
 * @author wxb
 * @date 2015-07-17
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class LogisticsController extends AdminbaseController {
	protected $curent_menu = 'Logistics/index';
	public  $model = '';
	function _init(){
		$this->model = D('Logistics');
	}
    public function index(){
    	$this->title = '物流公司列表';
    	$this->lists = $this->lists($this->model);
  		$this->display();
    }
}