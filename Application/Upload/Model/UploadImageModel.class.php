<?php
/**
 * 图像上传
 * @author xiongzw
 * @date 2015-03-30
 */
namespace Upload\Model;
use Upload\Model\BaseModel;
class UploadImageModel extends BaseModel { 
	protected $autoCheckFields  = false;
	public function _initialize(){
		parent::_initialize();
		$this->defaultImageConfig = C('Image');
	}
	
	/**
	 * 单图上传
	 *
	 * @param Array $config
	 *        	图片配置
	 * @param $file 上传图片的$_FILES[name]        	
	 */
	public function oneImage($config = array(), $file = '',$arr=array()) {
		$defaultConfig = $this->defaultImageConfig;
		return $this->uploadOne($defaultConfig,$config,$file,$arr);
	}
	
	/**
	 * 多图上传
	 */
	public function manyImage($config = array(),$arr=array()) {
		$defaultConfig = $this->defaultImageConfig;
		return $this->uploadMany($defaultConfig,$config,$arr);
	}
	
	/**
	 * base64图片上传
	 * @param  $baseData base数据
	 * @param  $ext 文件扩展名
	 * @param  $config 自定义配置
	 * @param  $arr 用户信息 
	 */
	 public function base64Upload($baseData,$ext='png',$config=array(),$arr=array()){
	 	return $this->doBase64Upload($baseData,$ext,$config,$arr);
	 }
}