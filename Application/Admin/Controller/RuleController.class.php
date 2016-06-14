<?php
/**
 * 管理员逻辑类
 * @author cwh
 * @date 2015-04-01
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class RuleController extends AdminbaseController {

    protected $curent_menu = 'Rule/index';

    /**
     * 列表
     */
    public function index(){
        $rule_model = D('Admin/Rule');
        $where = [];
        $rule = $this->lists($rule_model,$where,'id desc');
        $type_lists = $rule_model->getType();
        $rule = array_map(function($info) use ($type_lists){
            $info['type_name'] = $type_lists[$info['type']];
            return $info;
        },$rule);
        $this->assign('lists',$rule);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $rule_model = D('Admin/Rule');
        $type_lists = $rule_model->getType();
        $this->assign('type_lists', $type_lists);

        $id = I('request.id');
        $info = $rule_model->field(true)->find($id);
        if ($info) {
            $info ['config'] = unserialize($info ['config']);
        }
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $rule_model = D('Admin/Rule');
        $type = I('request.type');
        $data = [
            'code' => I('request.code','','htmlspecialchars,trim,strtolower'),
            'name' => I('request.name','','htmlspecialchars,trim'),
            'type' => $type,
            'config' => $this->_config_data($type),
            'formula' => I('request.formula'),
            'status' => I('request.status')
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            $data['id']=$id;
            $result = $rule_model->setData($where,$data);
        }else{
            $result = $rule_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 缓存配置数据格式
     * @param int $type 类型
     * @return string
     */
    private function _config_data($type){
        switch ($type) {
            case 1://普通认证
                break;
            case 2://表达式认证
                $data = [
                    'condition' => I('request.condition')
                ];
                break;
            case 3://模型认证
                //$param = I('request.param');
                $data = [
                    'model' => I('request.model'),
                    'function' => I('request.function'),
                    //'param' => explode(',', $param)
                ];
                break;
        }
        return serialize($data);
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $admin_model = D('Admin/Rule');
        $where = [
            'id' => $id
        ];
        $result = $admin_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 配置
     */
    public function config(){
        $this->curent_menu = 'Rule/config';
        $config_integral = M('ConfigsIntegral')->field(true)->select();
        $rule = [];
        $rule_param = [];
        foreach($config_integral as $v){
            $rule[$v['rule_code']] = $v['status'];
            $rule_param[$v['rule_code']] = unserialize($v['value']);
        }
        $this->assign('rule',$rule);
        $this->assign('rule_param',$rule_param);
        $this->display();
    }

    /**
     * 提交配置
     */
    public function config_submit(){
        $rule = I('request.rule');
        $rule_param = I('request.rule_param');

        foreach($rule_param as $k=>$v){
            $where = [
                'rule_code' =>$k
            ];
            $data = [
                'status'=>empty($rule[$k])?0:1,
                'value'=>serialize($v)
            ];
            M('ConfigsIntegral')->where($where)->data($data)->save();
        }

        $this->ajaxReturn($this->result->success()->toArray());
    }

}