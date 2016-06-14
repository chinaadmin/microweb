<?php
/**
 * 首页模型
 * @author xiongzw
 * @date 2015-04-24
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class HomeModel extends HomebaseModel{
	protected  $tableName = "goods";
	/**
	 * 获取商品
	 * @param string $where 
	 * @param string $order
	 * @param string $limit
	 * @return array
	 */
	public function getGoods($where="",$order='',$limit=''){
		$list_model = D('Home/List');
		$model = $list_model->viewModel();
		$data = $model->where($where)->order($order)->limit($limit)->select();
		$list_model->getThumb($data,1);
		return $data;
	}
	
	/**
	 * 获取品牌 
	 * @param number $limit
	 */
	public function getBrands($limit=0){
		return D('Admin/Brand')->order("sort desc")->limit($limit)->getBrands();
	}
	
	/**
	 * 获取展位商品
	 * @param $order 排序方式
	 * @param $limit 商品个数
	 * @param Integer $type 0:PC 1:手机
	 */
	public function featGoods($order,$limit,$position=2,$type=0){
		if($type){
			$feat = D("Admin/Feat")->getMobilelFeats($position);
		}else{
			$feat = D("Admin/Feat")->getFeats($position,"feat_name,feat_id");
		}
	    if($feat){
			$feat_ids = array_column($feat, "feat_id");
			$data = D("Admin/Feat")->getGoodsFeat($feat_ids);
			foreach($feat as $key=>&$v){
				$goods = array();
				foreach($data as $vo){
					if($v['feat_id'] == $vo['feat_id']){
						$goods[] = $vo['goods_id'];
					}
				}
				if($goods){
					$where = array(
							"goods_id"=>array("in",$goods)
					);
					$goods_data = $this->getGoods($where,$order,$limit);
				}
				if(empty($goods_data)){
					unset($feat[$key]);
				}else{
					$v['goods'] = $goods_data;
				}
			}
	    }
		return $feat;
	}
	
	 /**
     * 获取正常促销活动列表
     * @param null|int $type 类型
     * @param null $source 平台:0为全部,3:电脑 1:微商城 2:手机
     * @param null int $show_type 显示位置 1：首页板块
     * @return mixed
     */
	public function getActivity($type=null,$source=null,$show_type=null,$order='sort desc',$limit=10){
		$promptions = D("User/Promotions")->getNormalList($type,$source,$show_type);
		$return_data = array();
		foreach($promptions  as $key=>$v){
			if(!$v['all_goods']){
				$goods = M("PromotionsGoods")->where(['promotions_id'=>$v['id']])->select();
				$goods_ids = array_column($goods, "goods_id");
				$where = array (
						'goods_id' => array (
								'in',
								$goods_ids 
						) 
				);
				$goods_data = $this->getGoods($where,$order,$limit);
				$goods_data = D("Api/Home")->formatPosition($goods_data);
				$param_arr = json_decode($v['param'], true)[0];
				//if($v['time_type']==1){
					foreach ($goods_data as &$vs){
						foreach ($goods as $vo){
							if($vs['goodsId'] == $vo['goods_id']){
								$discount = $vo['discount']?$vo['discount']:$param_arr['content']['discount']['val'];
								$vs['startTime'] = $vo['start_time'];
								$vs['endTime'] = $vo['end_time'];
								$vs['promotionPrice']=discountAmount($vs['price'],$discount); //促销价
								$vs['promotionId']=$v['id'];
							    $vs['discount'] = $discount;
							}
						}
					}
				//}
			}
			$return_data[$key] = array(
					'promotionId'=>$v['id'],
					'name'=>$v['name'],
					'timeType'=>$v['time_type'],
					'startTime'=>$v['start_time'],
					'endTime'=>$v['end_time'],
					'goods'=>$goods_data,
					'type'=>$v['type']
					
			);
		}
		return $return_data;
	}
}