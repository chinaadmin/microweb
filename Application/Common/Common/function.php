<?php
use Think\Cache;
// 加密
function myMD5($password) {
    $pw_aid = C ( 'RBAC_AUTH_PASS' );
    return md5 ( substr ( md5 ( $pw_aid ), 4, - 11 ) . $pw_aid . substr ( md5 ( $password ), 9, - 15 ) );
}

// 密码加密
function getpass($str) {
    if (function_exists ( C ( 'AUTH_PWD_ENCODER' ) )) {
        return call_user_func ( C ( 'AUTH_PWD_ENCODER' ), $str );
    } else {
        return false;
    }
}
/**
 * 唯一id
 * @return string
 */
function uniqueId(){
    return md5(uniqid().rand_string());
}
// 密码强度
function passStrength($str){
    $score = 0;
    if (preg_match("/[0-9]+/", $str)) {
        $score++;
    }
    if (preg_match("/[0-9]{3,}/", $str)) {
        $score++;
    }
    if (preg_match("/[a-z]+/", $str)) {
        $score++;
    }
    if (preg_match("/[a-z]{3,}/", $str)) {
        $score++;
    }
    if (preg_match("/[A-Z]+/", $str)) {
        $score++;
    }
    if (preg_match("/[A-Z]{3,}/", $str)) {
        $score++;
    }
    if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/", $str)) {
        $score += 2;
    }
    if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/", $str)) {
        $score++;
    }
    if (strlen($str) >= 10) {
        $score++;
    }
    return $score;
}

function path_encrypt($string = 'http://www.situonlin.com/test/123.flv||http://c31.yunpan.360.cn/my?p=task',   $key = 'jitu#@#mall', $num = 5) {
    $str = $key . $string;
    $bstr = base64_encode ( $str );
    // 补位操作
    $j = 0;
    $bstr_len = strlen ( $bstr );
    for($i = $bstr_len - 1; $i >= 0; $i --) {
        if (substr ( $bstr, $i, 1 ) == '=') {
            $j ++;
        } else {
            break;
        }
    }
    $bstr = substr ( $bstr, 0, $bstr_len - $j ) . $j;

    $bstr_len = strlen ( $bstr );
    $cnt = ceil ( $bstr_len / $num ) - 1;

    $arr = array ();
    for($i = 0; $i <= $cnt; $i ++) {
        $start = $i * $num;
        $arr [] = substr ( $bstr, $start, $num );
    }
    krsort ( $arr );
    $mi_str = '';
    foreach ( $arr as $value ) {
        $mi_str .= $value;
    }
    return $mi_str;
}

function path_decrypt($string = 'bHY1yMy5mN0LzES90ZXLmNvbubGlul0dW93cuc2Ly93d0dHA6luZWhW9ubGc2l0d', $key = 'jitu#@#mall', $num = 5){
    $string = strrev($string);
    $str_len = strlen($string);
    $cnt = ceil($str_len / $num) - 1;
    $str = '';
    for ($i = 0; $i <= $cnt; $i++) {
        $start = $i * $num;
        $str .= strrev(substr($string, $start, $num));
    }
    $last_num = substr($str, strlen($str) - 1);
    $new_str = substr($str, 0, strlen($str) - 1);
    switch ($last_num) {
        case 1 :
            $new_str .= '=';
            break;
        case 2 :
            $new_str .= '==';
        default :
            $new_str .= '';
            break;
    }
    $new_str = base64_decode($new_str);
    $key_len = strlen($key);
    if (substr($new_str, 0, strlen($key)) == $key) {
        return substr($new_str, $key_len);
    } else {
        return '';
    }
}

/**
 * 获取顶级域名
 * @return string
 */
function getTopHost() {
    $host = $_SERVER ['HTTP_HOST'];
    $index = strpos ( $host, ':' );
    if ($index !== false) {
        $host = substr ( $host, 0 , $index );
    }
    return substr ( $host, strpos ( $host, '.' ) );
}

/**
 * 返回数组中指定的一列
 *
 * @param array $input
 *        	需要取出数组列的多维数组（或结果集）
 * @param mixed $columnKey
 *        	需要返回值的列，它可以是索引数组的列索引，或者是关联数组的列的键。 也可以是NULL，此时将返回整个数组
 * @param mixed $indexKey
 *        	作为返回数组的索引/键的列，它可以是该列的整数索引，或者字符串键值。
 * @return array 从多维数组中返回单列数组
 */
if (! function_exists ( 'array_column' )) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array ();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values ( $input );
            } else {
                foreach ( $input as $row ) {
                    $result [] = $row [$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ( $input as $row ) {
                    $result [$row [$indexKey]] = $row;
                }
            } else {
                foreach ( $input as $row ) {
                    $result [$row [$indexKey]] = $row [$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 替换表扩展名
 * @param string $sql 为sql语句可为数组
 * @param string $str 替换参数
 * @return mixed|string
 */
function toSql($sql, $str = "##") {
    if (empty ( $sql )) {
        return '';
    }
    return str_replace ( $str, C ( 'DB_PREFIX' ), $sql );
}

/**
 * 重组合更新sql
 * @param array $data 数据
 * @param string $table 更新数据表名
 * @param string $field 要更新字段
 * @param string $key 更新条件字段
 * @return string
 */
function array2UpdateSql(array $data, $table, $field = 'id', $key = "id",$where='') {
    $ids = implode ( ',', parseSqlValue(array_keys ( $data )));
    $sql = "UPDATE $table SET $field = CASE $key ";
    //$sql = "UPDATE ##{$table} SET $field = CASE $key ";
    //$sql = toSql ( $sql );
    foreach ( $data as $id => $ordinal ) {
        $sql .= " WHEN ".parseSqlValue($id)." THEN ".parseSqlValue($ordinal);
    }
    $sql .= " END WHERE $key IN ($ids)";
    $sql .= " ".$where;
    return $sql;
}

/**
 * 处理sql参数值
 * @param string $value 值
 * @return array|string
 */
function parseSqlValue($value) {
    if(is_string($value)) {
        $value =  strpos($value,':') === 0 && in_array($value,array_keys($this->bind))? addslashes($value) : '\''.addslashes($value).'\'';
    }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
        $value =  addslashes($value[1]);
    }elseif(is_array($value)) {
        $value =  array_map('parseSqlValue',$value);
    }elseif(is_bool($value)){
        $value =  $value ? '1' : '0';
    }elseif(is_null($value)){
        $value =  'null';
    }
    return $value;
}

/**
 * 返回结果
 * @param callable $closure
 * @return \Common\Org\Util\Results
 */
function result(Closure $closure = null){
    $result = new \Common\Org\Util\Results();
    if ($closure !== null){
        if( $closure instanceof Closure){
            $closure($result);
        }else{
            $result->content($closure);
        }
    }
    return $result;
}

/**
 * 递归删除目录及其文件夹
 */
function removeDir($dirName) {
	$dirName = substr ( $dirName, - 1 ) == '/' ? $dirName : $dirName . '/';
	if (! is_dir ( $dirName ))
		return;
	foreach ( glob ( $dirName . '/*' ) as $v ) {
		is_dir ( $v ) ? removeDir ( $v ) : unlink ( $v );
	}
	return rmdir ( $dirName );
}

/**
 * +----------------------------------------------------------
 * 把返回的数据集转换成Tree
 * +----------------------------------------------------------
 *
 * @access public
 *         +----------------------------------------------------------
 * @param array $list
 *        	要转换的数据集
 * @param string $pid
 *        	parent标记字段
 * @param string $level
 *        	level标记字段
 *        	+----------------------------------------------------------
 * @return array +----------------------------------------------------------
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
	// 创建Tree
	$tree = array ();
	if (is_array ( $list )) {
		// 创建基于主键的数组引用
		$refer = array ();
		foreach ( $list as $key => $data ) {
			$refer [$data [$pk]] = & $list [$key];
		}
		foreach ( $list as $key => $data ) {
			// 判断是否存在parent
			$parentId = $data [$pid];
			if ($root == $parentId) {
				$tree [] = & $list [$key];
			} else {
				if (isset ( $refer [$parentId] )) {
					$parent = & $refer [$parentId];
					$parent [$child] [] = & $list [$key];
				}
			}
		}
	}
	return $tree;
}
/**
 * 生成目录树
 *
 * @param array $data
 * @param string $id
 *        	索引主键名
 *        	@parma string $pid 父主键名
 * @return array
 */
function generateTree($data, $id = 'id', $pid = 'pid') {
	$tree = [ ];
	foreach ( $data as $item ) {
		if (isset ( $data [$item [$pid]] )) {
			$data [$item [$pid]] ['child'] [] = &$data [$item [$id]];
		} else {
			$tree [] = &$data [$item [$id]];
		}
	}
	return $tree;
}

/**
 * 多维数组合并
 * @param array $a
 * @param array $b
 * @return array
 */
function mergeArray($a, $b) {
    $args = func_get_args ();
    $res = array_shift ( $args );
    while ( ! empty ( $args ) ) {
        $next = array_shift ( $args );
        foreach ( $next as $k => $v ) {
            if (is_integer ( $k ))
                isset ( $res [$k] ) ? $res [] = $v : $res [$k] = $v;
            elseif (is_array ( $v ) && isset ( $res [$k] ) && is_array ( $res [$k] ))
                $res [$k] = mergeArray ( $res [$k], $v );
            else
                $res [$k] = $v;
        }
    }
    return $res;
}

/**
 * +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
 * +----------------------------------------------------------
 *
 * @param string $len
 *        	长度
 * @param string $type
 *        	字串类型
 *        	0 字母 1 数字 其它 混合
 * @param string $addChars
 *        	额外字符
 *        	+----------------------------------------------------------
 * @return string +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
    return \Org\Util\String::randString ( $len, $type, $addChars );
}

/**
 * 时间转换成时间戳函数
 * @param string $time 时间
 * @param bool $isdate 是否转换小时、分钟、秒
 * @return int 时间戳
 */
function tomktime($time, $isdate = false) {
    $year = ((int) substr($time, 0, 4)); //取得年份
    $month = ((int) substr($time, 5, 2)); //取得月份
    $day = ((int) substr($time, 8, 2)); //取得几号
    $h = 0;
    $m = 0;
    $s = 0;
    if ($isdate) {
        $h = ((int) substr($time, 11, 2)); //取得小时
        $m = ((int) substr($time, 14, 2)); //取得分钟
        $s = ((int) substr($time, 17, 2)); //取得秒钟
    }
    return mktime($h, $m, $s, $month, $day, $year);
}

/**
 * 返回当天末的时间戳
 * @param string $time 时间
 * @return 时间戳
 */
function tomkendtime($time) {
    $year = ((int) substr($time, 0, 4)); //取得年份
    $month = ((int) substr($time, 5, 2)); //取得月份
    $day = ((int) substr($time, 8, 2)); //取得几号
    $h = 23;
    $m = 59;
    $s = 59;
    return mktime($h, $m, $s, $month, $day, $year);
}

/**
 * 过滤html标签
 * @param $str 需要处理的字符串
 * @return str
 */
function delhtml($str){
    $str=trim(strip_tags($str));
    $str=preg_replace("/\s+/"," ",$str);
    $str=str_replace("&nbsp;","",$str);
    $str=str_replace("&quot;","",$str);
    $str = str_replace(" ","　",$str);
    $str = str_replace("<","&lt;",$str);
    $str = str_replace(">","&gt;",$str);
    $str = str_replace("&nbsp;","",$str);
    return $str;
}

/**
 * 获取图片规格字符串
 * @param int $width 宽度
 * @param int $height 高度
 * @return string
 */
function imageSizeStr($width,$height){
    return $width.'X'.$height;
}

/**
 * 根据模版代码及类型获取模版内容
 *
 * @param string $code 模版代码
 * @param string $type 模版类型
 * @param array $arr 模版数据变量
 */
function getTempContent($code, $type = 1, $arr) {
	$where = array();
	$where['temp_code'] = $code;
	$where['temp_type'] = $type;
	$temp = M('template')->field('temp_content as content')->where($where)->find();
	$view = new \Think\View();
    $view->assign($arr);
    $temp['content'] = $view->fetch('', htmlspecialchars_decode($temp['content']));
    return $temp['content'];
}



/**
 * 发送邮件
 *
 * @param $to 接收邮箱地址
 * @param string $name  接收邮件者名称
 * @param $subject 邮箱主题
 * @param $body 邮箱内容
 * @param string $attachment 附件列表
 * @return boolean
 */
function sendMail($to, $name, $subject = '', $body = '', $attachment = NULL) {
	$configs = M('configs')->cache(true,10)->where(['code' => ['like','web_smtp%']])->getField('code,value');
	Vendor('PHPMailer.class#phpmailer');
	try {
		$mail = new PHPMailer(true);
		$body = preg_replace('/\\\\/', '', $body); //Strip backslashes
		$mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
		$mail->SetLanguage('zh_cn'); //设置语言
		$mail->SMTPDebug = 0;                     // 关闭SMTP调试功能 1 = errors and messages 2 = messages only
		$mail->IsSMTP();                           // 设定使用SMTP服务
		$mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
		//$mail->SMTPSecure = 'ssl';                 // 使用安全协议
		$mail->Port = $configs['web_smtp_port'];         // SMTP服务器的端口号
		$mail->Host = $configs['web_smtp_host'];         // SMTP 服务器
		$mail->Username = $configs['web_smtp_username'];     // SMTP服务器用户名
		$mail->Password = $configs['web_smtp_password'];     // SMTP服务器密码
		//$mail->IsSendmail();  // tell the class to use Sendmail
		//$mail->AddReplyTo("shundatest@163.com","First Last");
		$mail->From = empty($configs['web_smtp_reply_email']) ? $configs['web_smtp_username'] : $configs['web_smtp_reply_email']; // 回复Email
		$mail->FromName = empty($configs['web_smtp_reply_name']) ? $configs['web_smtp_name'] : $configs['web_smtp_reply_name']; // 回复名称

		$mail->AddAddress($to, $name);

		$mail->Subject = $subject;

		$mail->MsgHTML($body);

		$mail->IsHTML(true); // HTML格式发送
		if (is_array($attachment)) { // 添加附件
			foreach ($attachment as $file) {
				is_file($file) && $mail->AddAttachment($file);
			}
		}
		$mail->Send();
		return true;
	} catch (phpmailerException $e) {
		return $e->errorMessage();
	}
}
//隐藏某些字符
function hideStr($str,$type){
	if($type == 'mobile'){
		return substr_replace($str,'*****',3,5) ;
	}else if($type == 'email'){
		$tmp = explode('@',$str);
		$len = strlen($tmp[0]) - 2;
		$len = $len >= 0 ? $len : 0;
		$tmp[0] = substr_replace($tmp[0], '***', 1,$len);
		return implode('@',$tmp);
	}
	exit('传参有误');
}

/**
 * 验证手机号
 * @param string $str 手机号
 * @return bool
 */
function checkMobile($str){
    $pattern = '/^1[34578][0-9]{9}$/';
    if (preg_match($pattern,$str)){
        return true;
    }else{
        return false;
    }
}
/**
 * URl路由
 * @param  $realUrl 路由地址
 * @return unknown|string
 */
function toUrl($url) {
	$route = C ( "URL_ROUTE_RULES" );
	/**
	 * 未定义路由规则
	 */
	if (! $route) {
		return $url;
	}
	foreach ( $route as $routeKey => $routeVal ) {
		$routeVal = preg_replace("/[(\?+)|(\=+)|(\&+)]/", "/",$routeVal);
		$routeKey = trim ( $routeKey );
		// 正则路由
		if (substr ( $routeKey, 0, 1 ) === '/') {
			$regGroup = array (); // 识别正则路由中的原子组
			preg_match_all ( "@\(.*?\)@i", $routeKey, $regGroup, PREG_PATTERN_ORDER );
			// 路由规则Value
			$searchRegExp = $routeVal;
			// 将正则路由的Value中的值#1换成(\d+)等形式
			for($i = 0, $total = count ( $regGroup [0] ); $i < $total; $i ++) {
				$searchRegExp = str_replace ( ':' . ($i + 1), $regGroup [0] [$i], $searchRegExp );
			}
			// URL参数
			$urlArgs = array ();
			if(substr ( $routeVal, 0, 1 ) === '/'){
				$rurl = $url;
			}else{
				$rurl = ltrim ( $url, "/" );
			}
			$rurl = rtrim ( $rurl, C ( "URL_HTML_SUFFIX" ) );
			$searchRegExp = str_replace ( "/", "\/", $searchRegExp );
			// 当前URL是否满足本次路由规则，如果满意获得url参数（原子组）
			preg_match_all ( "@^" . $searchRegExp . "$@i", $rurl, $urlArgs, PREG_SET_ORDER );
			// 满足路由规则
			if ($urlArgs) {
				// 清除路由中的/$与/正则边界
				$routeUrl = trim ( preg_replace ( array (
						'@/\^@',
						'@/[isUx]$@',
						'@\$@'
				), array (
						'',
						'',
						''
				), $routeKey ), '/' );
				/**
				 * 将路由规则中的(\d+)等形式替换为url中的具体值
				 * /admin(\d).html/ => admin1.html
				 */
				foreach ( $regGroup [0] as $k => $v ) {
					$v = preg_replace ( '@([\|\*\$\(\)\+\?\[\]\{\}\\\])@', '\\\$1', $v );
					$routeUrl = preg_replace ( '@' . $v . '@', $urlArgs [0] [$k + 1], $routeUrl, $count = 1 );
				}
				$routeUrl = str_replace ( "\/", "/", $routeUrl ) . C ( "URL_HTML_SUFFIX" );
				if (substr ( $routeUrl, 0, 1 ) !== '/') {
					$routeUrl = "/" . $routeUrl;
				}
				return $routeUrl;
			}
		} else {
			// 获得如 "info/:city_:row" 中的:city与:row
			$routeGetVars = array ();
			// 普通路由处理
			// 获得路由规则中以:开始的变量
			preg_match_all ( '/:([a-z]*)/i', $routeKey, $routeGetVars, PREG_PATTERN_ORDER );
			$getRouteUrl = $routeVal;
			switch (C ( "URL_MODEL" )) {
				case 2 :
					$getRouteUrl .= '/';
					foreach ( $routeGetVars [1] as $getK => $getV ) {
						$getRouteUrl .= $getV . '/(.*)/';
					}
					$getRouteUrl = '@' . trim ( $getRouteUrl, '/' ) . '@i';
					break;
				case 1 :
					$getRouteUrl .= '&';
					foreach ( $routeGetVars [1] as $getK => $getV ) {
						$getRouteUrl .= $getV . '=(.*)' . '&';
					}
					$getRouteUrl = '@' . trim ( $getRouteUrl, '&' ) . '@i';
					break;
			}
			$getArgs = array ();
			preg_match_all ( $getRouteUrl, $url, $getArgs, PREG_SET_ORDER );
			if ($getArgs) {
				// 去除路由中的传参数如:uid
				$newUrl = $routeKey;
				foreach ( $routeGetVars [0] as $rk => $getName ) {
					$newUrl = str_replace ( $getName, $getArgs [0] [$rk + 1], $newUrl );
				}
				if (substr ( $newUrl, 0, 1 ) !== '/') {
					$newUrl = "/" . $newUrl;
				}
				return $newUrl;
			}
		}
	}
	return $url;
}

/**
 * 分词
 * @param  $word 要分的字符串
 * @param real $pro  匹配精确度
 * @param number $show_pro 是否显示精确度
 * @return Ambigous <multitype:unknown , mixed>
 */
function pullWord($word,$pro=0.7,$show_pro=0){
	$len = iconv_strlen($word,"UTF-8");
	$result = array();
	if ($len > 1) {
		$url = "http://apis.baidu.com/apistore/pullword/words?source=" . $word . "&param1=" . $pro . "&param2=" . $show_pro;
		$curl = new \Common\Org\Util\Curl ();
		$curl->headers [] = 'apikey:'.C("JT_CONFIG_WEB_PULL_WORD_KEY");
		$result = trim ( $curl->st_get ( $url ) );
		$result = str_replace ( " ", "", $result );
		if ($result) {
			$result = array_filter ( explode ( "\n", $result ) );
		}
	}else{
		$result[] = $word;
	}
	return $result;
}
//格式化打印出数组所有内容  调试使用
function  myDump($data){
	echo '<pre>';
	print_r($data);
};
/**
 * 完整网络地址
 * @param string $path 路径
 * @return string
 */
function fullPath($path){
	if(strpos($path, "http://")===false && !empty($path)){
		$path =  'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME').$path;
	}
	return $path;
}

/**
 * 格式化金额（整数去除小数点）
 * @param $amount 金额
 * @return string
 */
function formatAmount($amount){
    return sprintf("%s",$amount*1);
}

/**
 * 折扣金额
 * @param $amount 金额
 * @param int $discount 折扣
 * @return string
 */
function discountAmount($amount,$discount = 10){
    if(!empty($discount)) {
        $amount = $amount * ($discount / 10);
    }
    return sprintf("%0.2f", $amount);
}
/**
 * 给editor内容里连接地址增加前缀
 */
function addEditorSrc($content){
	$pathHeader = 'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME');
	return preg_replace('/(src=")\s*?([^http][^"]*?")/', '$1'.$pathHeader.'$2', htmlspecialchars_decode($content));
}

/**
 * 依据设置概率值抽取
 * @param  array $proArr 实例：array(1=>5,2=>5); 键名为被抽取数,	键值为对应的概率
 * @return mix $result $proarr 的某个抽中的键名
 */
function get_rand($proArr) {
	$result = '';
	//概率数组的总概率精度
	$proSum = array_sum($proArr);
	//概率数组循环
	foreach ($proArr as $key => $proCur) {
		$randNum = mt_rand(1, $proSum);
		if ($randNum <= $proCur) {
			$result = $key;
			break;
		} else {
			$proSum -= $proCur;
		}
	}
	unset ($proArr);
	return $result;
}
/**
 * 判断时间期效
 * @param integer $start_time
 * @param integer $end_time
 * @return int -1活动已经结束，0活动未开始，1活动进行中
 */
function getTimePeriod($start_time,$end_time){
    return NOW_TIME < $start_time ? 0 : ( NOW_TIME > $end_time ? -1 :1 );
}

/**
 * 生成一个唯一字符串
 * @param int $length  生成的位数
 */
function createUniqStr($length = 32){
    $string = sha1(uniqid(mt_rand()));
    if ($length == strlen($string)) {
        return $string;
    }
    return substr($string, 0, $length);
}

/**
 * 字符转成一个短字符
 * @param  string $input [description]
 * @return [type]        [description]
 */
function shortUrl($input){
    $char = '';
    $input = crc32($input);
    $intInput = sprintf('%u', $input);
    while ($intInput > 0) {
        $s = $intInput % 62;
        if ($s > 35) {
            $s = chr($s + 61);
        } elseif ($s > 9 && $s <= 35) {
            $s = chr($s + 55);
        }
        $char .= $s;
        $intInput = floor($intInput / 62);
    }
    return $char;
}
