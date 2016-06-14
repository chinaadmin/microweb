<?php
/**
 * 店员逻辑类
 * @author wxb
 * @date 2015-06-17
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class StoresMemberController extends AdminbaseController {

    protected $curent_menu = 'StoresMember/index';
	//店员列表
    public function index(){
    	$this->title = '店员管理';
    	$id = $this->storesInfo();
    	$this->editStore($id);
    	//获取已有成员id
    	$viewFields = [
    			'stores_user' => [
                        'id',
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
    	$model = D('Stores/StoresUser')->getUserView($id,$viewFields);
    	$where = [];
    	if(($key = I('key','','trim'))){
			$where['username|nickname'] = ['like',"%{$key}%"];
    	}
        $this->lists = $this->lists($model, $where, ['manager'=>'desc','add_time'=>'desc']);
        $this->assign('stores_id',$id);
    	$this->display();
    }
    function del(){
    	$stores_id = $this->stores_id;
    	$id = I('request.uid');
    	$where = [
    			'id' => ['in',$id]
    	];
        if(!empty($stores_id)){
            $where['stores_id'] = $stores_id;
        }
    	$result = D('Stores/StoresUser')->delData($where);
    	$this->ajaxReturn($result->toArray());
    }
    //编辑更新店员资料
    function add_mod(){
    	$id = I('id','','trim');
        $where = [];
    	if($id && IS_AJAX){//更新操作
            /*if(!empty($stores_id)) {
                $where['stores_id'] = $stores_id;
            }*/
            $uid = I('uid','','trim');
    		$where['uid'] = $uid;
    		$data = [
    			'username' => I('username','','trim'),	
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
            if(!$res->isSuccess()){
                $this->ajaxReturn($res->toArray());
            }
            $data = [];
            $data['stores_id'] = I('stores_id','','trim');
            $data['uid'] = $uid;
            $res = D('Stores/StoresUser')->setData(['id'=>$id],$data);
    		$this->ajaxReturn($res->toArray());
    	}else if(IS_AJAX){//增加操作
    		$data = [
    				'username' => I('username','','trim'),
    				'mobile' => I('mobile'),
    				'email' => I('email','','trim'),
    				'status' => I('status',0,'int'),
    				'nickname' => I('nickname','','trim'),
    				'sex' => I('sex',0,'int'),
    				'role_id' => 5	,//店员
    				'password' => '888888'
    		];
    		$res = D('Admin/Admin')->addData($data);
    		if(!$res->isSuccess()){
    			$this->ajaxReturn($res->toArray());
    		}
    		$data = [];
    		$data['stores_id'] = I('stores_id','','trim');
    		$data['uid'] = $res->getResult();
    		$res = D('Stores/StoresUser')->addData($data);
    		$this->ajaxReturn($res->toArray());
    	}else if($id){//更新展示页
    		$viewFields = [
    				'stores_user' => [
                            'id',
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
    		$this->info = D('Stores/StoresUser')->getUserView(empty($this->stores_id)?null:$this->stores_id,$viewFields)->where(['stores_user.id' => $id])->find();

            if(empty($this->stores_id)){
                $this->stores_lists = D('Stores/Stores')->scope()->select();
            }
    	}else{
            $info = [];
            if(!empty($this->stores_id)){
                $info['name'] = M('stores')->where(['stores_id' => $this->stores_id])->getField('name');
            }else {
                $this->stores_lists = D('Stores/Stores')->scope()->select();
            }
    		$info['status'] = 1;
    		$info['sex'] = 1;
    		$this->info = $info;
    	}
        $this->assign('stores_id',$this->stores_id);
    	$this->display('edit');
    }
    function storesInfo(){
    	$sql = 'SELECT s.stores_id,s.name,COUNT(au.uid) membercount FROM `jt_stores` s 
				LEFT JOIN jt_stores_user su on s.stores_id = su.stores_id
				LEFT JOIN jt_admin_user au on au.uid = su.uid and au.delete_time = 0 and au.`status` = 1 
				GROUP BY stores_id';
    	$list = M()->query($sql);
        
    	$totalCount = 0;
    	$defaultStoresId = I('stores_id',null,'int');

    	if(!$defaultStoresId){
    		$defaultStoresId = $this->stores_id ? $this->stores_id : null;
    	}

		array_map(function($oneInfo)use(&$totalCount,&$defaultStoresId){
    		$totalCount += $oneInfo['membercount'];
    		if($defaultStoresId == null){
    			$defaultStoresId = $oneInfo['stores_id'];
    		}
    	}, $list);
    	$this->storesList = $list;
    	$this->totalCount = $totalCount;
    	$storeDetail = D('Stores/Stores')->getStoresById($defaultStoresId);

    	$sql = 'SELECT au.* FROM `jt_stores_user` su 
					left join jt_admin_user au on su.uid = au.uid
					where su.manager = 1 and su.stores_id =   
				'.$defaultStoresId;
    	$storeDetail['store_admin'] = M()->query($sql)[0]['username'];

    	$sql = 'SELECT au.* FROM `jt_stores_user` su
					left join jt_admin_user au on su.uid = au.uid
					where su.manager = 2 and su.stores_id =   
				'.$defaultStoresId;
    	$storeDetail['store_assistant'] = M()->query($sql)[0]['username'];
    	$this->storeDetail = $storeDetail;
    	return $defaultStoresId;
    }
    /**
     * 编辑
     */
    private function editStore($id){
    	$stores_model = D('Stores/Stores');
    	if(!empty($id)) {
    		$stores = $stores_model->field(true)->find($id);
    	}
    	$this->assign('editStore', $stores);
    }
    /**
     * 更新门店信息
     */
    public function storeUpdate(){
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
    			'remark' => I('request.remark')
    	];
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
     * 设置店长 不在这个店则增加
     */
    public function setManager(){
    	$stores_id = I('request.stores_id');
    	$uid = I('request.userid');
    	$where = [
    			'stores_id' => $stores_id,
    			'uid' => $uid
    	];
    	$data['manager'] = 1;
    	$model = D('Stores/StoresUser');
    	$model->where($where)->count();
    	if($model->where($where)->count()){ 
	    	$result = $model->setData($where,$data);
    	}else{//不在店里
    		//其它非店助设为店员
    		$result = $model->setData(['stores_id' => $stores_id,'manager' => 1],['manager' => 0]);
    		$data = array_merge($data,$where);
    		$result = $model->addData($data);
    	}
    	$this->ajaxReturn($result->toArray());
    }
    
    /**
     * 设置店助 不在这个店则增加
     */
    public function setAssistant(){
    	$stores_id = I('request.stores_id');
    	$uid = I('request.userid');
    	$where = [
    			'stores_id' => $stores_id,
    			'uid' => $uid
    	];
    	$data['manager'] = 2;
    	$model = D('Stores/StoresUser');
    	if($model->where($where)->count()){
    		$result = $model->setData($where,$data);
    	}else{//不在店里
    		//把原来店助变为店员
    		$result = $model->setData(['stores_id' => $stores_id,'manager' => 2],['manager' => 0]);
    		$data = array_merge($data,$where);
    		$result = $model->addData($data);
    	}
    	$this->ajaxReturn($result->toArray());
    }
    function changeStore(){
    	$yum_store_id = I('request.yum_store_id');
    	$new_stores_id = I('request.new_stores_id');
    	$uidStr = I('request.uidStr');
    	$model = D('Stores/StoresUser');
    	$where['stores_id'] = $yum_store_id;
    	$where['uid'] = ['in',$uidStr];
    	$data['stores_id'] = $new_stores_id;
    	$result = $model->setData($where,$data);
    	$this->ajaxReturn($result->toArray());
    }
}