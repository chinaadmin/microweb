<?php
/**
 * 菜单分组逻辑类
 * @author cwh
 * @date 2015-04-03
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class MenubarGroupController extends AdminbaseController {

    protected $curent_menu = 'MenubarGroup/index';

    /**
     * 列表
     */
    public function index(){
        $menu_model = D('Admin/MenubarGroup');
        $where = [];
        $menu = $this->lists($menu_model,$where);
        $this->assign('lists',$menu);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $user_model = D('Admin/MenubarGroup');
        $id = I('request.gr_id');
        if(!empty($id)) {
            $user = $user_model->field(true)->find($id);
        }
        $this->assign('info', $user);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.gr_id',0,'intval');
        $group_model = D('Admin/MenubarGroup');
        $data = [
            'name' => I('request.name','','htmlspecialchars,trim'),
            'icon' => I('request.icon','','htmlspecialchars,trim')
        ];
        if(!empty($id)) {
            $where = [
                'gr_id' => $id
            ];
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
        $id = I('request.gr_id');
        $admin_model = D('Admin/MenubarGroup');
        $where = [
            'gr_id' => $id
        ];
        $result = $admin_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

}