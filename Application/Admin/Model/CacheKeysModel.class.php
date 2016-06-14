<?php
/**
 * 缓存规则模型类
 * @author cwh
 * @date 2015-06-12
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class CacheKeysModel extends AdminbaseModel{

    protected $tableName = 'cache_keys';

    public $_validate = [
        [
            'key',
            'require',
            'key不能为空'
        ],
        [
            'key',
            '',
            'key已经存在',
            self::EXISTS_VALIDATE,
            'unique'
        ]
    ];

    /*public $_auto = [
        [
            'key',
            'checkPrefix',
            self::MODEL_BOTH,
            'callback'
        ]
    ];*/

    /**
     * 检查key前缀
     * @param string $key key
     * @return string
     */
    public function checkPrefix($key){
        $prefix = C('DATA_CACHE_PREFIX') ? C('DATA_CACHE_PREFIX') : 'jt_';
        if (stripos($key, $prefix) === false) {
            $key = "{$prefix}{$key}";
        }
        return $key;
    }

    /**
     * 生成缓存
     * @param string $ids id
     * @return \Common\Org\Util\Results
     */
    public function genereateCache($ids){
        $result = $this->result();
        if (empty($ids)) {
            return $result->error('缓存生成失败');
        }
        $where = [
            'id' => ['in',$ids]
        ];
        $listKeys = $this->where($where)->select();
        if ($listKeys) {
            foreach ($listKeys as $key => $val) {
                $genereate_result = $this->getCacheByInfo($val,true);
                if(!$genereate_result->isSuccess()){
                    return $genereate_result;
                }
            }
            return $result->success('缓存生成成功');
        } else {
            return $result->error('缓存生成失败');
        }
    }

    /**
     * 通过key获取缓存
     * @param string $key key
     * @param bool $flush 是否缓存数据
     * @param bool $is_refresh 是否刷新缓存
     * @return mixed
     */
    public function getCacheByKey($key,$flush = true, $is_refresh = false){
        $key = $this->checkPrefix($key);
        $redis_model = D('Common/Redis');
        $type = $redis_model->getRedis()->type($key);
        if ($type && $flush) {
            // 直接命中Redis
            $data = $redis_model->getCache($key, $type);
        } else {
            // 取数据库
            $where = [
                'key' => $key
            ];
            $key_setting = $this->field(true)->where($where)->find();
            if ($key_setting) {
                $genereate_result = $this->getCacheByInfo($key_setting, $is_refresh);
                if (!$genereate_result->isSuccess()) {
                    return null;
                }
                $data = $genereate_result->getResult();
            }
        }
        return $data;
    }

    /**
     * 根据key获取过期时间
     * @param string $key key
     * @return mixed
     */
    public function getTimes($key){
        return $this->where(['key'=>$key])->getField('timer');
    }

    /**
     * 获取缓存
     * @param array $info
     * @param bool $flush 是否生成缓存
     * @return array|mixed
     */
    public function getCacheByInfo($info,$flush = true){
        $result = $this->result();
        $config = unserialize($info ['config']);
        if (empty ($config)) {
            return $result->error('配置参数错误');
        }
        $data = [];
        $model = $this->dataFromObject($info ['type'], $config ['model']);
        switch ($info ['type']) {
            case 1:
                $params = $config ['param'] ? implode(',', $config ['param']) : '';
                $method = $config ['function'];
                if (!$method) {
                    return $result->error('配置参数错误');
                }
                $data = $model->$method ($params);
                break;
            case 2:
                $data = $model->query($config ['sql']);
                break;
        }
        // 自动生成缓存
        if ($flush) {
            if (D('Common/Redis')->setCache($info ['key'], $info ['redis_type'], $info ['timer'], $data) === false) {
                return $result->error('缓存生成失败');
            }
        }
        return $result->content($data)->success();
    }

    /**
     * 数据来源对象
     * @param int $type 类型
     * @param string $model 模型
     * @return \Model|\Think\Model
     */
    public function dataFromObject($type, $model = ''){
        if ($type == 1 && $model) {
            $model = ucfirst($model);
            return D($model);
        } else {
            return M();
        }
    }

}