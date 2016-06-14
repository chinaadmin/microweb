<?php
// 定义回调URL通用的URL
define ( 'URL_CALLBACK', 'http://api.tp-bihaohuo.cn/WechatPublic/callback' );
return array (
// 		'THINK_SDK_WECHAT' => array(
// 				'APP_KEY'    => 'wx0d337325bf4322c5',//'wxc1b6e5d6476e7d04', //应用注册成功后分配的 APP ID
// 				'APP_SECRET' => '3e1dbd7ff6765ced10522b95c9d55f61',//'7ddef132152b8d802bbd83cb2251398b', //应用注册成功后分配的KEY
// 				'CALLBACK'   => URL_CALLBACK,
// 		),
		//测试
		'THINK_SDK_WECHAT' => array(
				'APP_KEY'    => 'wxc1b6e5d6476e7d04', //应用注册成功后分配的 APP ID
				'APP_SECRET' => '7ddef132152b8d802bbd83cb2251398b', //应用注册成功后分配的KEY
				'CALLBACK'   => URL_CALLBACK,
		)
);