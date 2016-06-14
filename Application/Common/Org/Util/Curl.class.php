<?php

/**
 * curl 类
 * @author liuwh
 * @date 2014-8-26
 */
/**
 * 使用说明
 * $curl = new Curl();
 * GET方法
 * $curl->st_get('https://www.google.com',['name'=>123] , ['referer']=>'http://me.info');
 *
 * POST方法
 * $curl->st_post('https://www.google.com',['name'=>123] , ['referer']=>'http://me.info');
 */
namespace Common\Org\Util;

class Curl {
	protected $url;
	// curl 会话
	protected $session;
	protected $options = [ ];
	protected $response = '';
	// http头信息
	public $headers = [ 
			'Accept:text/html,application/xhtml+xml,application/xml',
			'Accept-Language:zh-CN,zh;q=0.8,en;q=0.6,ja;q=0.4,zh-TW;q=0.2' 
	];
	public $userAgent = [ 
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; GTB6; .NET CLR 2.0.50727)',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36' 
	];
	public $methods = [ 
			'st_get',
			'st_post' 
	];
	public $errorCode;
	public $errorString;
	public $info;
	// 超时时间
	public $timeOut = 10;

	public function __construct($url = '', $params = []) {
		$url && $this->url = $this->createUrl ( $url, $params );
	}

	/**
	 * 格式化url
	 *
	 * @param string $url        	
	 * @param array $params
	 *        	url参数
	 * @return Curl
	 */
	public function createUrl($url) {
		if (! preg_match ( '/^\w+:\/\//i', $url )) {
			$url = "http://{$url}";
		}
		$this->url = $url;
		$this->session = curl_init ( $this->url );
		return $this;
	}

	public function __call($method, $params) {
		$method = strtolower ( $method );
		if (in_array ( $method, $this->methods )) {
			$verb = str_replace ( 'st_', '', $method );
			array_unshift ( $params, $verb );
			return call_user_func_array ( array (
					$this,
					'_callMethod' 
			), $params );
		}
	}

	protected function _callMethod($method, $url, $params = [], $options = []) {
		if ($method == 'get') {
			$params = $params ? '?' . http_build_query ( $params ) : '';
			$url = $url . $params;
			$this->createUrl ( $url );
		} else {
			$this->createUrl ( $url );
			$this->{$method} ( $params );
		}
		$this->isSsl ();
		$this->setOptions ( $options );
		return $this->execute ();
	}

	/**
	 * 设置curl options
	 *
	 * @param array $options        	
	 * @return Curl
	 */
	public function setOptions($options = []) {
		foreach ( $options as $optionsKey => $option ) {
			$this->option ( $optionsKey, $option );
		}
		curl_setopt_array ( $this->session, $this->options );
		return $this;
	}

	/**
	 * 格式化options 参数
	 *
	 * @param
	 *        	options key $code
	 * @param option $value        	
	 * @param string $prefix        	
	 * @return Curl
	 */
	protected function option($code, $value, $prefix = 'opt') {
		if (is_string ( $code ) && ! is_numeric ( $code )) {
			$code = constant ( 'CURL' . strtoupper ( $prefix ) . '_' . strtoupper ( $code ) );
		}
		$this->options [$code] = $value;
		return $this;
	}

	/**
	 * post 方法
	 *
	 * @param array $params
	 *        	post 数据
	 * @param array $options        	
	 */
	public function post($params = [], $options = []) {
		if (is_array ( $params )) {
			$params = http_build_query ( $params );
		}
		$this->setOptions ( $options );
		$this->httpMethod ( 'post' );
		$this->option ( CURLOPT_POST, TRUE );
		$this->option ( CURLOPT_POSTFIELDS, $params );
	}

	public function httpMethod($method) {
		$this->options [CURLOPT_CUSTOMREQUEST] = strtoupper ( $method );
		return $this;
	}

	/**
	 * 使用ssl
	 *
	 * @param string $caVerify        	
	 * @param number $verifyHost        	
	 * @return Curl
	 */
	public function isSsl($caVerify = FALSE, $verifyHost = 1) {
		$url = parse_url ( $this->url );
		if ($url ['scheme'] == 'https') {
			if ($caVerify) {
				// todo 加入ca证书文件
				$cacert = 'ca.pam';
				// 只信任CA颁布的证书
				$this->options ( CURLOPT_SSL_VERIFYPEER, TRUE );
				// CA根证书，用来验证的网站证书是否是CA颁布
				$this->options ( CURLOPT_CAINFO, $cacert );
				$this->options ( CURLOPT_SSL_VERIFYHOST, $verifyHost );
			} else {
				$this->option ( CURLOPT_SSL_VERIFYPEER, FALSE );
				$this->option ( CURLOPT_SSL_VERIFYHOST, $verifyHost );
			}
		}
		return $this;
	}

	/**
	 * 使用cookie
	 *
	 * @param array $params        	
	 * @return Curl
	 */
	public function setCookies($params = []) {
		if (is_array ( $params )) {
			$params = http_build_query ( $params );
		}
		
		$this->option ( CURLOPT_COOKIE, $params );
		return $this;
	}

	/**
	 * 运行curl
	 */
	public function execute() {
		if (! isset ( $this->options [CURLOPT_TIMEOUT] )) {
			$this->options [CURLOPT_TIMEOUT] = $this->timeOut;
		}
		if (! isset ( $this->options [CURLOPT_RETURNTRANSFER] )) {
			$this->options [CURLOPT_RETURNTRANSFER] = true;
		}
		if (! isset ( $this->options [CURLOPT_FAILONERROR] )) {
			$this->options [CURLOPT_FAILONERROR] = true;
		}
		if (! isset ( $this->options [CURLOPT_HTTPHEADER] )) {
			$this->option ( CURLOPT_HTTPHEADER, $this->headers );
		}
		if (! isset ( $this->options [CURLOPT_USERAGENT] )) {
			$this->option ( CURLOPT_USERAGENT, array_rand ( $this->userAgent ) );
		}
		$this->setOptions ();
		$this->response = curl_exec ( $this->session );
		$this->info = curl_getinfo ( $this->session );
		// 请求失败
		if ($this->response === FALSE) {
			$errno = curl_errno ( $this->session );
			$error = curl_error ( $this->session );
			curl_close ( $this->session );
			$this->setDefaults ();
			$this->errorCode = $errno;
			$this->errorString = $error;
			return FALSE;
		} 		// 请求成功
		else {
			curl_close ( $this->session );
			$this->lastResponse = $this->response;
			$this->setDefaults ();
			return $this->lastResponse;
		}
	}

	public function debug() {
		echo "=============================================<br/>\n";
		echo "<h2>CURL Test</h2>\n";
		echo "=============================================<br/>\n";
		echo "<h3>Response</h3>\n";
		echo "<code>" . nl2br ( htmlentities ( $this->lastResponse ) ) . "</code><br/>\n\n";
		
		if ($this->errorString) {
			echo "=============================================<br/>\n";
			echo "<h3>Errors</h3>";
			echo "<strong>Code:</strong> " . $this->errorCode . "<br/>\n";
			echo "<strong>Message:</strong> " . $this->errorString . "<br/>\n";
		}
		
		echo "=============================================<br/>\n";
		echo "<h3>Info</h3>";
		echo "<pre>";
		print_r ( $this->info );
		echo "</pre>";
	}

	public function setDefaults() {
		$this->headers = [ ];
		$this->options = [ ];
		$this->session = null;
	}
}