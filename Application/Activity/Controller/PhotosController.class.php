<?php
/**
 * 照片墙控制器
 * @author xiongzw
 * @date 2015-07-09
 */
namespace Activity\Controller;
class PhotosController extends ActivityBaseController{
	//private $thumbSize = "290X386";
	private $thumbSize = array (
			"164X218" 
	);
	// 照片编号
	private $number = 1000; // 编号=photo_id+number
	                        // 集齐50赞不能修改照片
	private $vote = 20;
	public function _initialize() {
		parent::_initialize ();
		header ( 'Access-Control-Allow-Origin: *' );
		header ( 'Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,' );
	}
	/**
	 * 判断是修改还是新增
	 * <code>
	 * uid 用户id
	 * </code>
	 */
	public function photoShow() {
		$uid = I ( "post.uid", '' );
		$uid = path_decrypt ( $uid );
		if (empty ( $uid )) {
			$this->ajaxReturn ( $this->result->set ( "UID_REQUIRE" ) );
		}
		$this->_isDisable ( $uid );
		$data = D ( "Activity/Photos" )->existPhoto ( $uid );
		$return = array ();
		$return ['isUpdate'] = 0;
		if ($data) {
			$attr = current ( D ( "Upload/AttachMent" )->getAttach ( $data ['attr_id'] ) );
			$return ['photo'] = fullPath ( $attr ['path'] );
			$return ['isUpdate'] = 1;
			$return ['title'] = $data ['title'];
			$return ['disUpload'] = $data ['vote_num'] > $this->vote ? 1 : 0;
		}
		$this->ajaxReturn ( $this->result->success ()->content ( [ 
				'return' => $return 
		] ) );
	}
	
	/**
	 * 上传照片
	 * <code>
	 * file 上传的文件
	 * </code>
	 */
	private function _addPic($uid) {
		// $img = "./Uploads/Activity/20150713/55a391b065029.png";
		// $baseImg = chunk_split(base64_encode(file_get_contents($img)));
		// file_put_contents("./Uploads/Activity/111.png", base64_decode(I('post.base64')));exit;
		$this->_isDisable ( $uid );
		// 判断是否有上传
		$info = $this->_notUp ( $uid );
		if (! empty ( $_FILES ['file'] )) {
			// 更新或新增附件
			return $this->_doUpload ();
		} elseif (! empty ( I ( 'post.base', '' ) )) {
			return $this->_base64Upload ();
		} else {
			// 更新操作
			if ($info) {
				$photo = current ( D ( "Upload/AttachMent" )->getAttach ( $info ['attr_id'] ) );
				$data = array (
						'attr_id' => $info ['attr_id'],
						'photo' => fullPath ( $photo ['path'] ) 
				);
				return $data;
			} else {
				$this->ajaxReturn ( $this->result->set ( "PHOTO_REQUIRE" ) );
			}
		}
	}
	/**
	 * 上传照片操作
	 * 
	 * @return string
	 */
	public function _doUpload() {
		$photo_model = D ( "Activity/Photos" );
		$width = "";
		$height = "";
		foreach ( $this->thumbSize as $v ) {
			$size = explode ( "X", $v );
			$width .= $size [0] . ",";
			$height .= $size [1] . ",";
		}
		$width = rtrim ( $width, "," );
		$height = rtrim ( $height, "," );
		$config = array (
				"thumb" => true,
				'ThumbImage' => array (
						'thumbWidth' => $width, // 缩略图宽度
						'thumbHeight' => $height, // 缩略图高度
						'thumbType' => 1 
				),
				"savePath" => "Activity/" 
		);
		$result = $this->uploadPic ( $config, 'file' );
		if (! $result ['success']) {
			$this->ajaxReturn ( $this->result->error ( $result ['error'], "UPLOAD_ERROR" ) );
		} else {
			$data = array (
					'attr_id' => $result ['id'],
					'photo' => fullPath ( $result ['path'] ) 
			);
			//原图宽高判断缩略图尺寸
			$size = getimagesize(".".$result ['path']);
			$thumbSize = explode ( "X", $this->thumbSize [0] );
			if($size[0]>$size[1]){
				$sizeH = $thumbSize[0];
				$sizeW = intval($sizeH*($size[0]/$size[1]));
			}else{
				$sizeW = $thumbSize[0];
				$sizeH = intval($sizeW/($size[0]/$size[1]));
			}
			$this->_cut ( $result ['path'], 1,$sizeW, $sizeH );
			$this->_cut ( $result ['path'], 3, $sizeW, $sizeW );
			return $data;
		}
	}
	/**
	 * 图片裁剪
	 * 
	 * @param unknown $path        	
	 */
	private function _cut($path,$type,$width,$height) {
		$pathinfo = pathinfo ( $path );
		/* if(stripos($path, ".")!=0){
			$path = ".".$path;
		}
		if(empty($width)){
			$size = getimagesize($path);
		} */
		//$thumbPath = dirname ( $path ) . "/" . $pathinfo ['filename'] . "/" . $pathinfo ['filename'] . "_" . $this->thumbSize [0] . "." . $pathinfo ['extension'];
		//D ( "Activity/Photos" )->cut ( $path,$type,$size[0],$size[0],$thumbPath );
		$filename = "./".dirname ( $path ) . "/" . $pathinfo ['filename'] . "/" . $pathinfo ['filename'] . "_" . $this->thumbSize [0] . "." . $pathinfo ['extension'];
		if($type==1){
			$file = $path;
			D ( "Activity/Photos" )->cut ( $file,$type,$width,$height,$filename );
		}
		if($type==3){
			$file = $filename;
			D ( "Activity/Photos" )->cut ( $file,$type,$width,$height );
		}
	}
	/**
	 * base64上传
	 */
	private function _base64Upload() {
		foreach ( $this->thumbSize as $v ) {
			$size = explode ( "X", $v );
			$width .= $size [0] . ",";
			$height .= $size [1] . ",";
		}
		$width = rtrim ( $width, "," );
		$height = rtrim ( $height, "," );
		$baseImg = I('post.base','');
		$baseImg = preg_replace("/^data:image\/\w+;base64,/","",$baseImg);
		if(empty($baseImg)){
			$this->ajaxReturn($this->result->set("UPLOAD_ERROR"));
		}
		$baseImg = base64_decode ( $baseImg );
		$ext = I ( "post.ext", 'jpg', 'trim' );
		$config = array (
				"thumb" => true,
				'ThumbImage' => array (
						'thumbWidth' => $width, // 缩略图宽度
						'thumbHeight' => $height, // 缩略图高度
						'thumbType' => 3 
				),
				"savePath" => "Activity/" 
		);
		$result = D ( "Upload/UploadImage" )->base64Upload ( $baseImg, $ext, $config );
		if ($result) {
			$data = array (
					'attr_id' => $result ['att_id'],
					'photo' => fullPath ( $result ['path'] ),
			);
			//原图宽高判断缩略图尺寸
			$size = getimagesize(".".$result ['path']);
			$thumbSize = explode ( "X", $this->thumbSize [0] );
		    if($size[0]>$size[1]){
				$sizeH = $thumbSize[0];
				$sizeW = intval($sizeH*($size[0]/$size[1]));
			}else{
				$sizeW = $thumbSize[0];
				$sizeH = intval($sizeW/($size[0]/$size[1]));
			}
			$this->_cut ( $result ['path'], 1,$sizeW, $sizeH );
			$this->_cut ( $result ['path'], 3, $sizeW, $sizeW );
			return $data;
		} else {
			$this->ajaxReturn ( $this->result->error () );
		}
	}
	
	/**
	 * 新增我的上传
	 * <code>
	 * attr_id 附件id
	 * title 标题
	 * uid 用户id
	 * </code>
	 */
	public function addUpload() {
		$photo_model = D ( "Activity/Photos" );
		$uid = I ( 'post.uid', '' );
		$uid = path_decrypt ( $uid );
		if (empty ( $uid )) {
			$this->ajaxReturn ( $this->result->set ( "UID_REQUIRE" ) );
		}  
		// 上传照片
		$attr = $this->_addPic ( $uid );
		$data = array (
				"uid" => $uid,
				"attr_id" => $attr ['attr_id'],
				"add_time" => NOW_TIME,
				"update_time" => NOW_TIME,
				"title" => empty(I ( "post.title",''))?"俺的范":I ( "post.title",''),
				"status" => 1
		);
		$length = mb_strlen($data['title'],"UTF-8");
		if($length>20 || $length<2){
			$this->ajaxReturn($this->result->set("TITLE_LENGTH_ERROR"));
		}
		$photo = $photo_model->existPhoto ( $uid );
		if ($photo) {
			// 更新
			unset ( $data ['add_time'] );
			$where = array (
					'photo_id' => $photo ['photo_id'] 
			);
			$id = $photo ['photo_id'];
			$result = $photo_model->setData ( $where, $data );
			if ($result->isSuccess ()) {
				// 更新时删除原图
				if ($photo ['attr_id'] != $attr ['attr_id']) {
					D ( "Upload/AttachMent" )->delById ( $photo ['attr_id'] );
				}
			}
		} else {
			unset ( $data ['update_time'] );
			$result = $photo_model->addData ( $data );
			$results = $result->toArray ();
			$id = $results ['result'];
		}
		$this->ajaxReturn ( $result->content ( [ 
				'return' => [ 
						'photo_id' => $id,
						'photo' => $attr ['photo'] 
				] 
		] ) );
	}
	
	/**
	 * 用户是否禁用
	 * 
	 * @param $uid 用户id        	
	 */
	public function _isDisable($uid) {
		$result = D ( "Activity/Connect" )->isDidsble ( $uid );
		if (! $result->isSuccess ()) {
			$this->ajaxReturn ( $result );
		}
	}
	/**
	 * 集齐50个赞不能修改照片
	 * 
	 * @param $uid 用户id        	
	 */
	private function _notUp($uid) {
		$info = D ( "Activity/Photos" )->existPhoto ( $uid );
		if ($info && $info ['vote_num'] >= $this->vote) {
			$this->ajaxReturn ( $this->result->set ( "NOT_EDIT_PIC" ) );
		} else {
			return $info;
		}
	}
	/**
	 * 照片墙
	 * <code>
	 * keyword 关键字搜索
	 * code 机器码
	 * </code>
	 */
	public function photoList() {
		$keyword = I ( "post.keyword", "" );
		$uid = I ( 'post.uid', '' );
		$uid = path_decrypt($uid);
		$model = D ( "Activity/Photos" )->viewModel ();
		$where = array (
				'ActivityPhoto.status' => 1 
		);
		if ($keyword) {
			$where ['_string'] = "photo_id =" . intval ( $keyword - $this->number ) . " OR mobile='" . $keyword . "'";
		}
		if ($uid) {
			$photos = D ( "Activity/Photos" )->getPhotoByUid ( $uid );
		}
		$lists = $this->_lists ( $model, $where, 'ActivityPhoto.vote_num DESC' );
		D ( 'Home/List' )->getThumb ( $lists ['data'], $this->thumbSize [0], "attr_id" );
		foreach ( $lists ['data'] as &$v ) {
			unset ( $v ['attr_id'] );
			$v ['photo'] = fullPath ( $v ['thumb'] );
			unset ( $v ['thumb'] );
			$v ['number'] = $v ['photo_id'] + $this->number;
			if (in_array ( $v ['photo_id'], $photos )) {
				$v ['checked'] = 1;
			} else {
				$v ['checked'] = 0;
			}
		}
		$lists ['code'] = $this->getUnqiueCode ();
		$this->ajaxReturn ( $this->result->success ()->content ( $lists ) );
	}
	
	/**
	 * 照片详情
	 * <code>
	 * photo_id　照片ｉｄ
	 * code 机器码
	 * </code>
	 */
	public function info() {
		$photo_id = I ( 'request.photo_id' );
		$uid = I ( "post.uid", '' );
		$uid = path_decrypt ( $uid );
		if ($photo_id) {
			$photo_model = D ( "Activity/Photos" )->viewModel ();
			$info = $photo_model->where ( [ 
					'photo_id' => $photo_id 
			] )->find ();
			$attr = current ( D ( "Upload/AttachMent" )->getAttach ( $info ['attr_id'] ) );
			$info ['photo'] = fullPath ( $attr ['path'] );
			$info ['code'] = $this->getUnqiueCode ();
			$info ['number'] = $info ['photo_id'] + $this->number;
			$info ['checked'] = 0;
			$info ['is_my'] = 0;
			if ($uid) {
				if ($info ['uid'] == $uid) {
					$info ['is_my'] = 1;
				}
				$photos = D ( "Activity/Photos" )->getPhotoByUid ( $uid );
				if (in_array ( $photo_id, $photos )) {
					$info ['checked'] = 1;
				}
			}
			$this->ajaxReturn ( $this->result->success ()->content ( [ 
					'return' => $info 
			] ) );
		} else {
			$this->ajaxReturn ( $this->result->error () );
		}
	}
	
	/**
	 * 我的照片详情
	 * <code>
	 * uid 用户id
	 * code 机器码
	 * </code>
	 */
	public function myPhtoInfo() {
		$uid = I ( 'post.uid' );
		$uid = path_decrypt ( $uid );
		//$code = I ( "post.code", '' );
		if ($uid) {
			$photo_model = D ( "Activity/Photos" )->viewModel ();
			$info = $photo_model->where ( [ 
					'uid' => $uid 
			] )->find ();
			$attr = current ( D ( "Upload/AttachMent" )->getAttach ( $info ['attr_id'] ) );
			$info ['photo'] = fullPath ( $attr ['path'] );
			$info ['code'] = $this->getUnqiueCode ();
			$info ['number'] = $info ['photo_id'] + $this->number;
			$info ['checked'] = 0;
			$info ['is_my'] = 0;
			if ($uid) {
				if ($info ['uid'] == $uid) {
					$info ['is_my'] = 1;
				}
				$photos = D ( "Activity/Photos" )->getPhotoByUid ( $uid );
				if (in_array ( $info ['photo_id'], $photos )) {
					$info ['checked'] = 1;
				}
			}
			$this->ajaxReturn ( $this->result->success ()->content ( [ 
					'return' => $info 
			] ) );
		} else {
			$this->ajaxReturn ( $this->result->error () );
		}
	}
	/**
	 * 生成唯一机器码
	 */
	private function getUnqiueCode() {
		return uniqid () . "-" . rand_string ( 6, 1 );
	}
	/**
	 * *********************************************页面展示********************************************************
	 */
	// 照片墙列表
	public function lists() {
		$this->display ();
	}
	// 上传
	public function upload() {
		$this->display ();
	}
	// 详情
	public function detail() {
		$this->display ();
	}
	public function login() {
		$this->display ();
	}
/**
 * *********************************************页面展示********************************************************
 */
}