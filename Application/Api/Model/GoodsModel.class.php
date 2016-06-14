<?php
namespace Api\Model;
class GoodsModel extends ApiBaseModel{

    protected $autoCheckFields  =   false;

    /**
     * 格式化订单商品简介信息
     * @param array $data 商品数据
     * @return array
     */
    public function formatOrderGoods($data){
        //商品信息
        $return_data = [
            'id'=>$data['goods_id'],//商品id
            'name'=>$data['name'],//商品名称
            'marketPrice'=>$data['market_price'],//市场价
            'price'=>$data['goods_price'],//价格
            'photo'=>fullPath($data['pic']),//图片
        	'normsList' => $this->formatNorms($data['norms_value']) //商品规格二维数组  name=>value 
        ];
        return $return_data;
    }
    private function formatNorms($normsList){
    	foreach ($normsList as &$v){
    			if(!$v['photo']){
    				$v['photo'] = '';
    			}else{
	    			$v['photo'] = fullPath($v['photo']);
    			}
    	}
    	if(!$normsList){
    		return [];
    	}
    	return $normsList;
    }

}