<?php
namespace Api\Model;
class CartModel extends ApiBaseModel{

    protected $autoCheckFields  =   false;

    /**
     * 格式化列表信息
     * @param array $data 列表数据
     * @return array
     */
    public function formatLists($data){
        $return_data = [];
        if(!empty($data['cart_id'])) {//购物车id
            $return_data['id'] = $data['cart_id'];
        }
        $return_data['number'] = $data['number'];//数量
        $return_data['goods'] = D('Api/Goods')->formatOrderGoods($data);
        $return_data['price'] = $data['price'];//商品总价

        $return_data['express'] = $data['is_express']; // 是否支持快递

        return $return_data;
    }
    
    /**
     * 未登陆时获取购物车列表
     */
    public function cartList($carts){
    	$goods_ids = array_column($carts, "goodsId");
    	$norms = array_column($carts,"norms");
    	$norms_ids = array();
    	$norms = array_filter($norms,function($v)use(&$norms_ids){
    		$v = trim($v,"_");
    		$ids = explode("_", $v);
    		foreach($ids as $vo){
    			$norms_ids[] = $vo;
    		}
    		return $v;
    	});
    	if($norms){
	    	$where = array(
	    			"goods_norms_link"=>array("in",$norms)
	    	);
	    	//规格商品价格
	    	$normsAttr = M("GoodsNormsAttr")->where($where)->select();
    	}
    	//商品规格值
    	$norms_ids = array_filter($norms_ids);
    	if(!empty($norms_ids)){
    		$norms_data = $this->getNorms($goods_ids,$norms_ids);
    	}
    	$where = array(
    		"goods_id"=>array("in",$goods_ids)	
    	);
    	$data = M("Goods")->where($where)->select();
    	D('Admin/Order')->getPic($data);
    	$return_array = array();
    	foreach($data as $key=>$v){
    		if(!empty($normsAttr)){  //规格价格
    			foreach ($normsAttr as $vo){
    				if($vo['goods_id'] == $v['goods_id']){
    					$v['price'] = $vo['goods_norms_price'];
    				}
    			}
    		}
    		//商品规格
    		if(!empty($norms_data)){
    			foreach ($norms_data as $n){
    				if($v['goods_id'] == $n['goods_id']){
    					$v['normsList'][] = $n;
    				}
    			}
    		}
    		//购物数量 
    		foreach($carts as $vs){
    			if($v['goods_id']==$vs['goodsId']){
    				$v['number'] = $vs['num'];
    			}
    		}
    		$return_array[$key]=array(
    				"number"=>$v['number'],
    				"goods" => array(
    				    "id"=>$v['goods_id'],
    					"name"=>$v['name'],
    					"price"=>$v['price'],
    					"marketPrice"=>$v['old_price'],
    					"photo"=>fullPath($v['pic']),
    					"normsList"=>$v['normsList']
    				),
    				"price"=>$v['price']*$v['number']
    		);
    	}
    	return $return_array;
    }
    
    /**
     * 获取商品规格
     */
    private function getNorms($goods_id,$norms){
    	$viewFields = array(
    			"GoodsNormsValue"=>array(
    			      "goods_id",
    				  "norms_value_id",
    				  "norms_value",
    				  "_type"=>"LEFT"		
    	        ),
    			"NormsValue"=>array(
    					"norms_id",
    					"_on"=>"GoodsNormsValue.norms_value_id=NormsValue.norms_value_id"
    			)
    	);
    	$where = array(
    			"norms_value_id"=>array("in",$norms),
    			"goods_id" => array("in",$goods_id)
    	);
    	$data = $this->dynamicView($viewFields)->where($where)->select();
    	$norms_ids = array_column($data,"norms_id");
    	$where = array(
    			"norms_id"=>array("in",$norms_ids)
    	);
    	$norms_data = M("Norms")->where($where)->select();
    	$return_array = array();
    	foreach ($data as $key=>$v){
    		foreach($norms_data as $vo){
    			if($v['norms_id'] == $vo['norms_id']){
    				$v['name'] = $vo['norms_name'];
    			}
    		}
    		$return_array[$key] = array(
    				"goods_id"=>$v['goods_id'],
    				"id"=>$v['norms_value_id'],
    				"name"=>$v['name'],
    				"value"=>$v['norms_value'],
    				"photo"=>""
    		);
    	}
    	return $return_array;
    }

}