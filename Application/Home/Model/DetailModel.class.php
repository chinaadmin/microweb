<?php
/**
 * 商品详情
 * @author xiongzw
 * @date 2015-04-23
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class DetailModel extends HomebaseModel{
	protected $tableName = "goods";
	/**
	 * 通过id获取商品基本信息
	 */
	public function getById($id) {
		$join = "__GOODS_ATTACHED__ att ON goods.goods_id=att.goods_id";
		$data =$this->alias('goods')->join ( $join, "LEFT" )->where("goods.goods_id={$id} AND goods.delete_time=0")->find ();
		$data = $this->getThumb($data);
		$data['statistics'] = M("GoodsStatistics")->where(['goods_id'=>$id])->find();
		if($data['brand_id']){
			$data['brand'] = M('Brand')->where("brand_id={$data['brand_id']}")->find();
			$data['brand']['desc'] = html_entity_decode($data['brand']['desc']);
		} 
		return $data;
	}
	/**
	 * 获取商品图片
	 */
	public function getThumb($data){
		$data['attribute_id'] = json_decode($data['attribute_id'],true);
		$thumb = D('Upload/AttachMent')->getAttach($data['attribute_id'],true,true);
		foreach($thumb as $v){
			$data['thumb'][] = $v['thumb'];
		}
		return $data;
	}
	
	/**
	 * 获取关联产品
	 * @param $id 商品id
	 */
	public function links($id){
		$where = array(
				'goods_id'=>$id
		);
		$linkIds = M('GoodsLink')->where($where)->getField('link_id',true);
		if($linkIds){
			$linkWhere = array(
					'goods_id'=>array('in',$linkIds)
			);
			$data = $this->scope('goods')->where($linkWhere)->select();
			D('Home/List')->getThumb($data,1);
		}
		return $data?$data:"";
	}
	
	/**
	 * 获取商品属性
	 * @param $goods_id 商品id
	 */
	public function attrs($goods_id){
		$data = array();
		$attrs = M('GoodsAttr')->where("goods_id={$goods_id}")->select();
		if ($attrs) {
			// 获取属性信息
			$attr_id = array_unique ( array_column ( $attrs, "attr_id" ) );
			$where = array (
					'attr_id' => array (
							'in',
							$attr_id 
					) 
			);
			$attributes = M ( 'Attribute' )->where ( $where )->select ();
			$arr = array ();
			foreach ( $attrs as &$v ) {
				foreach ( $attributes as &$vs ) {
					if ($v ['attr_id'] == $vs ['attr_id']) {
						if ($vs ['attr_type'] > 0) {
							$v ['arr_type'] = $vs ['attr_type'];
							$arr [$vs ['name']] [] = $v;
							$arr [$vs ['name']] ['attr_type'] = $vs ['attr_type'];
						} else {
							$v ['name'] = $vs ['name'];
							$data ['attr'] [] = $v;
						}
					}
				}
			}
		}
		$data['checkAttr'] = $arr;
		return $data;
	}
	/**
	 * 记录用户浏览记录、商品点击量
	 * @param number $goods_id 商品id
	 * @param number $uid 用户id
	 * @param Int 浏览来源  1：PC 2:mobile
	 */
	public function clickAmount($goods_id,$uid=0,$source=1){
		$ip = get_client_ip();
		$redis_model = D('Common/Redis');
		$redis = $redis_model->getRedis();
		$key = C("DATA_CACHE_PREFIX").'goods_click'.$ip."_".$goods_id;
		if(!$redis->get($key)){
			$redis->set($key,1,0,7200);
			M("GoodsStatistics")->where("goods_id={$goods_id}")->setInc('click',1);
		}
		if($source==1){
			$goods = cookie("goods");
		    $goods[$goods_id] = NOW_TIME;
		    $goods = array_unique($goods);
			if(!$uid){
				if($source==1) cookie("goods",$goods,time());
			}else{
				$data = array();
				$i=0;
				foreach ($goods as $k=>$v){
					$data[$i] = array(
						'uid'=>$uid,
						'goods_id'=>$k,
						'add_time'=>$v,
						'source' => $source
					);
					$i++;
				}
				M("UserHistory")->addAll($data,'',true);
				cookie("goods",null);
			}
		}
	}
	
	/**
	 * 促销商品促销价显示
	 * @param  int $goods_id  商品id
	 * @param  int $price     
	 * @param  int $promotion_id
	 */
	public function promotionDiscount($promotion_id,$goods_id){
		$promotion = D("Admin/Promotions")->scope('normal')->where(['id'=>$promotion_id])->find();
	    if($promotion && $promotion['discount_type']==0){  //判断促销活动是否存在
	    	$where = array(
	    			'promotions_id'=>$promotion_id,
	    			'goods_id'=>$goods_id
	    	);
	    	return  M("PromotionsGoods")->where($where)->getField("discount");
	    }
	}
}