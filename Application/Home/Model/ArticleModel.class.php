<?php
/**
 * 文章模型类
 * @author cwh
 * @date 2015-04-16
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class ArticleModel extends HomebaseModel{

    protected $tableName = 'article';

    public $_validate = [
        ['name','require','标题不能为空'],
        ['cat_id','require','所属分类不能为空'],
        ['content','require','内容不能为空'],
    ];
    public $_auto = [
        ['add_time','time',self::MODEL_INSERT,'function']
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
     * 获取试图
     * @param int|null $code 编号
     * @param int|array $queryView 试图
     * @return array
     */
    public function getView($code = null,$queryView = null){
        if(!is_null($code)) {
            $where = [
                'code' => $code,
                'article_category.status' => 1
            ];
        }
        if(is_null($queryView)) {
            $queryView = [
                'article_category' => [
                    'code',
                    'name' => 'cat_name',//分类名称
                    //'status'//状态
                ],
                'article' => [
                    'id' => 'id',
                    'cat_id',//分类id
                    'name',//文章标题
                    'sort',//排序
                    'status',//状态
                    'add_time',
                    '_on' => 'article_category.cat_id = article.cat_id',
                ]
            ];
        }
        return $this->dynamicView($queryView)->where($where);
    }

    /**
     * 获取帮助菜单列表
     * @param int|string $cat_num 分类数目
     * @param int|string $art_num 文章数目
     * @return mixed
     */
    public function getHelpMenu($cat_num = 'all',$art_num = 'all'){
        return $this->getMenu($cat_num,$art_num,'help');
    }

    /**
     * 格式化菜单
     * @param array $article 文章
     * @return array
     */
    public function formatMenu($article){
        return [
            'id'=>$article['id'],
            'name'=>$article['name'],
            'url'=>U('Article/index', ['id' => $article['id']]),
        ];
    }

    /**
     * 获取菜单列表
     * @param int|string $cat_num 分类数目
     * @param int|string $art_num 文章数目
     * @param string $type 类型
     * @return mixed
     */
    public function getMenu($cat_num = 'all',$art_num = 'all',$type = 'default'){
        $menu = F('HelpMenu_'.$cat_num.'_'.$art_num.'_'.$type);
        if(!$menu) {
            $article_cat = D('Home/ArticleCategory')->cacheLevel($type);
            $cat_i = 0;
            $menu = [];
            //帮助分类
            $cat_ids = [];
            foreach ($article_cat as $v) {
                if ($cat_i >= $cat_num && $cat_num != 'all') {
                    break;
                }
                $cat_id = $v['cat_id'];
                $menu[$cat_id] = [
                    'id' => $cat_id,
                    'name' => $v['name']
                ];
                $cat_ids[] = $cat_id;
                $cat_i++;
            }

            //帮助文章
            if(empty($menu)){
                $article = [];
            }else {
                $where = [
                    'cat_id' => ['in', $menu]
                ];
                $field = [
                    'id', 'name', 'cat_id'
                ];
                $article = $this->where($where)->scope('normal')->field($field)->order('sort desc,id asc')->select();
            }
            foreach ($menu as &$menu_v) {
                $art_i = 0;
                $menu_child = [];
                foreach ($article as $art_v) {
                    if ($art_i >= $art_num && $art_num != 'all') {
                        break;
                    }
                    if($menu_v['id'] == $art_v['cat_id']) {
                        $menu_child[] = $this->formatMenu($art_v);
                        $art_i++;
                    }
                }
                $menu_v['child'] = $menu_child;
            }
            F('HelpMenu_'.$cat_num.'_'.$art_num.'_'.$type,$menu);
        }
        return $menu;
    }
    /**
     * 通过编号获取文字
     * @param  $code 编号
     * @param string $field
     * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
     */
    public function getByCode($code,$field=true){
    	$where = array(
    			"code"=>$code
    	);
    	return $this->field($field)->scope()->where($where)->find();
    }

}