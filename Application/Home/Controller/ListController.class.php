<?php
/**
 * 商品列表
 * @author xiongzw
 * @date 2015-04-22
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;
class ListController extends HomeBaseController{
	protected $list_model;
	public function _initialize() {
		parent::_initialize ();
		$this->list_model = D ( 'Home/List' );
	}
	/**
	 * 通过分类获取商品列表
	 */
	public function goodsLists() {
		//$where['delete_time'] = 0;
		$cat_id = I ( 'request.catId', 0, 'intval' );
		$type=I('request.type',0,'intval');
		if ($cat_id) {
			$cat_model = D('Admin/Category');
			$childs = $cat_model->getChilds ( $cat_id );
			$childs [] = $cat_id;
			 /* $where ['cat_id'] = array (
					'in',
					$childs 
			);  */
			$where = $this->list_model->getByCat($childs,$where);
		}
		$brand_id = I('request.brand',0,'intval');
		if($brand_id){
			$where['brand_id'] = $brand_id;
		}
		$feat = I('request.feat',0,'intval');
		if($feat){
			//$where[] = $feat."&featured>0";
			$goods = array_column(D("Admin/Feat")->getGoodsFeat($feat,"goods_id"),"goods_id");
			if($goods){
				$where['goods_id'] = array("in",$goods);
			}else{
				$where['goods_id'] = 0;
			}
		}
		if ($cat_id || $brand_id || $feat) {
			$order = $this->_querySort ();
			$model = $this->list_model->viewModel ();
			if($type){
				$_REQUEST['r'] = 21;
			}
			$data = $this->lists ( $model, $where, $order, true );
			$this->list_model->getThumb ( $data, 1);
			//计算促销价
			$data = $this->list_model->promotionPrice($data,SharedModel::SOURCE_PC);
			$this->assign ( "lists", $data );
			$this->assign ( 'cat_id', $cat_id );
			if($type){
				$this->_getCats($type,$cat_id);
				$this->assign("catType",$type);
				$this->display("catlist");
				exit;
			}else{
				$this->_feat($where);
			}
		}
		$this->display ( 'index' );
	}
	
	/**
	 * 热卖推荐
	 */
	private function _feat($where=array()){
		$where ['_string'] = "(2&featured)>0";
		$sales = $this->list_model->sales ( $where, 'sales desc,update_time desc', 4 );
		$this->list_model->getThumb ( $sales, 0 );
		$sales = $this->list_model->promotionPrice($sales,SharedModel::SOURCE_PC);
		$this->assign ( "sales", $sales );
	}
	/**
	 * 所有分类
	 */
	private function _getCats($type=1,$cat_id=0){
		$cats = D('Home/Category')->getCats($type,$cat_id);
		$cats = array_map(function ($oneLine)use($cat_id){
				$oneLine['selected'] = 0;
				foreach ($oneLine['child'] as &$val){
					if($val['cat_id'] == $cat_id){
						$oneLine['selected'] = 1;
					}
				}
				return $oneLine;
		}, $cats);
		$this->assign('cats',$cats);
	}
	/**
	 * 商品查询排序
	 */
	private function _querySort() {
		$type = array (
				'totalsales' => 'GoodsStatistics.sales',
				'price' => 'Goods.price',
				'winsdate' => 'Goods.add_time'
		);
		if (sort) {
			 foreach ( $type as $key => $v ) {
			 	$sort = I ( 'request.'.$key,'');
			 	if($sort){
			 		$order = $v." ".$sort;
			 	}
			} 
		}
		if(empty($order)){
			$order = "Goods.add_time DESC";
			$_GET['time'] = 'desc';
		}
		return $order;
	}
}