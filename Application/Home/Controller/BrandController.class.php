<?php
/**
 * 品牌详情
 * @author xiongzw
 * @date 2015-04-28
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;
class BrandController extends HomeBaseController{
	protected $brand_model;
	public function _initialize(){
		parent::_initialize();
		$this->brand_model = D('Home/Brand');
	}
	/**
	 * 品牌详情
	 */
	public function index(){
		$id = I('request.brand',0,'intval');
		//商品详情
		$brand = $this->brand_model->getById($id);
		$brand['desc'] = html_entity_decode($brand['desc']);
		//$lists = $this->brand_model->goodsByBrand($id);
		$model = D('Home/List')->viewModel();
		$where = array(
				'brand_id'=>$id
		);
		$lists = $this->lists($model,$where,"GoodsStatistics.sales DESC");
		D('Home/list')->getThumb($lists,1);
		$lists = D ( 'Home/List' )->promotionPrice($lists,SharedModel::SOURCE_PC);
		$this->assign("lists",$lists);
		$this->assign('brand',$brand);
		$this->display();
	}
}