<?php
/**
 * 配置逻辑类
 * @author cwh
 * @date 2015-04-07
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\Util\Tree;

class ConfigsController extends AdminbaseController {

    protected $curent_menu = 'Configs/index';

    public function index(){
        $configs_model = D('Admin/Configs');
        $where = [];
        $where['pid'] = 0;
        $where['type'] = 'group';
        if(!$this->is_super){
            $where['is_admin'] = ['neq',1];
        }
        $groups = $configs_model->field('configs_id,name')->where($where)->order('sort desc')->select();
        $pid = I ( 'request.pid', $groups [0] ['configs_id'], 'intval' );
        $this->assign('groups', $groups);
        $this->assign('pid', $pid);

        $where = [];
        $where['pid'] = $pid;
        if(!$this->is_super){
            $where['is_admin'] = array('neq',1);
        }
        $volist = $configs_model->field(true)->where($where)->order('sort desc')->select();
        foreach($volist as $i=>$v){
            if(!empty($v['options'])){
                $volist[$i]['options'] = $configs_model->unserializeOption($v['options']);
            }
            //图片
            if($v['type'] == 'image'){
                $volist[$i]['value'] = D('Upload/AttachMent')->getAttach($v['value']);
            }
        }
        $this->assign('lists', $volist);
        $this->display();
    }

    public function config(){
        $configs_model = D('Admin/Configs');
        $result = $configs_model->saveConf (I('post.'));
        $this->ajaxReturn($result->toArray());
    }

    public function lists() {
        $where = [];
        $configs_model = D('Admin/Configs');
        if(!$this->user_instance->isSuperAdmin ()){
            $where['is_admin'] = ['neq',1];
        }
        $menubar = $configs_model->where($where)->field('configs_id as id,pid,name,code,type,value,sort,is_system,is_admin')->order('sort desc,configs_id asc')->select();
        $configs_type = $configs_model->getType();
        foreach($menubar as $i=>$v){
            $menubar[$i]['type'] = $configs_type[$v['type']];
        }

        $tree = new Tree($menubar);
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $this->assign('lists', $tree->getArray());
        $this->display ();
    }

    /**
     * 编辑
     */
    public function edit(){
        $configs_model = D('Admin/Configs');
        $id = I('request.id',0,'intval');
        if(!empty($id)) {
            $configs = $configs_model->field(true)->find($id);
        }
        $this->assign('info', $configs);
        $this->assign('configs_type', $configs_model->getType());
        $this->assign('configs_lists',$configs_model->where(['pid'=>0])->getField('configs_id,name'));
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.configs_id',0,'intval');
        $group_model = D('Admin/Configs');
        $data = [
            'name' => I('request.name','','htmlspecialchars,trim'),
            'pid' => I('request.pid'),
            'code' => I('request.code','','htmlspecialchars,trim'),
            'type' => I('request.type'),
            'value' => I('request.value'),
            'desc' => I('request.desc'),
            'options' => I('request.options'),
            'sort' => I('request.sort'),
            'width' => I('request.width'),
            'is_system' => I('request.is_system'),
            'is_admin' => I('request.is_admin')
        ];
        if(!empty($id)) {
            $where = [
                'configs_id' => $id
            ];
            $data['configs_id'] = $id;
            $result = $group_model->setData($where,$data);
        }else{
            $result = $group_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $configs_model = D('Admin/Configs');
        $where = [
            'configs_id' => $id
        ];
        $result = $configs_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 保存排序
     */
    public function sort(){
        $configs_model = D('Admin/Configs');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','configs_id');
        $this->ajaxReturn($result->toArray());
    }

}