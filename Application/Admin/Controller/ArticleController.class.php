<?php
/**
 * 文章逻辑类
 * @author cwh
 * @date 2015-04-16
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\Util\Tree;

class ArticleController extends AdminbaseController {

    protected $curent_menu = 'Article/index';

    /**
     * 分组id
     * @var int
     */
    public $code = 0;

    public function _init(){

    }

    /**
     * 设置分类code
     * @param string $code 编号
     */
    private function _set_cat_code($code){
        if(empty($code)){
            $code = 'default';
        }
        $this->code = $code;
        $this->assign('code',$code);
        if(!empty($code)) {
            $menu_model = D('Home/ArticleCategory');
            $code_name = $menu_model->getCode($code);
            $this->assign('code_name', $code_name);
        }
        $current_menu = 'Article/cat_index';
        if($code != 'default'){
            $current_menu = 'Article/cat_index?code='.$code;
        }
        $this->setCurrentMenu($current_menu);
    }

    /**
     * 设置code
     * @param string $code 编号
     */
    private function _set_code($code){
        if(empty($code)){
            $code = 'default';
        }
        $this->code = $code;
        $this->assign('code',$code);
        if(!empty($code)) {
            $menu_model = D('Home/ArticleCategory');
            $code_name = $menu_model->getCode($code);
            $this->assign('code_name', $code_name);
        }
        $current_menu = 'Article/index';
        if($code != 'default'){
            $current_menu = 'Article/index?code='.$code;
        }
        $this->setCurrentMenu($current_menu);
    }

    /**
     * 类别列表
     */
    public function cat_index(){
        $this->_set_cat_code(I('request.code',''));
        $menu_model = D('Home/ArticleCategory');
        $where = [
            'code'=>$this->code
        ];
        $list = $menu_model->field('cat_id as id,cat_id,pid,name,sort,status')->where($where)->order('sort desc,cat_id asc')->select();
        $tree = new Tree($list);
        $tree->icon = array (
            '&nbsp;&nbsp;&nbsp;│ ',
            '&nbsp;&nbsp;&nbsp;├─ ',
            '&nbsp;&nbsp;&nbsp;└─ '
        );
        $cat_list = $tree->getArray();
        $this->assign('lists',$cat_list);
        $this->display();
    }

    /**
     * 编辑
     */
    public function cat_edit(){
        $info = [];
        $cat_model = D('Home/ArticleCategory');
        $id = I('request.id');
        if(!empty($id)) {
            $info = $cat_model->field(true)->find($id);
            $this->_set_cat_code($info['code']);
        }else{
            $this->_set_cat_code(I('request.code',''));
        }
        //获取上级菜单
        $top_list = $cat_model->cacheLevel($this->code,true);
        $tree = new Tree($top_list);
        $top_list = $tree->getArray();
        $this->assign('top_list',$top_list);

        $pid = I('request.pid');
        if(!empty($pid)){
            $info['pid'] = $pid;
        }
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 更新
     */
    public function cat_update(){
        $id = I('request.cat_id');
        $admin_model = D('Home/ArticleCategory');
        $data = [
            'pid' => I('request.pid'),
            'code' => I('request.code'),
            'name' => I('request.name'),
            'sort' => I('request.sort'),
            'status' => I('request.status'),
            'keywords' => I('request.keywords'),
            'description' => I('request.description'),
        ];
        if(!empty($id)) {
            $where = [
                'cat_id' => $id
            ];
            $result = $admin_model->setData($where,$data);
        }else{
            $result = $admin_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function cat_del(){
        $id = I('request.id');
        $menu_model = D('Home/ArticleCategory');
        if($menu_model->validateSub($id) > 0){
            $this->ajaxReturn($this->result->set('EXIST_SUB_MENUS','请先删除子菜单')->toArray());
        }else {
            //分类下是否存在文章
            if($menu_model->existArticle($id) > 0){
                $this->ajaxReturn($this->result->error('该分类下存在文章')->toArray());
            }
            $where = [
                'cat_id' => $id
            ];
            $result = $menu_model->delData($where);
            $this->ajaxReturn($result->toArray());
        }
    }

    /**
     * 保存排序
     */
    public function cat_sort(){
        $configs_model = D('Home/ArticleCategory');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','cat_id');
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 文章列表
     */
    public function index(){
    	U();
        $cat_id = I('request.cat_id',0);
        $where = [];
        if(!empty($cat_id)){
            $code = D('Home/ArticleCategory')->where(['cat_id'=>$cat_id])->getField('code');
            $where['cat_id'] = $cat_id;
        }else{
            $code = I('request.code','');
        }
        $this->assign('cat_id',$cat_id);

        $this->_set_code($code);
        $article_model = D('Home/Article')->getView($this->code);//->order('sort desc,id asc');

        //获取分类
        $cat_list = D('Home/ArticleCategory')->cacheLevel($this->code,false);
        $tree = new Tree($cat_list);
        $cat_list = $tree->getArray();
        $this->assign('cat_list',$cat_list);



        $menu = $this->lists($article_model,$where,'sort desc,id asc');
        $this->assign('lists',$menu);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $article = [];
        $article_model = D('Home/Article');
        $id = I('request.id');
        if(!empty($id)) {
            $queryView = [
                'article_category' => [
                    'code'
                ],
                'article' => [
                    'id' => 'id',
                    'cat_id',//分类id
                    'name',//文章标题
                    'sort',//排序
                    'status',//状态
                    'content',
                    'keywords',
                    'description',
                    'template',
                    'add_time',
                    '_on' => 'article_category.cat_id = article.cat_id',
                ]
            ];
            $article = $article_model->getView(null,$queryView)->where(['id'=>$id])->find();
            $this->_set_code($article['code']);
        }else{
            $this->_set_code(I('request.code',''));
        }

        //获取分类
        $top_list = D('Home/ArticleCategory')->cacheLevel($this->code,false);
        $tree = new Tree($top_list);
        $top_list = $tree->getArray();
        $this->assign('top_list',$top_list);

        $pid = I('request.pid');
        if(!empty($pid)){
            $article['pid'] = $pid;
        }
        $this->assign('info', $article);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $admin_model = D('Home/Article');
        $data = [
            'cat_id' => I('request.cat_id'),
            'name' => I('request.name'),
            'content' => I('request.content'),
            'keywords' => I('request.keywords'),
            'description' => I('request.description'),
            'sort' => I('request.sort'),
            'status' => I('request.status'),
            'template' => I('request.template')
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
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
        $id = I('request.id');
        $menu_model = D('Home/Article');
        $where = [
            'id' => $id
        ];
        $result = $menu_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 保存排序
     */
    public function sort(){
        $configs_model = D('Home/Article');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','id');
        $this->ajaxReturn($result->toArray());
    }
}