<?php
/**
 * 上传基类
 * @author xiongzw
 * @date 2015-04-08
 */
namespace Upload\Model;
use Think\Model;
use Think\Upload;
class BaseModel extends Model{
	Protected $autoCheckFields = false;
	//图片默认配置
	protected $defaultImageConfig;
	public function _initialize(){
		$upload = load_config(APP_PATH."Upload/Conf/upload.php");
		C($upload);
	}
	/**
	 *
	 * @return 单文件上传
	 * @param $config 自定义配置        	
	 * @param string $file
	 *        	表单key值
	 * @param $arr 用户附件信息        	
	 */
	public function uploadOne(Array $defaultConfig, $config = array(), $file = "", $arr = array()) {
		$configs = $this->config ( $defaultConfig, $config );
		$file = $file ? $_FILES [$file] : $_FILES ['file'];
		$upload = new Upload ( $configs );
		$info = $upload->uploadOne ( $file );
		$result ['success'] = false;
		if (! $info) {
			$result ['error'] = $upload->getError ();
		} else {
			$info ['path'] = $configs ['rootPath'] . $info ['savepath'] . $info ['savename'];
			$result = $this->formatData($configs, $info, $arr);
			$result ['success'] = true;
		}
		return $result;
	}
	/**
	 * 多文件上传
	 * 
	 * @param
	 *        	$config
	 * @param
	 *        	$arr
	 */
	public function uploadMany(Array $defaultConfig, $config = array(), $arr = array()) {
		$configs = $this->config ( $defaultConfig, $config );
		$upload = new Upload ( $configs );
		$info = $upload->upload ();
		$result ['success'] = false;
		if (! $info) {
			$result ['error'] = $upload->getError ();
		} else {
			foreach ( $info as &$file ) {
				$file['path'] = $configs['rootPath'].$file['savepath'].$file['savename'];
				$file = $this->formatData($configs, $file, $arr);
			}
			$result ['success'] = true;
		}
		return $result;
	}
	
	/**
	 * 远程图片下载
	 * @param $url 远程地址
	 * @param $config 自定义配置
	 * @param $user 用户信息
	 * @return array
	 */
	public function cacheImage($url,$config,$user=array()){
         $defaultConfig = C('Image');
         $config = $this->config($defaultConfig, $config);
         $pathinfo = pathinfo($url);
         if(!$pathinfo['extension']){
         	//$url = $url.".jpg"; //默认为jpg 
         	$pathinfo['extension'] ="jpg";
         }
         $path = $this->refle($config);
         $filename = $config['rootPath'].$config['savePath'].$path.".".$pathinfo['extension'];
         $dir = dirname($filename);
         if(!is_dir($dir)){
         	mkdir ( $dir, '0777', true );
         	$old = umask(0);
         	chmod($dir, 0777);
         	umask($old);
         }
         \Org\Net\Http::curlDownload($url,$filename); 
		 if(is_file($filename)){
		 	$info = array(
		 			'path' => $filename,
		 			'name' => $pathinfo['filename'],
		 			'ext'  => $pathinfo['extension'],
		 			'size' => filesize($filename)
		 	);
		 	$data = $this->processImage($config, $info);
		 	$info['thumbName'] = $data['thumbName'];
		 	$data['att_id'] = D ( 'Upload/AttachMent' )->attachment ( $info,$user);
		 }
		 return $data?$data:"";
	}
	/**
	 * base64图片上传
	 * @param $baseData 附件base64图片值
	 * @param $ext 图片扩展名
	 * @param $arr 用户信息
	 */
	public function doBase64Upload($baseData,$ext='png',$config=array(),$arr=array()){
		return $this->_downOrBase64($config, $ext,1, $baseData,$arr);
	}
	/**
	 * 远程下载或Base64上传
	 * @param  $config 自定义配置
	 * @param  $ext    文件扩展名
	 * @param number $type  1:base64上传  2：远程下载 
	 * @param $baseData_or_url base64值 或者远程地址  
	 * @return Ambigous <string, unknown>
	 */
	private function _downOrBase64($config,$ext,$type=1,$baseData_or_url,$arr=array()){
		$defaultConfig = C('Image');
		$config = $this->config($defaultConfig, $config);
		$path = $this->refle($config);
		$filename = $config['rootPath'].$config['savePath'].$path.".".$ext;
		$dir = dirname($filename);
		if(!is_dir($dir)){
			mkdir ( $dir, '0777', true );
			$old = umask(0);
			chmod($dir, 0777);
			umask($old);
		}
		if($type==1){
		    file_put_contents($filename, $baseData_or_url);
		}else{
			\Org\Net\Http::curlDownload($baseData_or_url,$filename);
		}
		if(is_file($filename)){
			$pathinfo = pathinfo($filename);
			$info = array(
					'path' => $filename,
					'name' => $pathinfo['filename'],
					'ext'  => $ext,
					'size' => filesize($filename)
			);
			$data = $this->processImage($config, $info);
			$info['thumbName'] = $data['thumbName'];
			$data['att_id'] = D ( 'Upload/AttachMent' )->attachment ( $info,$arr);
		}
		return $data?$data:"";
	}
	/**
	 * 目录
	 * @param  $config
	 * @return Ambigous <string, mixed>
	 */
	private function refle($config){
		//生产子目录
		if($config['autoSub']){
			$subPath = $config['subName'];
			if(is_array($subPath)){
				$path = call_user_func_array($subPath[0],(array)$subPath[1]);
			}
			if(is_string($subPath)){
				if(function_exists($subPath)){
					$path = call_user_func($subPath);
				}else{
					$path = $subPath;
				}
			}
		}
		$filename = call_user_func_array($config['saveName'][0], (array)$config['saveName'][1]);
		if($path) $filename = $path."/".$filename;
		return $filename;
	}
	
	/**
	 * 图片处理
	 * 
	 * @param
	 *        	$config
	 * @param
	 *        	$info
	 */
	private function processImage($configs, &$info, $arr = array()) {
		$processModel = D ( 'Upload/ProcessImage' );
		// 图片水印
		if ($configs ['water']) {
			$info ['path'] = $processModel->water ( $info ['path'],$configs['Water'] );
		}
		// 图片缩略
		if ($configs ['thumb']) {
			$info ['thumbName'] = $processModel->thumb ( $info ['path'],$configs['ThumbImage'] );
		}
		$info['path'] = ltrim($info['path'],".");
		return $info;
	}
	
	/**
	 * 配置项
	 * 
	 * @return $config 新配置
	 * @param array $DefaultConfig
	 *        	默认配置
	 * @param array $CustomConfig
	 *        	自定义配置
	 */
	protected function config(Array $DefaultConfig, Array $CustomConfig) {
		$config = $DefaultConfig;
		if (is_array ( $CustomConfig ) && ! empty ( $CustomConfig )) {
			$config = array_merge ( $DefaultConfig, $CustomConfig );
		}
		return $config;
	}
	/**
	 * 
	 * @param  $configs 文件配置 
	 * @param  $info  文件信息
	 * @param  $arr 附件信息
	 * @return 文件信息
	 */
	private function formatData($configs,&$info,$arr){
		$type = $this->filesType($info['path']);
		if(strtoupper($type) == "IMAGE"){
			$result = $this->processImage ($configs,$info, $arr );
		}else{
			$result = $info;
		}
		// 附加信息
		$result['id'] = D ( 'Upload/AttachMent' )->attachment ( $info, $arr );
		return $result;
	}
	
	/**
	 * 判断文件类型
	 */
	public function filesType($path){
		$image = array("png", "jpg", "jpeg", "gif", "bmp");
		$video = array("flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg",
				"ogg", "ogv", "mov", "wmv", "mp4", "webm", "mp3", "wav", "mid");
		$type = "";
		if(strpos($path, ".")!==0){
			$path =".".$path;
		}
		if(is_file($path)){
			$info = pathinfo($path);
			if(in_array($info['extension'],$image)){
				$type = "IMAGE";
			}elseif(in_array($info['extension'], $video)){
				$type = "VIDEO";
			}else{
				$type = "FILE";
			}
		}
		return $type;
	}
}