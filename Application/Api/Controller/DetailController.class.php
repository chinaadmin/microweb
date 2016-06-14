<?php
/**
 * 商品详情接口
 * @author xiongzw
 * @date 2015-07-01
 */
namespace Api\Controller;
use Common\Model\SharedModel;
class DetailController extends ApiBaseController{
	/**
	 * 商品详情
	 *      <code>
	 *         goods_id 商品id
	 *      </code>
	 */
      public function detail() {
		$goods_id = I ( "post.goods_id", 0, 'intval' );
		if ($goods_id) {
			$home_model = D("Home/Detail");
			$data = $home_model->getById ( $goods_id );
			$detail_model = D ( "Api/Detail" );
			$goods = $detail_model->formatData ( $data );
			//商品优惠信息
			$promtions = D('User/Promotions')->verifyGoods([$goods_id],SharedModel::SOURCE_WEIXIN.",".SharedModel::SOURCE_MOBILE);
			$promtions_goods = $promtions[$goods_id];
			if(!empty($promtions_goods)){
				$goods['price'] = discountAmount($data['price'],$promtions_goods['discount']);
				$goods['promtions_discount'] = $promtions_goods['discount'];
				$goods['promtions_limit'] = $promtions_goods['limit'];
			}
			//$norms = D ( 'Admin/Goods' )->getNorms ( $goods_id );
			$norms = $this->_norms($goods_id, $promtions_goods['discount']);
			if (array_filter ( $norms )) {
				$norms ['norms_value'] = $detail_model->formatNorms ( $norms ['norms_value'] );
			}
			foreach($norms['norms_attr'] as &$v){
				unset($v['goods_id']);
				unset($v['goods_norms_no']);
			}
			$goods ['norms'] = $norms;
			$attr = $home_model->attrs ( $goods_id ); // 商品属性
			$attr = $detail_model->formatAttr ( $attr );
			$goods ['attr'] = $attr;
			//判断是否收藏
			$goods['isCollect'] = 0;
			$token = I("post.token","");
			if($token){
				 $result = D('User/Token')->device()->auth($token);
				 if($result['uid']){
				 	if(D("Home/Collect")->hasCollect($goods_id,$result['uid'])){
				 		$goods['isCollect'] = 1;
				 	}
				 }
			}
			//点击量
			$home_model->clickAmount($goods_id,$this->user_id,2);
			$help = D("Admin/Article")->getByCode("2015061147442554","content");
			$goods['help'] = $help['content'];
			$this->ajaxReturn ( $this->result->success ()->content ( [ 
					'goods' => $goods 
			] ) );
		}else{
			$this->ajaxReturn($this->result->error("商品id不能为空！","PARAM_EMPTY"));
		}
	}
	
	/**
	 * 规格数据处理
	 */
	private function _norms($goods_id,$discount){
		$model = D('Admin/Goods');
		$norms = $model->getNorms($goods_id);
		if(!empty($discount)) {
			$norms['norms_attr'] = array_map(function ($info) use ($discount) {
				$info['goods_norms_price'] = discountAmount($info['goods_norms_price'], $discount);
				return $info;
			}, $norms['norms_attr']);
		}
		return $norms;
	}
}