<?php
/**
 * 菜单模型类
 * @author cwh
 * @date 2015-03-30
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Util\Tree;
class MenubarModel extends AdminbaseModel{

    protected $tableName = 'admin_menubar';

    /*protected $_validate = [
        ['name', 'require', '菜单名称不合法', 1],
        ['gr_id', 'existsGroup', '菜单所属用户组不存在', 1,'callback'],
        ['status', [0,1], '菜单状态不正确', 1,'in']
    ];

    protected function existsGroup($data){
        if(!getMenuGroupName($data)){
            return false;
        }
        return true;
    }*/

    //命名范围
    protected $_scope = [
        'default'=>[
            'where'=>['status'=>1],
        ]
    ];

    /**
     * 类别
     * @var array
     */
    private $category = [
        1=>'后台菜单',
        2=>'门店菜单'
    ];

    /**
     * 获取类别
     * @param int|null $category 分组id
     * @return array
     */
    public function getCategory($category = null){
        if(is_null($category)){
            return $this->category;
        }
        return $this->category[$category];
    }

    /**
     * 获取有级别的菜单列表
     * @param int $category 类别
     * @return array
     */
    public function getMenubarLevel($category = 1){
        $menu_lists = $this->cacheMenubarLevel(false,$category);
        //获取当前类型对应菜单
        $menu = [];
        foreach ($menu_lists as $v){
            $menu[$v['menu_id']] = $v;
        }
        return $menu;
    }

    /**
     * 获取有级别的权限列表
     * @param int $category 类别
     * @return array
     */
    public function getAccessLevel($category = 1){
        $menu_lists = $this->cacheAccessLevel(false,$category);
        //获取当前类型对应菜单
        $menu = [];
        foreach ($menu_lists as $v){
            $menu[$v['menu_id']] = $v;
        }
        return $menu;
    }

    /**
     * 缓存显示菜单
     * @param bool $is_refresh 是否刷新缓存
     * @param int $category 类别
     * @return array
     */
    public function cacheMenubarLevel($is_refresh = false,$category = 1){
        return $this->cacheLevel($is_refresh,$category);
    }

    /**
     * 缓存权限菜单
     * @param bool $is_refresh 是否刷新缓存
     * @param int $category 类别
     * @return array
     */
    public function cacheAccessLevel($is_refresh = false,$category = 1){
        return $this->cacheLevel($is_refresh,$category,2);
    }

    /**
     * 更新菜单
     */
    public function updataMenubarLevel(){
        foreach($this->category as $key=>$v) {
            $this->cacheMenubarLevel(true,$key);
            $this->cacheAccessLevel(true,$key);
        }
    }

    /**
     * 缓存菜单
     * @param bool $is_refresh 是否刷新缓存
     * @param int $category 类别
     * @param int $type 类型：1为菜单，2为权限
     * @return array|mixed
     */
    public function cacheLevel($is_refresh = false,$category = 1,$type = 1){
        $redis_model = D('Common/Redis');
        $redis = $redis_model->getRedis();
        $menubar_key = 'jt_menubar';
        $menubar = $redis->get($menubar_key);
        $menubar = empty($menubar)?[]:$redis_model->unformat($menubar);
        $key = $category.'_'.$type;
        if(empty($menubar[$key])||$is_refresh){
            $menubar[$key] = $this->getMenubarToDB($category,$type);
            $redis->set($menubar_key,$redis_model->format($menubar));
            $redis_model->setTimesByKey($menubar_key);
        }
        $menu_lists = [];
        foreach($menubar[$key] as $v){
            $v['show_url'] = strpos($v['url'],'http://')===false?U($v['module'].'/'.parse_name($v['url']),[],true,true):$v['url'];
            $menu_lists[$v['menu_id']] = $v;
        }
        return $menu_lists;
    }

    /**
     * 获取菜单（数据库方式）
     * @param int $category 类别
     * @param int $type 类型：1为菜单，2为权限
	 * @param int $pid  
     * @return array
     */
    public function getMenubarToDB($category = 1,$type = 1,$pid=false){
        $where = [
            'is_show'=>['EXP','& '.$type.' = '.$type],
            'status'=>1,
            'category'=>$category
        ];
		if(is_int($pid)){
			$where['pid'] = $pid;
		}
		
        $menu = $this->where($where)->field('menu_id as id,menu_id,pid,name,module,gr_id,url,relation')->order('sort desc,menu_id asc')->select();
        $tree = new Tree($menu);
        $lists = $tree->getArraylevel();
        return $lists;
    }

    /**
     * 缓存菜单组
     * @param bool $flush 是否缓存数据
     * @param bool $is_refresh 是否刷新缓存
     * @return array|mixed
     */
    public function cacheMenubarGroup($flush = true,$is_refresh = false){
        $redis_model = D('Common/Redis');
        $group_lists = $redis_model->getCacheByKey('jt_menubar_group',$flush,$is_refresh);
        $group_lists = $redis_model->unformat($group_lists);
        return $group_lists;
    }

    /**
     * 获取菜单分组（数据库方式）
     * @return array
     */
    public function getMenubarGroupToDB(){
        $groups = M('AdminMenubarGroup')->field(true)->select();
        $group_lists = [];
        foreach ($groups as $v){
            $group_lists[$v['gr_id']] = $v;
        }
        return $group_lists;
    }

    /**
     * 获取所有菜单关联id
     * @param array $menuids 菜单ids
     * @return array
     */
    public function getAllRelationIds($menuids){
        if(empty($menuids)){
            return [];
        }
        $where = [
            'menu_id'=>['IN',$menuids]
        ];
        $relation_lists = $this->where($where)->getField('relation',true);
        $relation_ids = array();
        foreach($relation_lists as $v){
            if(!empty($v)){
                $v = explode(',',$v);
                if(!empty($v)){
                    $relation_ids = array_merge($relation_ids,$v);
                }
            }
        }
        $menuids = array_merge($menuids,$relation_ids);
        return array_filter(array_unique($menuids));
    }

    /**
     * 检测是否有子菜单
     * @param integer $id
     */
    public function validateSub($id) {
        $where = [
            'pid' => $id
        ];
        return $this->where($where)->count();
    }

    /**
     * json关联菜单值
     * @param string $relation 关联菜单
     * @return string
     */
    public function encodeRelation($relation){
        $relation = explode(',',$relation);
        return json_encode(array_unique(array_filter($relation)));
    }

    /**
     * 反转json关联菜单值
     * @param string $relation 关联菜单
     * @return string
     */
    public function decodeRelation($relation){
        return json_decode($relation);
    }

} 