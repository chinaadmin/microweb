<?php
/**
 * 运费模板模型
 * @author wxb
 * @date 2015-07-24
 */
namespace Admin\Model;
use Think\Model\RelationModel;
class FreightTemplateModel extends RelationModel{
    protected $_auto = [
        ['ft_add_time','time',self::MODEL_INSERT,'function'],
        ['ft_update_time','time',self::MODEL_BOTH,'function']
    ];
    protected $_link = [
	   		'FreightDetail' => [   
	   				 'mapping_type'  => \Think\Model\RelationModel::HAS_MANY, 
	   				 'class_name'    => 'FreightDetail',    
	   				'foreign_key'   => 'fd_fk_ft',   
	   				'mapping_name'  => 'freight_detail_data',    
	   		],
   			'FreeFreightDetail' => [
   					'mapping_type'  => \Think\Model\RelationModel::HAS_MANY,
   					'class_name'    => 'FreeFreightDetail',
   					'foreign_key'   => 'fsd_fk_ft',
   					'mapping_name'  => 'free_freight_detail_data',

                    'mapping_fields' => 'fsd_id, fsd_fk_ft, fsd_county_id, fsd_egt_money' // 去掉不满足指定金额时需要付费金额字段
   			],

            // 不配送区域模型
            'NoDeliveryDetail' => [
                'mapping_type' => \Think\Model\RelationModel::HAS_ONE,
                'class_name' => 'NoDeliveryDetail',
                'foreign_key' => 'ndd_fk_ft',
                'mapping_name' => 'no_delivery_detail_data'
            ]

	 	];

    /**
     * 获取默认信息
     */
    public function getDefaultInfo(){
        return $this->where(['ft_default_status'=>1])->field(true)->find();
    }

    /**
     * 普通快递运费计算
     * @param $recaddress 收货地址信息
     * @param int $order_money 订单金额
     * @param int $order_weight 订单重量 g
     * @param $freight_template 运费模板
     * @return float|int
     */
    public function freight($recaddress,$order_money = 0,$order_weight = 0,$freight_template = null){
        //促销活动【免邮优惠】
        $goods = [];
		
        if(D('User/Promotions')->hasFreeMail($order_money,$goods,null,['provice'=>$recaddress['user_provice'],'city'=>$recaddress['user_city']])){
            return 0;
        }
       // return $this->doFreight($order_money,$order_weight);
        if(is_null($freight_template)){
            $freight_template = D('Admin/FreightTemplate')->relation(true)->where(['ft_default_status'=>1])->find();
        }
        if ($freight_template['ft_free_shipping_status'] == 1) {//开启指定地区包邮
            $free_freight_detail_data = $freight_template['free_freight_detail_data'];
            foreach ($free_freight_detail_data as $detail_v) {
                $fsd_county_id = explode(',', $detail_v['fsd_county_id']);
                foreach ($fsd_county_id as $v) {
                    if (strlen($v) == 3) {//省
                        if($recaddress['user_provice'] != $v){
                            continue;
                        }
                    } else {//市
                        if($recaddress['user_city'] != $v){
                            continue;
                        }
                    }
					
                    if(($order_money >= $detail_v['fsd_egt_money'])){//是否满金额包邮
						
					   return 0;
                   }else{
 
						$fsd_charge = 1;
                    }
                }
            }
			
        }
		
		if(($order_money >=$freight_template['ft_free_shipping_egt_total'])){

			if($fsd_charge != 1){
				return 0;
			}
			
		}
        if ($freight_template['ft_freight_status'] == 1) {//开启指定地区不包邮
		
			
            $freight_detail_data = $freight_template['freight_detail_data'];
            foreach ($freight_detail_data as $detail_v) {
                $fd_county_id = explode(',', $detail_v['fd_county_id']);
                foreach ($fd_county_id as $v) {
                    if (strlen($v) == 3) {//省
                        if($recaddress['user_provice'] != $v){
                            continue;
                        }
                    } else {//市
                        if($recaddress['user_city'] != $v){
                            continue;
                        }
                    }
                    $fd_first_weight = $detail_v['fd_first_weight'];
                    $fd_first_charge = $detail_v['fd_first_charge'];
                    if($detail_v['fd_first_weight'] >= $order_weight){//默认重量收邮费
                        return $fd_first_charge;
                    }else{
						
                        return ceil(($order_weight - $fd_first_weight)/$detail_v['fd_continue_weight'])*$detail_v['fd_continue_charge'] + $fd_first_charge;
                    }
                }
            }

            //指定地区不包邮默认配置
            $ft_freight_inner_weight = $freight_template['ft_freight_inner_weight'];
            $ft_freight_inner_charge = $freight_template['ft_freight_inner_charge'];
            if($ft_freight_inner_weight >= $order_weight){//默认重量收邮费
                return $ft_freight_inner_charge;
            }

            //超过默认重量收邮费
            return ceil(($order_weight - $ft_freight_inner_weight)/$freight_template['ft_freight_out_per_weight'])*$freight_template['ft_freight_out_per_charge'] + $ft_freight_inner_charge;
        }
        return 0;
    }
    /**
     * 计算运费 不参考后台设置
     * @param number $order_money 金额
     * @param number $order_weight 重量 g
     * @return number
     */
     private function doFreight($order_money = 0,$order_weight = 0){
    	if($order_money >= 100){
    		return 0;
    	}
    	$order_weight = $order_weight/1000;	//g->kg
    	if($order_weight <= 1){ 
    		return 5;
    	}else{	//大于1kg
    		$order_weight = $order_weight - 1;
    		return 5 + ceil($order_weight) * 2;
    	}
    }
}