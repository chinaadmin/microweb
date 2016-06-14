<?php
/**
 * 管理员逻辑类
 * @author cwh
 * @date 2015-04-01
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Org\Util\Date;

class CouponController extends AdminbaseController {

    protected $curent_menu = 'Coupon/index';

    /**
     * 列表
     */
    public function index(){
    	$coupon_model = D('User/Coupon');
        $where = [];
        $coupon = $this->lists($coupon_model,$where,'id desc');
        // dump($coupon);die;

        $Date = new Date();
        foreach($coupon as $i=>$v){
			$coupon[$i]['counts'] = $coupon_model->getCounts($v['id']);

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
        }
        // dump($coupon);die;

        $this->plist = M('AwardSubject')->distinct(true)->where(['as_type' => 2])->getField('as_coupon_id', true); //查找已关联活动的优惠券列表

        $this->assign('lists',$coupon);
        $this->display();
    }

    /**
     * 优惠券用户
     */
    public function code(){
        $coupon_model = D('User/Coupon');
        $id = I('request.id');
        $coupon_info = $coupon_model->where(['id'=>$id])->field(true)->find();
        $this->assign('coupon_info',$coupon_info);

        $where = [
            'coupon_id'=>$id
        ];
		
        $keyword = I('request.keyword','');
        if($keyword){
			$where['_string'] = "aliasname like '%{$keyword}%' or username like '%{$keyword}%' or mobile like '%{$keyword}%'";
        	$this->assign('keyword',$keyword);
        }

        $coupon_user_list = $this->lists($coupon_model->viewModel(),$where,'add_time desc');
        $coupon_user_list = array_map(function($info){
            if(!empty($info['order_id'])){
                $info['order_sn'] = M('Order')->where(['order_id'=>$info['order_id']])->getField('order_sn');
            }
            return $info;
        },$coupon_user_list);
        $this->assign('coupon_user_list',$coupon_user_list);


        $this->display();
    }

    /**
     * 获取列表json格式
     */
    public function getListsJson(){
        $is_system = I('request.is_system');
        $coupon_model = D('User/Coupon');
        $where = [];
        if(!empty($is_system)){
            $where['grant_rule'] = 1;
        }
        $coupon_lists = $coupon_model->scope('unexpired')->where($where)->select();
        $coupon_lists = array_map(function($info){
            $info['start_time'] = date('Y-m-d H-i-s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H-i-s',$info['end_time']);
            $use_string = '';
            switch($info['rule']){
                case 2:
                    $use_string = '订单满'.$info['order_money'].'元可用';
                    break;
                case 3:
                	$use_string = '指定商品优惠券';
                	break;
                default:
                    $use_string = '无消费金额限制';
            }
            $info['use'] = $use_string;
            return $info;
        },$coupon_lists);
        $this->ajaxReturn($this->result->content($coupon_lists)->success()->toArray());
    }

    /**
     * 编辑
     */
    public function edit(){
        $coupon_model = D('User/Coupon');
        $id = I('request.id');
        $info = $coupon_model->field(true)->find($id);
        $this->assign('info', $info);
        $this->goodsChoosedList($info['goods_ids']);
        $this->goodsClassifyList();
        $this->display();
    }
    private function addAttach(&$list){
    	$attachModel = D('Upload/AttachMent');
    	foreach($list as &$v){
    		if($v['attribute_id']){
    			$v['src'] = $attachModel->getAttach(json_decode($v['attribute_id'],true)["default"])[0]['path'];
    			$v['src'] = fullPath($v['src']);
    		}else{
    			$v['src'] = '';
    		}
    	}
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
    			'attribute_id'//原价
    	];
    	$d = D('Admin/Goods');
    	$list = $d->field($fields)->where(['goods_id' => ['in',$goods_ids]])->select();
    	$this->addAttach($list);
    	$this->goodsChoosedList = $list;
    }
    
    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $rule_model = D('User/Coupon');
        $rule = I('request.rule');
        $data = [
            'name' => I('request.name','','htmlspecialchars,trim'),
            'money' => I('request.money',0,'trim'),
            'rule' => $rule,
            'order_money' => I('request.order_money',0,'trim'),
            'grant_rule' => I('request.grant_rule'),
            'all_count' => I('request.all_count',0,'trim'),
            'limit_count' => I('request.limit_count',0,'trim'),
            'color' => I('request.color','','trim'),
            'status' => I('request.status',0,'trim'),
            'remark' => I('request.remark'),
            'start_time' => I('request.start_time'),
            'end_time' => I('request.end_time'),
            'goods_ids' => I('request.goods_ids'),
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            if(current(D('User/Coupon')->field("count")->find($id))>0){
            	$this->ajaxReturn($this->result->error("该优惠劵已发放,不能修改！")->toArray());
            }
            $data['id']=$id;
            $result = $rule_model->setData($where,$data);
        }else{
            $result = $rule_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $admin_model = D('User/Coupon');
        $where = [
            'id' => $id
        ];
        $result = $admin_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 发放优惠劵
     */
    public function issuing(){
        $uid = I('request.uid');//用户id
        $coupon_id = I('request.coupon_id');//优惠劵id

        $uid = explode(',',$uid);
        $coupon_id = explode(',',$coupon_id);
        $coupon_model = D('User/Coupon');
        $success_count = 0;
        foreach($coupon_id as $val){
            $success_count += $coupon_model->issuing($val,$uid);
        }

        $this->ajaxReturn($this->result->content([
            'success'=>$success_count,
            'fail'=>count($uid)*count($coupon_id)-$success_count
        ])->success()->toArray());
    }

    /**
     * 设置为已使用
     */
    public function isUse(){
        $id = I('request.id');
        $is_use = I('request.is_use');
        $data = [];
        if(empty($is_use)) {
            $data['use_time'] = 0;
        }else{
            $data['use_time'] = time();
        }
        if(M('CouponCode')->where(['id'=>$id,'order_id'=>['eq','']])->data($data)->save()===false){
            $this->error('设置失败');
        }else{
            $this->success('设置成功');
        }
    }

    /**
     * 删除用户优惠劵
     */
    public function delUser(){
        $id = I('request.id');
        if(M('CouponCode')->where(['id'=>$id,'order_id'=>['eq','']])->delete()===false){
            $this->ajaxReturn($this->result->error('删除失败')->toArray());
        }else{
            $this->ajaxReturn($this->result->success('删除成功')->toArray());
        }
    }

}