<?php
/**
 * 角色逻辑类
 * @author cwh
 * @date 2015-04-02
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class RoleController extends AdminbaseController {

    protected $curent_menu = 'Role/index';

    public function index(){
        $user_model = D('Admin/Role');
        $m = D('Upload/AttachMent');
        $type = I('request.type', 1);
        $this->assign('type',$type);
        $this->assign('type_lists',$user_model->getType());

        $where = [
            'type'=>$type
        ];
        $role_model = D('Admin/Role');
        //去除或显示超级用户
        if(!$this->is_super){
        	$where['_string'] = "role_id !=  ".$role_model->getSuperRoleId();
        }
        $user = $this->lists($user_model,$where);
        foreach ($user as &$v){
        	$v['picPath'] = $m->getAttach($v['avatar_id']);
        	if($v['picPath']){
        		$v['picPath'] = fullPath($v['picPath'][0]['path']);
        	}else{
        		$v['picPath'] = '';
        	}
        }
		$menubar_model = D('Admin/Menubar');
        $menu_list = $menubar_model->getMenubarToDB(1,1,0);
		$this -> assign('menu_list',$menu_list);
        $this->assign('lists',$user);
        $this->display();
    }

    /**
     * 获取权限json
     */
    public function getPermissionJson(){
        $id = I('request.id');
        $permission_lists = D('Role')->getPermissionIds($id);

        $menubar_model = D('Admin/Menubar');
        $menu_list = $menubar_model->getAccessLevel();
        $menu = [];
		$oid ="";
        foreach($menu_list as $v){ 
			$menu[] = [
				'id'=>$v['id'],
				'name'=>$v['name'],
				'pid'=>$v['pid'],
				'checked'=>in_array($v['id'],$permission_lists)?true:false,
				'level'=>$v['level']
			];
	
        }
        $this->ajaxReturn($this->result->content($menu)->success()->toArray());
    }

    /**
     * 设置权限
     */
    public function setPermission(){
        $id = I('request.role_id');
        $access = I('request.access');
        $result = D('Role')->setPermission($id,$access);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 编辑
     */
    public function edit(){
        $role_model = D('Admin/Role');
        $m = D('Upload/AttachMent');
        $id = I('request.role_id');
        if(!empty($id)) {
            $role = $role_model->field(true)->find($id);
            $role['picPath'] = $m->getAttach($role['avatar_id']);
            if($role['picPath']){
	            $role['picPath'][0]['path'] = fullPath($role['picPath'][0]['path']);
            }
        }
        $type = I('request.type');
        if(!empty($type)){
            $role['type'] = $type;
        }
        $this->assign('info', $role);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.role_id');
        $role_model = D('Admin/Role');
        $data = [
            'name' => I('request.name'),
            'status' => I('request.status'),
            'type'=>I('request.type'),
            'remark' => I('request.remark'),
            'avatar_id' => I('request.avatar_id'),
            'maxim' => I('request.maxim'),
        ];
        if($this->is_super){
            $data['code'] = I('request.code');
            $data['is_system'] = I('request.is_system');
        }
        if(!empty($id)) {
            $where = [
                'role_id' => $id
            ];
            $data['role_id']=$id;
            $result = $role_model->setData($where,$data);
        }else{
            $result = $role_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.role_id');
        $role_model = D('Admin/Role');
        $where = [
            'role_id' => $id
        ];
        $role_model->startTrans();
        if(M('AdminRoleMenubar')->where($where)->delete()===false){
            $role_model->rollback();
            $this->ajaxReturn($this->result->error('删除失败')->toArray());
        }
        $result = $role_model->delData($where);
        if($result->isSuccess()) {
            $role_model->commit();
        }else{
            $role_model->rollback();
        }
        $this->ajaxReturn($result->toArray());
    }


}