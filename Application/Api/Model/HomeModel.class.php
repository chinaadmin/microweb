<?php
/**
 * 接口首页模型
 * @author xiongzw
 * @date 2015-06-30
 */
namespace Api\Model;
class HomeModel extends ApiBaseModel{ 
	Protected $autoCheckFields = false;
	/**
	 * 幻灯片banner
	 */
	public function banner($field=true){
		$adp_model = D('Admin/AdPlace');
		$adp_info = $adp_model->scope()->where(['adp_id'=>3])->field($field)->find();
		if(empty($adp_info)){
			return '';
		}
		//$ad_lists = D('Admin/Ad')->getListsToInfo($adp_info);
		$ad_lists = D('Admin/Ad')->scope()->where(['adp_id'=>$adp_info['adp_id']])->order('sort desc')->select();
		$attr_ids = array_column($ad_lists,'mobile_photo');
		$attach = D('Upload/AttachMent')->getAttach($attr_ids);
		$attr_ids = array_column($attach,'path',"att_id");
		return array_map(function($data) use($attr_ids){
			$data['mobile_photo'] = $attr_ids[$data['mobile_photo']];
			return $data;
		},$ad_lists);
	}
	
	/**
	 * 广告位
	 * @param string $field
	 * @return string|unknown
	 */
	public function advent($field=true){
		$adp_model = D('Admin/AdPlace');
		$adp_info = $adp_model->scope()->where(['adp_id'=>7])->field($field)->find();
		if(empty($adp_info)){
			return '';
		}
		$ad_lists = D('Admin/Ad')->getListsToInfo($adp_info);
		return $ad_lists;
	}
	/**
	 * 格式化banner列表
	 * @param array $banner
	 * @return multitype:multitype:unknown
	 */
	public function formatBanner(Array $banner){
		$return_array = array();
		foreach($banner as $key=>$v){
			$return_array[$key]=array(
					"name"=>$v['name'],
					"photo"=>fullPath($v['mobile_photo']),
					"bdColor"=>$v['bd_color'],
					"urlType"=>$v['url_type'],
// 					"linkPoint"=>$v['link_point'],
// 					"linkId"=>$v['link_id'],
// 					"url"=>$v['url']
			);
			if($v['url_type']==1){
				$return_array[$key]["linkPoint"]=$v['link_point'];
				$return_array[$key]["linkId"]=$v['link_id'];
			
			}
			if($v['url_type']==2){
				$return_array[$key]["url"]=fullPath($v['url']);
				
			}
		}
		return $return_array;
	}
	
	/**
	 * 格式化爆款
	 * @param array $recommend
	 */
	public function formatRecommend(Array $recommend){
		$return_array = array();
		foreach($recommend as $key=>$v){
            $return_array[$key] = array(
            		'photo'=>fullPath($v['photo']),
            		'photoAlt'=>$v['photoAlt'],
            		'marketPrice'=>$v['old_price'],
            		"urlType"=>$v['url_type'],
            		"sales"=>$v['sales'],
            		"price"=>$v['price'],
            		"desc"=>$v['desc'],
            		"goodsId"=>'281'
            );
            if(!empty($v['link_id']) && $v['link_point']==1){
                  $v['goodsId'] = $v['link_id'];
            }
            if($v['url_type']==1){
            	 $return_array[$key]["linkPoint"]=$v['link_point'];
            	 $return_array[$key]["linkId"]=$v['link_id'];
            	 
            }
            if($v['url_type']==2){
            	$return_array[$key]["url"]=fullPath($v['url']);

            	$return_array[$key]["linkPoint"]= $v['link_point']; // 增加外部链接类型
            	$return_array[$key]["linkId"]= $v['link_id'];	// 增加外部链接链接id

            }
		}
		return $return_array;
	}
	/**
	 * 格式化展位推荐俩碧柔
	 * @param array $data
	 * @return multitype:multitype:unknown Ambigous <string, string>
	 */
	public function formatPosition(Array $data){
		$return_array = array();
		foreach($data as $key=>$v){
			$return_array[$key] = array(
					'name' => $v['name'],
					'photo'=>fullPath($v['thumb']),
					'stockNumber'=>$v['stock_number'],
					/* 'sales' => $v['sales'], */
					'price'=>$v['price'],
					'marketPrice'=>$v['old_price'],
					'goodsId' => $v['goods_id']
			);
		}
		return $return_array;
	}
	/**
	 * 格式化品牌列表
	 * @param  array $brand
	 * @return multitype:multitype:unknown Ambigous <string, string>
	 */
	public function formatBrand(Array $brand){
		$return_array = array();
		foreach ($brand as $key=>$v){
			$return_array[$key] = array(
				'brandId'=>$v['brand_id'],
				'name'=>$v['name'],
				'photo'=>fullPath($v['logo'])	
			); 
		}
		return $return_array;
	}
	
	/**
	 * 获取商品分类
	 */
	public function getCarts(){
		$cats = D("Home/Category")->getTopCats("cat_id,name,mobile_photo");
		D("Home/List")->getThumb($cats,"","mobile_photo");
		//获取二级分类
		if($cats){
			$ids = array_column($cats, "cat_id");
			$where = array(
					"pid"=>array("in",$ids)
			);
			$data = M("Category")->where($where)->select();
			D("Home/List")->getThumb($data,"","mobile_photo");
		}
		$return_array = array();
		foreach($cats as $key=>$v){
			if($data){
				foreach($data as $vo){
					if($v['cat_id'] == $vo['pid']){
						$v['child'][] = array(
								'catId'=>$vo['cat_id'],
								'name' => $vo['name'],
								'photo'=>fullPath($vo['thumb'])
						);
					}
				}
			}
			$v['goods_list'] = $this->getGoodsList($v['cat_id']);
			$return_array[$key] = array(
					"catId" => $v['cat_id'],
					"name"=>$v['name'],
					"photo"=>fullPath($v['thumb']),
					"child"=> $v['child'] ? $v['child'] : [],
					"goods_list"=> $v['goods_list'] ? $v['goods_list'] : [],
					'next_level_cat_id' => $tmp 
			);
		}
		/* foreach($cats as &$v){
			$v['catId'] = $v['cat_id'];
			unset($v['cat_id']);
		} */
		return $return_array;
	}
	/**
	 * 获取某一目录下的商品
	 * @param string $cat_id
	 */
	function getGoodsList($cat_id){
		if(!$cat_id){
			return [];
		}
		$_POST['returnNotJson'] = 1;
		$_POST['cat_id'] = $cat_id;
		$res =  A('Api/List')->goodsList();
		return $res;
	}
}