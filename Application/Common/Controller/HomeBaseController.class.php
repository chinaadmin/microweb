<?php
/**
 * 前台基类
 */
namespace Common\Controller;
use User\Org\Util\User;

class HomeBaseController extends BaseController{

    protected $user_instance;
    protected $user;
    protected $uid = 0;
    protected $code_file = 'home';
    public function _initialize(){
		parent::_initialize();

        $this->user_instance = User::getInstance ();
        $this->user = $this->user_instance->getUser();
        if(!empty($this->user)){
            $this->uid = $this->user['uid'];
        }
        $this->assign('user',$this->user);
        $this->_seo();

        //样式版本号
        $this->assign('style_version',C('JT_CONFIG_WEB_STYLE_VERSION'));
	}
	function _empty(){
		$this->_404();
	}
	
    /**
     * seo
     */
    private function _seo(){
        //网站头部SEO
        $act_seo = '_'.ACTION_NAME.'_seo';
        $arr_seo = [];
        //重设seo 针对动态的seo
        if (method_exists($this,$act_seo)){
            $arr_seo = $this->$act_seo();
        }
        $cl_seo = cl_seo($arr_seo);
        $this->assign('seo_title', $cl_seo['title']);
        $this->assign('seo_keyword', $cl_seo['keywords']);
        $this->assign('seo_description', $cl_seo['description']);
    }
    /**
     * 分类
     */
    public function cats(){
    	return D('Home/Category')->getCats();
    }

    /**
     * 菜单
     * @param int $group 分组
     * @return array
     */
    private function _menu($group){
        $menu_model = D('Admin/MallMenu');
        $menu_list = $menu_model->cacheLevel($group);
        $menu_list = array_map(function($data) use($menu_list){
            $menu = [
                'id'=>$data['id'],
                'pid'=>$data['pid'],
                'level'=>$data['level'],
                'name'=>$data['name'],
                'url'=>$data['show_url'],
                'current'=>0,
                'target'=>$data['target']==1?'_blank':'_top'
            ];
            return $menu;
        },$menu_list);
        return $menu_list;
    }

    /**
     * 顶部菜单
     * @return array
     */
    public function top_menu(){
        return $this->_menu(1);
    }

    /**
     * 底部菜单
     * @return array
     */
    public function bottom_menu(){
        return $this->_menu(2);
    }

    /**
     * 帮助菜单
     * @return array
     */
    public function help_menu(){
        return D('Home/Article')->getHelpMenu(5,5);
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
    	$request = I ( 'request.' );
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
    	$options = array_merge_recursive ( ( array ) $opt->getValue ( $model ), $options );
    	$total = $model->where ( $options ['where'] )->count ();
    	if (isset ( $request ['r'] )) {
    		$listRows = ( int ) $request ['r'];
    	} else {
    		$listRows = C ( 'JT_CONFIG_WEB_PAGE_LISTROWS' ) > 0 ? C ( 'JT_CONFIG_WEB_PAGE_LISTROWS' ) : 10;
    	}
    	$page = new \Common\Org\Util\Page ( $total, $listRows, $request );
    	if ($total > $listRows) {
    		$page->setConfig ('first' , '首页');
    		$page->setConfig ('last', '最后一页');
    		$page->setConfig ('prev' , '上一页');
    		$page->setConfig ('next' , '下一页');
    		$page->setConfig ( 'theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%' );
    	}
    	$p = $page->homeShow ();
    	$this->assign ( '_page', $p ? $p : '' );
    	$this->assign ( '_total', $total );
    	$currPage = I('request.'.C('VAR_PAGE'));
    	$currPage = $currPage>$total?$total:$currPage;
    	$this->assign("currPage",$currPage);//当前页
    	$this->assign('totalPages',$page->totalPages);
    	$options ['limit'] = $page->firstRow . ',' . $page->listRows;
    	$model->setProperty ( 'options', $options );
    	return $model->field ( $field )->select ();
    }
     
    /**
     *404
     */
    protected function _404() {
    	header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
    	redirect(U('error/index'));
    }
}