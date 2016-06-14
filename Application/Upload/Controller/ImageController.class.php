<?php
/**
 * 图片上传
 * @author xiongzw
 * @date 2015-03-30
 */
namespace Upload\Controller;
class ImageController extends BaseController{
	protected $model;
	public function _initialize(){
		parent::_initialize();
		$this->model = D('Upload/UploadImage');
		$this->arr['model'] = 'image';
	}
	/**
	 * 单图上传
	 */
	public function imageOne(){
		$oldPath = I('post.oldPath','','trim');
		if($oldPath && is_file($oldPath)){
			$this->removeImage($oldPath);
		}
		$file = "file";
		$thumb = I('post.thumb',false);
		$thumbWidth = I('post.thumbwidth',C('Image.ThumbImage.ThumbWidth'));
		$thumbHeight = I('post.thumbheight',C('Image.ThumbImage.ThumbHeight'));
		$thumbType = I('post.thumbtype',C('Image.ThumbImage.ThumbType'));
		if($thumb){
			$config = array(
				'ThumbImage' => array(
						'thumbWidth' => $thumbWidth,
						'thumbHeight' => $thumbHeight,
						'thumbType' => $thumbType,
				)
			);
		}else{
			$config['thumb'] = $thumb;
		}
		$result = $this->model->oneImage($config,$file,$this->arr);
		$this->ajaxReturn($result);
	}
	/**
	 * 多图上传
	 */
	public function imageMany(){
		$result = $this->model->manyImage("",$this->arr);
		$this->ajaxReturn($result);
	}
	
	/**
	 * 删除文件
	 */
	public function del(){
		$id =I('post.id');
		D('Upload/AttachMent')->delById($id);
	}
}