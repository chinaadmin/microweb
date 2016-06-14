<?php
/**
 * 后台公共类
 * @author cwh
 * @date 2015-3-27
 */
namespace Common\Controller;
use Common\Org\Util\JtAuth;
use User\Org\Util\User;
use Think\Controller;
class AdminbaseController extends BaseController{

    /**
     * 权限
     * @var string
     */
    protected $auth_instance = '';
    protected $code_file = 'admin';
    protected $user_instance;
    protected $user = null;
    protected $is_super = false;
    protected $no_auth_actions = [];
    protected $curent_menu = null;
    protected $stores_id = 0;//门店id

    public function _initialize() {
        parent::_initialize ();
        $this->user_instance = User::getInstance ();
        $this->auth_instance = JtAuth::getInstance();
        $this->user = $this->user_instance->getUser();
        $this->assign('user',$this->user);
        $this->is_super = $this->user_instance->isSuperAdmin ();
        $this->assign('is_super',$this->is_super);
        $this->stores_id = D('Admin/Admin')->getStoresId($this->user);
        $this->userAuth();
    }

    /**
     * 权限检测
     * @param string  $rule    检测的规则
     * @param string  $module  模块
     * @param string  $mode    check模式：为url验证，其他为完全匹配
     * @return boolean
     */
    protected function checkRule($rule,$module = null, $mode='url'){
        //超级管理员拥有全部权限
        if($this->is_super){
            return true;
        }
        $module = empty($module)?MODULE_NAME:$module;
        $rule = $module.'/'.$rule;
        if(!$this->auth_instance->check($rule,$this->user['uid'],$mode)){
            return false;
        }
        return true;
    }

    /**
     * 操作检测
     * @param string  $rule   检测的规则
     * @param string  $module  模块
     * @return boolean
     */
    public function checkOperate($rule,$module = null){
        $rule = strtolower($rule);
        $module = empty($module)?MODULE_NAME:$module;
        //缓存之前检测过的操作
        static $_rule = array();
        if(isset($_rule[$module.$rule])){
            return $_rule[$module.$rule];
        }
        $result = $this->checkRule($rule,$module,null);
        $_rule[$module.$rule] = $result;
        return $result;
    }

    /**
     * 用户认证
     */
    public function userAuth(){
        if (in_array(ACTION_NAME, array_map('strtolower',$this->no_auth_actions))) {
            C("USER_AUTH_ON", false);
        } else {
            // 恢复权限认证
            C("USER_AUTH_ON", true);
        }

        // 用户权限检查
        if (C('USER_AUTH_ON') && !in_array(CONTROLLER_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            if (!$this->user_instance->isLogin()){
                $this->redirect(C('USER_AUTH_GATEWAY'));
            }

            //验证权限
            $rule  = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
            if (!$this->checkRule($rule)){
                if(IS_AJAX){
                    $this->ajaxReturn($this->result->error('未授权访问!')->toArray());
                }else {
                    $this->error('未授权访问!');
                }
            }
        }
    }

    /**
     * 设置当前菜单
     * @param string $url 路径
     */
    public function setCurrentMenu($url){
        $this->curent_menu = $url;
    }

    /**
     * 是否当前菜单
     * @param string $url 路径
     * @return bool
     */
    public function isCurrentMenu($url){
        $current_url = is_null($this->curent_menu) ? CONTROLLER_NAME . '/' . ACTION_NAME : $this->curent_menu;
        if (strtolower($current_url) == strtolower($url)) {
            return true;
        }
        return false;
    }

    /**
     * 获取顶部菜单
     * @return array
     */
    public function getMenu(){
        //获取菜单列表
        $menubar = D('Admin/Menubar');
        $menu = $menubar->getMenubarLevel(1);
        //获取分组
        $group_lists = $menubar->cacheMenubarGroup();
        //获取有权限的菜单
        $auth_menu = [];
        foreach ($menu as $key => $v) {
            if ($v['level'] == 1) {
                //获取二级菜单有权限的列表
                $second_menu = [];
                $is_current = false;
                $left_menu = [];
                foreach ($menu as $k2 => $v2) {
                    if ($v2['level'] == 2 && $v2['pid'] == $v['id']) {
                        //验证是否有菜单权限
                        if ($this->checkRule($v2['url'], null,null)) {
                            //是否当前菜单
                            if($this->isCurrentMenu($v2['url'])){
                                $v2['is_current'] = 1;
                                $is_current = true;
                            }
                            //格式化为有分组的值
                            if (empty($v2['gr_id'])) {//没有分组
                                $left_menu['mu' . $k2] = $v2;
                            } else {
                                if (empty($left_menu['gr' . $v2['gr_id']])) {
                                    $left_menu['gr' . $v2['gr_id']] = $group_lists[$v2['gr_id']]; //增加分组分类
                                }
                                if (!empty($v2['is_current']) && $v2['is_current'] == 1) {
                                    $left_menu['gr' . $v2['gr_id']]['is_current'] = 1;
                                }
                                $left_menu['gr' . $v2['gr_id']]['child']['mu' . $k2] = $v2; //增加分组详情
                            }

                            $second_menu[$k2] = $v2;
                        }
                    }
                }

                //一级菜单是否有权限
                if ($this->checkRule($v['url'], null,null) || !empty($second_menu)) {
                    //是否当前菜单
                    if($this->isCurrentMenu($v['url']) || $is_current){
                        $v['is_current'] = 1;
                    }
                    if(!empty($second_menu)){
                        $first = reset($second_menu);
                        $v['show_url'] = $first['show_url'];
                        $v['child'] = $left_menu;
                    }
                    $auth_menu[$key] = $v;
                }
            }
        }
        return $auth_menu;
    }

    /**
     * 列表
     * @param string $model 模型
     * @param array $where 过滤条件
     * @param string $order 排序
     * @param bool $field 字段
     * @param int $total 字据总条数
     * @return mixed
     */
    public function lists($model, $where = [], $order = '', $field = true,$total = 0) {
   		$where = $where ? $where : [];
     	$order = $order ? $order : '';
   		$field = $field ? $field : true;
    	$options = [ ];
    	$request = I ( 'request' );
    	if (is_string ( $model )) {
    		$model = M ( $model );
    	}
    	$opt = new \ReflectionProperty ( $model, 'options' );
    	$opt->setAccessible ( true );
    	$pk = $model->getPk ();
    	if (isset ( $request ['_order'] ) && isset ( $request ['_field'] )) {
    		$options ['order'] = '`' . $request ['_field'] . '` ' . $request ['_order'];
    	} elseif ($order === '' && empty ( $options ['order'] ) && ! empty ( $pk )) {
    		$options ['order'] = $pk . ' desc';
    	} elseif ($order) {
    		$options ['order'] = $order;
    	}
    	$options ['where'] = $where;
    	if (empty ( $options ['where'] )) {
    		unset ( $options ['where'] );
    	}
    	$options = array_merge( ( array ) $opt->getValue ( $model ), $options );
    	$total = $total ? $total : $model->where ( $options ['where'] )->count ();
        $page = $this->page($total,$request);
    	$options ['limit'] = $page->firstRow . ',' . $page->listRows;
    	$model->setProperty ( 'options', $options );
    	return $model->field ( $field )->select ();
    }

    /**
     * 分页
     * @param int $total 总数
     * @param null $request 参数
     * @return \Common\Org\Util\Page
     */
    public function page($total,$request = null){
        if(!is_null($request)) {
            $request = I('request');
        }
        if (isset ( $request ['r'] )) {
            $listRows = ( int ) $request ['r'];
        } else {
            $listRows = C ( 'PAGE_LISTROWS' ) > 0 ? C ( 'PAGE_LISTROWS' ) : 10;
        }
        $page = new \Common\Org\Util\Page ( $total, $listRows, $request );
        if ($total > $listRows) {
            $page->setConfig ('prev' , '上一页');
            $page->setConfig ('next' , '下一页');
            $page->setConfig ( 'theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%' );
        }
        $p = $page->bsShow ();
        $this->assign ( '_page', $p ? $p : '' );
        $this->assign ( '_total', $total );
        return $page;
    }

    //编辑
    function edit(){
    	if(method_exists($this,'_before_my_edit')){
    		$this->_before_my_edit();
    	}

    	$this->checkCurdModel();
    	$id = I('id','','trim');
    	if(!$id){
    		$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
    	}
    	$this->info = $this->model->find($id);
        if(CONTROLLER_NAME === 'MarketPushing'){
            $this->department = M("AdminDepartment")->select(); //所属部门
        }

    	if(method_exists($this,'_after_my_edit')){
    		$this->_after_my_edit($this->info);
    	}
    	$this->display('edit_add');
    }
    //增加
    function add(){
    	if(method_exists($this,'_after_my_edit')){
    		$this->_after_my_edit([]);
    	}

        if(CONTROLLER_NAME === 'MarketPushing'){
            $this->department = M("AdminDepartment")->select(); //所属部门
        }

    	$this->display('edit_add');
    }
    //编辑增加处理方法
    function update(){
    	$this->checkCurdModel();
    	$model = $this->model;
    	if(!$model->create()){
    		$this->ajaxReturn($this->result->error($model->getError())->toArray());
    	}
    	if(method_exists($this,'_before_my_update')){
    		$this->_before_my_update(I('request.'));
    	}
    	$pk = $model->getPk();
        //这是个什么鬼，有bug liuwh
    	if($model->$pk){ //更新
    		$code = $model->save() !== false ? 'SUCCESS' : 'DATA_MODIFICATIONS_FAIL';
    	}else{ //新增
    		$code = $model->add() ? 'SUCCESS' : 'DATA_INSERTION_FAIL';
    	}
    	$this->ajaxReturn($this->result->set($code)->toArray());
    }
    //删除 支持批量删除
    function del(){
    	$this->checkCurdModel();
    	$id = I('id');
    	if(!$id){
    		$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
    	}
    	if(I('request.soft_deletion','','trim') || ( $deletField = I('deletField'))){ //软删除
    		$deletField = $deletField ? $deletField : 'delete_time';
    		$result = $this->model->save([$this->model->getPk() => ['in',(array)$id],$deletField => NOW_TIME]);
    	}else{
	    	$result = $this->model->delete(implode(',', (array)$id));
    	}
    	if($result){
    		$this->ajaxReturn($this->result->set('SUCCESS')->toArray());
    	}else{
    		$this->ajaxReturn($this->result->set('DATA_DELETE_FAIL')->toArray());
    	}
    }
    private function checkCurdModel(){
    	$model = I('model');
    	if($model){
    		$this->model = D($model);
    	}
    	if(!$this->model){
    		$this->ajaxReturn($this->result->error('模型为空')->toArray());
    	}
    }
}
