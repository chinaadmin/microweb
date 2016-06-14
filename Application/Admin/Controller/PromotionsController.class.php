<?php
/**
 * 促销逻辑类
 * @author cwh
 * @date 2015-08-11
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Org\Util\Date;

class PromotionsController extends AdminbaseController {

    protected $curent_menu = 'Promotions/index';

    /**
     * 列表
     */
    public function index(){
        $promotions_model = D('User/Promotions');
        //$promotions_model->giveCoupon(0,7);
        $where = [];
        $coupon = $this->lists($promotions_model,$where,'id desc');
        $type_lists = $promotions_model->getType();
        $Date = new Date();
        foreach($coupon as $i=>$v){
            if($v['status']==0){
                $coupon[$i]['date'] = '--';
                $coupon[$i]['status_name'] = toStress('禁用', 'label-important');
            }else {
                if ($v['end_time'] < NOW_TIME) {
                    $coupon[$i]['date'] = 0;
                    $coupon[$i]['status_name'] = toStress('已过期', 'label-important');
                } else if ($v['start_time'] > NOW_TIME) {
                    $ad_start_time = date('Y-m-d', $v['start_time']);
                    $day = $Date->dateDiff($ad_start_time);
                    $coupon[$i]['date'] = ceil($day) <= 0 ? 0 : ceil($day);
                    $coupon[$i]['status_name'] = toStress('未开始', 'label-danger');
                } else {
                    $ad_end_time = date('Y-m-d', $v['end_time']);
                    $day = $Date->dateDiff($ad_end_time);
                    $coupon[$i]['date'] = ceil($day) <= 0 ? 0 : ceil($day);
                    $coupon[$i]['status_name'] = toStress('已生效', 'label-success');
                }
            }
            $coupon[$i]['type_name'] = $type_lists[$v['type']];
        }

        $this->assign('lists',$coupon);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $promotions_model = D('User/Promotions');
        $id = I('request.id');
        $info = $promotions_model->field(true)->find($id);

        $type = I('request.type');
        if(!empty($type) && empty($info)){
            $info['type'] = $type;
        }
        
        $this->assign('info', $info);
        $param = json_decode($info['param'],true);
        switch($info['type']) {
            case 4:
                $shipment = $param[0]['condition']['shipment']['val'];
                if ($shipment) {
                    $nameStr = M('PositionCity')->where(['city_id' => ['in', $shipment]])->getField('city_name', true);//只显示市区
                    $param[0]['condition']['shipment']['county_name'] = implode(',', $nameStr);
                    $param[0]['condition']['shipment']['county_name_formate'] = D('Home/Area')->getCityFormat($shipment);
                }
        }
        $this->assign('param', $param);

        $source_lists = D('Common/Shared')->getSource();
        $this->assign('source_lists',$source_lists);

        $user_grade = D('User/UserGrade')->scope()->field(true)->select();
        $user_grade_count = D('User/User')->field('grade_id,COUNT(uid) as count')->group('grade_id')->select();
        $user_grade_count = array_column($user_grade_count,'count','grade_id');
        $user_grade = array_map(function($info) use ($user_grade_count){
            $info['count'] = $user_grade_count[$info['gid']]?:0;
            return $info;
        },$user_grade);
        $this->assign('user_grade',$user_grade);

        $user_group = M('UserGroup')->field(true)->select();
        $this->assign('user_group',$user_group);
        //促销商品
        $pro_goods = M("PromotionsGoods")->where(['promotions_id'=>$id])->select();
        if($pro_goods){
         $goods_ids = array_column($pro_goods, "goods_id");
         $goods = $this->goodsChoosedList($goods_ids);
         foreach($goods as &$v){
         	foreach ($pro_goods as $vs){
         		if($v['goods_id'] == $vs['goods_id']){
         			$v['start_time'] = $vs['start_time']?date('Y-m-d',$vs['start_time']):'';
         			$v['end_time'] = $vs['end_time']?date('Y-m-d',$vs['end_time']):'';
         			$v['discount'] = $vs['discount'];
         		}
         	}
         }
         $this->goods = $goods;
        }
        $this->goodsClassifyList();
        $this->display();
    }
    
    //商品分类
    private function goodsClassifyList(){
    	$this->goodsClassifyList = D('Admin/Category')->field(['cat_id','name'])->select();
    }
    
    /**
     * 选择的商品信息
     * @param str $goods_ids ,分割的商品id字窜
     */
    private  function goodsChoosedList($goods_ids){
    	if(!$goods_ids){
    		return;
    	}
    	$fields =  [
    	'goods_id',
    	'name', //商品名
    	'old_price',//原价
    	'price',
    	'attribute_id'//原价
    	];
    	$d = D('Admin/Goods');
    	$list = $d->field($fields)->where(['goods_id' => ['in',$goods_ids]])->select();
    	$this->addAttach($list);
    	$this->goodsChoosedList = $list;
    	return $list;
    }
    
    private function addAttach(&$list){
    	D("Home/List")->getThumb($list);
    	foreach($list as &$v){
    		$v['src'] = fullPath($v['thumb']);
    	}
    }
    
    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $promotions_model = D('User/Promotions');
        $data = [
            'name' => I('request.name','','htmlspecialchars,trim'),
            'type' => I('request.type',1,'intval'),
            'status' => I('request.status',0,'intval'),
            'all_goods'=> I('request.all_goods',0,'intval'),
            'param'=>$this->rule_param(),
            'remark' => I('request.remark'),
            'source' => I('request.source'),
            'object' => html_entity_decode(I('request.object')),
            'start_time' => I('request.start_time'),
            'end_time' => I('request.end_time'),
            'show_type'=>I('request.show_type'),     //展示方式
            'time_type'=>I('request.time_type'),      //计时方式
            'discount_type'=>I('request.discount_type') //打折方式
        ];
        if($data['all_goods'] == 0){
        	$this->validGoods($data['type']);
        }
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            $data['id'] = $id;
            $result = $promotions_model->setData($where,$data);
        }else{
            $result = $promotions_model->addData($data);
            $return = $result->toArray();
            $id = $return['result'];
        }
        if($result->isSuccess()){
          $this->addPromGoods($id,$data['type']);
        }
        $this->ajaxReturn($result->toArray());
    }
    
    /**
     * 选择商品下验证商品
     */
    private function validGoods($type){
    	$goods_start = I('post.goods_start_time','');
    	$goods_end = I('post.goods_end_time','');
    	$goods_price = I('post.goods_price','');
    	$goods_cheked_id =I("post.goods_cheked_id",'');
    	if(empty($goods_cheked_id) && in_array($type,array(3,5,6))){
    		$this->ajaxReturn($this->result->error("请选择促销商品！")->toArray());
    	}
    	$error = "";
    	$time_type = I('request.time_type');
    	if($type==3){
	    	foreach($goods_start as $key=>$v){
	    		if(empty($v) && $time_type){
	    			$error = "请选择商品促销开始时间！";
	    			break;
	    		}
	    		if(empty($goods_end[$key]) && $time_type){
	    			$error = "请选择商品促销结束时间！";
	    			break;
	    		}
	    		if(empty($goods_price[$key])){
	    			$error = "请填写商品折扣！";
	    			break;
	    		}
	    		if($goods_price[$key]<1 || $goods_price[$key]>10){
	    			$error = "请填写正确的折扣！";
	    			break;
	    		}
	    	}
    	}
    	if($error){
    		$this->ajaxReturn($this->result->error($error)->toArray());
    	}
    }
    /**
     * 添加活动商品
     */
    private function addPromGoods($id,$type){
    	M("PromotionsGoods")->where(['promotions_id'=>$id])->delete();
    	$all_goods = I('request.all_goods',0,'intval');
    	if(!$all_goods){
	    	$goods_start = I('post.goods_start_time','');
	    	$goods_end = I('post.goods_end_time','');
	    	$goods_price = I('post.goods_price','');
	    	$goods_cheked_id =I("post.goods_cheked_id",'');
	    	$rule_param = I('request.rule_param');
	    	$data = array();
	    	$i=0;
	    	if($type==3){
		    	foreach($goods_start as $key=>$v){
		    		$data[$i]=array(
		    				'start_time'=>strtotime($v),
		    				'end_time'=>strtotime($goods_end[$key]),
		    				'goods_id'=>$key,
		    				'promotions_id'=>$id,
		    				'type'=>$type,
		    				'discount'=>$goods_price[$key]
		    		);
		    		$i++;
		    	}
	    	}else{
	    		foreach($goods_cheked_id as $key=>$vs){
	    			$data[$key] = array(
	    					'goods_id'=>$vs,
	    					'promotions_id'=>$id,
	    					'type'=>$type,
	    					'discount'=>$rule_param['content_discount'][0]?$rule_param['content_discount'][0]:10
	    			);
	    		}
	    	}
	    	return M("PromotionsGoods")->addAll($data,'',true);
    	}
    }

    /**
     * 格式化优惠规则
     */
    public function rule_param(){
        $rule = I('request.rule');
        $rule_param = I('request.rule_param');
        $param = [];
        $param_index = [
            'first_single',//首次下单
            'condition',//条件
            'content'//内容
        ];
        if($rule){
	        for($i=0;$i<$rule['num'];$i++) {
	            $param_info = [];
	            foreach ($param_index as $v) {
	                $param_index_info = [];
	
	                $info = $rule[$v][$i];
	                if (!empty($info)) {
	                    if(!is_array($info)){
	                        $info = (array)$info;
	                    }
	
	                    foreach($info as $info_v) {
	                        if (is_numeric($info_v)) {
	                            $param_index_info[$v]['is_sel'] = true;
	                        } else {
	                            $param_index_info[$v][$info_v]['is_sel'] = true;
	                        }
	                        if ($rule_param[$v . '_' . $info_v][$i]) {
	                            $param_index_info[$v][$info_v]['val'] = $rule_param[$v . '_' . $info_v][$i];
	                        }
	                    }
	                }
	
	                //首次下单归类到条件里去
	                if ($v == 'first_single' && !empty($param_index_info)) {
	                    $param_index_info = ['condition' => $param_index_info];
	                }
	                $param_info = mergeArray($param_info, $param_index_info);
	            }
	            $param[] = $param_info;
	        }
        }else{
        	foreach ($rule_param as $key=>$v) {
        	      	$key = explode("_", $key);
        	      	$param[] = array(
        	      			$key[0]=>array(
        	      			    $key[1]=>[
        	      				   'is_sel'=>1,
        	      					'val'=>current($v)	
        	      				]	
        	      	        )
        	      	);
        	}
        }
        return json_encode($param);
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $promotions_model = D('User/Promotions');
        $where = [
            'id' => $id
        ];
        $result = $promotions_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

}