<?php
/**
 *	奖品管理控制器.
 *
 *	@author : liuwh
 *	@datetime ： 2016-03-06
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class PrizeController extends AdminbaseController
{
    protected $curent_menu = 'Prize/index';

    public function index()
    {
        $AwardModel = D('Prize');
        $status = I('request.status', '');
        $type = I('request.as_type', '');
        $_page_code = I('request.p', '');
        $where = [];
        if ($status !== '') {
            $where['status'] = $status;
        }
        if ($type !== '') {
            $where['as_type'] = $type;
        }

        $lists = $this->lists($AwardModel, $where, 'as_add_time desc');
		$draw = A('Draw');

        // dump($lists);die;
        $AwardRecordModel = D('AwardRecord');
        if ($lists) {
            $ids = array_column($lists, 'as_id');
            $where = [
                'fk_as_id' => ['in', $ids],
            ];
            $groupCount = $AwardRecordModel->where($where)->group('fk_as_id')->getField('fk_as_id , fk_as_id , count(1) as count');

            foreach ($lists as $key => &$val) {
				if($val['as_type']==2){
					$lists[$key]['counts'] = D('Admin/Prize')->getCouponCount($val['as_id'],$val['as_type']);
				}else{
					$lists[$key]['counts'] = D('Admin/Prize')->getHongCount($val['as_id'],$val['as_type']);
				}
                $val['as_name_label'] = $this->_showLabelText($AwardModel->vaildAwardTime($val['as_start_time'], $val['as_end_time']));
                $val['as_type_name'] = $AwardModel->getTypeName($val['as_type']);
                $val['status_name'] = $this->_showStatusText($val['status']);
                $val['is_join']   = $this->_isJoinActivity($val['as_id']);
                //有单位计量的奖品
                if (in_array($val['as_type'], [3, 4])) {
                    $val['as_hongbao_amount'] = $val['as_hongbao_amount'].$AwardModel->getTypeName($val['as_type'], 'unit');
                } else {
                    $val['as_hongbao_amount'] = '---';
                }
                if (isset($groupCount[$val['as_id']])) {
                    $val['get_number'] = $groupCount[$val['as_id']]['count'];
                } else {
                    $val['get_number'] = 0;
                }
            }
        }
        // dump($lists);die;
        $category = $AwardModel->typeHash;
        $this->assign('category', $category);
        $this->assign('lists', $lists);
        $this->assign('_status', $status);
        $this->assign('_type', $type);
        $this->assign('_page_code', $_page_code);
        $this->display();
    }
    
    /**
     * 判断奖品是否有参加活动
     * @param integer $as_id
     */
    private function _isJoinActivity($as_id){
        $rs1 = M('award_plan_detail')->where(['fk_as_id'=>$as_id])->count();
        $rs2 = M('award_plan')->where(['ap_is_using'=>1,'fk_bc_as_id'=>$as_id])->count();
        return ($rs1 || $rs2) ? "是": "否";
    }

    /**
     * 商品有效期html标签.
     *
     * @param int $status [description]
     *
     * @return string [description]
     */
    private function _showStatusText($status)
    {
        $text = '';
        switch ($status) {
            case '0':
                $text = toStress('禁用', 'label-important');
                break;
            default:
                $text = toStress('启用', 'label-success');
                break;
        }

        return $text;
    }

    /**
     * 商品有效期html标签.
     *
     * @param int $status [description]
     *
     * @return string [description]
     */
    private function _showLabelText($status)
    {
        $text = '';
        $style = '<span class="label :style">:text</span>';
        switch ($status) {
            case '-1':
                $text = toStress('已过期', 'label-important', $style);
                break;
            case '0':
                $text = toStress('未开始', 'label-danger', $style);
                break;
            default:
                $text = toStress('执行中', 'label-success', $style);
                break;
        }

        return $text;
    }

    /**
     * 获取优惠券.
     *
     * @return [type] [description]
     */
    public function getUseCoupon()
    {
        $CouponModel = D('User/Coupon');
        $result = $CouponModel->scope('normal')->field('id,name,start_time,end_time')->order('id desc')->select();
        if ($result){
            foreach ($result as $key => &$val) {
                $val ['name'] = $this->_showCouponName($val['name'] , $val['start_time'] , $val['end_time']);
            }
        }
        $this->ajaxReturn($result);
    }

    /**
     * 优惠券显示有效日期
     * @param  [type] $name  [description]
     * @param  [type] $start [description]
     * @param  [type] $end   [description]
     * @return [type]        [description]
     */
    private function _showCouponName ($name , $start , $end){
        return $name .' ['. dateFormat($start ,'m-d') . '至'. dateFormat($end ,'m-d').']';
    }

    /**
     * 编辑新增页面.
     *
     * @return [type] [description]
     */
    public function edit()
    {
        $AwardModel = D('Prize');
        $id = I('request.id');
        if ($id) {
            $where = [
                'as_id' => $id,
            ];
            $info = $AwardModel->where($where)->find();
            $this->assign('info', $info);
            if ($info && $info['as_type'] < 3) {
                $CouponModel = D('User/Coupon');
                $coupon = $CouponModel->scope('normal')->field('id,name,start_time,end_time')->order('id desc')->select();
                if ($coupon){
                    foreach ($coupon as $key => &$val) {
                        $val ['name'] = $this->_showCouponName($val['name'] , $val['start_time'] , $val['end_time']);
                    }
                }
                $this->assign('coupon', $coupon);
            }
        }
        $category = $AwardModel->typeHash;
        //暂时不使用赠品
        unset($category[1]);
        $this->assign('category', $category);
        $this->display();
    }

    /**
     * 修改奖品
     *
     * @return [type] [description]
     */
    public function update()
    {
        $id = I('request.id');
        $AwardModel = D('Prize');
        $data = [
            'as_name' => I('request.as_name'),
            'as_type' => I('request.as_type'),
            'as_start_time' => I('request.as_start_time'),
            'as_end_time' => I('request.as_end_time'),
            'as_coupon_id' => I('request.as_coupon_id'),
            'as_hongbao_amount' => I('request.as_hongbao_amount', '', 'doubleval'),
            'status' => I('request.status', 0, 'intval'),
        ];
        if (!empty($id)) {
            $where = [
                    'as_id' => $id,
                ];
            if ($this->_denyEdit($id)) {
                $this->ajaxReturn($this->result->error('活动已经开始,奖品不能修改！')->toArray());
            }
            $result = $AwardModel->setData($where, $data);
        } else {
            $result = $AwardModel->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    public function _before_del()
    {
        $id = I('request.id');
        if ($id && $this->_denyEdit($id ,'del')) {
            $this->ajaxReturn($this->result->error('活动已经开始或奖品已有人领取,不能删除！')->toArray());
        }

        return true;
    }

    /**
     * 禁止修改与删除
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    private function _denyEdit($id , $type = 'edit'){
        $where = [];
        $where ['fk_zp_as_id'] = $id;
        $where['fk_bc_as_id'] = $id;
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $map ['ap_is_using'] = 1;
        $using = D('AwardPlan')->where($map)->count();
        $recied = D('AwardRecord')->where(['fk_as_id' => $id])->count() > 0;
        if ($type == 'edit'){
            $result = $using && $recied;
        } else {
            $result = $using || $recied;
        }
        return $result;
    }

    /**
     * 数据导出excel.
     *
     * @return [type] [description]
     */
    public function export()
    {
        $status = I('request.status', '');
        $type = I('request.as_type', '');
        $where = [];
        if ($status !== '') {
            $where['status'] = $status;
        }
        if ($type !== '') {
            $where['as_type'] = $type;
        }
        $header = [
            '奖品名称', '奖品类型', '奖品', '有效时间', '领取数量', '启用状态',
        ];
        $fiels = [
            'as_name', 'as_type', 'as_hongbao_amount', 'as_start_time', 'as_end_time', 'status',
        ];
        $lists = [];
        $AwardModel = D('Prize');
        $AwardRecordModel = D('AwardRecord');
        $lists = $this->lists($AwardModel, $where, 'as_add_time desc');
        if ($lists) {
            $ids = array_column($lists, 'as_id');
            $where = [
                'fk_as_id' => ['in', $ids],
            ];
            $groupCount = $AwardRecordModel->where($where)->group('fk_as_id')->getField('fk_as_id , fk_as_id , count(1) as count');
            foreach ($lists as $key => &$val) {
                $val['as_start_time'] = dateFormat($val['as_start_time'], 'Y-m-d H:i:s');
                $val['as_end_time'] = dateFormat($val['as_end_time'], 'Y-m-d H:i:s');
                //有单位计量的奖品
                if (in_array($val['as_type'], [3, 4])) {
                    $val['as_hongbao_amount'] = $val['as_hongbao_amount'].$AwardModel->getTypeName($val['as_type'], 'unit');
                } else {
                    $val['as_hongbao_amount'] = '---';
                }
                if (isset($groupCount[$val['as_id']])) {
                    $val['get_number'] = $groupCount[$val['as_id']]['count'];
                } else {
                    $val['get_number'] = 0;
                }
                if ($val['status'] == 0) {
                    $val['status'] = '禁用';
                } else {
                    $val['status'] = '启用';
                }
                $val['as_type'] = $AwardModel->getTypeName($val['as_type']);
            }
        }
        $excel = new \Admin\Org\Util\ExcelComponent();
        $excel = $excel->createWorksheet();
        $excel->head($header, 'Candara', '16', '30');
        $file_name = 'prExport';
        $excel->listData($lists, $fiels);
        $excel->output($file_name.'.xlsx');
    }

    /*
     * 统计中奖
     */
    public function tongji(){
        $this->curent_menu = 'Prize/tongji';
        $as_name = I('request.as_name','');
        $where = [
            'status' => 1
        ];
        if (!empty($as_name)){
            $where['as_name'] = ['like',"%".$as_name."%"];
        }
        $model = M('award_subject');
        $list = $this->lists($model,$where,'as_add_time DESC');
        if (count($list)){
            foreach ($list as $k=>$v){
                $list[$k]['receive_total'] = M('award_record')->where(['fk_as_id'=>$v['as_id']])->count();
            }
        }
        $this->assign('lists',$list);
        $this->display();
    }


    //  删除所有奖品 仅用于测试
    public function deleteAll(){
        $result = M('AwardSubject')->where('1')->delete();
        if($result){
            echo "删除全部奖品成功！ 仅用于测试";
        }
    }

}
