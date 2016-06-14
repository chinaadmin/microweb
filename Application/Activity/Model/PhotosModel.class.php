<?php
/**
 * 照片墙模型
 * @author xiongzw
 * @date 2015-07-09
 */
namespace Activity\Model;
use \Think\Image;
class PhotosModel extends ActivityBaseModel{
	protected $tableName = "activity_photo";
	/**
	 * 通过用户id获取用户图片信息
	 * @param  $wechat_id 微信用户id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
	 */
	public function existPhoto($uid,$field=true){
		$where = array(
				'uid'=>$uid
		);
		return $this->field($field)->where($where)->find();
	}
	
	/**
	 * 通过照片墙id获取用户图片信息
	 * @param  $wechat_id 微信用户id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
	 */
	public function getPhoto($photo_id,$field=true){
		$where = array(
				'photo_id'=>$photo_id
		);
		return $this->field($field)->where($where)->find();
	}
	
	/**
	 * 试图模型
	 * @return Ambigous <\Common\Model\mixed, \Think\Model\RelationModel, \Think\Model\ViewModel>
	 */
	public function viewModel(){
		$viewFields = array(   
			'ActivityPhoto'=>array(
					"photo_id",
					"attr_id",
					"title",
					"vote_num",
					"status",
					"_type"=>"LEFT"
		     ),
			'ActivityUser'=>array(
					"uid",
					"nick",
					"status"=>"user_status",
					"_on"=>"ActivityPhoto.uid=ActivityUser.uid"	 
			 )
		);
		return $this->dynamicView($viewFields);
	}
	/**
	 * 通过机器码查询photo
	 * @param  $code 机器码
	 */
	public function getPhotoByUid($uid){
		$where = array(
				"uid"=>$uid
		);
		return M("ActivityVote")->where($where)->getField("photo_id",true);
	}
	
	/**
	 * 图片裁剪
	 * @path 图片路径
	 */
	public function cut($path,$type=3,$width='',$height='',$filename=''){
		$image = new Image (Image::IMAGE_IMAGICK);
		if(stripos($path, ".")!=0){
			$path = ".".$path;
		}
		$image->open($path);
		$width = $width?$width:$image->width();
		$height = $height?$height:$image->height();
		$filename = $filename?$filename:$path;
		if(!is_dir(dirname($filename))){
			mkdir ( dirname($filename), '0777', true );
			$old = umask(0);
			chmod($path, 0777);
			umask($old);
		}
		$image->thumb($width,$height,$type)->save($filename);
		return $filename;
	}
}