<?php
/**
 * redis模型类
 * @author cwh
 * @date 2015-06-12
 */
namespace Common\Model;
use Common\Org\Cache\Redis;

class RedisModel extends BaseModel{

    //不验证字段
    protected $autoCheckFields = false;

    /**
     * 字符串
     */
    const REDIS_STRING          = 1;
    /**
     * 集合
     */
    const REDIS_SET             = 2;
    /**
     * 列表
     */
    const REDIS_LIST            = 3;
    /**
     * 有序集
     */
    const REDIS_ZSET            = 4;
    /**
     * 哈希表
     */
    const REDIS_HASH            = 5;

    /**
     * 类型
     * @var array
     */
    public $type = [
        self::REDIS_STRING => [
            'id' => 1,
            'name' => 'string',
            'title' => '字符'
        ],
        self::REDIS_SET => [
            'id' => 2,
            'name' => 'set',
            'title' => '集合'
        ],
        self::REDIS_LIST => [
            'id' => 3,
            'name' => 'list',
            'title' => '队列'
        ],
        self::REDIS_ZSET => [
            'id' => 4,
            'name' => 'zset',
            'title' => '有序集'
        ],
        self::REDIS_HASH => [
            'id' => 5,
            'name' => 'hash',
            'title' => '哈希表'
        ]
    ];

    //redis实例
    private $redis;

    public function _initialize() {
        parent::_initialize ();

        $this->redis = new Redis([
            'host'=>C ( 'REDIS_HOST' ),
            'port'=>C ( 'REDIS_PORT' ),
            'timeout'=>C ( 'REDIS_TIMEOUT' ),
            'dbname'=>C ( 'REDIS_DBNAME' ),
            'ctype'=>C ( 'REDIS_CTYPE' ),
            'auth'=>C ( 'REDIS_AUTH' )
        ]);
    }

    /**
     * 获取类型对应属性
     * @param string $type 类型
     * @param string $attr 属性
     * @return null|string
     */
    public function getType($type = null,$attr = null){
        if(is_null($attr)){
            if(is_null($type)){
                return $this->type;
            }
            return $this->type[$type];
        }

        if(is_null($type)){
            $arr = [];
            foreach($this->type as $k=>$v){
                $arr[$k] = empty($v[$attr])?$v['id']:$v[$attr];
            }
            return $arr;
        }
        return isset($this->type[$type][$attr])?$this->type[$type][$attr]:$this->type[$type]['id'];
    }

    /**
     * 返回redis对象
     * @return \Common\Org\Cache\Redis
     */
    public function redis() {
        return $this->getRedis();
    }

    /**
     * 返回redis对象
     * @return \Common\Org\Cache\Redis
     */
    public function getRedis() {
        return $this->redis;
    }

    /**
     * 获取key列表
     * @param string $prefix 前缀
     * @return mixed
     */
    public function allKeys($prefix = '') {
        $key = $prefix ? $prefix . '*' : '*';
        return $this->redis->getKeys ( $key );
    }

    /**
     * 运行生成
     * @param string $key key
     * @param int $type 类型
     * @param int $timer 缓存时间
     * @param string|array $data 值
     * @return mixed
     */
    public function setCache($key, $type, $timer, $data){
        switch ($type) {
            case self::REDIS_STRING :
                if (is_array($data)) {
                    $data = $this->format($data);
                }
                $result = $this->redis->set($key, $data, 0, 0, $timer);
                break;
            case self::REDIS_SET :
                $result = $this->redis->setAdd($key, $data);
                break;
            case self::REDIS_LIST :
                $result = $this->redis->listPush($key, $data);
                break;
            case self::REDIS_ZSET :
                $result = $this->redis->setRange($key, $data);
                break;
            case self::REDIS_HASH :
                $result = $this->redis->hashSet($key, $data);
                break;
        }
        if ($timer) {
            $this->redis->setKeyExpire($key, $timer);
        }
        return $result;
    }

    /**
     * 获取数据
     * @param string $key key
     * @param int $type 类型
     * @return mixed
     */
    public function getCache($key, $type){
        switch ($type) {
            case self::REDIS_STRING :
                $result = $this->redis->get($key);
                break;
            case self::REDIS_SET :
                $result = $this->redis->setSize($key);
                break;
            case self::REDIS_LIST :
                $result = $this->redis->listSize($key);
                break;
            case self::REDIS_ZSET :
                $result = $this->redis->setRange($key);
                break;
            case self::REDIS_HASH :
                $result = $this->redis->hashGet($key);
                break;
        }
        return $result;
    }

    /**
     * 通过key获取缓存
     * @param string $key key
     * @param bool $flush 是否缓存数据
     * @param bool $is_refresh 是否刷新缓存
     * @return mixed
     */
    public function getCacheByKey($key,$flush = true, $is_refresh = false){
        return D('Admin/CacheKeys')->getCacheByKey($key, $flush, $is_refresh);
    }

    /**
     * 根据key获取过期时间
     * @param string $key key
     * @return mixed
     */
    public function getTimesByKey($key){
        return D('Admin/CacheKeys')->getTimes($key);
    }

    /**
     * 设置key过期时间
     * @param $key
     */
    public function setTimesByKey($key){
        $timer = $this->getTimesByKey($key);
        if ($timer) {
            $this->redis->setKeyExpire($key, $timer);
        }
    }

    /**
     * 格式化
     * @param string $cache 缓存
     * @return string
     */
    public function format($cache){
        return json_encode($cache);
    }

    /**
     * 反格式化
     * @param string $cache 缓存
     * @return mixed
     */
    public function unformat($cache){
        return is_array($cache)?$cache:json_decode($cache,true);
    }

    /**
     * 计算缓存大小
     * @param string $key key
     * @param int|null $type 类型
     * @return int
     */
    public function getSize($key, $type = null){
        if (is_null($type)) {
            $type = $this->redis->type($key);
        }
        switch ($type) {
            case self::REDIS_STRING :
                $size = $this->redis->strlen($key);
                break;
            case self::REDIS_SET :
                $size = $this->redis->setSize($key);
                break;
            case self::REDIS_LIST :
                $size = $this->redis->listSize($key);
                break;
            case self::REDIS_ZSET :
                $size = $this->redis->setSize($key, 1);
                break;
            case self::REDIS_HASH :
                $size = $this->redis->hashLen($key);
                break;
        }
        return $size;
    }

}