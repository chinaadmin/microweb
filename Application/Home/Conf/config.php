<?php
return [ 
		// '配置项'=>'配置值'
// 		'URL_PATHINFO_DEPR' => '_',
		'DEFAULT_THEME' => 'default', // 默认模板主题名称
		'THUMB_SIZE' => [  // 商品缩略图
				'200X200',
				'400X400',
				'700X700' 
		],
		// 商品默认图片
		'DEFAULT_IMAGE' => "/Public/Image/ListDefault/default.jpg",
		// 用户操作Model
		'USER_MODEL' => 'User/User',
		// 登录验证码
		'VERIFY_CONFIG' => [ 
				'imageH' => 40,
				'imageW' => 110,
				'fontSize' => 14,
				'type' => 'gif',
				'length' => 4,
				'useNoise' => false,
				'useCurve' => false 
		],
		// 头像缩略配置
		'HEAD_PIC' => [ 
				'thumb' => true,
				'savePath' => "headPic/",
				'ThumbImage' => [ 
						'thumbWidth' => '30,40,50', // 缩略图宽度
						'thumbHeight' => '30,40,50', // 缩略图高度
						'thumbType' => 1  // 1等比例缩放类型,2缩放后填充类型,3居中裁剪类型,4左上角裁剪类型,5右下角裁剪类型,6固定尺寸缩放类型
				]
				 
		],
		'LOAD_EXT_CONFIG' => 'appkey,route',  //  加载appkey配置
// 		'ERROR_PAGE' =>'http://'.$_SERVER['HTTP_HOST'].C('URL_PATHINFO_DEPR').'home'.C('URL_PATHINFO_DEPR').'Error'		
];