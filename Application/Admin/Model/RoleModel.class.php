<?php
/**
 * 管理员用户类
 * @author cwh
 * @date 2015-04-01
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use User\Org\Util\User;
class RoleModel extends AdminbaseModel{

    protected $tableName = 'admin_role';

    //命名范围
    protected $_scope = [
        'store'=>[
            'where'=>[
                'type'=>2,
                'status'=>1
            ]
        ]
    ];

    /**
     * 类型
     * @var array
     */
    private $type = [
        1=>'后台',
        2=>'门店'
    ];

    /**
     * 获取类型
     * @param int|null $type 分组id
     * @return array
     */
    public function getType($type = null){
        if(is_null($type)){
            return $this->type;
        }
        return $this->type[$type];
    }

    /**
     * 获取编号
     * @param int $role_id 角色id
     * @return mixed
     */
    public function getCode($role_id){
        return $this->where(['role_id'=>$role_id])->getField(true);
    }

    /**
     * 获取门店角色id
     * @return mixed
     */
    public function getStoreRoleIds(){
        return $this->scope('store')->getField('role_id',true);
    }

    /**
     * 获取门店角色列表
     * @return mixed
     */
    public function getStoreRole(){
        return $this->field(true)->scope('store')->select();
    }

    /**
     * 角色是否属于门店
     * @param int $role_id 角色id
     * @return mixed
     */
    public function isStoreRole($role_id){
        $type = $this->where(['role_id'=>$role_id])->getField('type');
        return $type == 2?true:false;
    }

    /**
     * 设置权限
     * @param int $id 角色id
     * @param array $access 权限
     * @return $this
     */
    public function setPermission($id,$access = []){
        $role_menubar_model = M('AdminRoleMenubar');
        $result = $role_menubar_model->where(['role_id'=>$id])->delete();
        if($result !== false){
            if(!empty($access)) {
                $dataall = [];
                $data = [
                    'role_id' => $id
                ];
                foreach ($access as $v) {
                    $data['menu_id'] = $v;
                    $dataall[] = $data;
                }
                $result = $role_menubar_model->addAll($dataall);
            }
            return $result!==false?$this->result()->success('设置权限成功') : $this->result()->error('设置权限失败');
        }
        return $this->result()->error('设置权限失败');
    }

    /**
     * 获取权限
     * @param int $id 角色id
     * @return array
     */
    public function getPermissionIds($id){
        if(empty($id)){
            return [];
        }
        $id = is_int($id)?$id:['IN',(array)$id];
        $role_menubar_model = M('AdminRoleMenubar');
        return $role_menubar_model->where(['role_id'=>$id])->getField('menu_id',true);
    }
    
    //去除或显示超级用户
    function scope_super() {
    	$user = User::getInstance();
    	if(!$user->isSuperAdmin()){
    		$role_id = $this->getSuperRoleId();
    		$this->options['where'] = array_merge($this->options['where'] ? $this->options['where'] : [] ,['role_id' => ['neq',$role_id]]);
    	}
    	return $this;
    }
    //获取超级管理员角色id
    function getSuperRoleId(){
    	return $this->where(['code' => 'admin'])->getField('role_id');
    }

}