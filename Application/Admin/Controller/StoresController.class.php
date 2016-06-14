<?php
/**
 * 门店逻辑类
 * @author cwh
 * @date 2015-05-08
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class StoresController extends AdminbaseController {

    protected $curent_menu = 'Stores/index';

    public function index(){
        $stores_model = D('Stores/Stores');
        $where = [];
        $stores = $this->lists($stores_model,$where,'sort desc,stores_id asc');
        $this->assign('lists',$stores);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $stores_model = D('Stores/Stores');
        $id = I('request.stores_id');
        if(!empty($id)) {
            $stores = $stores_model->field(true)->find($id);
        }
       	$stores['address_list'] = htmlspecialchars_decode($stores['address_list']);
       	$this->permission_delivery = $stores_model->get_permission_delivery($id,$this->stores_id,$this->user_instance->isAdmin() || $this->user_instance->isSuperAdmin());
        $this->assign('info', $stores);
        $this->display();
    }
    /**
     * 更新
     */
    public function update(){
        $id = I('request.stores_id');
        $stores_model = D('Stores/Stores');
        $localtion = I('request.provice').' '.I('request.city').' '.I('request.county');
        $data = [
            'name' => I('request.name'),
            'provice' => I('request.provice_id'),
            'city' => I('request.city_id'),
            'county' => I('request.county_id'),
            'localtion' => $localtion,
            'address' => I('request.address'),
            'phone' => I('request.phone'),
            'status' => I('request.status'),
            'sort' => I('request.sort',0),
            'remark' => I('request.remark'),
            'address_list' => I('request.address_list'),
            'lat_lon' => I('request.lat_lon')
        ];
        $am_start_time = I('request.am_start_time');
        $am_end_time = I('request.am_end_time');
        $pm_start_time = I('request.pm_start_time');
        $pm_end_time = I('request.pm_end_time');
        if($am_start_time && $am_end_time){
        	$amStart = strtotime('1987-10-08 '.$am_start_time);
        	$amEnd = strtotime('1987-10-08 '.$am_end_time);
        	if($amStart >= $amEnd){
        		$this->ajaxReturn($this->result->error()->setMsg('上午起始时间有误')->toArray());
        	}
        	$data['am_start_time'] = $am_start_time;
        	$data['am_end_time'] = $am_end_time;
        }
        if($pm_start_time && $pm_end_time){
        	$pmStart = strtotime('1987-10-08 '.$pm_start_time);
        	$pmEnd = strtotime('1987-10-08 '.$pm_end_time);
        	if($pmStart >= $pmEnd){
        		$this->ajaxReturn($this->result->error()->setMsg('下午起始时间有误')->toArray());
        	}
        	$data['pm_start_time'] = $pm_start_time;
        	$data['pm_end_time'] = $pm_end_time;
        }
        if(!empty($id)) {
            $where = [
                'stores_id' => $id
            ];
            $data['stores_id']=$id;
            $result = $stores_model->setData($where,$data);
        }else{
            $result = $stores_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.stores_id');
        $stores_model = D('Stores/Stores');
        $where = [
            'stores_id' => $id
        ];
        $result = $stores_model->delData($where,true);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 成员
     */
    public function member(){
        $stores_model = D('Stores/Stores');
        $id = I('request.stores_id');
        if(!empty($id)) {
            $stores = $stores_model->field(true)->find($id);
        }else{
            $this->redirect('Stores/index');
        }
        $this->assign('info',$stores);
        $role_lists = D('Admin/Role')->getStoreRole();
        $this->assign('role_lists',$role_lists);
        $this->display();
    }

    /**
     * 管理员列表
     */
    public function get_member_lists(){
        $page = I('get.page',1,'intval');
        $pageSize = I('get.pageSize',8,'intval');

        //获取已有成员id
        $id = I('request.stores_id');
        $uids = D('Stores/StoresUser')->getUserIds($id);

        $admin_model = D('Admin/Admin');
        $where = [];

        //关键字
        $keyword = I('get.name');
        if(!empty($keyword)){
            $where['username|nickname'] = ['like','%'. $keyword .'%'];
        }

        $stores_role_id = D('Admin/Role')->getStoreRoleIds();
        $where['role_id'] = ['in',$stores_role_id];
        /*if(!empty($uids)){
            $where['uid'] = ['not in',$uids];
        }*/

        $count = $admin_model->scope()->where($where)->count();
        $lists = $admin_model->scope()->field(true)->where($where)->page($page,$pageSize)->order('add_time desc')->select();
        if($lists){
            $lists = array_map(function($info) use ($uids){
                $info['is_sel'] = false;
                if(in_array($info['uid'],$uids)){
                    $info['is_sel'] = true;
                }
                return $info;
            },$lists);
        }

        $data = array();
        $data['items'] = $lists;
        $data['count'] = $count;
        $this->ajaxReturn($this->result->content($data)->success()->toArray());
    }

    /**
     * 获取门店成员列表
     */
    public function get_stores_user_lists(){
        //获取已有成员id
        $id = I('request.stores_id');
        $lists = D('Stores/StoresUser')->getUserView($id)->order(['manager'=>'desc','add_time'=>'desc'])->select();

        $data = [];
        $data['items'] = $lists;
        $this->ajaxReturn($this->result->content($data)->success()->toArray());
    }

    /**
     * 选择门店成员
     */
    public function sel_stores_user(){
        $id = I('request.stores_id');
        $uid = I('request.uid');
        $result = D('Stores/StoresUser')->addData([
            'stores_id'=>$id,
            'uid'=>$uid,
            'manager'=>0
        ]);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 设置店长
     */
    public function set_manager(){
        $id = I('request.stores_id');
        $uid = I('request.uid');
        $manager = I('request.manager');
        $result = D('Stores/StoresUser')->setData([
            'stores_id'=>$id,
            'uid'=>$uid,
        ],[
            'manager'=>$manager
        ]);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 设置角色
     */
    public function set_role(){
        $uid = I('request.uid');
        $role_id = I('request.role_id');
        if($role_id == 4){//设置店长
        	$stores_id = I('request.stores_id');
        	//检查是否已有店长
        	$manageUid = M('stores_user')->where(['stores_id' => $stores_id,'manager' => 1])->getField('uid');
        	if($manageUid){//已设置了店长 将原店长改为店员 
        		D('Admin/Admin')->where(['uid'=>$manageUid])->data(['role_id'=>5])->save();
        		M('stores_user')->where(['uid' => $manageUid, 'stores_id' => $stores_id])->data(['manager'=>0])->save();
        	}
        }
        $manager = ($role_id == 4) ? 1 : 0;
        D('Admin/Admin')->where(['uid'=>$uid])->data(['role_id'=>$role_id])->save();
       	M('stores_user')->where(['uid' => $uid, 'stores_id' => $stores_id])->data(['manager'=>$manager])->save();
        $this->ajaxReturn($this->result->success()->toArray());
    }

    /**
     * 删除门店成员
     */
    public function del_stores_user(){
        $id = I('request.stores_id');
        $uid = I('request.uid');
        $where = [
            'stores_id'=>$id,
            'uid' => $uid
        ];
        $result = D('Stores/StoresUser')->delData($where);
        $this->ajaxReturn($result->toArray());
    }
    //修改门店权限
    public function change_stores_permission(){
    	
    }
    /**
     * 编辑店员
     */
	public function edit_stores_user(){
			$stores_id = I('request.stores_id');
			$uid = I('uid','','trim');
			$role_id = I('role_id','','int');
			if($uid && IS_AJAX){//更新操作
				if($stores_id){//更新店员所属门店
					$manger = ($role_id == 4) ? 1 : 0;
					D('Stores/StoresUser')->setData(['uid' => $uid,'manger' => $manger],['stores_id' => $stores_id]);
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
				$this->info = D('Stores/StoresUser')->getUserView($stores_id,$viewFields)->where(['admin_user.uid' => $uid])->find();
			}
			$this->ifOpenSidebar = true; 
			$this->assign('stores_id',$stores_id);
			$this->display();
	}
	public function showStoresList(){
		return D('Stores/Stores')->getStores();
	}
	function getLatLonByAddress(){
		$address = I('address','','trim');
		$baiduMap = new \Common\Org\Util\BaiduMap();
		$res = $baiduMap->getLngLatByAddress($address);
		if(!$res){
			$this->ajaxReturn($this->result->error());
		}
		$this->ajaxReturn($this->result->content(implode(',', $res))->success()->toArray());
	}
}