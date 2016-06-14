<?php
/**
 * 商品列表模型
 * @author xiongzw
 * @date 2015-04-22
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class ListModel extends HomebaseModel{
	protected $tableName = 'goods';
	/**
	 * 获取推荐商品
	 */
	public function sales($where, $order = "update_time DESC", $limit = "") {
		/* return $this->scope ( 'goods' )
		        ->join ( "__GOODS_STATISTICS__ ON __GOODS__.goods_id=__GOODS_STATISTICS__.goods_id", "LEFT" )
		        ->where ( $where )
		        ->order ( $order )
		        ->limit ( $limit )
		        ->select (); */
		return $this->viewModel()->scope("goods")->where($where)->order($order)->limit($limit)->select();
	}
	
	/**
	 * 获取附件封面图
	 * @param 数组
	 * @param $key 图片尺寸key值
	 */
	  public function getThumb(Array &$data,$key=1,$columb="attribute_id",$source=null){
	  	if(is_numeric($key) || $key === true){
		 	$thumbSize = $this->size[$key]?$this->size[$key]:"";
	  	}else{
	  		$thumbSize = $key;
	  	}
		$attributes = array_column($data, $columb);
		$attr = array();
		foreach($attributes as $v){
			if(is_numeric($v)){
				$arr = array($v);
			}else{
				$arr = json_decode($v,true);
			}
			if($arr['default']){
			  $attr[] = $arr['default'];	
			}else{
				$attr[] = $arr[0];
			}
		}
		$thumb =  D('Upload/AttachMent')->getAttach($attr,true,true, $thumbSize);
		foreach($data as &$vo){
			foreach($thumb as $v){
				 if(is_numeric($vo[$columb])){ 
					//$vo[$columb] = array($vo[$columb]);
					$attrs =  array($vo[$columb]);
				}else{
					//$vo[$columb] = json_decode($vo[$columb],true);
					$attrs = json_decode($vo[$columb],true);
				}
				if(in_array($v['att_id'],$attrs)){
					if($thumbSize){
					  $vo['thumb'] = $v[$thumbSize];
					}else{
					  $vo['thumb'] = $v['path'];
					}
				}
			}
		}
	}   
	
	/**
	 * 列表促销价格
	 */
	public function promotionPrice($data,$source,$key="goods_id",$price="price"){
		if($data){
			$goods = array_column($data, $key);
			//商品优惠信息
			$promtions = D('User/Promotions')->verifyGoods($goods,$source);
			foreach($data as &$v){
				$promtions_goods = $promtions[$v[$key]];
				if(!empty($promtions_goods)){
					$v[$price] = discountAmount($v[$price],$promtions_goods['discount']);
					$v['promtions_discount'] = $promtions_goods['discount'];
					$v['promtions_limit'] = $promtions_goods['limit'];
				}
			}
		}
		return $data;
	}
	
	/**
	 * 切换动态模型
	 */
	public function viewModel($field=null){
		$viewFields = array (
				'Goods' => array (
						is_null($field['goods'])?'*':$field['goods'],
						'_type'=>"LEFT"
				),
				'GoodsStatistics' => array (
						is_null($field['statis'])?'*':$field['statis'],
						'_on' => 'Goods.goods_id=GoodsStatistics.goods_id' 
				)
		);
		return $this->dynamicView($viewFields)->where( $this->_scope['goods']['where']);
	}
	
	/**
	 * 组合分类查询商品条件
	 * @param  $cat_ids 分类id
	 * @param  $query 查询条件
	 */
	public function getByCat($cat_ids,$query){
		$where = array(
				'cat_id' => array('in',$cat_ids)
		);
		$goods_ids = M("GoodsSubcat")->where($where)->getField("goods_id",true);
		$goods_ids = array_unique($goods_ids);
		$query['cat_id'] = $where['cat_id'];
		if(!empty($goods_ids)){
		 $query['Goods.goods_id'] = array('in',$goods_ids);
		 $query['_logic'] = 'OR';
		 $map['_complex'] = $query;
		}
		$map = empty($map)?$query:$map;
		return $map;
	}
}
