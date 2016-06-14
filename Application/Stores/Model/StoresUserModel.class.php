<?php
/**
 * 门店成员模型类
 * @author cwh
 * @date 2015-05-11
 */
namespace Stores\Model;
class StoresUserModel extends StoresbaseModel{

    public $_auto = [
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    protected $tableName = 'stores_user';

    /**
     * 用户在门店中
     * @param string $uid 用户id
     * @param int $stores_id 门店id
     * @return bool
     */
    public function inStores($uid,$stores_id){
        $count = $this->where([
            'stores_id'=>$stores_id,
            'uid'=>$uid
        ])->count();
        return $count > 0?true:false;
    }

    /**
     * 获取用户所在门店id
     * @param string $uid 用户id
     * @return array|mixed
     */
    public function getStoresIds($uid){
        $stores_ids = $this->where(['uid'=>$uid])->getField('stores_id',true);
        return empty($stores_ids)?[]:$stores_ids;
    }

    /**
     * 获取门店成员id
     * @param int $stores_id 门店id
     * @return mixed
     */
    public function getUserIds($stores_id){
        return $this->where(['stores_id'=>$stores_id])->getField('uid',true);
    }
    
    /**
     * 通过门店id获取门店成员
     * @param unknown $stores_id
     * @param string $field
     */
    public function getUsers($stores_id){
    	$where = array(
    			"stores_id"=>array('in',(array)$stores_id)
    	);
    	$stores = $this->where($where)->select();
    	$uids = array_column($stores,'uid');
    	$data = array();
    	if($uids && $stores){      //获取用户信息
    		$users = D("Admin/Admin")->getUserInfo($uids);
    		if($users){
    			foreach($stores as &$v){
    				foreach ($users as $vo){
    					if($v['uid'] == $vo['uid']){
    						$v['username'] = $vo['nickname'];
    						$v['mobile'] = $vo['mobile'];
    					}
    				}
    				$data[$v['stores_id']][] = $v;
    			}
    		}else{
    			$data = $stores;
    		}
    	}
    	return $data;
    }
    
    /**
     * 用户门店成员信息
     * @param string $uid 用户id
     * @param bool $field 字段
     * @return mixed
     */
    public function storesUser($uid,$field=true){
    	 $where = array(
    	 		'uid'=>$uid
    	 );
    	 return $this->field($field)->where($where)->find();
    }

    /**
     * 获取门店用户试图
     * @param null $stores_id 门店id
     * @param null $query_view 关联试图
     * @return mixed
     */
    public function getUserView($stores_id = null,$query_view = null){
        if(!is_null($stores_id)) {
            $where = [
                'stores_id' => $stores_id
            ];
        }
        if(is_null($query_view)) {
            $query_view = [
                'stores_user' => [
                    'id',
                    'stores_id',
                    'uid',
                    'manager',
                    'add_time'
                ],
                'admin_user' => [
                    'role_id',
                    'username',
                    'email',
                    'mobile',
                    'nickname',
                    '_on' => 'stores_user.uid = admin_user.uid',
                ]
            ];
        }
        return $this->dynamicView($query_view)->where($where);
    }
    /**
     * 获取门店用户关联表
     * @param int $stores_id 门店id
     * @param array $relation 关联内容
     * @return mixed
     */
    public function getUserRelation($relation = null){
    	if(is_null($relation)) {
    		$relation = [
    				 'admin_user' => [   
    				 		 'mapping_type'  => 2,  
    				 		   'foreign_key'   => 'uid',
    				],
    				'stores' =>[
    						'mapping_type'  => 2,
    						'foreign_key'   => 'stores_id',
    						'mapping_fields' => 'name'
    				]
    			];
    	}
    	return $this->dynamicRelation($relation)->relation(true);
    }
}