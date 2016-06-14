<?php
/**
 * 首页检索
 * @author xiongzw
 * @date 2015-04-29
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;
class SearchController extends HomeBaseController{
	/**
	 * 搜索
	 */
	public function search() {
		$keywords = I ( 'request.searchKeywords', '' ,'trim');
		if ($keywords) {
			$where['name'] = D("Home/Search")->searchWord($keywords);
			$listModel = D ( 'Home/List' );
			$model = $listModel->viewModel ();
			$data = $this->lists ( $model, $where, "sales desc,update_time desc" );
			$data = $listModel->promotionPrice($data,SharedModel::SOURCE_PC);
			$listModel->getThumb ( $data, 1 );
			// 获取商品相关分类
			$catIds = array_unique ( array_column ( $data, "cat_id" ) );
			if ($catIds) {
				$cats = D ( 'Home/Category' )->getCatsLevel ( $catIds );
				$this->assign ( "cats", $cats );
			}
			$this->assign ( "lists", $data );
			$this->assign ( "search_word", $keywords );
		}
		$this->display ();
	}
}