<?php
/**
 * 运费模板管理
 * @author wxb
 * @date 2015-07-21
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class FreightTemplateController extends AdminbaseController{
	protected $model;
	protected $curent_menu = "freight_template/index";
	function _init(){
		$this->model = D('FreightTemplate')->relation(true);
	}
	public function index(){
		$this->title = '运费模板管理';
		$lists = $this->lists($this->model,[],'ft_update_time desc');
		$this->lists = $lists;
		$this->display();
	}
    function getFormatArea(){
    	$res = D('Home/Area')->getFormatArea();
    	$this->ajaxReturn($res);
    }
    private function getCounty($idStr){
    	$nameStr = M('position_county')->where(['county_id'=>['in',$idStr]])->getField('county_name',true);
    	return implode(',',$nameStr);
    }
    function update(){
    	$re = I('post.');
    	$data['freight_detail_data'] = [];             // 不包邮
    	$data['free_freight_detail_data'] = [];        // 包邮
    	$countFree = count($re['fsd_county_id']);      // 包邮记录总数
    	$countFreight = count($re['fd_county_id']);    // 不包邮记录总数

        // 获取包邮地区详情
    	for($i = 0;$i<$countFree;$i++){
    		$tmpFree = $this->getOneFreeFreightDetail($i,$re);
    		if($tmpFree){
	    		$data['free_freight_detail_data'][] = $tmpFree;
    		}
    	}

        // 获取不包邮地区详情
    	for($i = 0;$i<$countFreight;$i++){
    		$tmpFreight = $this->getOneFreightDetail($i,$re);
    		if($tmpFreight){
    			$data['freight_detail_data'][] = $tmpFreight;
    		}
    	}

        // 不配送区域详情
        if( 1 == $re['ft_no_delivery_status'] ){
            $data['no_delivery_detail_data']['ndd_id'] = $re['ndd_id'];
            $data['no_delivery_detail_data']['ndd_county_id'] = $re['ndd_county_id'];
        }else{
            $data['no_delivery_detail_data'] = [];
        }
        
        unset($re['ndd_id']); // 去掉数组中的ndd_id
        unset($re['ndd_county_id']); // 去掉数组中的ndd_county_id

    	$data = array_merge($data,$re);
    	$this->validate($data);//数据验证

    	$freeModel = M('free_freight_detail');
    	$freightModel = M('freight_detail');

        //  不配送区域模型
        $noDeliveryModel = M('NoDeliveryDetail');

        // 编辑运费模版
    	if($data['ft_id']){
    		$data['ft_update_time'] = NOW_TIME;
            // $res = $this->model->save($data);
    		$res = D('FreightTemplate')->relation([])->save($data); // 写入主表数据

    		//检查是否有子表增加记录
    		$freeData = [];
    		$freightData = [];
    		foreach ($data['free_freight_detail_data'] as $freeV){
    			$freeV['fsd_fk_ft'] = $data['ft_id'];
                // 更新数据
    			if($freeV['fsd_id']){
    				$freeModel->save($freeV);
    				continue;
    			}
                // 新增数据集合
    			$freeData[] = $freeV;
    		}
    		
    		foreach ($data['freight_detail_data'] as $freightV){
    			$freightV['fd_fk_ft'] = $data['ft_id'];
    			// 更新数据
                if($freightV['fd_id']){
    				$freightModel->save($freightV);
    				continue;
    			}
                // 新增数据集合
    			$freightData[] = $freightV;
    		}

            // 新增数据
    		if($freeData){
				$res = $freeModel->addAll($freeData);	
    		}
    		if($freightData){
    			$res = $freightModel->addAll($freightData);
    		}

            // 不配送区域
            if($data['no_delivery_detail_data']){
                $data['no_delivery_detail_data']['ndd_fk_ft'] = $data['ft_id'];
                if($data['no_delivery_detail_data']['ndd_id']){
                    $noDeliveryModel->save($data['no_delivery_detail_data']);   // 更新数据
                }else{
                    $noDeliveryModel->add($data['no_delivery_detail_data']);    // 新增数据
                }
            }

    	}else{
            // 增加运费模版
    		$data['ft_add_time'] = NOW_TIME;
    		$data['ft_update_time'] = NOW_TIME;
    		$res = $this->model->add($data);
    	}
		if($res !== false){
			$this->ajaxReturn($this->result->success()->toArray());
		}
		$this->ajaxReturn($this->result->error()->toArray());
    }

    //获取某一条包邮详情数据
    private function getOneFreeFreightDetail($key,$data){
    	$fsd_id = $data['fsd_id'];
    	$fsd_county_id = $data['fsd_county_id'];
    	$fsd_egt_money = $data['fsd_egt_money'];
    	$fsd_charge = $data['fsd_charge'];
    	$res['fsd_id'] = $fsd_id[$key];
    	$res['fsd_county_id'] = $fsd_county_id[$key];
    	$res['fsd_egt_money'] = $fsd_egt_money[$key];
    	// $res['fsd_charge'] = $fsd_charge[$key]; // 去掉不满足指定金额时需要付费金额
//     	$res = array_filter($res);
    	return $res;
    }
    //获取某一条不包邮详情数据
    private function getOneFreightDetail($key,$data){
    	$fd_id = $data['fd_id'];
    	$fd_county_id = $data['fd_county_id'];
    	$fd_first_weight = $data['fd_first_weight'];
    	$fd_first_charge = $data['fd_first_charge'];
    	$fd_continue_weight = $data['fd_continue_weight'];
    	$fd_continue_charge = $data['fd_continue_charge'];
    	$res['fd_id'] = $fd_id[$key];
    	$res['fd_county_id'] = $fd_county_id[$key];
    	$res['fd_first_weight'] = $fd_first_weight[$key];
    	$res['fd_first_charge'] = $fd_first_charge[$key];
    	$res['fd_continue_weight'] = $fd_continue_weight[$key];
    	$res['fd_continue_charge'] = $fd_continue_charge[$key];
//     	$res = array_filter($res);
    	return $res;
    }

    	//验证数据
    private function validate($data){
    	//查看默认模板是否可以设置
    	// if($data['ft_default_status'] == 1){
    	// 	$where['ft_default_status'] = $data['ft_default_status'];
 		  //  	$this->model->where('1=1')->save(['ft_default_status' => 0]); //将所有都设置为非默认
    	// }
    	//主表验证
    	$_validate = [
    			['ft_name','require','运费模板不能为空'],
    			['ft_home_delivery_elt_distance','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项: 门店区域 必须为数字',2 ],
    			['ft_home_delivery_weight_one','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项: 送货上门配送重量 必须为数字',2],
    			['ft_home_delivery_price_one','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:送货上门价格 必须为数字',2],
    			['ft_home_delivery_price_two','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:送货上门价格 必须为数字',2],
    			['ft_free_shipping_out_postage','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:指定地区满包邮 邮费必须为数字',2],
    			['ft_freight_inner_weight','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 重量必须为数字',2],
    			['ft_freight_inner_charge','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 金额必须为数字',2],
    			['ft_freight_out_per_weight','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 重量必须为数字',2],
    			['ft_freight_out_per_charge','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 金额必须为数字',2],
    	];
    	if(!D('FreightTemplate')->validate($_validate)->create($data)){
    		$this->ajaxReturn($this->result->error($this->model->getError())->toArray());
    	}
    	//详情表验证   
    	$ffdmodel = D('FreeFreightDetail');
    	$fdmodel = D('FreightDetail');
    	
    	$freight_validate =  [
    			['fsd_county_id','require','填空项:指定地区包邮 运送地不能为空',0],
    			['fsd_egt_money','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:指定地区包邮 金额必须为数字',0],
    			// ['fsd_charge','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:指定地区包邮 金额必须为数字',0], // 去掉 指定地区包邮设置未满足付邮费设置 验证
    	];
    	foreach($data['free_freight_detail_data'] as $fddv){
	    	if(!$ffdmodel->validate($freight_validate)->create($fddv)){
	    		$this->ajaxReturn($this->result->error($ffdmodel->getError())->toArray());
	    	}
    	} 	
    	//详情表验证    	
    	$free_validate =   [
    			['fd_county_id','require','填空项:设置指定地区的邮费模板 运送地不能为空',0],
    			['fd_first_weight','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 重量必须为数字',0],
    			['fd_first_charge','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 必须为数字',0],
    			['fd_continue_weight','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 重量必须为数字',0],
    			['fd_continue_charge','/^(([1-9]\d{0,9})|0)(\.\d+)?$/','填空项:设置指定地区的邮费模板 必须为数字',0],
    	];
    	foreach($data['freight_detail_data'] as $ffddv){
	    	if(!$fdmodel->validate($free_validate)->create($ffddv)){
	    		$this->ajaxReturn($this->result->error($fdmodel->getError())->toArray());
	    	}
    	}

        // 不配送区域详情验证
        // $nddmodel = D('NoDeliveryDetail');  
        // $no_derlivery_validate =   [
        //     ['ndd_county_id','require','填空项：设置不配送区域模版 不配送区域不能为空',0]
        // ];
        // if( ! $nddmodel->validate($no_derlivery_validate)->create( $data['no_delivery_detail_data']) ){
        //     $this->ajaxReturn($this->result->error($nddmodel->getError())->toArray());
        // }

    }

    /**
     * 设置默认模版
     * @param int $ftid 模版id
     */
    public function setDefault($ftid){
        $model = M('FreightTemplate');
        $model->where("1 = 1")->save(['ft_default_status' => 0]); // 设置所有模版默认值为0
        $result = $model->where(['ft_id' => $ftid])->setField('ft_default_status', '1');
        if($result){
            $this->redirect('index');
        }
    }


}
