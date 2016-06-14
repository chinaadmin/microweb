<?php
/**
 * 管理员逻辑类
 * @author cwh
 * @date 2015-04-01
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AdminController extends AdminbaseController {

    protected $curent_menu = 'Admin/index';
    protected $no_auth_actions = ['userinfo'];
    /**
     * 列表
     */
    public function index(){
		
	
    	$this->show_role_list();
        $user_model = D('Admin/Admin');
        $where = [];
        if($mix_keywords = I('mix_keywords','','trim')){
        	$this->mix_keywords = $mix_keywords;
        	$where['username|nickname'] = ['like','%'.$mix_keywords.'%'];
        }
        if(($role_id = I('role_id',0,'int') ) && $role_id != -1){
        	$this->assign('role_id',$role_id);
        	$where['role_id'] = $role_id;
        }
        $role_model = D('Admin/Role');
        //去除或显示超级用户
        if(!$this->is_super){
        	$where['_string'] = "role_id !=  ".$role_model->getSuperRoleId();
        }
        $user = $this->lists($user_model,$where,'add_time desc');
        //角色
        $role_ids = array_unique(array_column($user,'role_id'));
        if(empty($role_ids)){
            $role_lists = [];
        }else {
            $where = ['role_id' => ['in', $role_ids]];
            $role_lists = $role_model->where($where)->field(true)->select();
        }
        $role_lists = array_column($role_lists,'name','role_id');
        $user = array_map(function($info) use ($role_lists){
            $info['role_name'] = $role_lists[$info['role_id']];
            return $info;
        },$user);

        $this->assign('lists',$user);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        //获取角色列表
        $role_model = D('Admin/Role');
        $role_lists = $role_model->scope_super()->select();
        $this->assign('role_lists', $role_lists);
		//获取门店列表
		$stores_list = M('stores') -> field("stores_id,name") -> select();
		$this->assign('stores_list', $stores_list);
        $user_model = D('Admin/Admin');
        $id = I('request.uid');
        if(!empty($id)) {
            $user = $user_model->field(true)->find($id);
        }else{
        	$user['sex'] = 1;
        }
        $this->assign('info', $user);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.uid');
        $admin_model = D('Admin/Admin');
        $data = [
            'role_id' => I('request.role_id'),
            'username' => I('request.username'),
            'email' => I('request.email'),
            'sex' => I('request.sex'),
            'mobile' => I('request.mobile'),
            'nickname' => I('request.nickname'),
            'password' => I('request.password'),
            'status' => I('request.status'),
            'remark' => I('request.remark'),
            'stores_id' => I('request.stores_id')
        ];
        if(!empty($id)) {
            $where = [
                'uid' => $id
            ];
            $data['uid']=$id;
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
        $id = I('request.uid');
        if(C('SUPPER_USER_ID')==$id){
            $this->ajaxReturn($this->result->error('超级管理员账户不能删除')->toArray());
        }
        $admin_model = D('Admin/Admin');
        $where = [
            'uid' => $id
        ];
        $result = $admin_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }
    public function userinfo($url = "Admin/userinfo"){
    	$this->setCurrentMenu($url);
    	$stores_id = $this->stores_id;
    	$uid = I('uid','','trim');
    	if($uid && IS_AJAX){//更新操作
    		if($stores_id){
	    		$where['stores_id'] = $stores_id;
    		}
    		$where['uid'] = $uid;
    		$data = [
    				'mobile' => I('mobile'),
    				'email' => I('email','','trim'),
    				'status' => I('status',0,'int'),
    				'nickname' => I('nickname','','trim'),
    				'sex' => I('sex',0,'int')
    		];
    		if($password = I('password','','trim')){
    			$data['password'] = $password;
    		}
    		$res = D('Admin/Admin')->setData($where,$data);
    		$this->ajaxReturn($res->toArray());
    	}else if($uid && $stores_id){//更新展示页 门店用户
    		$viewFields = [
    				'stores_user' => [
    						'stores_id',
    						'uid',
    						'manager',
    				],
    				'admin_user' => [
    						'*',
    						'_on' => 'stores_user.uid = admin_user.uid',
    				],
    				'stores' => [
    						'name',
    						'_on' => 'stores.stores_id = stores_user.stores_id',
    				]
    		];
    		$this->info = D('Stores/StoresUser')->getUserView($this->stores_id,$viewFields)->where(['admin_user.uid' => $uid])->find();
    	}else{//后台管理用户
    		$this->info = D('Admin/Admin')->where(['uid' => $uid])->find();
    	}
    	$this->assign('stores_id',$stores_id); 
    	$this->display('editinfo');
    }
	private function show_role_list(){
		$this->role_list = D('Admin/Role')->scope_super()->select();
	}
}