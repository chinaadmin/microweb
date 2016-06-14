<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class IndexController extends HomeBaseController {
	protected $home_model;
	public function _initialize(){
		parent::_initialize();
		$this->home_model = D('Home/Home');
	}

    public function index(){
    	
    	//优惠精选
    	/* $where = array(
    			"1&featured>0"
    	);
    	$data = $this->home_model->getGoods($where,'sort desc,sales desc,Goods.add_time',C("JT_CONFIG_WEB_RECOM_CONFIG_BOUT_NUM")); */
    	$data= $this->home_model->featGoods('sort desc,sales desc,Goods.add_time',C("JT_CONFIG_WEB_RECOM_CONFIG_BOUT_NUM"));
    	$this->assign('lists',$data);
    	//品牌
    	$brands = $this->home_model->getBrands(C("JT_CONFIG_WEB_BRAND_NUM"));
    	$this->assign('brands',$brands);
        //首页标志
        $this->assign('web_is_default',1);

        //爆款列表
        $this->assign('hots_lists',D('Home/Recommend')->defaultHotList());
        $this->display();
    }
     public function activity(){
        $this->display();
    }
   
}