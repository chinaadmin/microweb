<?php
/**
 * 编辑器上传
 * @author xiongzw
 * @date 2015-04-07
 */
namespace Upload\Model;
class EditorModel extends BaseModel {
	protected $autoCheckFields = false;
	public function _initialize() {
		parent::_initialize();
		$this->defaultImageConfig = C ( 'editorImage' );
	}
	/**
	 * 图片上传
	 */
	public function uploadImage($config = array(), $file = "", $arr = array()) {
		$defaultConfig = $this->defaultImageConfig;
		$result = $this->uploadOne ( $defaultConfig, $config, $file, $arr );
		$result = $this->formatReturn ( $result );
		return $result;
	}
	/**
	 * 视频上传
	 * @param  $file
	 * @param  $arr
	 */
	public function uploadVideo($config = array(),$file="",$arr=array()){
		$defaultConfig = C('Video');
		$result = $this->uploadOne ( $defaultConfig, $config, $file, $arr );
		$result = $this->formatReturn ( $result );
		return $result;
	}
	/**
	 * 编辑器上传返回
	 */
	private function formatReturn($result) {
		/*if(strpos($result ['path'], "http://")===false && !empty($result ['path'])){
			$result ['path'] =  'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME').$result ['path'];
		}*/
		$return = array (
				"state" => $result ['success'] ? "SUCCESS" : $result ['error'], // 上传状态，上传成功时必须返回"SUCCESS"
				"url" => ltrim ( $result ['path'], "." ), // 返回的地址
				"title" => basename ( $result ['savename'] ), // 新文件名
				"original" => $result ['name'], // 原始文件名
				"type" => ".".$result ['ext'], // 文件类型
				"size" => $result ['size']  //文件大小
		);
		return $return;
	}
	/**
	 * 图片列表
	 * @return Ambigous <multitype:string multitype: number , multitype:string number multitype:multitype:string unknown   >
	 */
	public function listImage(){
		$where = array(
				"is_admin" => 1,
				"model"   => 'editor'
		);
		$total = M('UserAttachment')->where($where)->count();
		$_REQUEST ['p'] = I ( 'start', 0, 'intval' );
		$_REQUEST ['r'] = I ( 'size', 20, 'intval' );
		$request = I ( 'request' );
		if (isset ( $request ['r'] )) {
			$listRows = ( int ) $request ['r'];
		} else {
			$listRows = C ( 'PAGE_LISTROWS' ) > 0 ? C ( 'PAGE_LISTROWS' ) : 10;
		}
		$page = new \Think\Page ( $total, $listRows, $request );
		$limit = 'LIMIT ' . $page->firstRow . ',' . $page->listRows;
		$attIds = M('UserAttachment')->where($where)->order('id DESC')->limit($limit)->getField('att_id',true);
		$images = array();
		if($attIds){
		$whereFile = array(
				'att_id'=>array('in',$attIds),
				'type' => 'IMAGE'
		);
		$images = M('Attachment')->where($whereFile)->select();
		}
		if ($images) {
			$list = [ ];
			foreach ( $images as $val ) {
				$url = $val ['path'] . "/".$val ['name'].".".$val['ext'];
				$url = ltrim($url,".");
				$list [] = [
				'url' => $url,
				'mtime' => $val ['add_time']
				];
			}
			$data = [
			"state" => "SUCCESS",
			"list" => $list,
			"start" => ( int ) $_REQUEST ['p'],
			"total" => count ( $list )
			];
		} else {
			$data = [
			"state" => "no match file",
			"list" => [ ],
			"start" => 0,
			"total" => count ( $images )
			];
		}
		return $data;
	}
}