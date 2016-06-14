<?php
/**
 * 商品列表
 * @author xiongzw
 * @date 2015-06-30
 */
namespace Api\Controller;
use Common\Model\SharedModel;
class ListController extends ApiBaseController{
	/**
	 * 商品列表
	 *      <code>
	 *       cat_id 分类id
	 *       feat   推荐展位
	 *       brand_id 品牌id
	 *      </code>
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Api\Controller\ApiBaseController::lists()
	 */
	public function goodsList(){
		$list_model = D("Api/List");
		$where = array();
		$cat_id = I("post.cat_id",0,'trim');
		$feat = I('post.feat',0,'intval');
		$brand_id = I("post.brand_id",0,'intval');
		if($cat_id){
			 $cats = $list_model->getChildCarts($cat_id);
			 //$where['cat_id'] = array('in',$cats);
			 $where = D ( 'Home/List' )->getByCat($cats,$where);
		}
		if($feat){
			//$where['_string'] = $feat."&featured>0";
			$goods = array_column(D("Admin/Feat")->getGoodsFeat($feat,"goods_id"),"goods_id");
			if($goods){
				$where['goods_id'] = array("in",$goods);
			}else{
				$where['goods_id'] = 0;
			}
		}
		if($brand_id){
			$where['brand_id'] = $brand_id;
		}
		$where = $this->_search($where);
		if(empty($where)){
			$this->ajaxReturn($this->result->error("请求参数不能为空!","PARAM_EMPTY"));
		}
		$viewModel = D ( 'Home/List' )->viewModel ();
		$order = $this->_order();
		$data = $this->_lists($viewModel,$where,$order);
		D ( 'Home/List' )->getThumb ( $data['data'], 1);
		$data['data'] = D ( 'Home/List' )->promotionPrice($data['data'],SharedModel::SOURCE_WEIXIN.",".SharedModel::SOURCE_MOBILE);
		$data['data'] = $list_model->formatData($data['data']);
		if(I('post.returnNotJson')){
			return $data;
		}
		$this->ajaxReturn($this->result->success()->content($data));
	}
	
	/**
	 * 根据销量、价格、商家时间排序
	 *                 <code>
	 *                 order 排序条件   
	 *                      example:
	 *                             totalsales|asc
	 *                 </code>
	 * @return Ambigous <string, mixed, NULL>
	 */
	private function _order(){
		$type = array (
				'totalsales' => 'GoodsStatistics.sales',
				'price' => 'Goods.price',
				'winsdate' => 'Goods.add_time'
		);
		$order = I("post.order","",'trim');
		if($order){
			$order = explode("|", $order);
			$order = $type["{$order[0]}"]." ".$order[1];
		}else{
			$order = "GoodsStatistics.sales DESC";
		}
		$order .= ",Goods.sort DESC";
		return $order;
	}
	/**
	 * 关键字搜索
	 * @param  $where
	 *         <code>
	 *         key 关键字
	 *         </code>
	 * @return array
	 */
	private function _search($where){
		$keywords = I('post.key','');
		if($keywords){
			$where['Goods.name'] = D("Home/Search")->searchWord($keywords);
		}
		return $where;
	}
	/**
	 * 获取促销商品列表
	 */
	public function getActivityList(){
		$id = I('request.promotionId',0,'intval');      //促销id
		$promotion = M("Promotions")->where(['id'=>$id])->find();
		$goods = M("PromotionsGoods")->where(['promotions_id'=>$id])->select();
		$data = array();
		if($goods){
			$goods_id = array_column($goods, "goods_id");
			$list_model = D("Api/List");
			$where = array(
					'goods_id'=>array('in',$goods_id)
			);
			$viewModel = D ( 'Home/List' )->viewModel ();
			$order = $this->_order();
			$data = $this->_lists($viewModel,$where,$order);
			D ( 'Home/List' )->getThumb ( $data['data'], 1);
			$lists = $list_model->formatData($data['data']);
			if($promotion['type']==3){
				foreach($lists as &$vs){
					foreach ($goods as $vo){
						if($vs['goodsId'] == $vo['goods_id']){
							if($promotion['time_type']==1){   //单品计时
							 $vs['startTime'] = $vo['start_time'];
							 $vs['endTime'] = $vo['end_time'];
							}
							$vs['promotionPrice']=number_format(($vs['price']*$vo['discount'])/10,2);//促销价
						}
					}
				}
			}
			$data['data'] = array(
					'name'=>$promotion['name'],
					'startTime'=>$promotion['start_time'],
					'endTime'=>$promotion['end_time'],
					'timeType'=>$promotion['time_type'],
					'type'=>$promotion['type']
			);
			if($promotion['type']==5){
				$discount= json_decode($promotion['param'],true);
				$data['data']['discount'] = $discount[0]['content']['discount']['val'];
			}
			$data['data']['goods'] = $lists;
		}
		$this->ajaxReturn($this->result->success()->content($data));
	}
}
