<?php
/**
 * 文件附件信息
 * @author xiongzw
 * @date 2015-04-04
 */
namespace Upload\Model;
//use Upload\Model\BaseModel;
use Think\Model\AdvModel;
class AttachMentModel extends AdvModel {
	protected $tableName = "attachment";
	protected $partition = array( 
			'field' => 'att_id',
			 'type' => 'mod',
			 'num' => '10'
			
	 );
	/**
	 * 附件信息
	 * 
	 * @param $path 文件路径        	
	 * @param $type 附件类型        	
	 * @param $oldName 附件原文件名        	
	 */
	public function attachment($info, $arr = array()) {
		$path = $info ['path'];
		$type = $this->filesType ( $path );
		if (strtoupper ( $type ) == "IMAGE") {
			$filePath = ".".ltrim($path,".");
			$imageSize = getimagesize ( $filePath );
		}
		if(empty($arr)){
			$arr = $this->getUser();
		}
		$pathInfo = pathinfo ( $path );
		$data = array (
				'att_id'=>$this->getLaseId("Attachment","att_id")+1,
				'type' => strtoupper ( $type ),
				'name' => $pathInfo ['filename'],
				'ext' => $info ['ext'],
				'path' => $pathInfo ['dirname'],
				'size' => $info ['size'],
				'hash' => md5_file ( $path ),
				'add_time' => NOW_TIME,
				'width' => empty ( $imageSize ) ? 0 : $imageSize [0],
				'height' => empty ( $imageSize ) ? 0 : $imageSize [1],
				'thumbname' => !empty($info ['thumbName'])?$info['thumbName']:"" 
		);
		$att_table = ltrim($this->getPartitionTableName($data),C("DB_PREFIX"));
		$id = M($att_table)->add ( $data );
		$newArr = array (
				'id' => $this->getLaseId("UserAttachment","id")+1,
				'att_id' => $id,
				'model' => $arr ['model'] ? $arr ['model'] : "",
				'is_admin' => $arr ['is_admin'] ? $arr ['is_admin'] : 0,
				'uid' => $arr ['uid'] ? $arr ['uid'] : 0,
				'name' => $arr ['name'] ? $arr ['name'] : $info ['name'],
				'remark' => $arr ['remark'] ? $arr ['remark'] : "",
				'add_time' => NOW_TIME 
		);
		D( 'Upload/UserAttach' )->add ( $newArr );
		return $id; 
	}
	/**
	 * 获取表的最后一条记录
	 */
	public function getLaseId($table,$id){
		return M($table)->max($id);
	}
	/**
	 * 获取用户信息
	 */
	private function getUser(){
		$user = \User\Org\Util\User::getInstance();
		$isAdmin = $user->isAdmin ();
		$uid = $user->isLogin();
		$userArr = array (
				'is_admin' => $isAdmin ? 1 : 0,
				'uid' => $uid,
		);
		return $userArr;
	}
	/**
	 * 通过附件id删除附件
	 * @param $id 附件id
	 */
	public function delById($id) {
		$where = array (
				'att_id' => array (
						'in',
						$id 
				) 
		);
		$data = $this->where ( $where )->select ();
		// 删除文件
		foreach ( $data as $v ) {
			// 删除缩略图
			$path = $v ['path'] . "/";
			if (strpos ( ".", $path ) !== 0) {
				$path = "." . $path;
			}
			$dir = $path . $v ['name'] . "/";
			if (is_dir ( $dir )) {
				removeDir ( $dir );
			}
			$filename = $path . $v ['name'] . "." . $v ['ext'];
			if (is_file ( $filename )) {
				unlink ( $filename );
			}
		}
		// 删除用户附件信息
		M ( 'UserAttachment' )->where ( $where )->delete ();
		$this->where ( $where )->delete ();
	}
	
	/**
	 * 通过id获取附件地址
	 * @param $attIds 附件id
	 * @param $thumb 是否获取缩略图
	 * @param $thumbSize 要获取的缩略图尺寸,不填表示获取全部 example 200X200
	 */    
	public function getAttach($attIds,$field=true,$thumb=false,$thumbSize=""){
		$defaultImage = $attIds['default'];
		if($defaultImage && is_array($attIds)){
			unset($attIds['default']);
		}
        if(empty($attIds)){
            $data = [];
        }else{
        	if(is_string($attIds) && strpos(',', $attIds) === false){
        		$attIds = (array)$attIds;
        	}
            $where = [
                'att_id' => ['in', $attIds]
            ];
            $data = $this->field($field)->where($where)->select();
        }
		foreach($data as &$v){
			//取缩略图
			if($thumb){
				$dir = $v['path']."/".$v['name']."/";
				if($thumbSize){
					$v[$thumbSize] =  $dir.$v['name']."_".$thumbSize.".".$v['ext'];
					$this->defaultImg($v[$thumbSize]);
				}else{
					$v['thumb'] = json_decode($v['thumbname'],true);
					$thumb = array();
					foreach($v['thumb'] as $vs){
						$size = explode("_", $vs);
						$thumb[$size[1]] = $dir.$vs.".".$v['ext'];
						$this->defaultImg($thumb[$size[1]]);
					}
					$v['thumb'] = $thumb;
				}
			}
			$v['path'] = $v['path']."/".$v['name'].".".$v['ext'];
			if($defaultImage && $defaultImage==$v['att_id']){
				$v['default'] = true;
			}
		}
		return $data;
	}
	/**
	 * 取默认图片
	 */
	private function defaultImg(&$file){
		if(strpos($file, ".")!='0'){
			$file = ".".$file;
		}
		if(!is_file($file)){
			$file = C('DEFAULT_IMAGE');
		}
		$file = ltrim($file,".");
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