<?php
/**
 * 门店后台公共类
 * @author cwh
 * @date 2015-05-11
 */
namespace Stores\Controller;
use Common\Controller\BaseController;
use Stores\Org\Util\Stores;
use User\Org\Util\User;
use Think\Controller;
class StoresbaseController extends BaseController{

    protected $code_file = 'admin';
    protected $user;
    protected $stores;
    protected $stores_id = 0;
    protected $is_super = false;
    protected $no_auth_actions = [];
    protected $curent_menu = null;

    public function _initialize() {
        parent::_initialize ();
        $this->user = User::getInstance ();
        $this->assign('user',$this->user->getUser());
        $this->stores = Stores::getInstance();
        $this->stores_id = $this->stores->check();
        $this->assign('stores_id',$this->stores_id);
        $this->userAuth();
    }

    /**
     * 用户认证
     */
    public function userAuth(){
        // 不需要验证的操作
        if (in_array(ACTION_NAME, $this->no_auth_actions)) {
            C("USER_AUTH_ON", false);
        } else {
            // 恢复权限认证
            C("USER_AUTH_ON", true);
        }

        // 用户权限检查
        if (C('USER_AUTH_ON') && !in_array(CONTROLLER_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            if (!$this->user->isLogin() || !$this->stores_id){
                $this->redirect(C('USER_AUTH_GATEWAY'));
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
     * 获取顶部菜单
     * @return array
     */
    public function getMenu(){
        //是否验证
        $is_varification = false;

        //获取验证的列表
        $verificationlist = array();
        /*if(empty($_SESSION[C('ADMIN_AUTH_KEY')])){
         $verificationlist = ELRBAC::getAgencyAccessList();
        $is_varification = true;
        }*/

        //获取菜单列表
        $menubar = D('Admin/Menubar');
        $menu = $menubar->getMenubarLevel(2);

        //获取分组
        $group_lists = $menubar->cacheMenubarGroup();

        //显示顶部菜单项
        $show_menu = [];
        foreach ($menu as $key => $v) {
            $current_url = is_null($this->curent_menu) ? CONTROLLER_NAME . '/' . ACTION_NAME : $this->curent_menu;
            if (strtolower($current_url) == strtolower($v['url'])) {
                $v['is_current'] = 1;
                $menu[$v['id']]['is_current'] = 1;
                if (!empty($v['pid'])) {
                    $menu[$v['pid']]['is_current'] = 1;
                }
            }
        }

        foreach ($menu as $key => $v) {
            if ($v['level'] == 1) {
                //验证是否有菜单权限
                if ($is_varification || $this->checkRule($v['url'], null) || 1) {
                    $show_menu[$key] = $v;
                }

                if (!empty($v['is_current'])) {
                    //显示2级菜单
                    $second_menu = [];
                    $groups = [];
                    foreach ($menu as $k1 => $v2) {
                        if ($v2['level'] == 2 && $v2['pid'] == $v['id']) {
                            //验证是否有菜单权限
                            if ($is_varification || $this->checkRule($v2['url'], null) || 1) {
                                //获取分组
                                if (!empty($v2['gr_id'])) {
                                    $groups[$v2['gr_id']] = $group_lists[$v2['gr_id']];
                                }
                                $second_menu[$k1] = $v2;
                            }
                        }
                    }

                    //按照分组生成菜单
                    $left_menu = [];
                    foreach ($second_menu as $se_k => $se_v) {
                        if (empty($se_v['gr_id'])) {
                            $left_menu['mu' . $se_k] = $se_v;
                        } else {
                            if (empty($left_menu['gr' . $se_v['gr_id']])) {
                                $left_menu['gr' . $se_v['gr_id']] = $groups[$se_v['gr_id']];
                            }
                            if (!empty($se_v['is_current']) && $se_v['is_current'] == 1) {
                                $left_menu['gr' . $se_v['gr_id']]['is_current'] = 1;
                            }
                            $left_menu['gr' . $se_v['gr_id']]['child']['mu' . $se_k] = $se_v;
                        }
                    }
                    $show_menu[$key]['child'] = $left_menu;
                }
            }
        }

        return $show_menu;
    }

    function checkRule(){

    }
    
    /**
     * 列表
     * @param string $model 模型
     * @param array $where 过滤条件
     * @param string $order 排序
     * @param bool $field 字段
     * @return mixed
     */
    public function lists($model, $where = [], $order = '', $field = true) {
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
    	$options = array_merge ( ( array ) $opt->getValue ( $model ), $options );
    	$total = $model->where ( $options ['where'] )->count ();
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

}