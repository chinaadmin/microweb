<?php
/**
 * 广告逻辑类
 * @author cwh
 * @date 2015-05-05
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class AdminLogController extends AdminbaseController {

    //账户管理
    public function index() {
    	$mix_keywords = I('mix_keywords','','trim');
    	$this->title = '后台操作日志';
    	$model = D('Admin/AdminLog')->getModel();
    	$where  = [];
    	$search = [];
    	if($mix_keywords){
    		$map['adlog_uid|pk_adlog'] = $mix_keywords;
    		$map['username'] = ['like','%'. $mix_keywords.'%'];
    		$map['_logic'] = 'or';
    		$where['_complex'] = $map;
    		$this->mix_keywords = $mix_keywords;
    	}
    	if(($start_time = I('start_time','','trim')) && ($end_time = I('end_time','','trim'))){
    		$search['start_time'] = $start_time;
    		$search['end_time'] = $end_time;
    		$start_time = strtotime($start_time);
    		$end_time = strtotime($end_time);
    		$where['adlog_add_time'] = ['between',[$start_time,$end_time]];
    	}
    	$this->search = $search;
    	$this->list = $this->lists($model, $where, 'adlog_add_time desc');
        $this->display();
    }
	public function loginlog(){
		$this->title = '后台登入日志';
		$key = I('mix_keywords','','trim');
		$where = [];
		$map = [];
		$search = [];
		if($key){
			$this->mix_keywords = $key;
			$map['pk_adlog'] = $key;
			$map['adlog_account'] = ['like','%'.$key.'%'];
			$map['_logic'] = 'or';
			$where['_complex'] = $map;
		}
		if(($start_time = I('start_time','','trim')) && ($end_time = I('end_time','','trim'))){
			$search['start_time'] = $start_time;
			$search['end_time'] = $end_time;
			$start_time = strtotime($start_time);
			$end_time = strtotime($end_time);
			$where['adlog_add_time'] = ['between',[$start_time,$end_time]];
		}
		$this->search = $search;
		$this->list = $this->lists(M("admin_loginlog"),$where);
		 $this->display();
	}
}