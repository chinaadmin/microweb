<?php
/**
 * 编辑器上传
 * @author xiongzw
 * @date 2015-04-07
 */
namespace Upload\Controller;
class EditorController extends BaseController{
	public $model;
	public function _initialize(){
		parent::_initialize();
		$this->arr['model'] = "editor";
		$this->model = D('Upload/Editor');
	} 
	
	/**
	 * 图片上传
	 */
	 public function uploadImage(){
		$result = $this->model->uploadImage("","",$this->arr);
	    $this->ajaxReturn($result);
	} 
	/**
	 * 视频上传
	 */
	public function uploadVideo(){
		$result = $this->model->uploadVideo("","",$this->arr); 
		$this->ajaxReturn($result);
	}
	/**
	 * 编辑器配置项
	 */
	public function editor(){
	  $action = I('request.action','','strtolower');
	  switch($action){
	  	//编辑器配置
	  	case "config":
	  		$path = APP_PATH."Upload/json/config.json";
	  		$config = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($path));
	  		$config = json_decode($config,true);
	  		$this->ajaxReturn($config);
	  		break;
	  	//图片配置
	  	case "upload" :
	  		$this->uploadImage();
	  		break;
	  	case "catchimage":
	  		break;
	  	case "listimage":
	  		$data = $this->model->listImage();
	  		$this->ajaxReturn($data,'JSONP');
	  		break;
	  	case "uploadvideo":
	  		$this->uploadVideo();
	  		break;
	    }
	}
}