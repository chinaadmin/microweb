<?php
/**
 * 抽奖控制器
 * @author xiaohuakang
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class DrawController extends AdminbaseController{
	//中奖列表
	public function index(){
		$where = [];
		$where['as_type'] = I('request.as_type', '', 'intval');
		$where['as_id'] = I('request.as_id', '', 'intval');
		$where['ap_id'] = I('request.ap_id', '', 'intval');
		$where['reveiveState'] = I('request.ar_is_reveive', -1, 'intval');
		$where['sendState'] = I('request.ar_is_send', -1, 'intval');
		$where['drawKeyword'] = I('request.draw_keywords', '');
		$where['starTime'] = I('request.ap_start_time') ? strtotime(I('request.ap_start_time')) : '';
		$where['endTime'] = I('request.ap_end_time') ? strtotime(I('request.ap_end_time')) : '';
		$ord = '';
		switch ($where['as_type']){
		    case 2: //优惠卷
		        $whereStr = $this->_addWhere($where);
		        $m = D('User/Coupon')->DrawViewModel();
		        $ord = 'id DESC';
		        break;
		    case 3:  // 红包
		    case 4:  // 积分
		    default:
		        $whereStr = $this->_dowhere($where);
		        $m = D('Admin/Draw')->viewModel();
		        $ord = 'ar_id desc';
		}
		$lists = $this->lists($m, $whereStr, $ord,true);
		// $this->aplists = M('AwardPlan')->field('ap_id, ap_name')->select(); // 获取所有活动
		$this->assign($where);
		$this->assign('lists', $lists);
		$this->display();
	}

	/**
	 * 传奖品类型决定
	 * @param  [type] $type  [description]
	 * @param  [type] $id    [description]
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	private function _selectType($type , $id , $where){
		$key = in_array($type , [3,4]) ? 'ar.fk_as_id' : 'ar.fk_apc_id';
		return " {$key} = ".$id." AND" ;
	}
	
	/**
	 * 组合查询条件 for 优惠劵
	 * @param array $where
	 */
	private function _addWhere($where){
	    $whereStr = '';
	    if (!empty($where['as_type'])){
	        $whereStr .= " jas.as_type=".$where['as_type']." AND";
	    }
	    if (!empty($where['as_id'])){
	        $whereStr .= " jas.as_id=".$where['as_id']." AND";
	    }
	    if ($where['reveiveState']>=0){
	        if ($where['reveiveState'] == 0){
	           $whereStr .= " cc.use_time = '0' AND";
	        }
	        else{
	            $whereStr .= " cc.use_time > 0 AND";
	        }
	    }
	    return rtrim($whereStr,'AND');
	}

	//组合查询条件
	private function _dowhere($where){
		$whereStr = '';

		//奖品类型
		if( ! empty($where['as_type'])){
			$whereStr .= " as_type = ".$where['as_type']." AND";
		}

		//奖品id
		if( ! empty($where['as_id'])){
			$whereStr .= $this->_selectType($where['as_type'] , $where['as_id'] , $whereStr);
		}

		//活动方案
		if( ! empty($where['ap_id'])){
			$whereStr .= " ar.fk_ap_id = ".$where['ap_id']." AND";
		}

		//领取状态
		if(-1 != $where['reveiveState']){
			$whereStr .= " ar_is_reveive = ".$where['reveiveState']." AND";
		}
		//发货状态
		if(-1 != $where['sendState']){
			$whereStr .= " ar_is_send =".$where['sendState']." AND";
		}
		//关键字
		if( ! empty($where['drawKeyword'])){
			$whereStr .= " (username like '%{$where['drawKeyword']}%' OR as_name like '%{$where['drawKeyword']}%' OR ap_name like '%{$where['drawKeyword']}%') AND";
		}

		//起始时间
		if( ! empty($where['starTime'])){
			$whereStr .= " ar_draw_time >= ".$where['starTime']." AND";
		}
		//结束时间
		if( ! empty($where['endTime'])){
			$whereStr .= " ar_draw_time <= ".$where['endTime']." AND";
		}

		$whereStr = rtrim($whereStr, 'AND');
		return $whereStr;
	}

	//获取快递订单
	public function getExpressBill($arid){
		$data = D('Admin/Draw')->getExpressBillData($arid);
		$res = D('Admin/ExpressTemplate')->getDyTemplate('',[$data]);
		return $res[0];
	}
}
