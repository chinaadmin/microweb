<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class ArticleController extends HomeBaseController {

    public $cat_title = [
        'help'=>'帮助中心',
        'default'=>'关于我们',
    ];

    public function index(){
        $id = I('get.id');
        $where = [
            'id'=>$id
        ];
        $article = D('Home/Article')->field(true)->where($where)->find();
        $where = [
            'cat_id'=>$article['cat_id']
        ];
        $article_cat = D('Home/ArticleCategory')->field(true)->where($where)->find();
        $this->assign('article',$article);
        $this->assign('cat_title',$this->cat_title[$article_cat['code']]);

        //左边菜单
        $article_menu = D('Home/Article')->getMenu('all','all',$article_cat['code']);
        foreach($article_menu as &$article_v){
            $is_current = false;
            foreach($article_v['child'] as &$v) {
                if($v['id'] == $id) {
                    $v['current'] = 1;
                    $is_current = true;
                }
            }
            if($is_current){
                $article_v['current'] = 1;
            }
        }
        $this->assign('article_menu',$article_menu);
        $this->display();
    }

}