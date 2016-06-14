<?php
/**
 * 菜单逻辑类
 * @author cwh
 * @date 2015-03-30
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\Util\Tree;

class MenubarController extends AdminbaseController {

    protected $curent_menu = 'Menubar/index';

    /**
     * 列表
     */
    public function index(){
        $pid = I('request.pid',0);
        $this->assign('pid',$pid);

        $menu_model = D('Admin/Menubar');
        if(empty($pid)) {
            $category = I('request.category', 1);
        }else{
            $category = $menu_model->where(['menu_id'=>$pid])->getField('category');
        }
        $this->assign('category',$category);



        $this->assign('category_lists',$menu_model->getCategory());
        $where = [
            'pid'=>$pid,
            'category'=>$category
        ];
        $menu = $this->lists($menu_model,$where,'sort desc,menu_id asc');

        $gr_ids = array_unique(array_column($menu,'gr_id'));
        if(!empty($gr_ids)) {
            $group_model = D('Admin/MenubarGroup');
            $where = ['gr_id' => ['in', $gr_ids]];
            $gr_lists = $group_model->where($where)->getNames();
            $menu = array_map(function ($info) use ($gr_lists) {
                $info['group_name'] = $gr_lists[$info['gr_id']];
                $relation = json_decode($info['relation']);
                $info['is_relation'] = empty($relation)?'否':'是';
                return $info;
            }, $menu);
        }

        $this->assign('lists',$menu);

        //上级菜单
        $prev_id = $menu_model->where(['menu_id'=>$pid])->getField('pid');
        $prev_id = empty($prev_id)?0:$prev_id;
        $this->assign('prev_id',$prev_id);

        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        //获取菜单分组列表
        $group_model = D('Admin/MenubarGroup');
        $group_lists = $group_model->getNames();
        $this->assign('group_lists', $group_lists);

        $menu_model = D('Admin/Menubar');

        $menu = [];
        $id = I('request.menu_id');
        if(!empty($id)) {
            $menu = $menu_model->field(true)->find($id);
        }
        $pid = I('request.pid');
        if(!empty($pid)){
            $menu['pid'] = $pid;
        }
        $category = I('request.category');
        if(!empty($category)){
            $menu['category'] = $category;
        }
        $this->assign('info', $menu);

        //获取上级菜单
        $menu_list = $menu_model->cacheLevel(true,$menu['category'],0);
        $tree = new Tree($menu_list);
        $top_list = $tree->getArray();
        $this->assign('top_list',$top_list);

        $this->display();
    }

    /**
     * 获取关联菜单的Json
     */
    public function getRelationJson(){
        $id = I('request.id');
        $menu_model = D('Admin/Menubar');
        if(!empty($id)) {
            $menu_info = $menu_model->field(true)->find($id);
            $relation = $menu_model->decodeRelation($menu_info['relation']);
            $category = $menu_info['category'];
        }else{
            $category = I('request.category');
        }

        $menu_list = $menu_model->scope()->where(['category'=>$category])->field('menu_id as id,pid,name')->order('sort desc,menu_id asc')->select();
        $menu_list = array_map(function($info)use($relation){
            if(in_array($info['id'],$relation)) {
                $info['checked'] = true;
            }
            return $info;
        },$menu_list);
        $this->ajaxReturn($menu_list);
    }

    /**
     * 更新关联
     */
    public function updataRelation(){
        $id = I('request.id');
        $relation = I('request.relation');
        $admin_model = D('Admin/Menubar');
        $data = [
            'relation' => $admin_model->encodeRelation($relation),
        ];
        $where = [
            'menu_id' => $id
        ];
        $result = $admin_model->setData($where,$data);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.menu_id');
        $is_show_arr = I('request.is_show');
        $is_show = 0;
        array_map(function($info)use(&$is_show){
            $is_show = $info|$is_show;
        },$is_show_arr);
        $admin_model = D('Admin/Menubar');
        $gr_id = I('request.gr_id');
        $data = [
            'pid' => I('request.pid'),
            'gr_id' => empty($gr_id)?null:$gr_id,
            'name' => I('request.name'),
            'icon' => I('request.icon'),
            'module'=>I('request.module'),
            'category' => I('request.category',1),
            'is_show' => $is_show,
            'relation' => $admin_model->encodeRelation(I('request.relation')),
            'url' => I('request.url'),
            'sort' => I('request.sort'),
            'status' => I('request.status'),
            'remark' => I('request.remark')
        ];
        if(!empty($id)) {
            $where = [
                'menu_id' => $id
            ];
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
        $id = I('request.menu_id');
        $menu_model = D('Admin/Menubar');
        if($menu_model->validateSub($id) > 0){
            $this->ajaxReturn($this->result->set('EXIST_SUB_MENUS','请先删除子菜单')->toArray());
        }else {
            $where = [
                'menu_id' => $id
            ];
            $result = $menu_model->delData($where);
            $this->ajaxReturn($result->toArray());
        }
    }

    /**
     * 保存排序
     */
    public function sort(){
        $configs_model = D('Admin/Menubar');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','menu_id');
        $this->ajaxReturn($result->toArray());
    }

}