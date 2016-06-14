<?php
namespace Common\Org\Cache;

/**
 * phpredis扩展
 * by wbq 2012-5-22
 */
class Redis {
	// REDIS服务主机IP
	private $_host = null;
	
	// redis服务端口
	private $_port = null;

    // 授权密码
    private $_auth = null;
	
	// 连接时长 默认为0 不限制时长
	private $_timeout = 0;
	
	// 数据库名
	private $_dbname = null;
	
	// 连接类型 1普通连接 2长连接
	private $_ctype = 1;
	
	// 实例名
	public $_redis = null;
	
	// 事物对象
	private $_transcation = null;
	
	// 初始化
	public function __construct($options = []) {
		$this->_host = $options['host'];
		$this->_port = $options['port'];
		$this->_timeout = $options['timeout'];
		$this->_dbname = $options['dbname'];
		$this->_ctype = $options['ctype']?$options['ctype']: $this->_ctype;
        $this->_auth = $options['auth']?$options['auth']: $this->_auth;
		if (! isset ( $this->_redis )) {
			$this->_redis = new \Redis ();
			$this->connect ( $this->_host, $this->_port, $this->_auth,$this->_timeout, $this->_dbname, $this->_ctype );
		}
	}

	/**
	 * 连接redis服务器
	 */
	private function connect($host, $port, $auth,$timeout, $dbname, $type) {
		switch ($type) {
			case 1 :
				$this->_redis->connect ( $host, $port, $timeout );
				break;
			case 2 :
				$this->_redis->pconnect ( $host, $port, $timeout );
				break;
			default :
				break;
		}

        if(!empty($auth)){
            $this->_redis->auth($auth);
        }
	}

	/**
	 * 查看redis连接是否断开
	 *
	 * @return bool true:连接未断开 false:连接已断开
	 */
	public function ping() {
		$return = false;
		
		$return = $this->_redis->ping ();

		return $return === 'PONG' ? true : false;
	}

	/**
	 * 设置redis模式参数
	 *
	 * @param $option array
	 *        	参数数组键值对
	 * @return return true/false
	 */
	public function setOption($option = array()) {
		return $this->_redis->setOption ();
	}

	/**
	 * 获取redis模式参数
	 *
	 * @param array $option
	 *        	要获取的参数数组
     * @return int
	 */
	public function getOption($option = array()) {
		return $this->_redis->getOption ();
	}

    /**
     * 获取数据类型
     * @param string $key key值
     * @return string
     */
	public function type($key) {
		return $this->_redis->type ( $key );
	}

	/**
	 * 获取key内部信息
	 * 
	 * @param string $object        	
	 * @param string $key
     * @return string
	 */
	public function object($object, $key) {
		return $this->_redis->object ( $object, $key );
	}

	/**
	 * 写入key-value
	 *
	 * @param $key string
	 *        	要存储的key名
	 * @param $value mixed
	 *        	要存储的值
	 * @param $type int
	 *        	写入方式 0:不添加到现有值后面 1:添加到现有值的后面 默认0
	 * @param $repeat int
	 *        	0:不判断重复 1:判断重复
     * @param $time int
	 *        	过期时间(S)
	 * @param $old int
	 *        	1:返回旧的value 默认0
	 * @return bool true:成功 flase:失败
	 */
	public function set($key, $value, $type = 0, $repeat = 0, $time = 0, $old = 0) {
		$return = false;
		
		if ($type) {
			$return = $this->_redis->append ( $key, $value );
		} else {
			if ($old) {
				$return = $this->_redis->getSet ( $key, $value );
			} else {
				if ($repeat) {
					$return = $this->_redis->setnx ( $key, $value );
				} else {
					if ($time && is_numeric ( $time ))
						$return = $this->_redis->setex ( $key, $time, $value );
					else
						$return = $this->_redis->set ( $key, $value );
				}
			}
		}
		
		return $return;
	}

	/**
	 * 获取某个key值 如果指定了start end 则返回key值的start跟end之间的字符
	 *
	 * @param $key string/array
	 *        	要获取的key或者key数组
	 * @param $start int
	 *        	字符串开始index
	 * @param $end int
	 *        	字符串结束index
	 * @return mixed 如果key存在则返回key值 如果不存在返回false
	 */
	public function get($key = null, $start = null, $end = null) {
		$return = false;
		
		if (is_array ( $key ) && ! empty ( $key )) {
			$return = $this->_redis->getMultiple ( $key );
		} else {
			if (isset ( $start ) && isset ( $end ))
				$return = $this->_redis->getRange ( $key );
			else
				$return = $this->_redis->get ( $key );
		}
		
		return $return;
	}

	/**
	 * 删除某个key值
	 *
	 * @param array $key
	 *        	key数组
     * @return bool|void 删除成功的key的个数
	 */
	public function delete($key = array()) {
		$return = false;
		
		$return = $this->_redis->delete ( $key );
		
		return $return;
	}

	/**
	 * 判断某个key是否存在
	 *
	 * @param $key string
	 *        	要查询的key名
     * @return bool
	 */
	public function exists($key) {
		$return = false;
		
		$return = $this->_redis->exists ( $key );
		
		return $return;
	}

	/**
	 * key值自增或者自减
	 *
	 * @param $key string
	 *        	key名
	 * @param $type int
	 *        	0:自减 1:自增 默认为1
	 * @param $n int
	 *        	自增步长 默认为1
     * @return bool|int
	 */
	public function deinc($key, $type = 1, $n = 1) {
		$return = false;
		$n = ( int ) $n;
		
		switch ($type) {
			case 0 :
				if ($n == 1)
					$return = $this->_redis->decr ( $key );
				else if ($n > 1)
					$return = $this->_redis->decrBy ( $key, $n );
				break;
			case 1 :
				if ($n == 1)
					$return = $this->_redis->incr ( $key );
				else if ($n > 1)
					$return = $this->_redis->incrBy ( $key, $n );
				break;
			default :
				$return = false;
				break;
		}
		
		return $return;
	}

	/**
	 * 同时给多个key赋值
	 *
	 * @param $data array
	 *        	key值数组 array('key0'=>'value0','key1'=>'value1')
     * @return bool
	 */
	public function mset($data) {
		$return = false;
		
		$return = $this->_redis->mset ( $data );
		
		return $return;
	}

	/**
	 * 查询某个key的生存时间
	 *
	 * @param $key string
	 *        	要查询的key名
     * @return bool|int
	 */
	public function ttl($key) {
		$return = false;
		
		$return = $this->_redis->ttl ( $key );
		
		return $return;
	}

	/**
	 * 删除到期的key
	 *
	 * @param $key string
	 *        	key名
     * @return bool
	 */
	public function persist($key) {
		$return = false;
		
		$return = $this->_redis->persist ( $key );
		
		return $return;
	}

	/**
	 * 获取某一key的value
	 *
	 * @param $key string
	 *        	key名
     * @return bool|int
	 */
	public function strlen($key) {
		$return = false;
		
		$return = $this->_redis->strlen ( $key );
		
		return $return;
	}
	
	// +++-------------------------队列操作-------------------------+++//
	
	/**
	 * 入队列
	 *
	 * @param $list string
	 *        	队列名
	 * @param $value mixed
	 *        	入队元素值
	 * @param $direction int
	 *        	0:数据入队列头(左) 1:数据入队列尾(右) 默认为0
	 * @param $repeat int
	 *        	判断value是否存在 0:不判断存在 1:判断存在 如果value存在则不入队列
     * @return bool|int
	 */
	public function listPush($list, $value, $direction = 0, $repeat = 0) {
		$return = false;
		
		switch ($direction) {
			case 0 :
				if ($repeat)
					$return = $this->_redis->lPushx ( $list, $value );
				else
					$return = $this->_redis->lPush ( $list, $value );
				break;
			case 1 :
				if ($repeat)
					$return = $this->_redis->rPushx ( $list, $value );
				else
					$return = $this->_redis->rPush ( $list, $value );
				break;
			default :
				$return = false;
				break;
		}
		
		return $return;
	}

	/**
	 * 出队列
	 *
	 * @param $list1 string
	 *        	队列名
	 * @param $deriction int
	 *        	0:数据入队列头(左) 1:数据入队列尾(右) 默认为0
	 * @param $list2 string
	 *        	第二个队列名 默认null
	 * @param $timeout int
	 *        	timeout为0:只获取list1队列的数据
	 *        	timeout>0:如果队列list1为空 则等待timeout秒 如果还是未获取到数据 则对list2队列执行pop操作
     * @return array|bool|string
	 */
	public function listPop($list1, $deriction = 0, $list2 = null, $timeout = 0) {
		$return = false;
		
		switch ($deriction) {
			case 0 :
				if ($timeout && $list2)
					$return = $this->_redis->blPop ( $list1, $list2, $timeout );
				else
					$return = $this->_redis->lPop ( $list1 );
				break;
			case 1 :
				if ($timeout && $list2)
					$return = $this->_redis->brPop ( $list1, $list2, $timeout );
				else
					$return = $this->_redis->rPop ( $list1 );
				break;
			default :
				$return = false;
				break;
		}
		
		return $return;
	}

	/**
	 * 获取队列中元素数
	 *
	 * @param $list string
	 *        	队列名
     * @return bool|void
	 */
	public function listSize($list) {
		$return = false;
		
		$return = $this->_redis->lSize ( $list );
		
		return $return;
	}

	/**
	 * 为list队列的index位置的元素赋值
	 *
	 * @param $list string
	 *        	队列名
	 * @param $index int
	 *        	队列元素位置
	 * @param $value mixed
	 *        	元素值
     * @return BOOL
	 */
	public function listSet($list, $index = 0, $value = null) {
		$return = false;
		
		$return = $this->_redis->lSet ( $list, $index, $value );
		
		return $return;
	}

	/**
	 * 获取list队列的index位置的元素值
	 *
	 * @param $list string
	 *        	队列名
	 * @param $index int
	 *        	队列元素开始位置 默认0
	 * @param $end int
	 *        	队列元素结束位置 $index=0,$end=-1:返回队列所有元素
     * @return array|bool|void
	 */
	public function listGet($list, $index = 0, $end = null) {
		$return = false;
		
		if ($end) {
			$return = $this->_redis->lRange ( $list, $index, $end );
		} else {
			$return = $this->_redis->lGet ( $list, $index );
		}
		
		return $return;
	}

	/**
	 * 截取list队列，保留start至end之间的元素
	 *
	 * @param $list string
	 *        	队列名
	 * @param $start int
	 *        	开始位置
	 * @param $end int
	 *        	结束位置
     * @return array|bool
	 */
	public function listTrim($list, $start = 0, $end = -1) {
		$return = false;
		
		$return = $this->_redis->lTrim ( $list, $start, $end );
		
		return $return;
	}

	/**
	 * 删除list队列中count个值为value的元素
	 *
	 * @param $list string
	 *        	队列名
	 * @param $value int
	 *        	元素值
	 * @param $count int
	 *        	删除个数 0:删除所有 >0:从头部开始删除 <0:从尾部开始删除 默认为0删除所有
     * @return bool|int
	 */
	public function listRemove($list, $value, $count = 0) {
		$return = false;
		
		$return = $this->_redis->lRem ( $list, $value, $count );
		
		return $return;
	}

	/**
	 * 在list中值为$value1的元素前Redis::BEFORE或者后Redis::AFTER插入值为$value2的元素
	 * 如果list不存在，不会插入，如果$value1不存在，return -1
	 *
	 * @param $list string
	 *        	队列名
	 * @param $location int
	 *        	插入位置 0:之前 1:之后
	 * @param $value1 mixed
	 *        	要查找的元素值
	 * @param $value2 mixed
	 *        	要插入的元素值
     * @return bool|int
	 */
	public function listInsert($list, $location = 0, $value1, $value2) {
		$return = false;
		
		switch ($location) {
			case 0 :
				$return = $this->_redis->lInsert ( $list, \Redis::BEFORE, $value1, $value2 );
				break;
			case 1 :
				$return = $this->_redis->lInsert ( $list, \Redis::AFTER, $value1, $value2 );
				break;
			default :
				$return = false;
				break;
		}
		
		return $return;
	}

	/**
	 * pop出list1的尾部元素并将该元素push入list2的头部
	 *
	 * @param $list1 string
	 *        	队列名
	 * @param $list2 string
	 *        	队列名
     * @return bool|string
	 */
	public function rpoplpush($list1, $list2) {
		$return = false;
		
		$return = $this->_redis->rpoplpush ( $list1, $list2 );
		
		return $return;
	}
	
	// +++-------------------------集合操作-------------------------+++//
	
	/**
	 * 将value写入set集合 如果value存在 不写入 返回false
	 * 如果是有序集合则根据score值更新该元素的顺序
	 *
	 * @param $set string
	 *        	集合名
	 * @param $value mixed
	 *        	值
	 * @param $stype int
	 *        	集合类型 0:无序集合 1:有序集和 默认0
	 * @param $score int
	 *        	元素排序值
     * @return bool|int
	 */
	public function setAdd($set, $value = null, $stype = 0, $score = null) {
		$return = false;
		
		if ($stype && $score !== null) {
			$return = $this->_redis->zAdd ( $set, $score, $value );
		} else {
			$return = $this->_redis->sAdd ( $set, $value );
		}
		
		return $return;
	}

	/**
	 * 移除set1中的value元素 如果指定了set2 则将该元素写入set2
	 *
	 * @param $set1 string
	 *        	集合名
	 * @param $value mixed
	 *        	值
	 * @param $stype int
	 *        	集合类型 0:无序集合 1:有序集和 默认0
	 * @param $set2 string
	 *        	集合名
     * @return bool|int
	 */
	public function setMove($set1, $value = null, $stype = 0, $set2 = null) {
		$return = false;
		
		if ($set2) {
			$return = $this->_redis->sMove ( $set1, $set2, $value );
		} else {
			if ($stype)
				$return = $this->_redis->zRem ( $set1, $value );
			else
				$return = $this->_redis->sRem ( $set1, $value );
		}
		
		return $return;
	}

	/**
	 * 查询set中是否有value元素
	 *
	 * @param $set string
	 *        	集合名
	 * @param $value mixed
	 *        	值
     * @return bool
	 */
	public function setSearch($set, $value = null) {
		$return = false;
		
		$return = $this->_redis->sIsMember ( $set, $value );
		
		return $return;
	}

	/**
	 * 返回set中所有元素个数 有序集合要指定$stype=1
	 * 如果是有序集合并指定了$start和$end 则返回score在start跟end之间的元素个数
	 *
	 * @param $set string
	 *        	集合名
	 * @param $stype int
	 *        	集合类型 0:无序集合 1:有序集和 默认0
	 * @param $start int
	 *        	开始index
	 * @param $end int
	 *        	结束index
     * @return bool|int|void
	 */
	public function setSize($set, $stype = 0, $start = 0, $end = 0) {
		$return = false;
		
		if ($stype) {
			if ($start && $end)
				$return = $this->_redis->zCount ( $set, $start, $end );
			else
				$return = $this->_redis->zSize ( $set );
		} else {
			$return = $this->_redis->sSize ( $set );
		}
		
		return $return;
	}

	/**
	 * 随机返回set中一个元素并可选是否删除该元素
	 *
	 * @param $set string
	 *        	集合名
	 * @param $isdel int
	 *        	是否删除该元素 0:不删除 1:删除 默认为0
     * @return bool|string
	 */
	public function setPop($set, $isdel = 0) {
		$return = false;
		
		if ($isdel) {
			$return = $this->_redis->sPop ( $set );
		} else {
			$return = $this->_redis->sRandMember ( $set );
		}
		
		return $return;
	}

	/**
	 * 求交集 并可选是否将交集保存到新集合
	 *
	 * @param $set array
	 *        	集合名数组
	 * @param $newset string
	 *        	要保存到的集合名 默认为null 即不保存交集到新集合
	 * @param $stype int
	 *        	集合类型 0:无序集合 1:有序集和 默认0
	 * @param $weight array
	 *        	权重 执行function操作时要指定的每个集合的相同元素所占的权重 默认1
	 * @param $function string
	 *        	不同集合的相同元素的取值规则函数 SUM:取元素值的和 MAX:取最大值元素 MIN:取最小值元素
     * @return array|int
	 */
	public function setInter($set, $newset = null, $stype = 0, $weight = array(1), $function = 'SUM') {
		$return = array ();
		
		if (is_array ( $set ) && ! empty ( $set )) {
			if ($newset) {
				if ($stype)
					$return = $this->_redis->zInter ( $newset, $set, $weight, $function );
				else
					$return = $this->_redis->sInterStore ( $newset, $set );
			} else {
				$return = $this->_redis->sInter ( $set );
			}
		}
		
		return $return;
	}

	/**
	 * 求并集 并可选是否将交集保存到新集合
	 *
	 * @param $set array
	 *        	集合名数组
	 * @param $newset string
	 *        	要保存到的集合名 默认为null 即不保存交集到新集合
	 * @param $stype int
	 *        	集合类型 0:无序集合 1:有序集和 默认0
	 * @param $weight array
	 *        	权重 执行function操作时要指定的每个集合的相同元素所占的权重 默认1
	 * @param $function string
	 *        	不同集合的相同元素的取值规则函数 SUM:取元素值的和 MAX:取最大值元素 MIN:取最小值元素
     * @return array|int
	 */
	public function setUnion($set, $newset = null, $stype = 0, $weight = array(1), $function = 'SUM') {
		$return = array ();
		
		if (is_array ( $set ) && ! empty ( $set )) {
			if ($newset) {
				if ($stype)
					$return = $this->_redis->zUnion ( $newset, $set, $weight, $function );
				else
					$return = $this->_redis->sUnionStore ( $newset, $set );
			} else {
				$return = $this->_redis->sUnion ( $set );
			}
		}
		
		return $return;
	}

	/**
	 * 求差集 并可选是否将交集保存到新集合
	 *
	 * @param $set array
	 *        	集合名数组
	 * @param $newset string
	 *        	要保存到的集合名 默认为null 即不保存交集到新集合
     * @return array|int
	 */
	public function setDiff($set, $newset = null) {
		$return = array ();
		
		if (is_array ( $set ) && ! empty ( $set )) {
			if ($newset) {
				$return = $this->_redis->sDiffStore ( $newset, $set );
			} else {
				$return = $this->_redis->sDiff ( $set );
			}
		}
		
		return $return;
	}

	/**
	 * 返回set中所有元素
	 *
	 * @param $set string
	 *        	集合名
     * @return array|bool
	 */
	public function setMembers($set) {
		$return = false;
		
		$return = $this->_redis->sMembers ( $set );
		
		return $return;
	}

	/**
	 * 排序 分页等
	 *
	 * @param $set string
	 *        	集合名
	 * @param $option array
	 *        	选项
     * @return array|bool
	 */
	public function setSort($set, $option) {
		$return = false;
		$default_option = array (
				'by' => 'some_pattern_*', // 要匹配的排序value值
				'limit' => array (
						0,
						1 
				), // array(start,length)
				'get' => 'some_other_pattern_*', // 多个匹配格式:array('some_other_pattern1_*','some_other_pattern2_*')
				'sort' => 'asc', // asc|desc 默认asc
				'alpha' => TRUE, //
				'store' => 'some_need_pattern_*'  // 永久性排序值
				);
		
		$option = array_merge ( $default_option, $option );
		
		$return = $this->_redis->sort ( $set, $option );
		
		return $return;
	}
	
	// +++-------------------------有序集合操作-------------------------+++//
	
	/**
	 * ***只针对有序集合操作
	 * 返回set中index从start到end的所有元素
	 *
	 * @param $set string
	 *        	集合名
	 * @param $start int
	 *        	开始Index
	 * @param $end int
	 *        	结束Index
	 * @param $order int
	 *        	排序方式 0:从小到大排序 1:从大到小排序 默认0
	 * @param $score bool
	 *        	元素排序值 false:返回数据不带score true:返回数据带score 默认false
     * @return array|bool
	 */
	public function setRange($set, $start, $end, $order = 0, $score = false) {
		$return = false;
		
		if ($order) {
			$return = $this->_redis->zRevRange ( $set, $start, $end, $score );
		} else {
			$return = $this->_redis->zRange ( $set, $start, $end, $score );
		}
		
		return $return;
	}

	/**
	 * ***只针对有序集合操作
	 * 删除set中score从start到end的所有元素
	 *
	 * @param $set string
	 *        	集合名
	 * @param $start int
	 *        	开始score
	 * @param $end int
	 *        	结束score
     * @return bool|int
	 */
	public function setDeleteRange($set, $start, $end) {
		$return = false;
		
		$return = $this->_redis->zRemRangeByScore ( $set, $start, $end );
		
		return $return;
	}

	/**
	 * ***只针对有序集合操作
	 * 获取set中某个元素的score
	 * 如果指定了inc参数 则给该元素的score增加inc值
	 * 如果没有该元素 则将该元素写入集合
	 *
	 * @param $set string
	 *        	集合名
	 * @param $value mixed
	 *        	元素值
	 * @param $inc int
	 *        	要给score增加的数值 默认是null 不执行score增加操作
     * @return bool|float
	 */
	public function setScore($set, $value, $inc = null) {
		$return = false;
		
		if ($inc) {
			$return = $this->_redis->zIncrBy ( $set, $inc, $value );
		} else {
			$return = $this->_redis->zScore ( $set, $value );
		}
		
		return $return;
	}
	
	// +++-------------------------哈希操作-------------------------+++//
	
	/**
	 * 将key->value写入hash表中
	 *
	 * @param $hash string
	 *        	哈希表名
	 * @param $data array
	 *        	要写入的数据 array('key'=>'value')
     * @return bool
	 */
	public function hashSet($hash, $data) {
		$return = false;
		
		if (is_array ( $data ) && ! empty ( $data )) {
			$return = $this->_redis->hMset ( $hash, $data );
		}
		
		return $return;
	}

	/**
	 * 获取hash表的数据
	 *
	 * @param $hash string
	 *        	哈希表名
	 * @param $key mixed
	 *        	表中要存储的key名 默认为null 返回所有key>value
	 * @param $type int
	 *        	要获取的数据类型 0:返回所有key 1:返回所有value 2:返回所有key->value
     * @return array|bool|string
	 */
	public function hashGet($hash, $key = array(), $type = 0) {
		$return = false;
		
		if ($key) {
			if (is_array ( $key ) && ! empty ( $key ))
				$return = $this->_redis->hMGet ( $hash, $key );
			else
				$return = $this->_redis->hGet ( $hash, $key );
		} else {
			switch ($type) {
				case 0 :
					$return = $this->_redis->hKeys ( $hash );
					break;
				case 1 :
					$return = $this->_redis->hVals ( $hash );
					break;
				case 2 :
					$return = $this->_redis->hGetAll ( $hash );
					break;
				default :
					$return = false;
					break;
			}
		}
		
		return $return;
	}

	/**
	 * 获取hash表中元素个数
	 *
	 * @param $hash string
	 *        	哈希表名
     * @return bool|int
	 */
	public function hashLen($hash) {
		$return = false;
		
		$return = $this->_redis->hLen ( $hash );
		
		return $return;
	}

	/**
	 * 删除hash表中的key
	 *
	 * @param $hash string
	 *        	哈希表名
	 * @param $key mixed
	 *        	表中存储的key名
     * @return bool|int
	 */
	public function hashDel($hash, $key) {
		$return = false;
		
		$return = $this->_redis->hDel ( $hash, $key );
		
		return $return;
	}

	/**
	 * 查询hash表中某个key是否存在
	 *
	 * @param $hash string
	 *        	哈希表名
	 * @param $key mixed
	 *        	表中存储的key名
     * @return bool
	 */
	public function hashExists($hash, $key) {
		$return = false;
		
		$return = $this->_redis->hExists ( $hash, $key );
		
		return $return;
	}

	/**
	 * 自增hash表中某个key的值
	 *
	 * @param $hash string
	 *        	哈希表名
	 * @param $key mixed
	 *        	表中存储的key名
	 * @param $inc int
	 *        	要增加的值
     * @return bool|int
	 */
	public function hashInc($hash, $key, $inc) {
		$return = false;
		
		$return = $this->_redis->hIncrBy ( $hash, $key, $inc );
		
		return $return;
	}
	
	// +++-------------------------其他操作-------------------------+++//
	
	/**
	 * 自增hash表中某个key的值
	 *
	 * @param $key string
	 *        	哈希表名
	 * @param $time mixed
	 *        	表中存储的key名
     * @return bool|void
	 */
	public function setKeyExpire($key, $time) {
		$return = false;
		
		$return = $this->_redis->setTimeout ( $key, $time );
		
		return $return;
	}

	/**
	 * 获取满足给定pattern的所有key
	 *
	 * @param $key regexp
	 *        	key匹配表达式 模式:user* 匹配以user开始的key
     * @return array|bool
	 */
	public function getKeys($key = null) {
		$return = false;
		
		$return = $this->_redis->keys ( $key );
		
		return $return;
	}

	/**
	 * 将数据保存到硬盘 同步/异步
	 *
	 * @param int $type
	 *        	保存方式 0:同步 1:异步 默认0
	 * @param int $time
	 *        	是否要获取上次成功将数据保存到磁盘的Unix时戳 0:不返回时间 1:返回时间
     * @return bool|int
	 */
	public function hwSave($type = 0, $time = 0) {
		$return = false;
		
		if ($type) {
			$return = $this->_redis->bgsave ();
		} else {
			$return = $this->_redis->save ();
		}
		if ($time) {
			$return = $this->_redis->lastSave ();
		}
		
		return $return;
	}

	/**
	 * 获取上次成功将数据保存到磁盘的Unix时戳
	 */
	public function lastSave() {
		$return = false;
		
		$return = $this->_redis->lastSave ();
		
		return $return;
	}

	/**
	 * 获取redis版本信息等详情
	 */
	public function info() {
		$return = false;
		
		$return = $this->_redis->info ();
		
		return $return;
	}

	/**
	 * 获取数据库中key的数目
	 */
	public function dbSize() {
		$return = false;
		
		$return = $this->_redis->dbSize ();
		
		return $return;
	}
	
	// +++-------------------------事务操作-------------------------+++//
	
	/**
	 * 开始进入事务操作
	 *
	 * @param return object
	 *        	事务对象
	 */
	public function tranStart() {
		$this->_transcation = $this->_redis->multi ();
	}

	/**
	 * 提交完成事务
	 *
	 * @param return bool
	 *        	事务执行成功 提交操作
	 */
	public function tranCommit() {
		return $this->_transcation->exec ();
	}

	/**
	 * 回滚事务
	 *
	 * @param return bool
	 *        	事务执行失败 回滚操作
	 */
	public function tranRollback() {
		return $this->_transcation->discard ();
	}

	/**
	 * 清空当前数据库
	 */
	public function flushDB() {
		return $this->_redis->flushDB ();
	}
}