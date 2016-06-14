<?php
/**
 * 菜单模型类
 * @author cwh
 * @date 2015-03-30
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Util\Tree;
class MallMenuModel extends AdminbaseModel{

    protected $tableName = 'mall_menubar';

    public $_validate = [
        [['name','group'],'','该菜单已经存在',self::MUST_VALIDATE,'unique',self::MODEL_BOTH]
    ];

    /**
     * 分组
     * @var array
     */
    private $group = [
        1=>'顶部菜单',
        2=>'底部菜单'
    ];

    /**
     * 类型
     * @var array
     */
    private $type = [
        1=>'链接',
        //2=>'单页面'
    ];

    /**
     * 菜单指向
     * @var array
     */
    private $target = [
        0=>'当前窗口',
        1=>'新窗口'
    ];

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>['status'=>1],
        ],
        'default'=>[

        ]
    ];

    /**
     * 获取菜单分组
     * @param int|null $group_id 分组id
     * @return array
     */
    public function getGroup($group_id = null){
        if(is_null($group_id)){
            return $this->group;
        }
        return $this->group[$group_id];
    }

    /**
     * 获取菜单类型
     * @param int|null $type_id 类型id
     * @return array
     */
    public function getType($type_id = null){
        if(is_null($type_id)){
            return $this->type;
        }
        return $this->type[$type_id];
    }

    /**
     * 获取菜单指向
     * @param int|null $key 指向
     * @return array
     */
    public function getTarget($key = null){
        if(is_null($key)){
            return $this->target;
        }
        return $this->target[$key];
    }

    /**
     * 缓存菜单
     * @param int $group 分组
     * @param bool $flush 是否缓存数据
     * @param bool $is_refresh 是否刷新缓存
     * @return array|mixed
     */
    public function cacheLevel($group = 1,$flush = true,$is_refresh = false){
        $redis_model = D('Common/Redis');
        $menu = $redis_model->getCacheByKey('jt_mall_menu',$flush,$is_refresh);
        $menu = $redis_model->unformat($menu);
        $menu_lists = [];
        foreach($menu as $v){
            if($v['group']== $group) {
                $v['show_url'] = strpos($v['url'], 'http://') === false ? U($v['url']) : $v['url'];
                $menu_lists[$v['id']] = $v;
            }
        }
        return $menu_lists;
    }

    /**
     * 获取菜单（数据库方式）
     * @return array
     */
    public function getLevelToDB(){
        $where = [
            'status'=>1
        ];
        $menu = $this->where($where)->field('id,pid,name,type,group,url,target')->order('sort desc,id asc')->select();
        $tree = new Tree($menu);
        return $tree->getArraylevel();
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

}