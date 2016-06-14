<?php
/**
 * 抽奖活动管理
 * @author wxb
 * @date 2015-01-14
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class AwardController extends AdminbaseController {
    protected $curent_menu = 'Award/index';
    protected $model = NULL;
    function _init(){
    	$this->model = D('award');
    	$this->m = $this->model;
    }
    function index(){
    	$this->title = '抽奖活动管理';
    	$where = [];
    	if(($startTime = I('startTime','','trim')) && ($endTime = I('endTime','','trim'))){
    		$startTime = strtotime($startTime);
    		$endTime = strtotime($endTime.' 23:59:59');
    		$where['ap_start_time'] = ['egt',$startTime];
    		$where['ap_end_time'] = ['elt',$endTime];
    	}
    	if($ap_name = I('ap_name','','trim')){
    		$where['ap_name'] = ['like','%'.$ap_name.'%'];
    	}
    	$list = $this->lists($this->model,$where);
    	$this->list = $this->model->formatAwardList($list);
    	$this->display();
    }
    function edit(){
    	$info = [];
    	if($id = I('id')){
    		$info = $this->model->getOnePlan($id);
    	}

        $this->prizeType = $this->model->prizeType; // 奖品类型
        $this->couponList = M("coupon")->field('id, name')->where(['status' => 1])->select(); // 获取优惠券列表

    	$this->info = $info;
    	$this->display();
    }
    function update(){
		$res = $this->model->update($_REQUEST);
		$this->ajaxReturn($this->result->success()->toArray());
    }

    //根据奖品类别id查询奖品
    public function getPrizeByType(){
        $type = I("type", "", "intval"); //奖品类别id
        if(! $type){
            $this->ajaxReturn(['code' => 0, 'msg' => '奖品类别id不能为空！']);
        }
        //查询条件
        $where['as_type'] = $type;
        $where['status'] = 1;
        $where['as_end_time'] = ['gt', mktime()]; //过期时间
        $prize = M("awardSubject")->where($where)->field('as_id, as_name')->select();
        if($prize){
            $this->ajaxReturn(['code' => 1, 'data' => $prize]);
        }else{
            $this->ajaxReturn(['code' => 2, 'msg' => '当前类别下没有奖品！']);
        }
    }

    /**
    * 切换状态
    * @param int $apid 活动方案id
    * @param int $val 设置的值
    */
    public function switchState($apid, $val){
        $rerult = M('AwardPlan')->where(['ap_id' => $apid])->setField('ap_is_using', $val);
        if($result !== false){
            $this->ajaxReturn(['code' => 1, 'msg' => '修改状态成功！']);
        }else{
            $this->ajaxReturn(['code' => 0, 'msg' => '修改状态失败！']);
        }
    }

}