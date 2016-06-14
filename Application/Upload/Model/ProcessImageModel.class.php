<?php
/**
 * 图片处理
 * @author xiongzw
 * @date 2014-04-03
 */
namespace Upload\Model;
use Upload\Model\BaseModel;
use Think\Image;
class ProcessImageModel extends BaseModel {
	protected $image;
	protected $autoCheckFields  = false;
	public function _initialize() {
		parent::_initialize();
		$this->image = new Image (Image::IMAGE_IMAGICK);
	}
	/**
	 * 图片缩略
	 * 
	 * @param $image 图片路径        	
	 * @param $config 自定义配置        	
	 */
	public function thumb($imgPath, $config = array()) {
		$configThumb = C ( 'Image.ThumbImage' );
		if (is_array ( $config ) && ! empty ( $config )) {
			$configThumb = array(
					'thumbWidth' => $config['thumbWidth']?$config['thumbWidth']:$configThumb['thumbWidth'],
					'thumbHeight' => $config['thumbHeight']?$config['thumbHeight']:$configThumb['thumbHeight'],
					'thumbType' => $config['thumbType']?$config['thumbType']:$configThumb['thumbType'],
			);
		}
		$thumbWidth = explode ( ",", $configThumb ['thumbWidth'] );
		$thumbHeight = explode ( ",", $configThumb ['thumbHeight'] );
		$pathInfo = pathinfo ( $imgPath );
		$thumbName = array();
		foreach ( $thumbWidth as $key => $v ) {
			$filename = $pathInfo ['filename'] . "_" . $v . "X" . $thumbHeight [$key];
			$thumbName[] = $filename;
			$path = $pathInfo ['dirname'] . "/" . $pathInfo ['filename'] . "/";
			if (! is_dir ( $path )) {
				mkdir ( $path, '0777', true );
				$old = umask(0);
				chmod($path, 0777);
				umask($old);
				
			}
			$savePath = $path . $filename . "." . $pathInfo ['extension'];
			$this->image->open ( $imgPath );
			$this->image->thumb ( $v, $thumbHeight [$key], $configThumb ['thumbType'] )->save ( $savePath );
		}
		$thumbName = json_encode($thumbName);
		return $thumbName;
	}
	
	/**
	 * 图片水印
	 * 
	 * @param $config 自定义皮遏制        	
	 * @param $path 图片路径        	
	 * @param $waterPath 水印图片路径        	
	 */
	public function water($path, $config = array(), $waterPath = '') {
		$waterConfig = C ( 'Image.Water' );
		if (is_array ( $config ) && ! empty ( $config )) {
			$waterConfig = array_merge ( $waterConfig, $config );
		}
		$waterPath = $waterPath ? $waterPath : $waterConfig ['waterPath'];
		if (is_file ( $path ) && is_file ( $waterPath )) {
			$this->image->open ( $path )->water ( $waterPath, $waterConfig ['waterPlace'] )->save ( $path );
		}
		return $path;
	}
	 /**
     * 裁剪图片
     * @param  integer $w      裁剪区域宽度
     * @param  integer $h      裁剪区域高度
     * @param  integer $x      裁剪区域x坐标
     * @param  integer $y      裁剪区域y坐标
     * @param  integer $width  图片保存宽度
     * @param  integer $height 图片保存高度
     * @param  boolen  $cover 是否覆盖原图
     * @return Object          当前图片处理库对象
     */
	public function crop($path, $w, $h, $x = 0, $y = 0, $width = null, $height = null,$cover=false) {
		$this->image->open ( $path );
		$filename = $path;
		if(!$cover){
			$info = pathinfo($path);
			$filename = $info['dirname']."/".$info['filename']."_crop.".$info['extension'];
		}
		$this->image->crop ( $w, $h, $x, $y, $width, $height)->save ( $filename );
		return $filename;
	}
}