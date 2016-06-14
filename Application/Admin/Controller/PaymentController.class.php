<?php
/**
 * 支付方式逻辑类
 * @author cwh
 * @date 2015-05-26
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class PaymentController extends AdminbaseController {

    protected $curent_menu = 'Payment/index';

    /**
     * 列表
     */
    public function index(){
        $payment_model = D('Admin/Payment');
        $where = [];
        $payment = $this->lists($payment_model,$where);
        $type_lists = $payment_model->getType();
        $payment = array_map(function($info)use($type_lists){
            $info['type_name'] = $type_lists[$info['type']];
            return $info;
        },$payment);
        $this->assign('lists',$payment);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $payment_model = D('Admin/Payment');
        $this->assign('type_lists',$payment_model->getType());

        $id = I('request.id');
        if(!empty($id)) {
            $payment = $payment_model->field(true)->find($id);
            if(!empty($payment['photo'])) {
                $payment ['photo'] = D('Upload/AttachMent')->getAttach($payment ['photo']);
            }
        }
        $this->assign('info', $payment);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $payment_model = D('Admin/Payment');
        $data = [
            'id' => I('request.id'),
            'name' => I('request.name','','trim'),
            'code' => I('request.code','','trim'),
            'type' => I('request.type'),
            'status' => I('request.status'),
            'photo'=>I('request.photo'),
            'remark' => I('request.remark')
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            $data['id']=$id;
            $result = $payment_model->setData($where,$data);
        }else{
            $result = $payment_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $payment_model = D('Admin/Payment');
        $where = [
            'id' => $id
        ];
        $result = $payment_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

}