<?php
/**
 * 菜单逻辑类
 * @author cwh
 * @date 2015-04-13
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\Util\Tree;

class MallMenuController extends AdminbaseController {

    protected $curent_menu = 'MallMenu/index?group=1';

    /**
     * 分组id
     * @var int
     */
    public $group = 0;

    public function _init(){
        $this->_set_group(I('request.group',0));
    }

    /**
     * 设置分组id
     * @param int $group_id 分组id
     */
    private function _set_group($group_id){
        $this->group = $group_id;
        $this->assign('group',$group_id);
        if(!empty($group_id)) {
            $menu_model = D('Admin/MallMenu');
            $group_name = $menu_model->getGroup($group_id);
            $this->assign('group_name', $group_name);
        }
        $this->setCurrentMenu('MallMenu/index?group='.$group_id);
    }

    public function index(){
        $menu_model = D('Admin/MallMenu');
        $where = [
            'group'=>$this->group
        ];
        $menu = $menu_model->where($where)->order('sort desc,id asc')->select();
        $menu = array_map(function($info)use($menu_model){
            $types = $menu_model->getType();
            $target = $menu_model->getTarget();
            $info['type_name'] = $types[$info['type']];
            $info['target_name'] = '';
            //if($info['type']==1){
                $info['target_name'] = $target[$info['target']];
            //}
            return $info;
        },$menu);
        $tree = new Tree($menu);
        $tree->icon = array (
            '&nbsp;&nbsp;&nbsp;│ ',
            '&nbsp;&nbsp;&nbsp;├─ ',
            '&nbsp;&nbsp;&nbsp;└─ '
        );
        $menu_list = $tree->getArray();
        $this->assign('lists',$menu_list);

        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $menu = [];
        $menu_model = D('Admin/MallMenu');
        $id = I('request.id');
        if(!empty($id)) {
            $menu = $menu_model->field(true)->find($id);
            $this->_set_group($menu['group']);
        }
        //获取上级菜单
        $top_list = $menu_model->cacheLevel($this->group,false);
        $tree = new Tree($top_list);
        $top_list = $tree->getArray();
        $this->assign('top_list',$top_list);

        //获取菜单类型
        $type_list = $menu_model->getType();
        $this->assign('type_list',$type_list);

        //获取菜单指向
        $target_list = $menu_model->getTarget();
        $this->assign('target_list',$target_list);

        $pid = I('request.pid');
        if(!empty($pid)){
            $menu['pid'] = $pid;
        }
        $this->assign('info', $menu);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $name = I('request.name');
        $admin_model = D('Admin/MallMenu');
        $data = [
            'pid' => I('request.pid'),
            'group' => I('request.group'),
            'name' => trim($name),
            'type' => I('request.type'),
            'url' => I('request.url'),
            'target' => I('request.target'),
            'sort' => I('request.sort'),
            'status' => I('request.status'),
            'content' => I('request.content')
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            $data['id'] = $id;
            $result = $admin_model->setData($where,$data);
        }else{
            $result = $admin_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $menu_model = D('Admin/MallMenu');
        if($menu_model->validateSub($id) > 0){
            $this->ajaxReturn($this->result->set('EXIST_SUB_MENUS','请先删除子菜单')->toArray());
        }else {
            $where = [
                'id' => $id
            ];
            $result = $menu_model->delData($where);
            $this->ajaxReturn($result->toArray());
        }
    }

    /**
     * 保存排序
     */
    public function sort(){
        $configs_model = D('Admin/MallMenu');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','id');
        $this->ajaxReturn($result->toArray());
    }
}