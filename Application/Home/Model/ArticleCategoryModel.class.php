<?php
/**
 * 文章分类模型类
 * @author cwh
 * @date 2015-04-16
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
use Common\Org\Util\Tree;

class ArticleCategoryModel extends HomebaseModel{

    protected $tableName = 'article_category';

    /**
     * 分组编号
     * @var array
     */
    private $code = [
        'default'=>'文章',
        'help'=>'帮助文章',
    ];

    /**
     * 获取编号
     * @param string $code 编号
     * @return array
     */
    public function getCode($code){
        if(is_null($code)){
            return $this->code;
        }
        return $this->code[$code];
    }

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>['status'=>1],
        ],
        'default'=>[

        ]
    ];

    /**
     * 缓存文章分类
     * @param string $code 编号
     * @param bool $is_refresh 是否刷新缓存
     * @return array|mixed
     */
    public function cacheLevel($code = 'default',$is_refresh = false){
        $redis_model = D('Common/Redis');
        $redis = $redis_model->getRedis();
        $key = 'jt_article_category';
        $article_category = $redis->get($key);
        $article_category = empty($article_category)?[]:$redis_model->unformat($article_category);
        if(empty($article_category[$code])||$is_refresh){
            $article_category[$code] = $this->getLevelToDB($code);
            $redis->set($key,$redis_model->format($article_category));
            $redis_model->setTimesByKey($key);
        }
        return $article_category[$code];
    }

    /**
     * 获取文章分类（数据库方式）
     * @param string $code 编号
     * @return array
     */
    public function getLevelToDB($code = 'default'){
        $where = [
            'code'=>$code,
            'status'=>1
        ];
        $cats = $this->where($where)->field('cat_id as id,cat_id,pid,name,code')->order('sort desc,id asc')->select();
        $tree = new Tree($cats);
        $cat_lists = $tree->getArraylevel();
        return $cat_lists;
    }

    /**
     * 检测是否有子分类
     * @param int $id 分类id
     * @return bool|int
     */
    public function validateSub($id) {
        $where = [
            'pid' => $id
        ];
        return $this->where($where)->count();
    }

    /**
     * 是否存在文章
     * @param int $cat_id 分类id
     * @return bool|int
     */
    public function existArticle($cat_id){
        return D('Home/Article')->where(['cat_id'=>$cat_id])->count();
    }
}