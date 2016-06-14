<?php
/**
 * 上传配置
 */
return array (
		 //图片配置
		'Image' => array(
				'mimes'         =>  array(), //允许上传的文件MiMe类型
				'exts'          =>  array("jpg","jpeg","png","gif","bmp"),
				'maxSize'       =>  '3145728', //上传的文件大小限制 (0-不做限制)
				'rootPath'      =>  "./Uploads/",//上传根路径
				'savePath'      =>  "Image/",
				'autoSub'       =>  true, //自动子目录保存文件
				'subName'       =>  array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
				'saveName'      =>  array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
				'saveExt'       =>  '', //文件保存后缀，空则使用原后缀
				'replace'       =>  false, //存在同名是否覆盖
				'hash'          =>  true, //是否生成hash编码
				'callback'      =>  false, //检测文件是否存在回调，如果存在返回文件信息数组
				'thumb'         =>  true,//图片是否缩略
				'water'         =>  false, //是否添加水印
				//缩略图配置
				'ThumbImage' => array(
						'thumbWidth' => '200,400,700',//缩略图宽度
						'thumbHeight' => '200,400,700',//缩略图高度
						'thumbType' => 1, //1等比例缩放类型,2缩放后填充类型,3居中裁剪类型,4左上角裁剪类型,5右下角裁剪类型,6固定尺寸缩放类型
				),
				//图片水印配置
				'Water' => array(
						'waterPlace' => 9, //1 左上角水印,2上居中水印,3 右上角水印,4 左居中水印,5居中水印,6右居中水印,7 左下角水印,8下居中水印,9 右下角水印
						'waterPath'  =>'',
				)
		),
		//编辑器图片上传
		'editorImage' => array(
		        'mimes'         =>  array(), //允许上传的文件MiMe类型
				'exts'          =>  array("jpg","jpeg","png","gif","bmp"),
				'maxSize'       =>  '3145728', //上传的文件大小限制 (0-不做限制)
				'rootPath'      =>  "./Uploads/",//上传根路径
				'savePath'      =>  "Ueditor/images/",
				'autoSub'       =>  true, //自动子目录保存文件
				'subName'       =>  array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
				'saveName'      =>  array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
				'saveExt'       =>  '', //文件保存后缀，空则使用原后缀
				'replace'       =>  false, //存在同名是否覆盖
				'hash'          =>  true, //是否生成hash编码
				'callback'      =>  false, //检测文件是否存在回调，如果存在返回文件信息数组
				'thumb'         =>  false,//图片是否缩略
				'water'         =>  false, //是否添加水印
				//缩略图配置
				'ThumbImage' => array(
						'thumbWidth' => '200,300',//缩略图宽度
						'thumbHeight' => '200,300',//缩略图高度
						'thumbType' => 1, //1等比例缩放类型,2缩放后填充类型,3居中裁剪类型,4左上角裁剪类型,5右下角裁剪类型,6固定尺寸缩放类型
				),
				//图片水印配置
				'Water' => array(
						'waterPlace' => 9, //1 左上角水印,2上居中水印,3 右上角水印,4 左居中水印,5居中水印,6右居中水印,7 左下角水印,8下居中水印,9 右下角水印
						'waterPath'  =>'',
				)
		),
		//视频上传
		'Video' => array(
		'mimes'         =>  array(), //允许上传的文件MiMe类型
		'exts'          =>  array("flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg",
        "ogg", "ogv", "mov", "wmv", "mp4", "webm", "mp3", "wav", "mid"),
		'maxSize'       =>  '102400000', //100M
		'rootPath'      =>  "./Uploads/",//上传根路径
		'savePath'      =>  "Ueditor/videos/",
		'autoSub'       =>  true, //自动子目录保存文件
		'subName'       =>  array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'saveName'      =>  array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'saveExt'       =>  '', //文件保存后缀，空则使用原后缀
		'replace'       =>  false, //存在同名是否覆盖
		'hash'          =>  true, //是否生成hash编码
		'callback'      =>  false, //检测文件是否存在回调，如果存在返回文件信息数组
		)
);